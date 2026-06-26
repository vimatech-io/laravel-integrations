<?php

declare(strict_types=1);

namespace Vimatech\Integrations\Webhooks;

use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Vimatech\Integrations\Contracts\EventKeyStore;
use Vimatech\Integrations\Contracts\WebhookTranslator;
use Vimatech\Integrations\DriverRegistry;
use Vimatech\Integrations\Events\WebhookReceived;
use Vimatech\Integrations\Events\WebhookRejected;
use Vimatech\Integrations\Exceptions\IntegrationException;
use Vimatech\Integrations\IntegrationManager;

/**
 * Generic inbound webhook endpoint:
 *
 *     POST {prefix}/{capability}/{driver?}
 *
 * Verifies the request, translates it to canonical events, enforces
 * idempotency and dispatches each event.
 */
final class WebhookController
{
    private const DEFAULT_EVENT_TTL = 86400;

    public function __construct(
        private readonly IntegrationManager $manager,
        private readonly DriverRegistry $registry,
        private readonly EventKeyStore $store,
        private readonly Dispatcher $events,
        private readonly Container $container,
        private readonly Config $config,
    ) {}

    public function __invoke(Request $request, string $capability, ?string $driver = null): JsonResponse
    {
        if (! $this->registry->webhooksEnabled($capability)) {
            throw new NotFoundHttpException("Webhooks are not enabled for capability [{$capability}].");
        }

        $translator = $this->resolveTranslator($capability, $driver);
        $effectiveDriver = $this->effectiveDriver($capability, $driver);

        if (! $translator->verify($request)) {
            WebhookRejected::dispatch($capability, $effectiveDriver, 'signature');

            throw new HttpException(403, 'Invalid webhook signature.');
        }

        // Fires on every accepted delivery (including redeliveries); only the
        // translated canonical events below are de-duplicated.
        WebhookReceived::dispatch($capability, $effectiveDriver, $request->all());

        $ttl = $this->eventTtl();
        $processed = 0;

        foreach ($translator->translate($request) as $event) {
            if (! $this->store->acquire($event->idempotencyKey(), $ttl)) {
                continue; // Duplicate delivery — already handled.
            }

            $this->events->dispatch($event);
            $processed++;
        }

        return new JsonResponse(['ok' => true, 'processed' => $processed]);
    }

    private function resolveTranslator(string $capability, ?string $driver): WebhookTranslator
    {
        $translatorClass = $this->registry->webhookTranslator($capability);

        if ($translatorClass !== null) {
            /** @var object $translator */
            $translator = $this->container->make($translatorClass);

            if (! $translator instanceof WebhookTranslator) {
                throw new IntegrationException(
                    "Configured webhook translator [{$translatorClass}] for [{$capability}] must implement "
                    .WebhookTranslator::class.'.'
                );
            }

            return $translator;
        }

        $resolved = $this->manager->driver($capability, $driver);

        if (! $resolved instanceof WebhookTranslator) {
            throw new IntegrationException(
                "Driver for capability [{$capability}] does not implement ".WebhookTranslator::class
                .' and no translator is configured.'
            );
        }

        return $resolved;
    }

    private function eventTtl(): int
    {
        $ttl = $this->config->get('integrations.webhooks.event_ttl', self::DEFAULT_EVENT_TTL);

        return is_numeric($ttl) ? (int) $ttl : self::DEFAULT_EVENT_TTL;
    }

    /**
     * The driver key that actually handled the delivery, for event reporting:
     * the URL segment, else the capability default when the driver itself is
     * the translator, else null for a capability-level configured translator.
     */
    private function effectiveDriver(string $capability, ?string $driver): ?string
    {
        if ($driver !== null) {
            return $driver;
        }

        if ($this->registry->webhookTranslator($capability) !== null) {
            return null;
        }

        return $this->registry->defaultKey($capability);
    }
}

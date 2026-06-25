<?php

declare(strict_types=1);

namespace Vimatech\Integrations\Tests\Fixtures;

use Illuminate\Http\Request;
use Vimatech\Integrations\Contracts\Driver;
use Vimatech\Integrations\Contracts\WebhookTranslator;

/**
 * A driver that also acts as its own webhook translator. It verifies a static
 * signature header and translates the request body into PaymentSettled events.
 */
final class StripeWebhookGateway implements Driver, WebhookTranslator
{
    /**
     * @param  array<string, mixed>  $config
     */
    public function __construct(public array $config = []) {}

    public function verify(Request $request): bool
    {
        return $request->header('X-Signature') === ($this->config['secret'] ?? 'whsec_test');
    }

    public function translate(Request $request): iterable
    {
        /** @var array<int, array{reference: string, amount: int}> $events */
        $events = $request->input('events', []);

        foreach ($events as $event) {
            yield new PaymentSettled($event['reference'], (int) $event['amount']);
        }
    }
}

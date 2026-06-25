<?php

declare(strict_types=1);

namespace Vimatech\Integrations\Webhooks;

/**
 * Base class for normalized, provider-agnostic events produced by a
 * WebhookTranslator and dispatched through Laravel's event system.
 *
 * Consumer packages extend this to define their canonical vocabulary (e.g.
 * InvoiceDelivered, PaymentSettled), each carrying a stable idempotency key.
 */
abstract class CanonicalEvent
{
    /**
     * A stable, globally-unique key used for webhook idempotency. Repeated
     * deliveries of the same logical event MUST return the same key.
     */
    abstract public function idempotencyKey(): string;

    /**
     * A human-readable event name; defaults to the concrete class name.
     */
    public function name(): string
    {
        return static::class;
    }
}

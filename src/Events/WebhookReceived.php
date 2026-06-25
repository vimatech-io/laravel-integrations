<?php

declare(strict_types=1);

namespace Vimatech\Integrations\Events;

use Illuminate\Foundation\Events\Dispatchable;

/**
 * Dispatched after an inbound webhook has been verified, before its canonical
 * events are processed.
 */
final class WebhookReceived
{
    use Dispatchable;

    /**
     * @param  array<array-key, mixed>  $payload
     */
    public function __construct(
        public readonly string $capability,
        public readonly ?string $driver,
        public readonly array $payload,
    ) {}
}

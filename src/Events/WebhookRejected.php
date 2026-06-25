<?php

declare(strict_types=1);

namespace Vimatech\Integrations\Events;

use Illuminate\Foundation\Events\Dispatchable;

/**
 * Dispatched when an inbound webhook fails verification and is rejected.
 */
final class WebhookRejected
{
    use Dispatchable;

    public function __construct(
        public readonly string $capability,
        public readonly ?string $driver,
        public readonly string $reason,
    ) {}
}

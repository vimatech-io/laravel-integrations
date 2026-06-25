<?php

declare(strict_types=1);

namespace Vimatech\Integrations\Tests\Fixtures;

use Vimatech\Integrations\Webhooks\CanonicalEvent;

/**
 * Example canonical event a consumer would define.
 */
final class PaymentSettled extends CanonicalEvent
{
    public function __construct(
        public readonly string $reference,
        public readonly int $amount,
    ) {}

    public function idempotencyKey(): string
    {
        return 'payment-settled:'.$this->reference;
    }
}

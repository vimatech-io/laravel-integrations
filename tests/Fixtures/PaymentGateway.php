<?php

declare(strict_types=1);

namespace Vimatech\Integrations\Tests\Fixtures;

use Vimatech\Integrations\Contracts\Driver;

/**
 * Example capability contract — the kind of interface a CONSUMER package would
 * define. The integrations package itself never sees this.
 */
interface PaymentGateway extends Driver
{
    public function name(): string;

    /**
     * @return array<string, mixed>
     */
    public function config(): array;
}

<?php

declare(strict_types=1);

namespace Vimatech\Integrations\Tests\Fixtures;

final class AdyenGateway implements PaymentGateway
{
    /**
     * @param  array<string, mixed>  $config
     */
    public function __construct(public array $config) {}

    public function name(): string
    {
        return 'adyen';
    }

    public function config(): array
    {
        return $this->config;
    }
}

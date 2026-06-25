<?php

declare(strict_types=1);

use Vimatech\Integrations\Exceptions\DriverNotConfigured;
use Vimatech\Integrations\Facades\Integrations;
use Vimatech\Integrations\IntegrationManager;
use Vimatech\Integrations\Tests\Fixtures\AdyenGateway;
use Vimatech\Integrations\Tests\Fixtures\StripeGateway;

it('resolves a driver by key', function (): void {
    $driver = Integrations::driver('payments', 'adyen');

    expect($driver)->toBeInstanceOf(AdyenGateway::class)
        ->and($driver->name())->toBe('adyen');
});

it('resolves the default driver when no key is given', function (): void {
    expect(Integrations::driver('payments'))->toBeInstanceOf(StripeGateway::class);
});

it('passes resolved credentials to the adapter', function (): void {
    /** @var StripeGateway $driver */
    $driver = Integrations::driver('payments', 'stripe');

    expect($driver->config())
        ->toHaveKey('api_key', 'sk_test_stripe')
        ->toHaveKey('class', StripeGateway::class);
});

it('caches resolved driver instances', function (): void {
    expect(Integrations::driver('payments', 'stripe'))
        ->toBe(Integrations::driver('payments', 'stripe'));
});

it('throws when the capability is unknown', function (): void {
    Integrations::driver('unknown');
})->throws(DriverNotConfigured::class);

it('throws when the driver key is unknown', function (): void {
    Integrations::driver('payments', 'paypal');
})->throws(DriverNotConfigured::class);

it('honours a custom factory registered via extend', function (): void {
    /** @var IntegrationManager $manager */
    $manager = app(IntegrationManager::class);

    $manager->extend('payments', 'stripe', fn (array $config) => new StripeGateway(['custom' => true] + $config));

    expect(Integrations::driver('payments', 'stripe')->config())->toHaveKey('custom', true);
});

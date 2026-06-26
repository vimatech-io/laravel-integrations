<?php

declare(strict_types=1);

use Vimatech\Integrations\Contracts\ResolvesTenantDriver;
use Vimatech\Integrations\Exceptions\UnresolvableDriver;
use Vimatech\Integrations\Facades\Integrations;
use Vimatech\Integrations\Tests\Fixtures\AdyenGateway;
use Vimatech\Integrations\Tests\Fixtures\StaticTenantResolver;
use Vimatech\Integrations\Tests\Fixtures\StripeGateway;

it('routes to a driver by context dimension', function (): void {
    expect(Integrations::for('payments')->resolve(['country' => 'NL']))
        ->toBeInstanceOf(AdyenGateway::class);

    expect(Integrations::for('payments')->resolve(['country' => 'FR']))
        ->toBeInstanceOf(StripeGateway::class);
});

it('falls back to the default driver when context does not match', function (): void {
    expect(Integrations::for('payments')->resolve(['country' => 'DE']))
        ->toBeInstanceOf(StripeGateway::class);
});

it('falls back to the default driver when context is empty', function (): void {
    expect(Integrations::for('payments')->resolve())
        ->toBeInstanceOf(StripeGateway::class);
});

it('resolves the default explicitly', function (): void {
    expect(Integrations::for('payments')->default())->toBeInstanceOf(StripeGateway::class);
});

it('resolves an explicit key via the router', function (): void {
    expect(Integrations::for('payments')->via('adyen'))->toBeInstanceOf(AdyenGateway::class);
});

it('throws on strict resolution when context does not match', function (): void {
    Integrations::for('payments')->resolveStrict(['country' => 'DE']);
})->throws(UnresolvableDriver::class);

it('lets a bound tenant resolver override routing', function (): void {
    app()->instance(ResolvesTenantDriver::class, new StaticTenantResolver([42 => 'adyen']));

    // Tenant override wins even though the country would route to stripe.
    expect(Integrations::for('payments')->resolve(['tenant' => 42, 'country' => 'FR']))
        ->toBeInstanceOf(AdyenGateway::class);
});

it('defers to routing when the tenant resolver returns null', function (): void {
    app()->instance(ResolvesTenantDriver::class, new StaticTenantResolver([42 => 'adyen']));

    expect(Integrations::for('payments')->resolve(['tenant' => 99, 'country' => 'NL']))
        ->toBeInstanceOf(AdyenGateway::class);
});

it('throws when default() is called and no default is configured', function (): void {
    Integrations::for('nodefault')->default();
})->throws(UnresolvableDriver::class);

it('throws when resolving with no context, no routing and no default', function (): void {
    Integrations::for('nodefault')->resolve();
})->throws(UnresolvableDriver::class);

it('still resolves an explicit key when no default is configured', function (): void {
    expect(Integrations::for('nodefault')->via('a'))->toBeInstanceOf(StripeGateway::class);
});

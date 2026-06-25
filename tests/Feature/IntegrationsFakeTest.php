<?php

declare(strict_types=1);

use PHPUnit\Framework\ExpectationFailedException;
use Vimatech\Integrations\Facades\Integrations;
use Vimatech\Integrations\Testing\FakeDriver;
use Vimatech\Integrations\Tests\Fixtures\StripeGateway;

it('records driver usage and can assert it', function (): void {
    $fake = Integrations::fake();

    Integrations::driver('payments', 'stripe');

    $fake->assertDriverUsed('payments');
    $fake->assertDriverUsed('payments', 'stripe');
    $fake->assertDriverNotUsed('payments', 'adyen');
});

it('returns a generic fake driver by default', function (): void {
    Integrations::fake();

    expect(Integrations::driver('payments', 'stripe'))->toBeInstanceOf(FakeDriver::class);
});

it('returns explicitly provided fakes', function (): void {
    $double = new StripeGateway(['faked' => true]);

    Integrations::fake(['payments:stripe' => $double]);

    expect(Integrations::driver('payments', 'stripe'))->toBe($double);
});

it('preserves routing logic while faking drivers', function (): void {
    $fake = Integrations::fake();

    Integrations::for('payments')->resolve(['country' => 'NL']);

    // Routing still resolved the "adyen" key, even though a fake was returned.
    $fake->assertDriverUsed('payments', 'adyen');
});

it('fails the assertion when the driver was not used', function (): void {
    $fake = Integrations::fake();

    $fake->assertDriverUsed('payments');
})->throws(ExpectationFailedException::class);

it('can fake a capability that is not configured', function (): void {
    $fake = Integrations::fake();

    expect(Integrations::driver('messaging'))->toBeInstanceOf(FakeDriver::class);

    $fake->assertDriverUsed('messaging', 'default');
});

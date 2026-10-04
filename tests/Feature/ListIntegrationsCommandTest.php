<?php

declare(strict_types=1);

it('lists configured capabilities', function (): void {
    $this->artisan('integrations:list')
        ->expectsOutputToContain('payments')
        ->expectsOutputToContain('einvoice')
        ->assertExitCode(0);
});

it('shows none for a capability without a default driver', function (): void {
    config()->set('integrations.capabilities', [
        'sms' => ['drivers' => []],
    ]);

    $this->artisan('integrations:list')
        ->expectsOutputToContain('default: none')
        ->assertExitCode(0);
});

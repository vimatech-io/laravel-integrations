<?php

declare(strict_types=1);

it('lists configured capabilities', function (): void {
    $this->artisan('integrations:list')
        ->expectsOutputToContain('payments')
        ->expectsOutputToContain('einvoice')
        ->assertExitCode(0);
});

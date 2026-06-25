<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Vimatech\Integrations\Webhooks\WebhookController;

Route::group([
    'prefix' => config('integrations.webhooks.prefix', 'integrations/webhooks'),
    'middleware' => config('integrations.webhooks.middleware', ['api']),
], function (): void {
    Route::post('{capability}/{driver?}', WebhookController::class)
        ->name('integrations.webhooks');
});

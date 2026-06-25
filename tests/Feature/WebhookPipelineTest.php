<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Event;
use Vimatech\Integrations\Events\WebhookReceived;
use Vimatech\Integrations\Events\WebhookRejected;
use Vimatech\Integrations\Tests\Fixtures\PaymentSettled;

function postWebhook(array $events, string $signature = 'whsec_test')
{
    return test()->postJson(
        'integrations/webhooks/einvoice',
        ['events' => $events],
        ['X-Signature' => $signature],
    );
}

it('verifies, translates and dispatches canonical events', function (): void {
    Event::fake([WebhookReceived::class, PaymentSettled::class]);

    postWebhook([
        ['reference' => 'inv-1', 'amount' => 1000],
        ['reference' => 'inv-2', 'amount' => 2500],
    ])->assertOk()->assertJson(['ok' => true, 'processed' => 2]);

    Event::assertDispatched(WebhookReceived::class);
    Event::assertDispatchedTimes(PaymentSettled::class, 2);
    Event::assertDispatched(
        PaymentSettled::class,
        fn (PaymentSettled $e): bool => $e->reference === 'inv-1' && $e->amount === 1000,
    );
});

it('rejects a request with an invalid signature', function (): void {
    Event::fake([WebhookRejected::class, PaymentSettled::class]);

    postWebhook([['reference' => 'inv-1', 'amount' => 1000]], signature: 'nope')
        ->assertForbidden();

    Event::assertDispatched(WebhookRejected::class);
    Event::assertNotDispatched(PaymentSettled::class);
});

it('is idempotent across repeated deliveries of the same event', function (): void {
    Event::fake([PaymentSettled::class]);

    postWebhook([['reference' => 'inv-1', 'amount' => 1000]])
        ->assertJson(['processed' => 1]);

    // Same idempotency key — should be skipped on redelivery.
    postWebhook([['reference' => 'inv-1', 'amount' => 1000]])
        ->assertJson(['processed' => 0]);

    Event::assertDispatchedTimes(PaymentSettled::class, 1);
});

it('returns 404 when webhooks are disabled for the capability', function (): void {
    test()->postJson('integrations/webhooks/payments', ['events' => []], ['X-Signature' => 'whsec_test'])
        ->assertNotFound();
});

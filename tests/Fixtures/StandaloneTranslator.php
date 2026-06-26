<?php

declare(strict_types=1);

namespace Vimatech\Integrations\Tests\Fixtures;

use Illuminate\Http\Request;
use Vimatech\Integrations\Contracts\WebhookTranslator;

/**
 * A capability-level translator configured via `webhooks.translator`, i.e. not
 * tied to any driver instance.
 */
final class StandaloneTranslator implements WebhookTranslator
{
    public function verify(Request $request): bool
    {
        return $request->header('X-Token') === 'ok';
    }

    public function translate(Request $request): iterable
    {
        /** @var array<int, array{reference: string, amount: int}> $events */
        $events = $request->input('events', []);

        foreach ($events as $event) {
            yield new PaymentSettled($event['reference'], (int) $event['amount']);
        }
    }
}

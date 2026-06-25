# Laravel Integrations

[![CI](https://github.com/vimatech-io/laravel-integrations/actions/workflows/ci.yml/badge.svg)](https://github.com/vimatech-io/laravel-integrations/actions/workflows/ci.yml)
[![Latest Version](https://img.shields.io/packagist/v/vimatech/laravel-integrations.svg)](https://packagist.org/packages/vimatech/laravel-integrations)
[![License](https://img.shields.io/packagist/l/vimatech/laravel-integrations.svg)](LICENSE)

A config-driven **ports & adapters** foundation for integrating external providers in Laravel.

Adding a provider means writing **one isolated adapter class and a config entry** — you never touch
business logic, routing, or the webhook pipeline. It generalizes Laravel's own `Manager`/driver pattern
with **context routing** (route a capability to a driver by country, tenant, …) and a **normalized
inbound webhook pipeline** (verify → translate → de-duplicate → dispatch canonical events).

This package is intentionally **domain-free**: it ships no concrete vendor and no business logic. The
*capabilities* (what an adapter actually does) are contracts defined by **your** application or by
consumer packages.

---

## Table of contents

- [Why](#why)
- [Installation](#installation)
- [Core concepts](#core-concepts)
- [Configuration](#configuration)
- [Adding a driver](#adding-a-driver)
- [Resolving & routing drivers](#resolving--routing-drivers)
- [Per-tenant overrides](#per-tenant-overrides)
- [Webhooks](#webhooks)
- [Credentials & secure storage](#credentials--secure-storage)
- [The `integrations:list` command](#the-integrationslist-command)
- [Testing](#testing)
- [Octane & FrankenPHP](#octane--frankenphp)
- [Quality](#quality)

---

## Why

External providers leak into business logic in predictable ways: a `match ($country)` here, a
hard-coded SDK client there, bespoke webhook controllers everywhere. This package gives you a single,
boring seam:

```
Business logic ──▶ Integrations::for('einvoice')->resolve(['country' => $invoice->country])
                                   │
                   ┌───────────────┴───────────────┐
                   ▼                                ▼
            ChorusProAdapter                    SdiAdapter        ← swap/add via config only
```

Your business logic depends on a **capability contract**; the concrete provider is selected by
configuration and runtime context.

## Installation

```bash
composer require vimatech/laravel-integrations
```

The service provider is auto-discovered. Publish the config (and, if you use the database idempotency
store, the migration):

```bash
php artisan vendor:publish --tag=integrations-config
php artisan vendor:publish --tag=integrations-migrations   # only for the "database" webhook store
```

Requires **PHP 8.3+** and **Laravel 11, 12 or 13**.

## Core concepts

| Concept | Description |
| --- | --- |
| **Capability** | A named contract owned by a consumer (e.g. `einvoice`, `payments`). This package never sees it. |
| **Driver** | An adapter implementing a capability. Marked with `Vimatech\Integrations\Contracts\Driver`. |
| **`IntegrationManager`** | Resolves a driver by capability + key from config, à la `Illuminate\Support\Manager`. |
| **`ContextRouter`** | Resolves a driver by default or by a context array (`['country' => 'FR']`). |
| **`ResolvesTenantDriver`** | Optional contract to override the driver per tenant from your database. |
| **`WebhookTranslator`** | Verifies an inbound request and translates it into canonical events. |
| **`CanonicalEvent`** | Provider-agnostic event base class with a stable idempotency key. |

## Configuration

`config/integrations.php` (abbreviated — see the published file for full comments):

```php
return [
    'credentials' => [
        'store' => env('INTEGRATIONS_CREDENTIAL_STORE', 'config'), // 'config' | 'encrypted'
    ],

    'webhooks' => [
        'prefix'      => env('INTEGRATIONS_WEBHOOK_PREFIX', 'integrations/webhooks'),
        'middleware'  => ['api'],
        'event_store' => env('INTEGRATIONS_WEBHOOK_STORE', 'cache'), // 'cache' | 'database'
        'event_table' => 'integration_webhook_events',
        'event_ttl'   => 86400,
    ],

    'capabilities' => [
        'einvoice' => [
            'default' => env('EINVOICE_DRIVER', 'chorus_pro'),

            'routing' => [
                'by'  => 'country',                 // the context dimension to route on
                'map' => [
                    'FR' => 'chorus_pro',           // contextValue => driverKey
                    'IT' => 'sdi',
                ],
            ],

            'drivers' => [
                'chorus_pro' => [
                    'class'   => \App\Integrations\ChorusProAdapter::class,
                    'api_key' => env('CHORUS_PRO_KEY'),
                ],
                'sdi' => [
                    'class'   => \App\Integrations\SdiAdapter::class,
                    'api_key' => env('SDI_KEY'),
                ],
            ],

            'webhooks' => [
                'enabled'    => true,
                'translator' => null,               // null = use the driver if it is a WebhookTranslator
            ],
        ],
    ],
];
```

## Adding a driver

**1. Define the capability contract** (in your app or a consumer package). It extends the `Driver`
marker:

```php
use Vimatech\Integrations\Contracts\Driver;

interface EInvoiceNetwork extends Driver
{
    public function send(Invoice $invoice): string;
}
```

**2. Write the adapter.** Adapter constructors receive the resolved config array:

```php
final class ChorusProAdapter implements EInvoiceNetwork
{
    public function __construct(private array $config) {}

    public function send(Invoice $invoice): string
    {
        // talk to Chorus Pro using $this->config['api_key']
    }
}
```

**3. Register it in config** under the capability's `drivers` map. That's it — no business-logic
changes.

> Need bespoke construction (a pre-built SDK client, etc.)? Register a factory:
>
> ```php
> app(IntegrationManager::class)->extend('einvoice', 'chorus_pro', function (array $config) {
>     return new ChorusProAdapter(ChorusClient::make($config['api_key']));
> });
> ```

## Resolving & routing drivers

```php
use Vimatech\Integrations\Facades\Integrations;

// Explicit key
$driver = Integrations::driver('einvoice', 'sdi');

// Capability default
$driver = Integrations::driver('einvoice');

// Route by context — uses the capability's `routing.by` dimension
$driver = Integrations::for('einvoice')->resolve(['country' => $invoice->country]);
```

Context resolution order for `for($capability)->resolve($context)`:

1. A bound [`ResolvesTenantDriver`](#per-tenant-overrides) (per-tenant override).
2. Static routing on the configured `routing.by` dimension.
3. The capability `default`.
4. Otherwise `UnresolvableDriver` is thrown.

Need to **fail instead of falling back** to the default when context doesn't match? Use
`resolveStrict()`:

```php
Integrations::for('einvoice')->resolveStrict(['country' => 'DE']); // throws UnresolvableDriver
```

Other router methods: `default()`, `via('sdi')`, and `key($context)` (returns the resolved driver key
without instantiating).

## Per-tenant overrides

Bind an implementation of `ResolvesTenantDriver` to let the database decide. Return `null` to defer to
static routing:

```php
use Vimatech\Integrations\Contracts\ResolvesTenantDriver;

final class TenantDriverResolver implements ResolvesTenantDriver
{
    public function resolveDriverKey(string $capability, array $context): ?string
    {
        return Tenant::find($context['tenant'] ?? null)
            ?->integrationKey($capability);
    }
}

// In a service provider:
$this->app->bind(ResolvesTenantDriver::class, TenantDriverResolver::class);
```

```php
Integrations::for('einvoice')->resolve(['tenant' => $tenant->id, 'country' => 'FR']);
```

## Webhooks

A single generic inbound route is registered:

```
POST {prefix}/{capability}/{driver?}
```

For each request, the pipeline:

1. Checks that webhooks are **enabled** for the capability (else `404`).
2. Resolves a `WebhookTranslator` — the configured `webhooks.translator`, or the resolved driver if it
   implements `WebhookTranslator`.
3. Calls `verify($request)`. On failure it dispatches `WebhookRejected` and returns `403`.
4. Dispatches `WebhookReceived`.
5. Calls `translate($request)` and, for each `CanonicalEvent`, enforces **idempotency** via the event
   key store before dispatching it through Laravel's event system.

Make a driver translate its own webhooks:

```php
use Illuminate\Http\Request;
use Vimatech\Integrations\Contracts\Driver;
use Vimatech\Integrations\Contracts\WebhookTranslator;
use Vimatech\Integrations\Webhooks\CanonicalEvent;

final class ChorusProAdapter implements EInvoiceNetwork, WebhookTranslator
{
    public function __construct(private array $config) {}

    public function verify(Request $request): bool
    {
        return hash_equals(
            $this->config['webhook_secret'],
            (string) $request->header('X-Signature'),
        );
    }

    public function translate(Request $request): iterable
    {
        foreach ($request->input('events', []) as $raw) {
            yield new InvoiceDelivered($raw['id']);
        }
    }
}
```

Define canonical events your application listens for. The `idempotencyKey()` must be **stable** across
redeliveries:

```php
use Vimatech\Integrations\Webhooks\CanonicalEvent;

final class InvoiceDelivered extends CanonicalEvent
{
    public function __construct(public readonly string $invoiceId) {}

    public function idempotencyKey(): string
    {
        return "einvoice:delivered:{$this->invoiceId}";
    }
}
```

**Idempotency store** is configurable via `webhooks.event_store`:

- `cache` (default) — uses the atomic `Cache::add()` operation.
- `database` — uses a unique index on the published `integration_webhook_events` table.

> **Idempotency is claimed *before* dispatch.** An event key is marked as seen
> as soon as it is accepted, so a redelivery is skipped even if a listener fails.
> Make your canonical-event listeners **queued** (`ShouldQueue`): the webhook
> returns `200` immediately, and listener failures are retried by the queue
> rather than by the provider re-sending the webhook. Keep listeners idempotent
> on your own side too.

## Credentials & secure storage

By default credentials are read straight from config (`store => 'config'`). Set
`store => 'encrypted'` to decrypt the keys listed in a driver's `encrypted` array using Laravel's
encrypter:

```php
'drivers' => [
    'chorus_pro' => [
        'class'     => ChorusProAdapter::class,
        'api_key'   => env('CHORUS_PRO_KEY'),   // ciphertext at rest
        'encrypted' => ['api_key'],
    ],
],
```

To store credentials with [`vimatech/laravel-secure-fields`](https://github.com/vimatech-io) or any
other backend, bind your own `CredentialStore` — the package never assumes a vendor:

```php
use Vimatech\Integrations\Contracts\CredentialStore;

$this->app->singleton(CredentialStore::class, SecureFieldsCredentialStore::class);
```

## The `integrations:list` command

```bash
php artisan integrations:list
```

Prints every configured capability with its drivers, default, routing map and webhook status.

## Testing

Swap the manager for a fake and assert which drivers your code used:

```php
use Vimatech\Integrations\Facades\Integrations;

it('uses the SDI driver for Italian invoices', function () {
    $fake = Integrations::fake();

    app(InvoiceSender::class)->send($italianInvoice);

    $fake->assertDriverUsed('einvoice', 'sdi');
});
```

`Integrations::fake()` keeps the **real routing logic** (so context routing still resolves to the right
key) while returning recording doubles. Provide your own capability fakes when you need behaviour:

```php
$fake = Integrations::fake([
    'einvoice:sdi' => new FakeEInvoiceNetwork(),   // your double implementing the capability contract
]);
```

Available assertions on the fake: `assertDriverUsed()`, `assertDriverNotUsed()`, `assertNothingUsed()`,
and `used()` for the raw record.

## Octane & FrankenPHP

The package is built for long-lived workers. It keeps **no static or global state**; the only mutable
state is the per-key driver instance cache on the `IntegrationManager` singleton — which is a
performance *win* under workers, since each adapter is built once and reused across requests.

Three rules keep it safe and fast in worker mode:

1. **Keep adapters stateless per request.** Read from the injected `$config`; never store request-bound
   state (the current user, the `Request`, a cart) on an adapter, or it will leak into the next request.
   Driver resolution itself is just array lookups plus a one-time container build.

2. **Queue your canonical-event listeners** (`ShouldQueue`) — see the
   [webhook idempotency note](#webhooks). The worker returns `200` immediately and retries happen on the
   queue.

3. **Per-tenant credentials via `extend()`?** The instance cache is keyed by `capability:key`, not by
   tenant. That is correct when credentials come from config (static per key). Only if you register an
   `extend()` factory that *captures* per-tenant credentials do you need to avoid the shared cache —
   resolve those per tenant in your own code instead.

If (and only if) you intentionally keep request state on an adapter, flush the cache each request:

```php
use Laravel\Octane\Events\RequestReceived;
use Vimatech\Integrations\IntegrationManager;

Event::listen(RequestReceived::class, fn () => app(IntegrationManager::class)->forgetDrivers());
```

Leave this off otherwise — it discards the build cache that makes workers fast.

`env()` is only ever read inside `config/integrations.php`, and routes are registered once, so the
package is fully compatible with `config:cache` and `route:cache`.

## Quality

```bash
composer test     # Pest + orchestra/testbench
composer lint     # Laravel Pint
composer stan     # PHPStan (level max) + Larastan
```

## Credits

Built and maintained by [Vimatech](https://github.com/vimatech-io). Created by
[Adel Zemzemi](https://github.com/vimatech-io).

## License

The MIT License (MIT). Please see the [License File](LICENSE) for more information.

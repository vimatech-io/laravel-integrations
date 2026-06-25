<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Credential store
    |--------------------------------------------------------------------------
    |
    | Controls how driver credentials defined under each capability are turned
    | into usable values before they reach an adapter.
    |
    |   - "config":    use the values as-is (read from env in this file).
    |   - "encrypted": decrypt any credential keys listed in a driver's
    |                  "encrypted" array using Laravel's Encrypter.
    |
    | To store credentials at rest with vimatech/laravel-secure-fields, bind
    | your own implementation of Vimatech\Integrations\Contracts\CredentialStore
    | in a service provider — this package never assumes a vendor.
    |
    */

    'credentials' => [
        'store' => env('INTEGRATIONS_CREDENTIAL_STORE', 'config'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Webhooks
    |--------------------------------------------------------------------------
    |
    | A single generic inbound route is registered as:
    |
    |     POST {prefix}/{capability}/{driver?}
    |
    | Each capability opts in via its own "webhooks" block below. Inbound
    | requests are verified, translated into canonical events and dispatched,
    | with idempotency enforced through the configured event key store.
    |
    */

    'webhooks' => [
        'prefix' => env('INTEGRATIONS_WEBHOOK_PREFIX', 'integrations/webhooks'),

        'middleware' => ['api'],

        // Idempotency backend: "cache" or "database".
        'event_store' => env('INTEGRATIONS_WEBHOOK_STORE', 'cache'),

        // Cache store used when event_store = "cache" (null = default store).
        'cache_store' => env('INTEGRATIONS_WEBHOOK_CACHE_STORE'),

        // Table used when event_store = "database".
        'event_table' => 'integration_webhook_events',

        // How long a processed event key is remembered, in seconds.
        'event_ttl' => 86400,
    ],

    /*
    |--------------------------------------------------------------------------
    | Capabilities
    |--------------------------------------------------------------------------
    |
    | A capability is a contract owned by a CONSUMER package (for example an
    | "e-invoice network" or a "payment gateway"). This package does not know
    | any concrete capability — it only wires drivers, routing and webhooks.
    |
    | Schema per capability:
    |
    |   'capability_name' => [
    |       'default' => 'driver_key',
    |
    |       'routing' => [
    |           'by'  => 'country',           // context dimension to route on
    |           'map' => [
    |               'FR' => 'driver_key_a',    // contextValue => driverKey
    |               'IT' => 'driver_key_b',
    |           ],
    |       ],
    |
    |       'drivers' => [
    |           'driver_key_a' => [
    |               'class'     => \App\Integrations\SomeAdapter::class,
    |               'api_key'   => env('SOME_API_KEY'),
    |               // 'encrypted' => ['api_key'], // when credentials.store = "encrypted"
    |           ],
    |       ],
    |
    |       'webhooks' => [
    |           'enabled'    => true,
    |           // null = use the resolved driver if it implements WebhookTranslator
    |           'translator' => null,
    |       ],
    |   ],
    |
    | Adapter constructors receive the resolved config array, e.g.
    | `public function __construct(public array $config) {}`.
    |
    */

    'capabilities' => [
        // Defined by your application / consumer packages.
    ],

];

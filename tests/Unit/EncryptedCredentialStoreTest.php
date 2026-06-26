<?php

declare(strict_types=1);

use Vimatech\Integrations\Credentials\EncryptedCredentialStore;
use Vimatech\Integrations\DriverRegistry;
use Vimatech\Integrations\IntegrationManager;
use Vimatech\Integrations\Tests\Fixtures\StripeGateway;

it('decrypts only the listed credential keys', function (): void {
    $encrypter = app('encrypter');
    $store = new EncryptedCredentialStore($encrypter);

    $resolved = $store->resolve([
        'class' => 'Foo',
        'api_key' => $encrypter->encrypt('secret-value'),
        'public' => 'plain',
        'encrypted' => ['api_key'],
    ]);

    expect($resolved['api_key'])->toBe('secret-value')
        ->and($resolved['public'])->toBe('plain')
        ->and($resolved)->not->toHaveKey('encrypted');
});

it('passes through values that are not valid ciphertext', function (): void {
    $store = new EncryptedCredentialStore(app('encrypter'));

    $resolved = $store->resolve([
        'api_key' => 'not-encrypted',
        'encrypted' => ['api_key'],
    ]);

    expect($resolved['api_key'])->toBe('not-encrypted');
});

it('decrypts credentials through the manager before building the driver', function (): void {
    $encrypter = app('encrypter');

    $registry = new DriverRegistry([
        'payments' => [
            'default' => 'stripe',
            'drivers' => [
                'stripe' => [
                    'class' => StripeGateway::class,
                    'api_key' => $encrypter->encrypt('top-secret'),
                    'encrypted' => ['api_key'],
                ],
            ],
        ],
    ]);

    $manager = new IntegrationManager(app(), $registry, new EncryptedCredentialStore($encrypter));

    /** @var StripeGateway $driver */
    $driver = $manager->driver('payments', 'stripe');

    expect($driver->config()['api_key'])->toBe('top-secret')
        ->and($driver->config())->not->toHaveKey('encrypted');
});

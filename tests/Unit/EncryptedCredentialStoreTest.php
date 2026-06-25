<?php

declare(strict_types=1);

use Vimatech\Integrations\Credentials\EncryptedCredentialStore;

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

<?php

declare(strict_types=1);

namespace Vimatech\Integrations\Credentials;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Contracts\Encryption\Encrypter;
use Vimatech\Integrations\Contracts\CredentialStore;

/**
 * Decrypts the credential keys listed in a driver's `encrypted` array using
 * Laravel's Encrypter. Values that are not valid ciphertext are passed through
 * unchanged, so partially-encrypted configs are tolerated during migration.
 *
 * This is a sensible default; for credentials stored with a dedicated package
 * such as vimatech/laravel-secure-fields, bind your own CredentialStore.
 */
final class EncryptedCredentialStore implements CredentialStore
{
    public function __construct(private readonly Encrypter $encrypter) {}

    public function resolve(array $config): array
    {
        /** @var list<string> $encrypted */
        $encrypted = $config['encrypted'] ?? [];

        foreach ($encrypted as $key) {
            if (! isset($config[$key]) || ! is_string($config[$key])) {
                continue;
            }

            try {
                $config[$key] = $this->encrypter->decrypt($config[$key]);
            } catch (DecryptException) {
                // Leave the value as-is when it is not valid ciphertext.
            }
        }

        unset($config['encrypted']);

        return $config;
    }
}

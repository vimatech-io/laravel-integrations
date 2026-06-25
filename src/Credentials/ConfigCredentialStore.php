<?php

declare(strict_types=1);

namespace Vimatech\Integrations\Credentials;

use Vimatech\Integrations\Contracts\CredentialStore;

/**
 * Default store: returns credentials exactly as configured (typically read from
 * environment variables in the config file).
 */
final class ConfigCredentialStore implements CredentialStore
{
    public function resolve(array $config): array
    {
        return $config;
    }
}

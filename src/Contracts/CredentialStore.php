<?php

declare(strict_types=1);

namespace Vimatech\Integrations\Contracts;

/**
 * Resolves a driver's raw configuration array into usable credentials before
 * the adapter is instantiated.
 *
 * Bind a custom implementation (for example one backed by
 * vimatech/laravel-secure-fields) to decrypt credentials stored at rest.
 */
interface CredentialStore
{
    /**
     * @param  array<string, mixed>  $config
     * @return array<string, mixed>
     */
    public function resolve(array $config): array;
}

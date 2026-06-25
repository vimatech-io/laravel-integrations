<?php

declare(strict_types=1);

namespace Vimatech\Integrations\Contracts;

/**
 * Allows a tenant-aware application to override which driver key is used for a
 * capability based on runtime context (typically read from the database).
 *
 * Bind an implementation to the container to have the ContextRouter consult it
 * before falling back to static routing or the configured default.
 */
interface ResolvesTenantDriver
{
    /**
     * Return the driver key to use, or null to defer to static routing.
     *
     * @param  array<string, mixed>  $context
     */
    public function resolveDriverKey(string $capability, array $context): ?string;
}

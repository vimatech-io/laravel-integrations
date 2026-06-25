<?php

declare(strict_types=1);

namespace Vimatech\Integrations\Tests\Fixtures;

use Vimatech\Integrations\Contracts\ResolvesTenantDriver;

/**
 * Stand-in for a database-backed per-tenant driver override.
 */
final class StaticTenantResolver implements ResolvesTenantDriver
{
    /**
     * @param  array<int|string, string>  $map  tenant id => driver key
     */
    public function __construct(private readonly array $map) {}

    public function resolveDriverKey(string $capability, array $context): ?string
    {
        $tenant = $context['tenant'] ?? null;

        if (! is_scalar($tenant)) {
            return null;
        }

        return $this->map[$tenant] ?? null;
    }
}

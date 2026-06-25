<?php

declare(strict_types=1);

namespace Vimatech\Integrations\Routing;

use Vimatech\Integrations\Contracts\Driver;
use Vimatech\Integrations\Contracts\ResolvesTenantDriver;
use Vimatech\Integrations\DriverRegistry;
use Vimatech\Integrations\Exceptions\UnresolvableDriver;
use Vimatech\Integrations\IntegrationManager;

/**
 * Resolves a driver for a single capability, either explicitly, by the
 * configured default, or by routing a context array (e.g. ['country' => 'FR']).
 *
 * Resolution order for context-based routing:
 *   1. A bound ResolvesTenantDriver (per-tenant override from the database).
 *   2. Static routing on the configured `by` dimension.
 *   3. The capability default (unless strict resolution was requested).
 *   4. Otherwise an UnresolvableDriver exception.
 */
final class ContextRouter
{
    public function __construct(
        private readonly IntegrationManager $manager,
        private readonly DriverRegistry $registry,
        private readonly string $capability,
        private readonly ?ResolvesTenantDriver $tenantResolver = null,
    ) {}

    /**
     * The capability's default driver.
     */
    public function default(): Driver
    {
        $key = $this->registry->defaultKey($this->capability)
            ?? throw UnresolvableDriver::noDefault($this->capability);

        return $this->manager->driver($this->capability, $key);
    }

    /**
     * Resolve a driver by explicit key.
     */
    public function via(string $key): Driver
    {
        return $this->manager->driver($this->capability, $key);
    }

    /**
     * Resolve a driver from context, falling back to the default when context
     * does not match any route.
     *
     * @param  array<string, mixed>  $context
     */
    public function resolve(array $context = []): Driver
    {
        return $this->manager->driver($this->capability, $this->key($context));
    }

    /**
     * Resolve a driver from context WITHOUT falling back to the default. Throws
     * UnresolvableDriver when neither a tenant override nor static routing
     * matches.
     *
     * @param  array<string, mixed>  $context
     */
    public function resolveStrict(array $context): Driver
    {
        $key = $this->contextKey($context)
            ?? throw UnresolvableDriver::forContext($this->capability, $context);

        return $this->manager->driver($this->capability, $key);
    }

    /**
     * Resolve the driver key for a context, with default fallback.
     *
     * @param  array<string, mixed>  $context
     */
    public function key(array $context = []): string
    {
        return $this->contextKey($context)
            ?? $this->registry->defaultKey($this->capability)
            ?? throw UnresolvableDriver::forContext($this->capability, $context);
    }

    /**
     * Resolve a driver key from context alone (tenant override then static
     * routing), without falling back to the default.
     *
     * @param  array<string, mixed>  $context
     */
    private function contextKey(array $context): ?string
    {
        return $this->tenantKey($context) ?? $this->routeKey($context);
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function tenantKey(array $context): ?string
    {
        return $this->tenantResolver?->resolveDriverKey($this->capability, $context);
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function routeKey(array $context): ?string
    {
        $routing = $this->registry->routing($this->capability);
        $by = $routing['by'] ?? null;

        if ($by === null) {
            return null;
        }

        $value = $context[$by] ?? null;

        if (! is_scalar($value)) {
            return null;
        }

        return $routing['map'][(string) $value] ?? null;
    }
}

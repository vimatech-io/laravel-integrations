<?php

declare(strict_types=1);

namespace Vimatech\Integrations;

use Closure;
use Illuminate\Contracts\Container\Container;
use Vimatech\Integrations\Contracts\CredentialStore;
use Vimatech\Integrations\Contracts\Driver;
use Vimatech\Integrations\Contracts\ResolvesTenantDriver;
use Vimatech\Integrations\Exceptions\DriverNotConfigured;
use Vimatech\Integrations\Routing\ContextRouter;

/**
 * Resolves integration drivers by capability + key from configuration, in the
 * spirit of Illuminate\Support\Manager but fully config-driven so that adding a
 * provider means adding an adapter class and a config entry — nothing else.
 */
class IntegrationManager
{
    /**
     * Resolved driver instances, keyed by "capability:key".
     *
     * @var array<string, Driver>
     */
    protected array $resolved = [];

    /**
     * Custom driver factories, keyed by "capability:key".
     *
     * @var array<string, Closure(array<string, mixed>, Container): Driver>
     */
    protected array $creators = [];

    public function __construct(
        protected readonly Container $container,
        protected readonly DriverRegistry $registry,
        protected readonly CredentialStore $credentials,
    ) {}

    /**
     * Resolve a driver for the given capability. When $key is null the
     * capability's default driver is used.
     */
    public function driver(string $capability, ?string $key = null): Driver
    {
        $key ??= $this->registry->defaultKey($capability)
            ?? throw DriverNotConfigured::noDefault($capability);

        return $this->resolved[$capability.':'.$key] ??= $this->resolve($capability, $key);
    }

    /**
     * Get a context router scoped to a capability.
     */
    public function for(string $capability): ContextRouter
    {
        return new ContextRouter(
            $this,
            $this->registry,
            $capability,
            $this->tenantResolver(),
        );
    }

    /**
     * Register a custom factory for a specific capability + driver key.
     *
     * @param  Closure(array<string, mixed>, Container): Driver  $creator
     */
    public function extend(string $capability, string $key, Closure $creator): static
    {
        $this->creators[$capability.':'.$key] = $creator;

        return $this;
    }

    /**
     * @return list<string>
     */
    public function capabilities(): array
    {
        return $this->registry->capabilities();
    }

    public function registry(): DriverRegistry
    {
        return $this->registry;
    }

    /**
     * Forget cached driver instances (mainly useful in tests).
     */
    public function forgetDrivers(): static
    {
        $this->resolved = [];

        return $this;
    }

    protected function resolve(string $capability, string $key): Driver
    {
        $config = $this->credentials->resolve(
            $this->registry->driverConfig($capability, $key)
        );

        $creator = $this->creators[$capability.':'.$key] ?? null;

        $driver = $creator !== null
            ? $creator($config, $this->container)
            : $this->build($capability, $key, $config);

        return $driver;
    }

    /**
     * @param  array<string, mixed>  $config
     */
    protected function build(string $capability, string $key, array $config): Driver
    {
        /** @var class-string|null $class */
        $class = $config['class'] ?? null;

        if ($class === null) {
            throw DriverNotConfigured::missingClass($capability, $key);
        }

        /** @var object $instance */
        $instance = $this->container->make($class, ['config' => $config] + $config);

        if (! $instance instanceof Driver) {
            throw DriverNotConfigured::notADriver($capability, $key, $class);
        }

        return $instance;
    }

    protected function tenantResolver(): ?ResolvesTenantDriver
    {
        if (! $this->container->bound(ResolvesTenantDriver::class)) {
            return null;
        }

        /** @var ResolvesTenantDriver $resolver */
        $resolver = $this->container->make(ResolvesTenantDriver::class);

        return $resolver;
    }
}

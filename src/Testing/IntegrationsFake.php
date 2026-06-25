<?php

declare(strict_types=1);

namespace Vimatech\Integrations\Testing;

use Illuminate\Contracts\Container\Container;
use PHPUnit\Framework\Assert as PHPUnit;
use Vimatech\Integrations\Contracts\CredentialStore;
use Vimatech\Integrations\Contracts\Driver;
use Vimatech\Integrations\DriverRegistry;
use Vimatech\Integrations\IntegrationManager;

/**
 * A test double for the IntegrationManager that records driver resolutions and
 * returns predefined or generated fakes, while preserving the real routing
 * logic (ContextRouter still routes by context against the configured maps).
 */
final class IntegrationsFake extends IntegrationManager
{
    /**
     * Explicit fakes keyed by "capability:key" or "capability".
     *
     * @var array<string, Driver>
     */
    private array $fakeDrivers = [];

    /**
     * Recorded resolutions.
     *
     * @var list<array{capability: string, key: string}>
     */
    private array $used = [];

    /**
     * @param  array<string, Driver>  $fakeDrivers
     */
    public function __construct(Container $container, array $fakeDrivers = [])
    {
        /** @var DriverRegistry $registry */
        $registry = $container->make(DriverRegistry::class);
        /** @var CredentialStore $credentials */
        $credentials = $container->make(CredentialStore::class);

        parent::__construct($container, $registry, $credentials);

        $this->fakeDrivers = $fakeDrivers;
    }

    public function driver(string $capability, ?string $key = null): Driver
    {
        // Tolerate capabilities that aren't configured, so a test can fake an
        // integration without wiring its config.
        $key ??= ($this->registry->has($capability) ? $this->registry->defaultKey($capability) : null) ?? 'default';

        $this->used[] = ['capability' => $capability, 'key' => $key];

        return $this->fakeDrivers[$capability.':'.$key]
            ?? $this->fakeDrivers[$capability]
            ?? ($this->fakeDrivers[$capability.':'.$key] = new FakeDriver($capability, $key));
    }

    /**
     * Register (or replace) a fake driver.
     */
    public function set(string $capability, ?string $key, Driver $driver): self
    {
        $this->fakeDrivers[$key === null ? $capability : $capability.':'.$key] = $driver;

        return $this;
    }

    public function assertDriverUsed(string $capability, ?string $key = null): void
    {
        PHPUnit::assertNotEmpty(
            $this->recordsFor($capability, $key),
            $key === null
                ? "Expected a driver for capability [{$capability}] to be used, but none was."
                : "Expected driver [{$key}] for capability [{$capability}] to be used, but it was not."
        );
    }

    public function assertDriverNotUsed(string $capability, ?string $key = null): void
    {
        PHPUnit::assertEmpty(
            $this->recordsFor($capability, $key),
            "Expected no driver for capability [{$capability}] to be used, but one was."
        );
    }

    public function assertNothingUsed(): void
    {
        PHPUnit::assertEmpty($this->used, 'Expected no integration drivers to be used.');
    }

    /**
     * @return list<array{capability: string, key: string}>
     */
    public function used(): array
    {
        return $this->used;
    }

    /**
     * @return list<array{capability: string, key: string}>
     */
    private function recordsFor(string $capability, ?string $key): array
    {
        return array_values(array_filter(
            $this->used,
            static fn (array $record): bool => $record['capability'] === $capability
                && ($key === null || $record['key'] === $key)
        ));
    }
}

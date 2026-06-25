<?php

declare(strict_types=1);

namespace Vimatech\Integrations;

use Vimatech\Integrations\Exceptions\DriverNotConfigured;

/**
 * Read-only view over the configured capabilities, drivers, routing and
 * webhook settings. Pure configuration access — it never instantiates drivers.
 *
 * @phpstan-type DriverConfig array{class?: class-string, encrypted?: list<string>}&array<string, mixed>
 * @phpstan-type RoutingConfig array{by?: string, map?: array<string, string>}
 * @phpstan-type CapabilityConfig array{
 *     default?: string,
 *     routing?: RoutingConfig,
 *     drivers?: array<string, DriverConfig>,
 *     webhooks?: array{enabled?: bool, translator?: class-string|null}
 * }
 */
final class DriverRegistry
{
    /**
     * @param  array<string, CapabilityConfig>  $capabilities
     */
    public function __construct(private readonly array $capabilities) {}

    /**
     * @return list<string>
     */
    public function capabilities(): array
    {
        return array_keys($this->capabilities);
    }

    public function has(string $capability): bool
    {
        return isset($this->capabilities[$capability]);
    }

    /**
     * @return CapabilityConfig
     */
    public function capability(string $capability): array
    {
        return $this->capabilities[$capability]
            ?? throw DriverNotConfigured::capability($capability);
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function drivers(string $capability): array
    {
        return $this->capability($capability)['drivers'] ?? [];
    }

    /**
     * @return array<string, mixed>
     */
    public function driverConfig(string $capability, string $key): array
    {
        $drivers = $this->drivers($capability);

        return $drivers[$key]
            ?? throw DriverNotConfigured::driver($capability, $key);
    }

    public function defaultKey(string $capability): ?string
    {
        return $this->capability($capability)['default'] ?? null;
    }

    /**
     * @return RoutingConfig
     */
    public function routing(string $capability): array
    {
        return $this->capability($capability)['routing'] ?? [];
    }

    public function webhooksEnabled(string $capability): bool
    {
        if (! $this->has($capability)) {
            return false;
        }

        return (bool) ($this->capability($capability)['webhooks']['enabled'] ?? false);
    }

    public function webhookTranslator(string $capability): ?string
    {
        /** @var class-string|null $translator */
        $translator = $this->capability($capability)['webhooks']['translator'] ?? null;

        return $translator;
    }
}

<?php

declare(strict_types=1);

namespace Vimatech\Integrations\Exceptions;

use Vimatech\Integrations\Contracts\Driver;

/**
 * Thrown when a capability, driver key or driver class is missing or invalid in
 * the configuration.
 */
final class DriverNotConfigured extends IntegrationException
{
    public static function capability(string $capability): self
    {
        return new self("Integration capability [{$capability}] is not configured.");
    }

    public static function driver(string $capability, string $key): self
    {
        return new self("Driver [{$key}] is not configured for capability [{$capability}].");
    }

    public static function noDefault(string $capability): self
    {
        return new self("Capability [{$capability}] has no default driver and none was given.");
    }

    public static function missingClass(string $capability, string $key): self
    {
        return new self("Driver [{$key}] for capability [{$capability}] has no 'class' defined.");
    }

    public static function notADriver(string $capability, string $key, string $class): self
    {
        return new self(
            "Driver [{$key}] for capability [{$capability}] resolved to [{$class}], "
            .'which does not implement '.Driver::class.'.'
        );
    }
}

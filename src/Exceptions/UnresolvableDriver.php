<?php

declare(strict_types=1);

namespace Vimatech\Integrations\Exceptions;

/**
 * Thrown when the ContextRouter cannot resolve a driver for the given context
 * and no fallback is permitted.
 */
final class UnresolvableDriver extends IntegrationException
{
    public static function noDefault(string $capability): self
    {
        return new self("No default driver is configured for capability [{$capability}].");
    }

    /**
     * @param  array<string, mixed>  $context
     */
    public static function forContext(string $capability, array $context): self
    {
        $rendered = $context === [] ? '(empty)' : json_encode($context, JSON_THROW_ON_ERROR);

        return new self("Unable to resolve a driver for capability [{$capability}] from context {$rendered}.");
    }
}

<?php

declare(strict_types=1);

namespace Vimatech\Integrations\Facades;

use Illuminate\Support\Facades\Facade;
use RuntimeException;
use Vimatech\Integrations\Contracts\Driver;
use Vimatech\Integrations\IntegrationManager;
use Vimatech\Integrations\Testing\IntegrationsFake;

/**
 * @method static Driver driver(string $capability, ?string $key = null)
 * @method static \Vimatech\Integrations\Routing\ContextRouter for(string $capability)
 * @method static list<string> capabilities()
 * @method static \Vimatech\Integrations\DriverRegistry registry()
 * @method static IntegrationManager extend(string $capability, string $key, \Closure $creator)
 *
 * @see IntegrationManager
 */
final class Integrations extends Facade
{
    /**
     * Swap the manager for a fake instance.
     *
     * @param  array<string, Driver>  $drivers  Predefined fakes keyed by "capability:key" or "capability".
     */
    public static function fake(array $drivers = []): IntegrationsFake
    {
        $app = self::getFacadeApplication()
            ?? throw new RuntimeException('Integrations::fake() requires a booted application.');

        $fake = new IntegrationsFake($app, $drivers);

        self::swap($fake);

        return $fake;
    }

    protected static function getFacadeAccessor(): string
    {
        return IntegrationManager::class;
    }
}

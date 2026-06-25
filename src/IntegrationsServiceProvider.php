<?php

declare(strict_types=1);

namespace Vimatech\Integrations;

use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Encryption\Encrypter;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\ServiceProvider;
use Vimatech\Integrations\Console\ListIntegrationsCommand;
use Vimatech\Integrations\Contracts\CredentialStore;
use Vimatech\Integrations\Contracts\EventKeyStore;
use Vimatech\Integrations\Credentials\ConfigCredentialStore;
use Vimatech\Integrations\Credentials\EncryptedCredentialStore;
use Vimatech\Integrations\Webhooks\CacheEventKeyStore;
use Vimatech\Integrations\Webhooks\DatabaseEventKeyStore;

/**
 * @phpstan-import-type CapabilityConfig from DriverRegistry
 */
final class IntegrationsServiceProvider extends ServiceProvider
{
    private const CONFIG_PATH = __DIR__.'/../config/integrations.php';

    public function register(): void
    {
        $this->mergeConfigFrom(self::CONFIG_PATH, 'integrations');

        $this->registerRegistry();
        $this->registerCredentialStore();
        $this->registerEventKeyStore();
        $this->registerManager();
    }

    public function boot(): void
    {
        $this->registerRoutes();

        if ($this->app->runningInConsole()) {
            $this->registerPublishing();

            $this->commands([
                ListIntegrationsCommand::class,
            ]);
        }
    }

    private function registerRegistry(): void
    {
        $this->app->singleton(DriverRegistry::class, function (Container $app): DriverRegistry {
            /** @var Config $config */
            $config = $app->make('config');

            /** @var array<string, CapabilityConfig> $capabilities */
            $capabilities = $config->get('integrations.capabilities', []);

            return new DriverRegistry($capabilities);
        });
    }

    private function registerCredentialStore(): void
    {
        $this->app->singleton(CredentialStore::class, function (Container $app): CredentialStore {
            /** @var Config $config */
            $config = $app->make('config');

            return match ($config->get('integrations.credentials.store', 'config')) {
                'encrypted' => new EncryptedCredentialStore($app->make(Encrypter::class)),
                default => new ConfigCredentialStore,
            };
        });
    }

    private function registerEventKeyStore(): void
    {
        $this->app->singleton(EventKeyStore::class, function (Container $app): EventKeyStore {
            /** @var Config $config */
            $config = $app->make('config');

            return match ($config->get('integrations.webhooks.event_store', 'cache')) {
                'database' => new DatabaseEventKeyStore(
                    $app->make(DatabaseManager::class)->connection(),
                    $this->stringConfig($config, 'integrations.webhooks.event_table', 'integration_webhook_events'),
                ),
                default => new CacheEventKeyStore(
                    $app->make('cache')->store($this->nullableStringConfig($config, 'integrations.webhooks.cache_store')),
                ),
            };
        });
    }

    private function registerManager(): void
    {
        $this->app->singleton(IntegrationManager::class, function (Container $app): IntegrationManager {
            return new IntegrationManager(
                $app,
                $app->make(DriverRegistry::class),
                $app->make(CredentialStore::class),
            );
        });

        $this->app->alias(IntegrationManager::class, 'integrations');
    }

    private function registerRoutes(): void
    {
        // loadRoutesFrom() already skips loading when routes are cached.
        $this->loadRoutesFrom(__DIR__.'/../routes/webhooks.php');
    }

    private function registerPublishing(): void
    {
        $this->publishes([
            self::CONFIG_PATH => $this->app->configPath('integrations.php'),
        ], 'integrations-config');

        $this->publishes([
            __DIR__.'/../database/migrations' => $this->app->databasePath('migrations'),
        ], 'integrations-migrations');
    }

    private function stringConfig(Config $config, string $key, string $default): string
    {
        $value = $config->get($key, $default);

        return is_string($value) ? $value : $default;
    }

    private function nullableStringConfig(Config $config, string $key): ?string
    {
        $value = $config->get($key);

        return is_string($value) ? $value : null;
    }
}

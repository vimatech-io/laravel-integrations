<?php

declare(strict_types=1);

namespace Vimatech\Integrations\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use Vimatech\Integrations\IntegrationsServiceProvider;
use Vimatech\Integrations\Tests\Fixtures\AdyenGateway;
use Vimatech\Integrations\Tests\Fixtures\StripeGateway;
use Vimatech\Integrations\Tests\Fixtures\StripeWebhookGateway;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            IntegrationsServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $config = $app['config'];

        $config->set('integrations.capabilities', [
            'payments' => [
                'default' => 'stripe',
                'routing' => [
                    'by' => 'country',
                    'map' => [
                        'FR' => 'stripe',
                        'NL' => 'adyen',
                    ],
                ],
                'drivers' => [
                    'stripe' => [
                        'class' => StripeGateway::class,
                        'api_key' => 'sk_test_stripe',
                    ],
                    'adyen' => [
                        'class' => AdyenGateway::class,
                        'api_key' => 'adyen_test',
                    ],
                ],
            ],
            'einvoice' => [
                'default' => 'stripe_hooks',
                'drivers' => [
                    'stripe_hooks' => [
                        'class' => StripeWebhookGateway::class,
                        'secret' => 'whsec_test',
                    ],
                ],
                'webhooks' => [
                    'enabled' => true,
                ],
            ],
        ]);

        $config->set('integrations.webhooks.event_store', 'cache');
        $config->set('cache.default', 'array');
    }
}

<?php

declare(strict_types=1);

namespace Vimatech\Integrations\Console;

use Illuminate\Console\Command;
use Vimatech\Integrations\DriverRegistry;

/**
 * Lists every configured capability with its drivers, default, routing and
 * webhook status.
 */
final class ListIntegrationsCommand extends Command
{
    protected $signature = 'integrations:list';

    protected $description = 'List configured integration capabilities and their drivers';

    public function handle(DriverRegistry $registry): int
    {
        $capabilities = $registry->capabilities();

        if ($capabilities === []) {
            $this->components->warn('No integration capabilities are configured.');

            return self::SUCCESS;
        }

        foreach ($capabilities as $capability) {
            $default = $registry->defaultKey($capability) ?? '—';
            $routing = $registry->routing($capability);

            $this->components->twoColumnDetail("<fg=cyan;options=bold>{$capability}</>", "default: <fg=green>{$default}</>");

            foreach (array_keys($registry->drivers($capability)) as $key) {
                $marker = $key === $registry->defaultKey($capability) ? ' <fg=green>(default)</>' : '';
                $this->components->twoColumnDetail("  driver: {$key}{$marker}", '');
            }

            if (($routing['by'] ?? null) !== null) {
                foreach (($routing['map'] ?? []) as $value => $key) {
                    $this->components->twoColumnDetail("  route: {$routing['by']}={$value}", "→ {$key}");
                }
            }

            $this->components->twoColumnDetail(
                '  webhooks',
                $registry->webhooksEnabled($capability) ? '<fg=green>enabled</>' : '<fg=gray>disabled</>'
            );

            $this->newLine();
        }

        return self::SUCCESS;
    }
}

<?php

declare(strict_types=1);

namespace Vimatech\Integrations\Testing;

use Vimatech\Integrations\Contracts\Driver;

/**
 * A generic recording double returned by IntegrationsFake when no explicit fake
 * was provided for a capability/key. Consumers may pass their own fakes (that
 * implement the relevant capability contract) instead.
 */
final class FakeDriver implements Driver
{
    public function __construct(
        public readonly string $capability,
        public readonly ?string $key,
    ) {}
}

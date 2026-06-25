<?php

declare(strict_types=1);

namespace Vimatech\Integrations\Webhooks;

use Illuminate\Contracts\Cache\Repository;
use Vimatech\Integrations\Contracts\EventKeyStore;

/**
 * Cache-backed idempotency store. Relies on the atomic `add()` operation, which
 * only succeeds when the key is not already present.
 */
final class CacheEventKeyStore implements EventKeyStore
{
    private const PREFIX = 'integrations:webhook:';

    public function __construct(private readonly Repository $cache) {}

    public function acquire(string $key, int $ttl): bool
    {
        return $this->cache->add(self::PREFIX.$key, true, $ttl);
    }
}

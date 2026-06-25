<?php

declare(strict_types=1);

namespace Vimatech\Integrations\Webhooks;

use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\QueryException;
use Vimatech\Integrations\Contracts\EventKeyStore;

/**
 * Database-backed idempotency store. Relies on a unique index on the `key`
 * column: a duplicate insert raises a QueryException, which we treat as "seen".
 */
final class DatabaseEventKeyStore implements EventKeyStore
{
    public function __construct(
        private readonly ConnectionInterface $connection,
        private readonly string $table,
    ) {}

    public function acquire(string $key, int $ttl): bool
    {
        try {
            $this->connection->table($this->table)->insert([
                'key' => $key,
                'created_at' => now(),
            ]);

            return true;
        } catch (QueryException) {
            return false;
        }
    }
}

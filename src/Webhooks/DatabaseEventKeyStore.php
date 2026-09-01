<?php

declare(strict_types=1);

namespace Vimatech\Integrations\Webhooks;

use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\QueryException;
use Vimatech\Integrations\Contracts\EventKeyStore;

/**
 * Database-backed idempotency store. Relies on a unique index on the `key`
 * column: a duplicate insert raises an integrity constraint violation, which
 * means the event has been seen. Any other database failure is re-thrown, so
 * the delivery fails and the provider retries rather than being told the event
 * was already handled.
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
        } catch (QueryException $e) {
            if (! $this->isDuplicate($e)) {
                throw $e;
            }

            return false;
        }
    }

    /**
     * SQLSTATE class 23 is "integrity constraint violation" on MySQL, PostgreSQL
     * and SQLite alike. A missing table, a closed connection or a denied
     * permission carries a different class and is not a duplicate.
     */
    private function isDuplicate(QueryException $e): bool
    {
        return str_starts_with((string) $e->getCode(), '23');
    }
}

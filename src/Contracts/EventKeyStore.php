<?php

declare(strict_types=1);

namespace Vimatech\Integrations\Contracts;

/**
 * Backs webhook idempotency by remembering which event keys have already been
 * processed.
 */
interface EventKeyStore
{
    /**
     * Atomically claim an event key.
     *
     * Returns true if the key was claimed for the first time (the caller should
     * process the event) or false if it was already seen (a duplicate).
     */
    public function acquire(string $key, int $ttl): bool;
}

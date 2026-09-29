<?php

declare(strict_types=1);

namespace PhpMiniCache\Transaction;

use PhpMiniCache\Storage\Store;

/**
 * The Store the server runs commands against: every write is passed
 * through and reported to the TransactionManager, so a WATCHed key that
 * changes aborts the watcher's EXEC.
 *
 * Wrapping the store rather than the write commands means no handler can
 * forget to report - SET, DEL, INCR and anything added later all write
 * through here, inside a transaction or not.
 *
 * One deliberate difference from Redis: a DEL of a key that does not exist
 * still counts as a write. Redis only signals keys it actually removed,
 * which leaves cache-aside open to the classic race - a reader misses,
 * WATCHes, reads the old row from the database; a writer updates the row
 * and DELs the (absent) key; the reader's EXEC then stores the old row.
 * Treating the DEL as a write aborts that EXEC, which is what makes WATCH
 * usable as the reader's guard.
 *
 * Expiration is not reported: a key that expires under a watch was not
 * written, and the reader that watched it is about to overwrite it anyway.
 */
final readonly class KeyWatchingStore implements Store
{
    public function __construct(
        private Store $store,
        private TransactionManager $transactions,
    ) {
    }

    public function set(string $key, mixed $value, ?int $ttlSeconds = null): void
    {
        $this->store->set($key, $value, $ttlSeconds);
        $this->transactions->touch($key);
    }

    public function setKeepingTtl(string $key, mixed $value): bool
    {
        $written = $this->store->setKeepingTtl($key, $value);

        if ($written) {
            $this->transactions->touch($key);
        }

        return $written;
    }

    public function get(string $key): mixed
    {
        return $this->store->get($key);
    }

    public function has(string $key): bool
    {
        return $this->store->has($key);
    }

    public function delete(string $key): bool
    {
        $deleted = $this->store->delete($key);
        $this->transactions->touch($key);

        return $deleted;
    }

    public function sweepExpired(): int
    {
        return $this->store->sweepExpired();
    }
}

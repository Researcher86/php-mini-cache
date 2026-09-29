<?php

declare(strict_types=1);

namespace PhpMiniCache\Transaction;

use PhpMiniCache\Command\Command;
use PhpMiniCache\Connection\ClientConnection;

/**
 * Tracks each connection's queued commands between MULTI and EXEC/DISCARD,
 * and the keys it WATCHes for optimistic locking.
 *
 * "Is a transaction open" is the presence of an entry, not a flag on it -
 * which is why an open transaction with nothing queued yet is an empty
 * array rather than a missing key, and why discard() unsets instead of
 * clearing. Keyed by connection id, with the same cleanup obligation as
 * ChannelRegistry.
 *
 * WATCH is Redis's check-and-set: a connection watches keys, and if any of
 * them is written by anyone before its EXEC, the EXEC is aborted (a null
 * reply) instead of run. Only keys currently watched are tracked, so the
 * bookkeeping is bounded by live watches, not by every key ever written.
 * A watch ends with the connection's EXEC, DISCARD, UNWATCH or disconnect.
 */
final class TransactionManager
{
    /** @var array<int, list<Command>> */
    private array $queued = [];

    /** @var array<string, array<int, true>> key => ids of connections watching it */
    private array $watchers = [];

    /** @var array<int, array<string, true>> connection id => keys it watches */
    private array $watched = [];

    /** @var array<int, true> connections whose watched keys changed since WATCH */
    private array $dirty = [];

    public function begin(ClientConnection $connection): void
    {
        $this->queued[$connection->id()] = [];
    }

    public function isActive(ClientConnection $connection): bool
    {
        return isset($this->queued[$connection->id()]);
    }

    public function queue(ClientConnection $connection, Command $command): void
    {
        $this->queued[$connection->id()][] = $command;
    }

    /** Ends the transaction, if any, and the connection's watches with it. */
    public function discard(ClientConnection $connection): void
    {
        unset($this->queued[$connection->id()]);
        $this->unwatch($connection);
    }

    public function watch(ClientConnection $connection, string ...$keys): void
    {
        foreach ($keys as $key) {
            $this->watchers[$key][$connection->id()] = true;
            $this->watched[$connection->id()][$key] = true;
        }
    }

    public function unwatch(ClientConnection $connection): void
    {
        $id = $connection->id();

        foreach (array_keys($this->watched[$id] ?? []) as $key) {
            unset($this->watchers[$key][$id]);

            if ($this->watchers[$key] === []) {
                unset($this->watchers[$key]);
            }
        }

        unset($this->watched[$id], $this->dirty[$id]);
    }

    /**
     * Records a write to $key: every connection watching it will have its
     * next EXEC aborted. Called for every write, including a DEL of a key
     * that does not exist (see KeyWatchingStore).
     */
    public function touch(string $key): void
    {
        foreach (array_keys($this->watchers[$key] ?? []) as $id) {
            $this->dirty[$id] = true;
        }
    }

    /** Whether a key this connection watches was written since it watched it. */
    public function isDirty(ClientConnection $connection): bool
    {
        return isset($this->dirty[$connection->id()]);
    }

    /**
     * Removes and returns the connection's queued commands, ending its
     * transaction and its watches (EXEC consumes both, run or aborted).
     *
     * @return list<Command>
     */
    public function drain(ClientConnection $connection): array
    {
        $commands = $this->queued[$connection->id()] ?? [];
        unset($this->queued[$connection->id()]);
        $this->unwatch($connection);

        return $commands;
    }
}

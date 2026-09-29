<?php

declare(strict_types=1);

namespace PhpMiniCache\Command\Handler;

use PhpMiniCache\Command\Command;
use PhpMiniCache\Command\CommandHandler;
use PhpMiniCache\Connection\ClientConnection;
use PhpMiniCache\Protocol\RespValue;
use PhpMiniCache\Storage\Store;
use PhpMiniCache\Transaction\TransactionManager;

/**
 * WATCH key [key ...]: the next EXEC on this connection is aborted if any
 * of these keys is written before it. See TransactionManager.
 */
final readonly class WatchCommand implements CommandHandler
{
    public function __construct(
        private TransactionManager $transactions,
    ) {
    }

    public function handle(Command $command, Store $store, ClientConnection $connection): RespValue
    {
        if ($command->arguments === []) {
            return RespValue::error("ERR wrong number of arguments for 'watch' command");
        }

        // A watch has to start before the transaction it guards: inside
        // MULTI the check would begin after the reads it is meant to cover.
        if ($this->transactions->isActive($connection)) {
            return RespValue::error('ERR WATCH inside MULTI is not allowed');
        }

        $this->transactions->watch($connection, ...$command->arguments);

        return RespValue::simpleString('OK');
    }
}

<?php

declare(strict_types=1);

namespace PhpMiniCache\Command\Handler;

use PhpMiniCache\Command\Command;
use PhpMiniCache\Command\CommandHandler;
use PhpMiniCache\Connection\ClientConnection;
use PhpMiniCache\Protocol\RespValue;
use PhpMiniCache\Storage\Store;
use PhpMiniCache\Transaction\TransactionManager;

/** UNWATCH: forget every key this connection watches. */
final readonly class UnwatchCommand implements CommandHandler
{
    public function __construct(
        private TransactionManager $transactions,
    ) {
    }

    public function handle(Command $command, Store $store, ClientConnection $connection): RespValue
    {
        $this->transactions->unwatch($connection);

        return RespValue::simpleString('OK');
    }
}

#!/usr/bin/env php
<?php

declare(strict_types=1);

use PhpMiniCache\Connection\ClientConnection;
use PhpMiniCache\Logging\ConsoleLogger;
use PhpMiniCache\Server\CacheServer;
use PhpMiniCache\Server\ServerConfig;

require __DIR__ . '/bootstrap.php';

$host = getenv('CACHE_HOST') ?: '127.0.0.1';
$port = (int) (getenv('CACHE_PORT') ?: 6380);

// Both off unless asked for, the way CacheServer itself defaults: unset
// (or empty) means no persistence and no idle timeout. A snapshot path
// alone still saves once, on a graceful shutdown; CACHE_SNAPSHOT_INTERVAL
// adds periodic saves on top.
$snapshotPath = getenv('CACHE_SNAPSHOT_PATH') ?: null;
$snapshotInterval = getenv('CACHE_SNAPSHOT_INTERVAL') ?: null;
$idleTimeout = getenv('CACHE_IDLE_TIMEOUT') ?: null;

$logger = new ConsoleLogger();
$server = new CacheServer(
    new ServerConfig(host: $host, port: $port),
    idleTimeoutSeconds: $idleTimeout === null ? null : (float) $idleTimeout,
    snapshotPath: $snapshotPath,
    snapshotIntervalSeconds: $snapshotInterval === null ? null : (float) $snapshotInterval,
    logger: $logger,
);

$logger->info(sprintf('Listening on %s', $server->localAddress()));

if ($snapshotPath !== null) {
    $logger->info(sprintf('Snapshots: %s', $snapshotPath));
}

// The event loop accepts connections as they arrive, so this process never
// blocks waiting on one particular client; everything each connection then
// sends is read, executed and answered from inside the same loop. This
// callback is the one hook into that - it runs per accepted connection,
// before the client has sent anything.
$server->run(function (ClientConnection $connection) use ($server, $logger): void {
    $logger->info(sprintf('Client connected. Total: %d', $server->connectedClientCount()));
});

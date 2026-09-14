<?php

declare(strict_types=1);

use PhpMiniCache\Sdk\CacheClient;
use PhpMiniCache\Sdk\CacheClientException;

require dirname(__DIR__) . '/vendor/autoload.php';

/**
 * Shared by every script under examples/: each one answers one question
 * against a real, already-running server (start it first with
 * `make run-server`), so this just builds the client they all use - and
 * turns "nothing is listening on 6380" into the sentence that actually
 * helps, instead of a stack trace from the middle of the demo.
 */
function exampleClient(float $timeoutSeconds = 5.0): CacheClient
{
    $client = new CacheClient(
        getenv('CACHE_HOST') ?: '127.0.0.1',
        (int) (getenv('CACHE_PORT') ?: 6380),
        $timeoutSeconds,
    );

    try {
        $client->ping();
    } catch (CacheClientException $exception) {
        fwrite(STDERR, $exception->getMessage() . " - is `make run-server` running?\n");

        exit(1);
    }

    return $client;
}

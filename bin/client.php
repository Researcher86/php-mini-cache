#!/usr/bin/env php
<?php

declare(strict_types=1);

use PhpMiniCache\Protocol\RespType;
use PhpMiniCache\Protocol\RespValue;
use PhpMiniCache\Sdk\CacheClient;
use PhpMiniCache\Sdk\CacheClientException;
use PhpMiniCache\Sdk\CommandFailedException;

require dirname(__DIR__) . '/vendor/autoload.php';

/**
 * Turns a reply into the one line a terminal wants, which is the only part
 * of this script that is not CacheClient doing the work.
 */
function describe(RespValue $value): string
{
    return match ($value->type) {
        RespType::SimpleString, RespType::Integer => (string) $value->value,
        RespType::Error => 'ERROR: ' . $value->value,
        RespType::BulkString => $value->value === null ? '(nil)' : $value->value,
        RespType::Array => $value->value === null
            ? '(nil)'
            : implode(', ', array_map(describe(...), $value->value)),
    };
}

$arguments = array_slice($argv, 1); // @phpstan-ignore variable.undefined ($argv is always set for a CLI script)

if ($arguments === []) {
    $arguments = ['PING'];
}

$client = new CacheClient(
    getenv('CACHE_HOST') ?: '127.0.0.1',
    (int) (getenv('CACHE_PORT') ?: 6380),
);

try {
    fwrite(STDOUT, describe($client->command(...$arguments)) . "\n");
} catch (CommandFailedException $exception) {
    // The server answered, and said no - reported the way the reference CLI does,
    // rather than as this script failing.
    fwrite(STDOUT, 'ERROR: ' . $exception->error . "\n");

    exit(1);
} catch (CacheClientException $exception) {
    fwrite(STDERR, $exception->getMessage() . "\n");

    exit(1);
} finally {
    $client->close();
}

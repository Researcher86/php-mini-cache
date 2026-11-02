#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * One question: does a key set with EX actually disappear once its TTL
 * has passed, and does that happen without ever reading it again in
 * between (Phase 17's active expiration, not just Phase 16's lazy check)?
 *
 * The second GET alone cannot tell: a lazy check would answer (nil) too.
 * What tells is INFO's expired_keys, read before that GET — the sweep has
 * already counted the key although nobody read it.
 */

require __DIR__ . '/bootstrap.php';

$client = exampleClient();

$expiredKeys = static function () use ($client): int {
    preg_match('/^expired_keys:(\d+)/m', $client->info(), $match);

    return (int) $match[1];
};

echo "SET session abc EX 2\n";
$client->set('session', 'abc', ttlSeconds: 2);
echo "-> OK\n\n";

echo "GET session (immediately)\n";
echo '-> ' . ($client->get('session') ?? '(nil)') . "\n\n";

$before = $expiredKeys();
echo "Waiting 3 seconds for the TTL to pass...\n\n";
sleep(3);

// INFO is cumulative since the server started, so the difference is what
// expired during the wait.
echo "INFO expired_keys during the wait, with nobody reading the key\n";
echo '-> ' . ($expiredKeys() - $before) . "\n\n";

echo "GET session (after the TTL)\n";
echo '-> ' . ($client->get('session') ?? '(nil)') . "\n";

$client->close();

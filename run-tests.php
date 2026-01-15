#!/usr/bin/env php
<?php

/**
 * Test Runner Script
 * Provides convenient shortcuts for running tests
 */

$command = $argv[1] ?? 'all';

$commands = [
    'all' => 'vendor/bin/phpunit',
    'unit' => 'vendor/bin/phpunit --testsuite "Unit Tests"',
    'integration' => 'vendor/bin/phpunit tests/DatabaseIntegrationTest.php tests/EdgeCaseTest.php',
    'security' => 'vendor/bin/phpunit tests/SecurityTest.php',
    'coverage' => 'vendor/bin/phpunit --coverage-html coverage',
    'watch' => 'watch -n 2 vendor/bin/phpunit',
];

if (!isset($commands[$command])) {
    echo "IPFS Master Test Runner\n\n";
    echo "Usage: php run-tests.php [command]\n\n";
    echo "Available commands:\n";
    foreach ($commands as $cmd => $description) {
        echo "  {$cmd}\n";
    }
    exit(1);
}

echo "Running: {$commands[$command]}\n";
passthru($commands[$command], $returnCode);
exit($returnCode);

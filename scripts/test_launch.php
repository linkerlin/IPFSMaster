#!/usr/bin/env php
<?php
/**
 * Test background process launching
 */

require_once __DIR__ . '/../src/Models/Database.php';

echo "Testing background process launch...\n\n";

$db = Database::getInstance();

// Create test task
$stmt = $db->prepare("INSERT INTO background_tasks (task_type, status, total) VALUES (:type, :status, :total)");
$stmt->bindValue(':type', 'test_launch', SQLITE3_TEXT);
$stmt->bindValue(':status', 'pending', SQLITE3_TEXT);
$stmt->bindValue(':total', 10, SQLITE3_INTEGER);
$stmt->execute();
$taskId = $db->lastInsertRowID();

echo "Created task ID: $taskId\n";

// Launch background process
$scriptPath = __DIR__ . '/sync_pins.php';
$phpBinary = PHP_BINARY;

echo "PHP Binary: $phpBinary\n";
echo "Script Path: $scriptPath\n";

if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
    echo "Platform: Windows\n";
    
    // Method 1: Simple start command
    $cmd = "start /B \"\" \"$phpBinary\" \"$scriptPath\" $taskId";
    echo "Command: $cmd\n\n";
    
    echo "Executing...\n";
    pclose(popen($cmd, 'r'));
    
} else {
    echo "Platform: Unix/Linux\n";
    $cmd = "nohup $phpBinary $scriptPath $taskId > /dev/null 2>&1 &";
    echo "Command: $cmd\n\n";
    exec($cmd);
}

echo "\nWaiting 2 seconds...\n";
sleep(2);

// Check task status
$stmt = $db->prepare("SELECT * FROM background_tasks WHERE id = :id");
$stmt->bindValue(':id', $taskId, SQLITE3_INTEGER);
$result = $stmt->execute();
$task = $result->fetchArray(SQLITE3_ASSOC);

echo "\nTask Status:\n";
echo "  ID: {$task['id']}\n";
echo "  Type: {$task['task_type']}\n";
echo "  Status: {$task['status']}\n";
echo "  Progress: {$task['progress']}/{$task['total']}\n";
echo "  PID: {$task['pid']}\n";
echo "  Started: {$task['started_at']}\n";

if ($task['status'] === 'pending') {
    echo "\n❌ Background process DID NOT start!\n";
    echo "   The task is still in 'pending' status.\n";
    echo "   Try running the script manually:\n";
    echo "   php scripts/sync_pins.php $taskId\n";
} elseif ($task['status'] === 'running' || $task['status'] === 'completed') {
    echo "\n✅ Background process started successfully!\n";
} else {
    echo "\n⚠️  Unexpected status: {$task['status']}\n";
    if ($task['error_message']) {
        echo "   Error: {$task['error_message']}\n";
    }
}

// Wait a bit more for completion
if ($task['status'] === 'running') {
    echo "\nWaiting for completion...\n";
    for ($i = 0; $i < 10; $i++) {
        sleep(1);
        $stmt = $db->prepare("SELECT status, progress, total FROM background_tasks WHERE id = :id");
        $stmt->bindValue(':id', $taskId, SQLITE3_INTEGER);
        $result = $stmt->execute();
        $task = $result->fetchArray(SQLITE3_ASSOC);
        
        echo "  Progress: {$task['progress']}/{$task['total']} ({$task['status']})\n";
        
        if ($task['status'] === 'completed' || $task['status'] === 'failed') {
            break;
        }
    }
}

echo "\n";

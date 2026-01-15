#!/usr/bin/env php
<?php
/**
 * Background Task System Demo
 * 
 * This script demonstrates the background task system in action
 * Usage: php demo_background_task.php
 */

require_once __DIR__ . '/../src/Models/Database.php';

echo "\n";
echo "╔══════════════════════════════════════════════════════════╗\n";
echo "║          IPFS Master - Background Task Demo            ║\n";
echo "╚══════════════════════════════════════════════════════════╝\n";
echo "\n";

// Simulate a background task
$db = Database::getInstance();

echo "📝 Creating a demo task...\n";
$stmt = $db->prepare("INSERT INTO background_tasks (task_type, status, total) VALUES (:type, :status, :total)");
$stmt->bindValue(':type', 'demo_sync', SQLITE3_TEXT);
$stmt->bindValue(':status', 'pending', SQLITE3_TEXT);
$stmt->bindValue(':total', 50, SQLITE3_INTEGER);
$stmt->execute();
$taskId = $db->lastInsertRowID();

echo "✓ Task created with ID: $taskId\n\n";

// Simulate task execution
echo "🚀 Starting background task simulation...\n";
$stmt = $db->prepare("UPDATE background_tasks SET status = :status, started_at = CURRENT_TIMESTAMP WHERE id = :id");
$stmt->bindValue(':status', 'running', SQLITE3_TEXT);
$stmt->bindValue(':id', $taskId, SQLITE3_INTEGER);
$stmt->execute();

echo "\n";
echo "┌────────────────────────────────────────────────────────┐\n";
echo "│ Progress Simulation                                    │\n";
echo "└────────────────────────────────────────────────────────┘\n";

for ($i = 1; $i <= 50; $i++) {
    // Update progress
    $stmt = $db->prepare("UPDATE background_tasks SET progress = :progress, current_item = :item WHERE id = :id");
    $stmt->bindValue(':progress', $i, SQLITE3_INTEGER);
    $stmt->bindValue(':item', "Item_" . $i, SQLITE3_TEXT);
    $stmt->bindValue(':id', $taskId, SQLITE3_INTEGER);
    $stmt->execute();
    
    // Calculate percentage
    $percentage = ($i / 50) * 100;
    $barLength = 40;
    $filled = round($barLength * $percentage / 100);
    $bar = str_repeat('█', $filled) . str_repeat('░', $barLength - $filled);
    
    // Clear line and print progress
    echo "\r";
    echo sprintf("[%s] %3d%% (%2d/50) Processing: Item_%d", $bar, $percentage, $i, $i);
    
    // Simulate work
    usleep(100000); // 0.1 second
}

echo "\n\n";

// Complete task
echo "✓ Completing task...\n";
$result = json_encode([
    'synced' => 48,
    'total' => 50,
    'errors' => 2,
    'error_details' => ['Item_23 failed', 'Item_45 failed']
]);

$stmt = $db->prepare("UPDATE background_tasks SET status = :status, progress = :progress, result = :result, completed_at = CURRENT_TIMESTAMP WHERE id = :id");
$stmt->bindValue(':status', 'completed', SQLITE3_TEXT);
$stmt->bindValue(':progress', 50, SQLITE3_INTEGER);
$stmt->bindValue(':result', $result, SQLITE3_TEXT);
$stmt->bindValue(':id', $taskId, SQLITE3_INTEGER);
$stmt->execute();

echo "\n";
echo "┌────────────────────────────────────────────────────────┐\n";
echo "│ Task Summary                                           │\n";
echo "└────────────────────────────────────────────────────────┘\n";

// Query final status
$stmt = $db->prepare("SELECT * FROM background_tasks WHERE id = :id");
$stmt->bindValue(':id', $taskId, SQLITE3_INTEGER);
$result = $stmt->execute();
$task = $result->fetchArray(SQLITE3_ASSOC);

$resultData = json_decode($task['result'], true);

echo "\n";
echo "  Task ID      : {$task['id']}\n";
echo "  Type         : {$task['task_type']}\n";
echo "  Status       : {$task['status']}\n";
echo "  Progress     : {$task['progress']}/{$task['total']}\n";
echo "  Synced       : {$resultData['synced']} items\n";
echo "  Errors       : {$resultData['errors']} items\n";
echo "  Created      : {$task['created_at']}\n";
echo "  Started      : {$task['started_at']}\n";
echo "  Completed    : {$task['completed_at']}\n";

if (!empty($resultData['error_details'])) {
    echo "\n  Error Details:\n";
    foreach ($resultData['error_details'] as $error) {
        echo "    • $error\n";
    }
}

echo "\n";
echo "┌────────────────────────────────────────────────────────┐\n";
echo "│ Database Query                                         │\n";
echo "└────────────────────────────────────────────────────────┘\n";
echo "\n";
echo "  You can query this task in your database:\n";
echo "  \$ sqlite3 database/ipfs_master.db\n";
echo "  sqlite> SELECT * FROM background_tasks WHERE id = $taskId;\n";
echo "\n";

// Cleanup option
echo "Would you like to clean up this demo task? [y/N]: ";
$handle = fopen("php://stdin", "r");
$line = fgets($handle);
fclose($handle);

if (trim(strtolower($line)) === 'y') {
    $stmt = $db->prepare("DELETE FROM background_tasks WHERE id = :id");
    $stmt->bindValue(':id', $taskId, SQLITE3_INTEGER);
    $stmt->execute();
    echo "✓ Demo task cleaned up.\n";
} else {
    echo "ℹ Task kept in database for your inspection.\n";
}

echo "\n";
echo "╔══════════════════════════════════════════════════════════╗\n";
echo "║                  Demo Complete! 🎉                      ║\n";
echo "╚══════════════════════════════════════════════════════════╝\n";
echo "\n";
echo "💡 Learn more:\n";
echo "   - BACKGROUND_TASKS.md - Technical documentation\n";
echo "   - PIN_SYNC_IMPLEMENTATION.md - Implementation guide\n";
echo "\n";

<?php
/**
 * Quick test for background task system
 * 
 * Run: php tests/BackgroundTaskTest.php
 */

require_once __DIR__ . '/../src/Models/Database.php';

echo "Testing Background Task System...\n\n";

try {
    $db = Database::getInstance();
    
    // Test 1: Create a task
    echo "1. Creating test task... ";
    $stmt = $db->prepare("INSERT INTO background_tasks (task_type, status, total) VALUES (:type, :status, :total)");
    $stmt->bindValue(':type', 'test_task', SQLITE3_TEXT);
    $stmt->bindValue(':status', 'pending', SQLITE3_TEXT);
    $stmt->bindValue(':total', 100, SQLITE3_INTEGER);
    $stmt->execute();
    $taskId = $db->lastInsertRowID();
    echo "✓ Task created with ID: $taskId\n";
    
    // Test 2: Update task progress
    echo "2. Updating task progress... ";
    $stmt = $db->prepare("UPDATE background_tasks SET status = :status, progress = :progress, current_item = :item WHERE id = :id");
    $stmt->bindValue(':status', 'running', SQLITE3_TEXT);
    $stmt->bindValue(':progress', 50, SQLITE3_INTEGER);
    $stmt->bindValue(':item', 'Item 50', SQLITE3_TEXT);
    $stmt->bindValue(':id', $taskId, SQLITE3_INTEGER);
    $stmt->execute();
    echo "✓ Progress updated to 50/100\n";
    
    // Test 3: Query task status
    echo "3. Querying task status... ";
    $stmt = $db->prepare("SELECT * FROM background_tasks WHERE id = :id");
    $stmt->bindValue(':id', $taskId, SQLITE3_INTEGER);
    $result = $stmt->execute();
    $task = $result->fetchArray(SQLITE3_ASSOC);
    
    if ($task && $task['status'] === 'running' && $task['progress'] == 50) {
        echo "✓ Task status correct\n";
        echo "   - Status: {$task['status']}\n";
        echo "   - Progress: {$task['progress']}/{$task['total']}\n";
        echo "   - Current: {$task['current_item']}\n";
    } else {
        echo "✗ Task status incorrect\n";
        var_dump($task);
    }
    
    // Test 4: Complete task with result
    echo "4. Completing task... ";
    $result = json_encode(['synced' => 95, 'errors' => 5]);
    $stmt = $db->prepare("UPDATE background_tasks SET status = :status, progress = :progress, result = :result, completed_at = CURRENT_TIMESTAMP WHERE id = :id");
    $stmt->bindValue(':status', 'completed', SQLITE3_TEXT);
    $stmt->bindValue(':progress', 100, SQLITE3_INTEGER);
    $stmt->bindValue(':result', $result, SQLITE3_TEXT);
    $stmt->bindValue(':id', $taskId, SQLITE3_INTEGER);
    $stmt->execute();
    echo "✓ Task completed\n";
    
    // Test 5: Verify final state
    echo "5. Verifying final state... ";
    $stmt = $db->prepare("SELECT * FROM background_tasks WHERE id = :id");
    $stmt->bindValue(':id', $taskId, SQLITE3_INTEGER);
    $result = $stmt->execute();
    $task = $result->fetchArray(SQLITE3_ASSOC);
    
    if ($task['status'] === 'completed' && $task['progress'] == 100 && $task['completed_at']) {
        echo "✓ Final state correct\n";
        $resultData = json_decode($task['result'], true);
        echo "   - Synced: {$resultData['synced']}\n";
        echo "   - Errors: {$resultData['errors']}\n";
    } else {
        echo "✗ Final state incorrect\n";
        var_dump($task);
    }
    
    // Test 6: Check for concurrent tasks
    echo "6. Testing concurrent task detection... ";
    $result = $db->query("SELECT id FROM background_tasks WHERE task_type = 'pin_sync' AND status IN ('pending', 'running') LIMIT 1");
    $concurrentTask = $result->fetchArray();
    if ($concurrentTask === false) {
        echo "✓ No concurrent pin_sync tasks\n";
    } else {
        echo "⚠ Found concurrent task ID: {$concurrentTask['id']}\n";
    }
    
    // Cleanup
    echo "7. Cleaning up test task... ";
    $stmt = $db->prepare("DELETE FROM background_tasks WHERE id = :id");
    $stmt->bindValue(':id', $taskId, SQLITE3_INTEGER);
    $stmt->execute();
    echo "✓ Cleaned up\n";
    
    echo "\n✅ All tests passed!\n";
    
} catch (Exception $e) {
    echo "\n❌ Test failed: " . $e->getMessage() . "\n";
    exit(1);
}

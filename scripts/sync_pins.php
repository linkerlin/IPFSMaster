#!/usr/bin/env php
<?php
/**
 * Background Pin Sync Script
 * 
 * This script runs as a separate process to sync pins from IPFS node
 * Usage: php sync_pins.php <task_id>
 */

// Prevent timeout
set_time_limit(0);
ini_set('max_execution_time', 0);

// Load dependencies
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/Models/Database.php';
require_once __DIR__ . '/../src/Models/IPFSClient.php';

function isWindows() {
    return strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
}

function exportPinsToFile($filePath) {
    $dir = dirname($filePath);
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }

    $escapedFile = escapeshellarg($filePath);
    if (isWindows()) {
        $cmd = 'cmd /c "ipfs pin ls --type=recursive --quiet > ' . $escapedFile . ' 2>&1"';
    } else {
        $cmd = 'sh -c "ipfs pin ls --type=recursive --quiet > ' . $escapedFile . ' 2>&1"';
    }

    $output = [];
    $code = 0;
    exec($cmd, $output, $code);
    return [$code, implode("\n", $output)];
}

function countNonEmptyLines($filePath) {
    $count = 0;
    $handle = fopen($filePath, 'r');
    if (!$handle) {
        return 0;
    }
    while (($line = fgets($handle)) !== false) {
        if (trim($line) !== '') {
            $count++;
        }
    }
    fclose($handle);
    return $count;
}

// Get task ID from command line
$taskId = isset($argv[1]) ? (int)$argv[1] : 0;

if ($taskId === 0) {
    echo "Error: Task ID is required\n";
    exit(1);
}

try {
    $db = Database::getInstance();
    $ipfs = new IPFSClient();
    
    // Update task status to running
    $stmt = $db->prepare("UPDATE background_tasks SET status = :status, started_at = CURRENT_TIMESTAMP, pid = :pid WHERE id = :id");
    $stmt->bindValue(':status', 'running', SQLITE3_TEXT);
    $stmt->bindValue(':pid', getmypid(), SQLITE3_INTEGER);
    $stmt->bindValue(':id', $taskId, SQLITE3_INTEGER);
    $stmt->execute();
    
    // Export pins to file using ipfs CLI
    $pinListPath = __DIR__ . '/../database/pins_cids.txt';
    echo "[Task $taskId] Exporting pins to file...\n";
    [$exportCode, $exportOutput] = exportPinsToFile($pinListPath);

    if ($exportCode !== 0) {
        $stmt = $db->prepare("UPDATE background_tasks SET status = :status, error_message = :error, completed_at = CURRENT_TIMESTAMP WHERE id = :id");
        $stmt->bindValue(':status', 'failed', SQLITE3_TEXT);
        $stmt->bindValue(':error', "ipfs pin ls failed: " . trim($exportOutput), SQLITE3_TEXT);
        $stmt->bindValue(':id', $taskId, SQLITE3_INTEGER);
        $stmt->execute();

        echo "[Task $taskId] Export failed: $exportOutput\n";
        exit(1);
    }

    $totalPins = countNonEmptyLines($pinListPath);
    if ($totalPins === 0) {
        $stmt = $db->prepare("UPDATE background_tasks SET status = :status, total = 0, progress = 0, completed_at = CURRENT_TIMESTAMP, result = :result WHERE id = :id");
        $stmt->bindValue(':status', 'completed', SQLITE3_TEXT);
        $stmt->bindValue(':result', json_encode(['synced' => 0, 'message' => 'No pins found']), SQLITE3_TEXT);
        $stmt->bindValue(':id', $taskId, SQLITE3_INTEGER);
        $stmt->execute();

        echo "[Task $taskId] No pins found. Task completed.\n";
        exit(0);
    }

    echo "[Task $taskId] Found $totalPins pins to sync\n";

    // Update total count
    $stmt = $db->prepare("UPDATE background_tasks SET total = :total WHERE id = :id");
    $stmt->bindValue(':total', $totalPins, SQLITE3_INTEGER);
    $stmt->bindValue(':id', $taskId, SQLITE3_INTEGER);
    $stmt->execute();
    
    // Clear existing pins
    $db->exec("DELETE FROM pins");
    echo "[Task $taskId] Cleared existing pins from database\n";
    
    // Process each pin
    $processed = 0;
    $errors = [];
    
    $file = new SplFileObject($pinListPath, 'r');
    while (!$file->eof()) {
        $cid = trim($file->fgets());
        if ($cid === '') {
            continue;
        }
        try {
            // Update progress
            $stmt = $db->prepare("UPDATE background_tasks SET progress = :progress, current_item = :item WHERE id = :id");
            $stmt->bindValue(':progress', $processed + 1, SQLITE3_INTEGER);
            $stmt->bindValue(':item', substr($cid, 0, 20) . '...', SQLITE3_TEXT);
            $stmt->bindValue(':id', $taskId, SQLITE3_INTEGER);
            $stmt->execute();
            
            // Try to get object stats (with timeout protection)
            $size = 0;
            $metadata = null;
            try {
                $stat = $ipfs->objectStat($cid);
                $size = $stat['CumulativeSize'] ?? 0;
                $metadata = json_encode($stat);
            } catch (Exception $e) {
                // Ignore stat errors, just log them
                $errors[] = "Failed to get stats for $cid: " . $e->getMessage();
            }
            
            // Insert into database
            $stmt = $db->prepare("INSERT INTO pins (cid, type, size, metadata) VALUES (:cid, :type, :size, :metadata)");
            $stmt->bindValue(':cid', $cid, SQLITE3_TEXT);
            $stmt->bindValue(':type', 'recursive', SQLITE3_TEXT);
            $stmt->bindValue(':size', $size, SQLITE3_INTEGER);
            $stmt->bindValue(':metadata', $metadata, SQLITE3_TEXT);
            $stmt->execute();
            
            $processed++;
            
            // Log progress every 10 items
            if ($processed % 10 === 0) {
                echo "[Task $taskId] Progress: $processed / $totalPins\n";
            }
            
        } catch (Exception $e) {
            $errors[] = "Error processing $cid: " . $e->getMessage();
            echo "[Task $taskId] Error: " . $e->getMessage() . "\n";
        }
    }
    
    // Mark task as completed
    $result = [
        'synced' => $processed,
        'total' => $totalPins,
        'errors' => count($errors),
        'error_details' => array_slice($errors, 0, 10) // Keep only first 10 errors
    ];
    
    $stmt = $db->prepare("UPDATE background_tasks SET status = :status, progress = :progress, completed_at = CURRENT_TIMESTAMP, result = :result WHERE id = :id");
    $stmt->bindValue(':status', 'completed', SQLITE3_TEXT);
    $stmt->bindValue(':progress', $totalPins, SQLITE3_INTEGER);
    $stmt->bindValue(':result', json_encode($result), SQLITE3_TEXT);
    $stmt->bindValue(':id', $taskId, SQLITE3_INTEGER);
    $stmt->execute();
    
    echo "[Task $taskId] Sync completed: $processed pins synced\n";
    if (count($errors) > 0) {
        echo "[Task $taskId] Errors encountered: " . count($errors) . "\n";
    }
    
    exit(0);
    
} catch (Exception $e) {
    // Log error to database
    try {
        $db = Database::getInstance();
        $stmt = $db->prepare("UPDATE background_tasks SET status = :status, error_message = :error, completed_at = CURRENT_TIMESTAMP WHERE id = :id");
        $stmt->bindValue(':status', 'failed', SQLITE3_TEXT);
        $stmt->bindValue(':error', $e->getMessage(), SQLITE3_TEXT);
        $stmt->bindValue(':id', $taskId, SQLITE3_INTEGER);
        $stmt->execute();
    } catch (Exception $dbError) {
        echo "[Task $taskId] Failed to update error in database: " . $dbError->getMessage() . "\n";
    }
    
    echo "[Task $taskId] Fatal error: " . $e->getMessage() . "\n";
    exit(1);
}

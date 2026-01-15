#!/usr/bin/env php
<?php
/**
 * Background Worker Process
 * 
 * 从 SQLite 数据库中获取待执行的任务并处理
 * Usage: php worker.php
 */

// 防止超时
set_time_limit(0);
ini_set('max_execution_time', 0);

// 加载依赖
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/src/Models/Database.php';
require_once __DIR__ . '/src/Models/IPFSClient.php';

// 获取 worker ID（支持多进程）
$workerId = isset($argv[1]) ? (int)$argv[1] : 1;

echo "[Worker $workerId] Starting background worker...\n";
echo "[Worker $workerId] Process ID: " . getmypid() . "\n";

// 任务处理函数
function processTask($taskId, $workerId) {
    try {
        $db = Database::getInstance();
        $ipfs = new IPFSClient();
        
        // 获取任务详情
        $stmt = $db->prepare("SELECT * FROM background_tasks WHERE id = :id");
        $stmt->bindValue(':id', $taskId, SQLITE3_INTEGER);
        $result = $stmt->execute();
        $task = $result->fetchArray(SQLITE3_ASSOC);
        
        if (!$task) {
            echo "[Worker $workerId] Task $taskId not found\n";
            return;
        }
        
        echo "[Worker $workerId] Processing task $taskId: {$task['task_type']}\n";
        
        // 更新任务状态为运行中
        $stmt = $db->prepare("UPDATE background_tasks SET status = :status, started_at = CURRENT_TIMESTAMP, pid = :pid WHERE id = :id");
        $stmt->bindValue(':status', 'running', SQLITE3_TEXT);
        $stmt->bindValue(':pid', getmypid(), SQLITE3_INTEGER);
        $stmt->bindValue(':id', $taskId, SQLITE3_INTEGER);
        $stmt->execute();
        
        // 根据任务类型执行不同的处理
        switch ($task['task_type']) {
            case 'pin_sync':
                processPinSync($taskId, $workerId, $db, $ipfs);
                break;
                
            default:
                throw new Exception("Unknown task type: {$task['task_type']}");
        }
        
    } catch (Exception $e) {
        echo "[Worker $workerId] Task $taskId failed: " . $e->getMessage() . "\n";
        
        // 更新任务为失败状态
        $db = Database::getInstance();
        $stmt = $db->prepare("UPDATE background_tasks SET status = :status, error_message = :error, completed_at = CURRENT_TIMESTAMP WHERE id = :id");
        $stmt->bindValue(':status', 'failed', SQLITE3_TEXT);
        $stmt->bindValue(':error', $e->getMessage(), SQLITE3_TEXT);
        $stmt->bindValue(':id', $taskId, SQLITE3_INTEGER);
        $stmt->execute();
    }
}

function processPinSync($taskId, $workerId, $db, $ipfs) {
    // 导出 pins 到文件
    $pinListPath = __DIR__ . '/database/pins_cids.txt';
    echo "[Worker $workerId] [Task $taskId] Exporting pins to file...\n";
    
    $dir = dirname($pinListPath);
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    
    // 使用 ipfs CLI 导出
    $isWindows = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
    $escapedFile = escapeshellarg($pinListPath);
    
    if ($isWindows) {
        $cmd = 'cmd /c "ipfs pin ls --type=recursive --quiet > ' . $escapedFile . ' 2>&1"';
    } else {
        $cmd = 'sh -c "ipfs pin ls --type=recursive --quiet > ' . $escapedFile . ' 2>&1"';
    }
    
    $output = [];
    $code = 0;
    exec($cmd, $output, $code);
    
    if ($code !== 0) {
        throw new Exception("ipfs pin ls failed: " . implode("\n", $output));
    }
    
    // 统计总数
    $totalPins = 0;
    $handle = fopen($pinListPath, 'r');
    if ($handle) {
        while (($line = fgets($handle)) !== false) {
            if (trim($line) !== '') {
                $totalPins++;
            }
        }
        fclose($handle);
    }
    
    if ($totalPins === 0) {
        $stmt = $db->prepare("UPDATE background_tasks SET status = :status, total = 0, progress = 0, completed_at = CURRENT_TIMESTAMP, result = :result WHERE id = :id");
        $stmt->bindValue(':status', 'completed', SQLITE3_TEXT);
        $stmt->bindValue(':result', json_encode(['synced' => 0, 'message' => 'No pins found']), SQLITE3_TEXT);
        $stmt->bindValue(':id', $taskId, SQLITE3_INTEGER);
        $stmt->execute();
        
        echo "[Worker $workerId] [Task $taskId] No pins found\n";
        return;
    }
    
    echo "[Worker $workerId] [Task $taskId] Found $totalPins pins to sync\n";
    
    // 更新总数
    $stmt = $db->prepare("UPDATE background_tasks SET total = :total WHERE id = :id");
    $stmt->bindValue(':total', $totalPins, SQLITE3_INTEGER);
    $stmt->bindValue(':id', $taskId, SQLITE3_INTEGER);
    $stmt->execute();
    
    // 清空现有 pins
    $db->exec("DELETE FROM pins");
    echo "[Worker $workerId] [Task $taskId] Cleared existing pins from database\n";
    
    // 逐行处理
    $processed = 0;
    $errors = [];
    
    $file = new SplFileObject($pinListPath, 'r');
    while (!$file->eof()) {
        $cid = trim($file->fgets());
        if ($cid === '') {
            continue;
        }
        
        try {
            // 尝试获取对象统计信息
            $size = 0;
            $metadata = null;
            try {
                $stat = $ipfs->objectStat($cid);
                $size = $stat['CumulativeSize'] ?? 0;
                $metadata = json_encode($stat);
            } catch (Exception $e) {
                // 忽略统计错误
                $errors[] = "Failed to get stats for $cid: " . $e->getMessage();
            }
            
            // 插入数据库
            $stmt = $db->prepare("INSERT OR REPLACE INTO pins (cid, type, size, metadata) VALUES (:cid, :type, :size, :metadata)");
            $stmt->bindValue(':cid', $cid, SQLITE3_TEXT);
            $stmt->bindValue(':type', 'recursive', SQLITE3_TEXT);
            $stmt->bindValue(':size', $size, SQLITE3_INTEGER);
            $stmt->bindValue(':metadata', $metadata, SQLITE3_TEXT);
            $stmt->execute();
            
            $processed++;
            
            // 每处理 100 个更新一次进度（减少数据库锁定）
            if ($processed % 100 === 0 || $processed === $totalPins) {
                $stmt = $db->prepare("UPDATE background_tasks SET progress = :progress, current_item = :item WHERE id = :id");
                $stmt->bindValue(':progress', $processed, SQLITE3_INTEGER);
                $stmt->bindValue(':item', "已处理 $processed / $totalPins", SQLITE3_TEXT);
                $stmt->bindValue(':id', $taskId, SQLITE3_INTEGER);
                $stmt->execute();
                echo "[Worker $workerId] [Task $taskId] Progress: $processed / $totalPins\n";
            }
            
        } catch (Exception $e) {
            $errors[] = "Error processing $cid: " . $e->getMessage();
            echo "[Worker $workerId] [Task $taskId] Error: " . $e->getMessage() . "\n";
        }
    }
    
    // 标记任务完成
    $result = [
        'synced' => $processed,
        'total' => $totalPins,
        'errors' => count($errors),
        'error_details' => array_slice($errors, 0, 10)
    ];
    
    $stmt = $db->prepare("UPDATE background_tasks SET status = :status, progress = :progress, completed_at = CURRENT_TIMESTAMP, result = :result WHERE id = :id");
    $stmt->bindValue(':status', 'completed', SQLITE3_TEXT);
    $stmt->bindValue(':progress', $totalPins, SQLITE3_INTEGER);
    $stmt->bindValue(':result', json_encode($result), SQLITE3_TEXT);
    $stmt->bindValue(':id', $taskId, SQLITE3_INTEGER);
    $stmt->execute();
    
    echo "[Worker $workerId] [Task $taskId] Sync completed: $processed pins synced\n";
    if (count($errors) > 0) {
        echo "[Worker $workerId] [Task $taskId] Errors encountered: " . count($errors) . "\n";
    }
}

// 主循环
$db = Database::getInstance();
$lastCheck = 0;
$checkInterval = 2; // 每 2 秒检查一次新任务

echo "[Worker $workerId] Waiting for tasks...\n";

while (true) {
    try {
        // 查找待处理的任务
        $result = $db->query("SELECT id FROM background_tasks WHERE status = 'pending' ORDER BY created_at ASC LIMIT 1");
        $task = $result->fetchArray(SQLITE3_ASSOC);
        
        if ($task) {
            processTask($task['id'], $workerId);
        } else {
            // 没有任务时休眠
            sleep($checkInterval);
            
            // 每 30 秒输出一次心跳
            if (time() - $lastCheck > 30) {
                echo "[Worker $workerId] Heartbeat - waiting for tasks...\n";
                $lastCheck = time();
            }
        }
        
    } catch (Exception $e) {
        echo "[Worker $workerId] Error in main loop: " . $e->getMessage() . "\n";
        sleep(5); // 发生错误后等待 5 秒再继续
    }
}

<?php

class PinController extends Controller {
    
    public function index() {
        $db = Database::getInstance();
        $db->exec("UPDATE background_tasks SET status = 'failed', error_message = '后台进程长时间未响应', completed_at = CURRENT_TIMESTAMP WHERE task_type = 'pin_sync' AND status IN ('pending', 'running') AND created_at < datetime('now', '-10 minutes')");
        $result = $db->query("SELECT * FROM pins ORDER BY pinned_at DESC");
        
        $pins = [];
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $pins[] = $row;
        }

        $taskStmt = $db->prepare("SELECT * FROM background_tasks WHERE task_type = 'pin_sync' AND status IN ('pending', 'running') ORDER BY created_at DESC LIMIT 1");
        $taskResult = $taskStmt->execute();
        $syncTask = $taskResult->fetchArray(SQLITE3_ASSOC) ?: null;

        $this->render('pins', [
            'pins' => $pins,
            'syncTask' => $syncTask
        ]);
    }

    public function table() {
        $db = Database::getInstance();
        $result = $db->query("SELECT * FROM pins ORDER BY pinned_at DESC");

        $pins = [];
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $pins[] = $row;
        }

        $this->renderPartial('partials/pins_table', [
            'pins' => $pins
        ]);
    }
    
    public function add() {
        if ($this->isPost()) {
            $cid = $this->getPost('cid');
            $name = $this->getPost('name', '');
            
            try {
                $ipfs = new IPFSClient();
                $db = Database::getInstance();
                $recursivePin = $db->getSetting('recursive_pin', '1') === '1';
                
                // Recursively pin the CID
                if ($recursivePin) {
                    $ipfs->recursivePin($cid);
                } else {
                    $ipfs->pinAdd($cid, false);
                }
                
                // Get object stats
                $stat = null;
                try {
                    $stat = $ipfs->objectStat($cid);
                } catch (Exception $e) {
                    // Ignore if stat fails
                }
                
                // Save to database
                $stmt = $db->prepare("INSERT OR REPLACE INTO pins (cid, name, size, type, metadata) VALUES (:cid, :name, :size, :type, :metadata)");
                $stmt->bindValue(':cid', $cid, SQLITE3_TEXT);
                $stmt->bindValue(':name', $name, SQLITE3_TEXT);
                $stmt->bindValue(':size', $stat ? $stat['CumulativeSize'] : 0, SQLITE3_INTEGER);
                $stmt->bindValue(':type', $recursivePin ? 'recursive' : 'direct', SQLITE3_TEXT);
                $stmt->bindValue(':metadata', json_encode($stat), SQLITE3_TEXT);
                $stmt->execute();

                $stmt = $db->prepare("INSERT INTO import_history (cid, source_path, import_type, status, completed_at) VALUES (:cid, :source, :type, :status, CURRENT_TIMESTAMP)");
                $stmt->bindValue(':cid', $cid, SQLITE3_TEXT);
                $stmt->bindValue(':source', $name ?: $cid, SQLITE3_TEXT);
                $stmt->bindValue(':type', 'cid', SQLITE3_TEXT);
                $stmt->bindValue(':status', 'completed', SQLITE3_TEXT);
                $stmt->execute();
                
                if ($this->isHtmx()) {
                    $this->json(['success' => true, 'message' => 'CID pinned successfully']);
                } else {
                    $this->redirect('/pins');
                }
            } catch (Exception $e) {
                if ($this->isHtmx()) {
                    $this->json(['success' => false, 'error' => $e->getMessage()], 400);
                } else {
                    $this->redirect('/pins?error=' . urlencode($e->getMessage()));
                }
            }
        } else {
            $this->redirect('/pins');
        }
    }
    
    public function remove() {
        if ($this->isPost()) {
            $cid = $this->getPost('cid');
            
            try {
                $ipfs = new IPFSClient();
                $ipfs->pinRm($cid);
                
                // Remove from database
                $db = Database::getInstance();
                $stmt = $db->prepare("DELETE FROM pins WHERE cid = :cid");
                $stmt->bindValue(':cid', $cid, SQLITE3_TEXT);
                $stmt->execute();
                
                if ($this->isHtmx()) {
                    $this->htmxTrigger('toast', ['message' => 'Pin已成功移除', 'type' => 'success']);
                    $this->renderPartial('partials/pins_table', [
                        'pins' => $this->fetchPins()
                    ]);
                } else {
                    $this->redirect('/pins');
                }
            } catch (Exception $e) {
                if ($this->isHtmx()) {
                    $this->htmxTrigger('toast', ['message' => $e->getMessage(), 'type' => 'danger']);
                    $this->renderPartial('partials/pins_table', [
                        'pins' => $this->fetchPins()
                    ]);
                } else {
                    $this->redirect('/pins?error=' . urlencode($e->getMessage()));
                }
            }
        } else {
            $this->redirect('/pins');
        }
    }
    
    public function sync() {
        try {
            $db = Database::getInstance();

            $db->exec("UPDATE background_tasks SET status = 'failed', error_message = '后台进程未启动或已退出', completed_at = CURRENT_TIMESTAMP WHERE task_type = 'pin_sync' AND status IN ('pending', 'running') AND created_at < datetime('now', '-2 minutes')");
            
            // Check if there's already a running sync task
            $result = $db->query("SELECT id FROM background_tasks WHERE task_type = 'pin_sync' AND status IN ('pending', 'running') LIMIT 1");
            if ($result->fetchArray()) {
                if ($this->isHtmx()) {
                    $this->json(['success' => false, 'error' => '已有同步任务正在运行中']);
                    return;
                }
                $this->redirect('/pins?error=' . urlencode('已有同步任务正在运行中'));
                return;
            }
            
            // Create background task - worker.php will pick it up automatically
            $stmt = $db->prepare("INSERT INTO background_tasks (task_type, status) VALUES (:type, :status)");
            $stmt->bindValue(':type', 'pin_sync', SQLITE3_TEXT);
            $stmt->bindValue(':status', 'pending', SQLITE3_TEXT);
            $stmt->execute();
            $taskId = $db->lastInsertRowID();
            
            if ($this->isHtmx()) {
                $this->json(['success' => true, 'taskId' => $taskId, 'message' => '后台同步任务已启动，worker进程正在处理']);
            } else {
                $this->redirect('/pins?sync_started=' . $taskId);
            }
        } catch (Exception $e) {
            if ($this->isHtmx()) {
                $this->json(['success' => false, 'error' => $e->getMessage()]);
            } else {
                $this->redirect('/pins?error=' . urlencode($e->getMessage()));
            }
        }
    }
    
    public function taskStatus() {
        $taskId = $this->getGet('id');
        if (!$taskId) {
            $this->json(['error' => 'Task ID is required'], 400);
            return;
        }
        
        try {
            $db = Database::getInstance();
            $stmt = $db->prepare("SELECT * FROM background_tasks WHERE id = :id");
            $stmt->bindValue(':id', $taskId, SQLITE3_INTEGER);
            $result = $stmt->execute();
            $task = $result->fetchArray(SQLITE3_ASSOC);
            
            if (!$task) {
                $this->json(['error' => 'Task not found'], 404);
                return;
            }

            if ($task['status'] === 'pending' && !empty($task['created_at'])) {
                $createdAt = strtotime($task['created_at']);
                if ($createdAt !== false && (time() - $createdAt) > 30) {
                    $failStmt = $db->prepare("UPDATE background_tasks SET status = :status, error_message = :error, completed_at = CURRENT_TIMESTAMP WHERE id = :id");
                    $failStmt->bindValue(':status', 'failed', SQLITE3_TEXT);
                    $failStmt->bindValue(':error', '后台进程未启动或已退出', SQLITE3_TEXT);
                    $failStmt->bindValue(':id', $taskId, SQLITE3_INTEGER);
                    $failStmt->execute();

                    $task['status'] = 'failed';
                    $task['error_message'] = '后台进程未启动或已退出';
                }
            }
            
            // Parse result JSON if exists
            if ($task['result']) {
                $task['result'] = json_decode($task['result'], true);
            }
            
            // Calculate percentage
            $percentage = 0;
            if ($task['total'] > 0) {
                $percentage = round(($task['progress'] / $task['total']) * 100, 1);
            }
            
            $this->json([
                'id' => $task['id'],
                'type' => $task['task_type'],
                'status' => $task['status'],
                'progress' => (int)$task['progress'],
                'total' => (int)$task['total'],
                'percentage' => $percentage,
                'current_item' => $task['current_item'],
                'result' => $task['result'],
                'error' => $task['error_message'],
                'created_at' => $task['created_at'],
                'started_at' => $task['started_at'],
                'completed_at' => $task['completed_at']
            ]);
        } catch (Exception $e) {
            $this->json(['error' => $e->getMessage()], 500);
        }
    }

    private function fetchPins() {
        $db = Database::getInstance();
        $result = $db->query("SELECT * FROM pins ORDER BY pinned_at DESC");

        $pins = [];
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $pins[] = $row;
        }

        return $pins;
    }
}

<?php

class PinController extends Controller {
    
    public function index() {
        $db = Database::getInstance();
        $result = $db->query("SELECT * FROM pins ORDER BY pinned_at DESC");
        
        $pins = [];
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $pins[] = $row;
        }
        
        $this->render('pins', [
            'pins' => $pins
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
            $ipfs = new IPFSClient();
            $pins = $ipfs->pinLs('recursive');
            
            $db = Database::getInstance();
            
            // Clear existing pins
            $db->exec("DELETE FROM pins");
            
            // Add all pins from IPFS
            if (isset($pins['Keys'])) {
                foreach ($pins['Keys'] as $cid => $info) {
                    $stmt = $db->prepare("INSERT INTO pins (cid, type) VALUES (:cid, :type)");
                    $stmt->bindValue(':cid', $cid, SQLITE3_TEXT);
                    $stmt->bindValue(':type', $info['Type'], SQLITE3_TEXT);
                    $stmt->execute();
                }
            }
            
            $this->redirect('/pins');
        } catch (Exception $e) {
            $this->redirect('/pins?error=' . urlencode($e->getMessage()));
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

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
    
    public function add() {
        if ($this->isPost()) {
            $cid = $this->getPost('cid');
            $name = $this->getPost('name', '');
            
            try {
                $ipfs = new IPFSClient();
                
                // Recursively pin the CID
                $ipfs->recursivePin($cid);
                
                // Get object stats
                $stat = null;
                try {
                    $stat = $ipfs->objectStat($cid);
                } catch (Exception $e) {
                    // Ignore if stat fails
                }
                
                // Save to database
                $db = Database::getInstance();
                $stmt = $db->prepare("INSERT OR REPLACE INTO pins (cid, name, size, type, metadata) VALUES (:cid, :name, :size, :type, :metadata)");
                $stmt->bindValue(':cid', $cid, SQLITE3_TEXT);
                $stmt->bindValue(':name', $name, SQLITE3_TEXT);
                $stmt->bindValue(':size', $stat ? $stat['CumulativeSize'] : 0, SQLITE3_INTEGER);
                $stmt->bindValue(':type', 'recursive', SQLITE3_TEXT);
                $stmt->bindValue(':metadata', json_encode($stat), SQLITE3_TEXT);
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
                    $this->json(['success' => true, 'message' => 'Pin removed successfully']);
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
}

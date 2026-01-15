<?php

class UploadController extends Controller {
    
    public function index() {
        $this->render('upload', []);
    }
    
    public function file() {
        if ($this->isPost()) {
            if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
                $this->json(['success' => false, 'error' => 'No file uploaded or upload error'], 400);
                return;
            }
            
            try {
                $ipfs = new IPFSClient();
                $tmpFile = $_FILES['file']['tmp_name'];
                $filename = $_FILES['file']['name'];
                
                // Add file to IPFS
                $result = $ipfs->add($tmpFile, $filename);
                $cid = $result['Hash'];
                
                // The file is already pinned by default in add()
                
                // Save to database
                $db = Database::getInstance();
                $stmt = $db->prepare("INSERT OR REPLACE INTO pins (cid, name, size, type) VALUES (:cid, :name, :size, :type)");
                $stmt->bindValue(':cid', $cid, SQLITE3_TEXT);
                $stmt->bindValue(':name', $filename, SQLITE3_TEXT);
                $stmt->bindValue(':size', $result['Size'] ?? 0, SQLITE3_INTEGER);
                $stmt->bindValue(':type', 'recursive', SQLITE3_TEXT);
                $stmt->execute();
                
                // Add to history
                $stmt = $db->prepare("INSERT INTO import_history (cid, source_path, import_type, status, completed_at) VALUES (:cid, :source, :type, :status, CURRENT_TIMESTAMP)");
                $stmt->bindValue(':cid', $cid, SQLITE3_TEXT);
                $stmt->bindValue(':source', $filename, SQLITE3_TEXT);
                $stmt->bindValue(':type', 'file', SQLITE3_TEXT);
                $stmt->bindValue(':status', 'completed', SQLITE3_TEXT);
                $stmt->execute();
                
                $this->json([
                    'success' => true,
                    'cid' => $cid,
                    'name' => $filename,
                    'size' => $result['Size'] ?? 0,
                    'gateway_url' => $ipfs->getGatewayUrl($cid)
                ]);
            } catch (Exception $e) {
                $this->json(['success' => false, 'error' => $e->getMessage()], 500);
            }
        } else {
            $this->redirect('/upload');
        }
    }
    
    public function folder() {
        if ($this->isPost()) {
            $folderPath = $this->getPost('folder_path');
            
            if (empty($folderPath) || !is_dir($folderPath)) {
                $this->json(['success' => false, 'error' => 'Invalid folder path'], 400);
                return;
            }
            
            try {
                $ipfs = new IPFSClient();
                $results = $ipfs->addDirectory($folderPath);
                
                $db = Database::getInstance();
                
                foreach ($results as $result) {
                    if (isset($result['Hash'])) {
                        $cid = $result['Hash'];
                        $name = $result['Name'] ?? basename($folderPath);
                        
                        // Save to database
                        $stmt = $db->prepare("INSERT OR REPLACE INTO pins (cid, name, size, type) VALUES (:cid, :name, :size, :type)");
                        $stmt->bindValue(':cid', $cid, SQLITE3_TEXT);
                        $stmt->bindValue(':name', $name, SQLITE3_TEXT);
                        $stmt->bindValue(':size', $result['Size'] ?? 0, SQLITE3_INTEGER);
                        $stmt->bindValue(':type', 'recursive', SQLITE3_TEXT);
                        $stmt->execute();
                    }
                }
                
                $mainCid = end($results)['Hash'] ?? null;
                
                if ($mainCid) {
                    // Add to history
                    $stmt = $db->prepare("INSERT INTO import_history (cid, source_path, import_type, status, completed_at) VALUES (:cid, :source, :type, :status, CURRENT_TIMESTAMP)");
                    $stmt->bindValue(':cid', $mainCid, SQLITE3_TEXT);
                    $stmt->bindValue(':source', $folderPath, SQLITE3_TEXT);
                    $stmt->bindValue(':type', 'folder', SQLITE3_TEXT);
                    $stmt->bindValue(':status', 'completed', SQLITE3_TEXT);
                    $stmt->execute();
                }
                
                $this->json([
                    'success' => true,
                    'cid' => $mainCid,
                    'files_count' => count($results)
                ]);
            } catch (Exception $e) {
                $this->json(['success' => false, 'error' => $e->getMessage()], 500);
            }
        } else {
            $this->redirect('/upload');
        }
    }
}

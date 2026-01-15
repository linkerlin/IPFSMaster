<?php

class UploadController extends Controller {
    
    public function index() {
        $this->render('upload', []);
    }
    
    public function file() {
        if ($this->isPost()) {
            try {
                $ipfs = new IPFSClient();
                $db = Database::getInstance();
                $autoPin = $db->getSetting('auto_pin', '1') === '1';
                $recursivePin = $db->getSetting('recursive_pin', '1') === '1';
                $isFolderUpload = isset($_FILES['folder']) && is_array($_FILES['folder']['name']) && !empty($_FILES['folder']['name'][0]);
                $isFileUpload = isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK;

                if (!$isFolderUpload && !$isFileUpload) {
                    $this->json(['success' => false, 'error' => 'No file or folder uploaded'], 400);
                    return;
                }

                if ($isFolderUpload) {
                    $files = [];
                    $fileCount = count($_FILES['folder']['name']);
                    for ($i = 0; $i < $fileCount; $i++) {
                        if ($_FILES['folder']['error'][$i] !== UPLOAD_ERR_OK) {
                            continue;
                        }
                        $files[] = [
                            'tmp_name' => $_FILES['folder']['tmp_name'][$i],
                            'name' => $_FILES['folder']['name'][$i]
                        ];
                    }

                    if (empty($files)) {
                        throw new Exception('Folder upload failed: no valid files');
                    }

                    $firstPath = $files[0]['name'];
                    $rootName = explode('/', $firstPath)[0] ?? 'folder';

                    // Add files without auto-pin to avoid device issues
                    $results = $ipfs->addFiles($files, false, true);
                    if (empty($results)) {
                        throw new Exception('IPFS add failed');
                    }

                    $mainCid = end($results)['Hash'] ?? null;
                    if (!$mainCid) {
                        throw new Exception('IPFS add failed: missing root CID');
                    }

                    // Pin separately if needed
                    if ($autoPin) {
                        try {
                            if ($recursivePin) {
                                $ipfs->recursivePin($mainCid);
                            } else {
                                $ipfs->pinAdd($mainCid, false);
                            }
                        } catch (Exception $e) {
                            // Log pin error but don't fail the upload
                            error_log("Pin failed for folder $mainCid: " . $e->getMessage());
                        }

                        $stat = null;
                        try {
                            $stat = $ipfs->objectStat($mainCid);
                        } catch (Exception $e) {
                            $stat = null;
                        }

                        $stmt = $db->prepare("INSERT OR REPLACE INTO pins (cid, name, size, type) VALUES (:cid, :name, :size, :type)");
                        $stmt->bindValue(':cid', $mainCid, SQLITE3_TEXT);
                        $stmt->bindValue(':name', $rootName, SQLITE3_TEXT);
                        $stmt->bindValue(':size', $stat ? $stat['CumulativeSize'] : 0, SQLITE3_INTEGER);
                        $stmt->bindValue(':type', $recursivePin ? 'recursive' : 'direct', SQLITE3_TEXT);
                        $stmt->execute();
                    }

                    // Add to history
                    $stmt = $db->prepare("INSERT INTO import_history (cid, source_path, import_type, status, completed_at) VALUES (:cid, :source, :type, :status, CURRENT_TIMESTAMP)");
                    $stmt->bindValue(':cid', $mainCid, SQLITE3_TEXT);
                    $stmt->bindValue(':source', $rootName, SQLITE3_TEXT);
                    $stmt->bindValue(':type', 'folder', SQLITE3_TEXT);
                    $stmt->bindValue(':status', 'completed', SQLITE3_TEXT);
                    $stmt->execute();

                    $filesCount = max(0, count($results) - 1);

                    $this->json([
                        'success' => true,
                        'cid' => $mainCid,
                        'name' => $rootName,
                        'files_count' => $filesCount,
                        'gateway_url' => $ipfs->getGatewayUrl($mainCid, $rootName)
                    ]);
                    return;
                }

                $tmpFile = $_FILES['file']['tmp_name'];
                $filename = $_FILES['file']['name'];

                // Add file to IPFS (without auto-pin to avoid device issues)
                $result = $ipfs->add($tmpFile, $filename, false);
                if (!$result || !isset($result['Hash'])) {
                    throw new Exception('IPFS add failed');
                }
                $cid = $result['Hash'];

                // Pin separately if needed
                if ($autoPin) {
                    try {
                        if ($recursivePin) {
                            $ipfs->recursivePin($cid);
                        } else {
                            $ipfs->pinAdd($cid, false);
                        }
                    } catch (Exception $e) {
                        // Log pin error but don't fail the upload
                        error_log("Pin failed for $cid: " . $e->getMessage());
                    }

                    $stmt = $db->prepare("INSERT OR REPLACE INTO pins (cid, name, size, type) VALUES (:cid, :name, :size, :type)");
                    $stmt->bindValue(':cid', $cid, SQLITE3_TEXT);
                    $stmt->bindValue(':name', $filename, SQLITE3_TEXT);
                    $stmt->bindValue(':size', $result['Size'] ?? 0, SQLITE3_INTEGER);
                    $stmt->bindValue(':type', $recursivePin ? 'recursive' : 'direct', SQLITE3_TEXT);
                    $stmt->execute();
                }

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
                    'gateway_url' => $ipfs->getGatewayUrl($cid, $filename)
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
                $db = Database::getInstance();
                $autoPin = $db->getSetting('auto_pin', '1') === '1';
                $recursivePin = $db->getSetting('recursive_pin', '1') === '1';

                $results = $ipfs->addDirectory($folderPath, $autoPin);
                $mainCid = !empty($results) ? (end($results)['Hash'] ?? null) : null;
                $rootName = basename($folderPath);
                
                if ($mainCid) {
                    if ($autoPin) {
                        if ($recursivePin) {
                            $ipfs->recursivePin($mainCid);
                        } else {
                            $ipfs->pinAdd($mainCid, false);
                        }

                        $stat = null;
                        try {
                            $stat = $ipfs->objectStat($mainCid);
                        } catch (Exception $e) {
                            $stat = null;
                        }

                        $stmt = $db->prepare("INSERT OR REPLACE INTO pins (cid, name, size, type) VALUES (:cid, :name, :size, :type)");
                        $stmt->bindValue(':cid', $mainCid, SQLITE3_TEXT);
                        $stmt->bindValue(':name', $rootName, SQLITE3_TEXT);
                        $stmt->bindValue(':size', $stat ? $stat['CumulativeSize'] : 0, SQLITE3_INTEGER);
                        $stmt->bindValue(':type', $recursivePin ? 'recursive' : 'direct', SQLITE3_TEXT);
                        $stmt->execute();
                    }

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

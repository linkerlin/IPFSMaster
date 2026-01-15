<?php

class BrowseController extends Controller {
    
    public function view($cid = null) {
        if (!$cid) {
            $cid = $this->getGet('cid');
        }
        
        if (!$cid) {
            $this->redirect('/');
            return;
        }
        
        $ipfs = new IPFSClient();
        $db = Database::getInstance();
        $error = null;
        $links = [];
        $stat = null;
        $pinName = null;
        
        try {
            // Get saved name for this CID (if any)
            $stmt = $db->prepare("SELECT name FROM pins WHERE cid = :cid LIMIT 1");
            $stmt->bindValue(':cid', $cid, SQLITE3_TEXT);
            $result = $stmt->execute();
            $row = $result ? $result->fetchArray(SQLITE3_ASSOC) : null;
            $pinName = $row['name'] ?? null;

            // Get links
            $ls = $ipfs->ls($cid);
            if (isset($ls['Objects'][0]['Links'])) {
                $links = $ls['Objects'][0]['Links'];
            }
            
            // Get stats
            try {
                $stat = $ipfs->objectStat($cid);
            } catch (Exception $e) {
                // Ignore
            }
        } catch (Exception $e) {
            $error = $e->getMessage();
        }
        
        $this->render('browse', [
            'cid' => $cid,
            'links' => $links,
            'stat' => $stat,
            'error' => $error,
            'ipfs' => $ipfs,
            'pinName' => $pinName
        ]);
    }
}

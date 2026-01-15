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
        $error = null;
        $links = [];
        $stat = null;
        
        try {
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
            'ipfs' => $ipfs
        ]);
    }
}

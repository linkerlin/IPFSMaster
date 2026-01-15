<?php

class HomeController extends Controller {
    
    public function index() {
        $ipfs = new IPFSClient();
        
        $nodeInfo = null;
        $version = null;
        $error = null;
        
        try {
            $nodeInfo = $ipfs->id();
            $version = $ipfs->version();
        } catch (Exception $e) {
            $error = $e->getMessage();
        }
        
        // Get recent pins from database
        $db = Database::getInstance();
        $result = $db->query("SELECT * FROM pins ORDER BY pinned_at DESC LIMIT 10");
        
        $recentPins = [];
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $recentPins[] = $row;
        }
        
        $this->render('dashboard', [
            'nodeInfo' => $nodeInfo,
            'version' => $version,
            'error' => $error,
            'recentPins' => $recentPins,
            'ipfs' => $ipfs
        ]);
    }
}

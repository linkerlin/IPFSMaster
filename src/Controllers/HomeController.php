<?php

class HomeController extends Controller {
    
    public function index() {
        // Get recent pins from database (fast, no IPFS call)
        $db = Database::getInstance();
        $result = $db->query("SELECT * FROM pins ORDER BY pinned_at DESC LIMIT 10");
        
        $recentPins = [];
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $recentPins[] = $row;
        }

        $countResult = $db->query("SELECT COUNT(*) as total FROM pins");
        $countRow = $countResult ? $countResult->fetchArray(SQLITE3_ASSOC) : null;
        $pinnedCount = $countRow['total'] ?? 0;
        
        // Render page immediately, IPFS stats will be loaded via htmx
        $this->render('dashboard', [
            'recentPins' => $recentPins,
            'pinnedCount' => $pinnedCount
        ]);
    }

    public function stats() {
        $ipfs = new IPFSClient();

        $nodeInfo = null;
        $version = null;
        $repoStat = null;
        $bwStat = null;
        $error = null;

        try {
            $nodeInfo = $ipfs->id();
            $version = $ipfs->version();
            $repoStat = $ipfs->statsRepo();
            $bwStat = $ipfs->statsBw();
        } catch (Exception $e) {
            $error = $e->getMessage();
        }

        $db = Database::getInstance();
        $result = $db->query("SELECT COUNT(*) as total FROM pins");
        $row = $result ? $result->fetchArray(SQLITE3_ASSOC) : null;
        $pinnedCount = $row['total'] ?? 0;

        $this->renderPartial('partials/dashboard_stats', [
            'nodeInfo' => $nodeInfo,
            'version' => $version,
            'repoStat' => $repoStat,
            'bwStat' => $bwStat,
            'error' => $error,
            'pinnedCount' => $pinnedCount,
            'ipfs' => $ipfs
        ]);
    }
}

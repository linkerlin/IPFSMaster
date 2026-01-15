<?php

class Database {
    private static $instance = null;
    private $db;
    
    private function __construct() {
        $dbPath = __DIR__ . '/../../database/ipfs_master.db';
        $dbDir = dirname($dbPath);
        
        if (!is_dir($dbDir)) {
            mkdir($dbDir, 0755, true);
        }
        
        $this->db = new SQLite3($dbPath);
        $this->db->busyTimeout(30000); // 30 seconds timeout
        // Enable WAL mode for better concurrency
        $this->db->exec('PRAGMA journal_mode=WAL');
        $this->db->exec('PRAGMA synchronous=NORMAL');
        $this->initDatabase();
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function initDatabase() {
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS settings (
                key TEXT PRIMARY KEY,
                value TEXT NOT NULL,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )
        ");
        
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS pins (
                cid TEXT PRIMARY KEY,
                name TEXT,
                size INTEGER DEFAULT 0,
                type TEXT DEFAULT 'recursive',
                pinned_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                metadata TEXT
            )
        ");
        
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS import_history (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                cid TEXT NOT NULL,
                source_path TEXT,
                import_type TEXT,
                status TEXT DEFAULT 'pending',
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                completed_at DATETIME,
                error_message TEXT
            )
        ");
        
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS background_tasks (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                task_type TEXT NOT NULL,
                status TEXT DEFAULT 'pending',
                progress INTEGER DEFAULT 0,
                total INTEGER DEFAULT 0,
                current_item TEXT,
                result TEXT,
                error_message TEXT,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                started_at DATETIME,
                completed_at DATETIME,
                pid INTEGER
            )
        ");
        
        $this->db->exec("
            CREATE INDEX IF NOT EXISTS idx_tasks_status ON background_tasks(status, created_at)
        ");
        
        // Initialize default settings if not exists
        $this->initDefaultSettings();
    }
    
    private function initDefaultSettings() {
        $defaults = [
            'ipfs_rpc_url' => '/ip4/127.0.0.1/tcp/5001',
            'ipfs_gateway_url' => 'http://127.0.0.1:8080',
            'auto_pin' => '1',
            'recursive_pin' => '1'
        ];
        
        foreach ($defaults as $key => $value) {
            $stmt = $this->db->prepare("INSERT OR IGNORE INTO settings (key, value) VALUES (:key, :value)");
            $stmt->bindValue(':key', $key, SQLITE3_TEXT);
            $stmt->bindValue(':value', $value, SQLITE3_TEXT);
            $stmt->execute();
        }
    }
    
    public function query($sql) {
        return $this->db->query($sql);
    }
    
    public function prepare($sql) {
        return $this->db->prepare($sql);
    }

    public function getSetting($key, $default = null) {
        $stmt = $this->db->prepare("SELECT value FROM settings WHERE key = :key");
        $stmt->bindValue(':key', $key, SQLITE3_TEXT);
        $result = $stmt->execute();
        $row = $result->fetchArray(SQLITE3_ASSOC);
        return $row ? $row['value'] : $default;
    }
    
    public function saveSetting($key, $value) {
        $stmt = $this->db->prepare("INSERT OR REPLACE INTO settings (key, value) VALUES (:key, :value)");
        $stmt->bindValue(':key', $key, SQLITE3_TEXT);
        $stmt->bindValue(':value', $value, SQLITE3_TEXT);
        return $stmt->execute();
    }
    
    public function getConnection() {
        return $this->db;
    }
    
    public function exec($sql) {
        return $this->db->exec($sql);
    }
    
    public function lastInsertRowID() {
        return $this->db->lastInsertRowID();
    }
    
    public function escapeString($str) {
        return $this->db->escapeString($str);
    }
}

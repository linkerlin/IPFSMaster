<?php

use PHPUnit\Framework\TestCase;

/**
 * Integration tests for database operations
 */
class DatabaseIntegrationTest extends TestCase {
    private $db;

    protected function setUp(): void {
        $this->db = Database::getInstance();
        // Clean up test data
        $this->db->exec("DELETE FROM pins WHERE cid LIKE 'test_%'");
        $this->db->exec("DELETE FROM import_history WHERE cid LIKE 'test_%'");
    }

    protected function tearDown(): void {
        // Clean up after tests
        $this->db->exec("DELETE FROM pins WHERE cid LIKE 'test_%'");
        $this->db->exec("DELETE FROM import_history WHERE cid LIKE 'test_%'");
    }

    public function testInsertAndRetrievePin(): void {
        $testCid = 'test_Qm123abc';
        $testName = 'Test File';
        
        $stmt = $this->db->prepare("INSERT INTO pins (cid, name, size, type) VALUES (:cid, :name, :size, :type)");
        $stmt->bindValue(':cid', $testCid, SQLITE3_TEXT);
        $stmt->bindValue(':name', $testName, SQLITE3_TEXT);
        $stmt->bindValue(':size', 1024, SQLITE3_INTEGER);
        $stmt->bindValue(':type', 'recursive', SQLITE3_TEXT);
        $result = $stmt->execute();
        
        $this->assertNotFalse($result);

        // Retrieve
        $stmt = $this->db->prepare("SELECT * FROM pins WHERE cid = :cid");
        $stmt->bindValue(':cid', $testCid, SQLITE3_TEXT);
        $result = $stmt->execute();
        $row = $result->fetchArray(SQLITE3_ASSOC);

        $this->assertNotFalse($row);
        $this->assertSame($testCid, $row['cid']);
        $this->assertSame($testName, $row['name']);
        $this->assertSame(1024, $row['size']);
        $this->assertSame('recursive', $row['type']);
    }

    public function testInsertImportHistory(): void {
        $testCid = 'test_Qm456def';
        
        $stmt = $this->db->prepare("INSERT INTO import_history (cid, source_path, import_type, status) VALUES (:cid, :source, :type, :status)");
        $stmt->bindValue(':cid', $testCid, SQLITE3_TEXT);
        $stmt->bindValue(':source', 'test.txt', SQLITE3_TEXT);
        $stmt->bindValue(':type', 'file', SQLITE3_TEXT);
        $stmt->bindValue(':status', 'completed', SQLITE3_TEXT);
        $result = $stmt->execute();

        $this->assertNotFalse($result);

        // Verify
        $stmt = $this->db->prepare("SELECT * FROM import_history WHERE cid = :cid");
        $stmt->bindValue(':cid', $testCid, SQLITE3_TEXT);
        $result = $stmt->execute();
        $row = $result->fetchArray(SQLITE3_ASSOC);

        $this->assertNotFalse($row);
        $this->assertSame($testCid, $row['cid']);
        $this->assertSame('completed', $row['status']);
    }

    public function testDeletePin(): void {
        $testCid = 'test_Qm789ghi';
        
        // Insert
        $stmt = $this->db->prepare("INSERT INTO pins (cid, name) VALUES (:cid, :name)");
        $stmt->bindValue(':cid', $testCid, SQLITE3_TEXT);
        $stmt->bindValue(':name', 'To Delete', SQLITE3_TEXT);
        $stmt->execute();

        // Delete
        $stmt = $this->db->prepare("DELETE FROM pins WHERE cid = :cid");
        $stmt->bindValue(':cid', $testCid, SQLITE3_TEXT);
        $stmt->execute();

        // Verify deletion
        $stmt = $this->db->prepare("SELECT * FROM pins WHERE cid = :cid");
        $stmt->bindValue(':cid', $testCid, SQLITE3_TEXT);
        $result = $stmt->execute();
        $row = $result->fetchArray(SQLITE3_ASSOC);

        $this->assertFalse($row);
    }

    public function testUpdateSetting(): void {
        $key = 'test_setting_key';
        $value1 = 'initial_value';
        $value2 = 'updated_value';

        // Insert
        $stmt = $this->db->prepare("INSERT OR REPLACE INTO settings (key, value) VALUES (:key, :value)");
        $stmt->bindValue(':key', $key, SQLITE3_TEXT);
        $stmt->bindValue(':value', $value1, SQLITE3_TEXT);
        $stmt->execute();

        // Verify initial
        $this->assertSame($value1, $this->db->getSetting($key));

        // Update
        $stmt = $this->db->prepare("INSERT OR REPLACE INTO settings (key, value) VALUES (:key, :value)");
        $stmt->bindValue(':key', $key, SQLITE3_TEXT);
        $stmt->bindValue(':value', $value2, SQLITE3_TEXT);
        $stmt->execute();

        // Verify update
        $this->assertSame($value2, $this->db->getSetting($key));

        // Cleanup
        $this->db->exec("DELETE FROM settings WHERE key = '$key'");
    }
}

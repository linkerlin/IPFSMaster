<?php

use PHPUnit\Framework\TestCase;

/**
 * Database edge cases and error handling
 */
class DatabaseEdgeCaseTest extends TestCase {
    private $db;

    protected function setUp(): void {
        $this->db = Database::getInstance();
    }

    protected function tearDown(): void {
        $this->db->exec("DELETE FROM pins WHERE cid LIKE 'edge_%'");
        $this->db->exec("DELETE FROM import_history WHERE cid LIKE 'edge_%'");
        $this->db->exec("DELETE FROM settings WHERE key LIKE 'edge_%'");
    }

    public function testInsertDuplicatePinUsesReplace(): void {
        $cid = 'edge_duplicate_test';
        
        // First insert
        $stmt = $this->db->prepare("INSERT OR REPLACE INTO pins (cid, name, size) VALUES (:cid, :name, :size)");
        $stmt->bindValue(':cid', $cid, SQLITE3_TEXT);
        $stmt->bindValue(':name', 'First', SQLITE3_TEXT);
        $stmt->bindValue(':size', 100, SQLITE3_INTEGER);
        $stmt->execute();

        // Second insert with same CID
        $stmt = $this->db->prepare("INSERT OR REPLACE INTO pins (cid, name, size) VALUES (:cid, :name, :size)");
        $stmt->bindValue(':cid', $cid, SQLITE3_TEXT);
        $stmt->bindValue(':name', 'Second', SQLITE3_TEXT);
        $stmt->bindValue(':size', 200, SQLITE3_INTEGER);
        $stmt->execute();

        // Should have only one record with updated values
        $stmt = $this->db->prepare("SELECT * FROM pins WHERE cid = :cid");
        $stmt->bindValue(':cid', $cid, SQLITE3_TEXT);
        $result = $stmt->execute();
        
        $rows = [];
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $rows[] = $row;
        }

        $this->assertCount(1, $rows);
        $this->assertSame('Second', $rows[0]['name']);
        $this->assertSame(200, $rows[0]['size']);
    }

    public function testGetSettingWithNullDefault(): void {
        $value = $this->db->getSetting('edge_nonexistent');
        $this->assertNull($value);
    }

    public function testGetSettingWithEmptyString(): void {
        $stmt = $this->db->prepare("INSERT INTO settings (key, value) VALUES (:key, :value)");
        $stmt->bindValue(':key', 'edge_empty', SQLITE3_TEXT);
        $stmt->bindValue(':value', '', SQLITE3_TEXT);
        $stmt->execute();

        $value = $this->db->getSetting('edge_empty', 'default');
        $this->assertSame('', $value);
    }

    public function testLargePinInsertBatch(): void {
        $startTime = microtime(true);
        
        $stmt = $this->db->prepare("INSERT INTO pins (cid, name, size) VALUES (:cid, :name, :size)");
        
        for ($i = 0; $i < 500; $i++) {
            $stmt->bindValue(':cid', "edge_batch_{$i}", SQLITE3_TEXT);
            $stmt->bindValue(':name', "Batch Pin {$i}", SQLITE3_TEXT);
            $stmt->bindValue(':size', $i * 1024, SQLITE3_INTEGER);
            $stmt->execute();
        }

        $endTime = microtime(true);
        $duration = $endTime - $startTime;

        // Should complete in reasonable time
        $this->assertLessThan(2.0, $duration, "500 inserts took {$duration}s");

        // Verify count
        $result = $this->db->query("SELECT COUNT(*) as count FROM pins WHERE cid LIKE 'edge_batch_%'");
        $row = $result->fetchArray(SQLITE3_ASSOC);
        $this->assertSame(500, $row['count']);

        // Cleanup
        $this->db->exec("DELETE FROM pins WHERE cid LIKE 'edge_batch_%'");
    }

    public function testImportHistoryWithNullFields(): void {
        $stmt = $this->db->prepare("INSERT INTO import_history (cid, source_path, import_type, status) VALUES (:cid, :source, :type, :status)");
        $stmt->bindValue(':cid', 'edge_null_test', SQLITE3_TEXT);
        $stmt->bindValue(':source', null, SQLITE3_NULL);
        $stmt->bindValue(':type', 'unknown', SQLITE3_TEXT);
        $stmt->bindValue(':status', 'pending', SQLITE3_TEXT);
        $result = $stmt->execute();

        $this->assertNotFalse($result);

        // Verify
        $stmt = $this->db->prepare("SELECT * FROM import_history WHERE cid = :cid");
        $stmt->bindValue(':cid', 'edge_null_test', SQLITE3_TEXT);
        $result = $stmt->execute();
        $row = $result->fetchArray(SQLITE3_ASSOC);

        $this->assertNull($row['source_path']);
        $this->assertSame('unknown', $row['import_type']);
    }

    public function testSettingTimestampUpdates(): void {
        $key = 'edge_timestamp_test';
        
        // Insert
        $stmt = $this->db->prepare("INSERT INTO settings (key, value) VALUES (:key, :value)");
        $stmt->bindValue(':key', $key, SQLITE3_TEXT);
        $stmt->bindValue(':value', 'initial', SQLITE3_TEXT);
        $stmt->execute();

        $stmt = $this->db->prepare("SELECT updated_at FROM settings WHERE key = :key");
        $stmt->bindValue(':key', $key, SQLITE3_TEXT);
        $result = $stmt->execute();
        $row1 = $result->fetchArray(SQLITE3_ASSOC);

        sleep(1);

        // Update
        $stmt = $this->db->prepare("INSERT OR REPLACE INTO settings (key, value, updated_at) VALUES (:key, :value, CURRENT_TIMESTAMP)");
        $stmt->bindValue(':key', $key, SQLITE3_TEXT);
        $stmt->bindValue(':value', 'updated', SQLITE3_TEXT);
        $stmt->execute();

        $stmt = $this->db->prepare("SELECT updated_at FROM settings WHERE key = :key");
        $stmt->bindValue(':key', $key, SQLITE3_TEXT);
        $result = $stmt->execute();
        $row2 = $result->fetchArray(SQLITE3_ASSOC);

        // Timestamps should be different
        $this->assertNotEquals($row1['updated_at'], $row2['updated_at']);
    }

    public function testSpecialCharactersInData(): void {
        $specialChars = "Test with 'quotes', \"double quotes\", and \n newlines & symbols <>";
        
        $stmt = $this->db->prepare("INSERT INTO pins (cid, name) VALUES (:cid, :name)");
        $stmt->bindValue(':cid', 'edge_special_chars', SQLITE3_TEXT);
        $stmt->bindValue(':name', $specialChars, SQLITE3_TEXT);
        $stmt->execute();

        $stmt = $this->db->prepare("SELECT name FROM pins WHERE cid = :cid");
        $stmt->bindValue(':cid', 'edge_special_chars', SQLITE3_TEXT);
        $result = $stmt->execute();
        $row = $result->fetchArray(SQLITE3_ASSOC);

        $this->assertSame($specialChars, $row['name']);
    }
}

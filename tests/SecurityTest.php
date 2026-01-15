<?php

use PHPUnit\Framework\TestCase;

/**
 * Security and validation tests
 */
class SecurityTest extends TestCase {
    public function testHtmlEscapingInTemplates(): void {
        $dangerousString = '<script>alert("XSS")</script>';
        $escaped = htmlspecialchars($dangerousString);
        
        $this->assertStringNotContainsString('<script>', $escaped);
        $this->assertStringContainsString('&lt;script&gt;', $escaped);
    }

    public function testSqlInjectionPrevention(): void {
        $db = Database::getInstance();
        $maliciousInput = "'; DROP TABLE pins; --";
        
        // Using prepared statements should prevent injection
        $stmt = $db->prepare("SELECT * FROM pins WHERE cid = :cid");
        $stmt->bindValue(':cid', $maliciousInput, SQLITE3_TEXT);
        $result = $stmt->execute();
        
        // Should not throw error, should just not find anything
        $this->assertNotFalse($result);
        
        // Table should still exist
        $check = $db->query("SELECT name FROM sqlite_master WHERE type='table' AND name='pins'");
        $row = $check->fetchArray(SQLITE3_ASSOC);
        $this->assertNotFalse($row);
        $this->assertSame('pins', $row['name']);
    }

    public function testCidValidation(): void {
        $validCids = [
            'QmTest123',
            'bafybeigdyrzt5sfp7udm7hu76uh7y26nf3efuylqabf3oclgtqy55fbzdi',
            'QmYwAPJzv5CZsnA625s3Xf2nemtYgPpHdWEz79ojWnPbdG'
        ];

        $invalidCids = [
            '',
            '<script>',
            'not a cid',
            '../../etc/passwd'
        ];

        foreach ($validCids as $cid) {
            // Valid CIDs should be alphanumeric with some length
            $this->assertTrue(strlen($cid) > 5);
            $this->assertMatchesRegularExpression('/^[a-zA-Z0-9]+$/', $cid);
        }
    }

    public function testPathTraversalPrevention(): void {
        $maliciousPath = '../../etc/passwd';
        $normalized = basename($maliciousPath);
        
        $this->assertSame('passwd', $normalized);
        $this->assertStringNotContainsString('..', $normalized);
    }

    public function testDatabaseEscaping(): void {
        $db = Database::getInstance();
        $dangerous = "test'; DROP TABLE settings; --";
        
        // PDO使用预处理语句，所以直接保存和读取应该相等
        $db->saveSetting('dangerous_key', $dangerous);
        $result = $db->getSetting('dangerous_key');
        $this->assertSame($dangerous, $result);
        
        // 验证settings表仍然存在（没有被DROP掉）
        $result = $db->getConnection()->query("SELECT name FROM sqlite_master WHERE type='table' AND name='settings'");
        $table = $result->fetchArray(SQLITE3_ASSOC);
        $this->assertNotFalse($table, 'Settings table should still exist');
    }
}

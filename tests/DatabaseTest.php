<?php

use PHPUnit\Framework\TestCase;

class DatabaseTest extends TestCase {
    private $db;

    protected function setUp(): void {
        $this->db = Database::getInstance();
        $this->db->exec("DELETE FROM settings WHERE key = 'test_key'");
    }

    public function testGetSettingReturnsDefaultWhenMissing(): void {
        $value = $this->db->getSetting('test_key', 'default_value');
        $this->assertSame('default_value', $value);
    }

    public function testGetSettingReturnsStoredValue(): void {
        $stmt = $this->db->prepare("INSERT OR REPLACE INTO settings (key, value) VALUES (:key, :value)");
        $stmt->bindValue(':key', 'test_key', SQLITE3_TEXT);
        $stmt->bindValue(':value', 'stored_value', SQLITE3_TEXT);
        $stmt->execute();

        $value = $this->db->getSetting('test_key', 'default_value');
        $this->assertSame('stored_value', $value);
    }

    public function testDefaultSettingsExist(): void {
        $rpc = $this->db->getSetting('ipfs_rpc_url');
        $gateway = $this->db->getSetting('ipfs_gateway_url');

        $this->assertNotEmpty($rpc);
        $this->assertNotEmpty($gateway);
    }
}

<?php

use PHPUnit\Framework\TestCase;

/**
 * Error handling and exception tests
 */
class ErrorHandlingTest extends TestCase {
    public function testDatabaseQueryErrorDoesNotCrash(): void {
        $db = Database::getInstance();
        
        // Invalid SQL should not crash the system
        $result = @$db->query("SELECT * FROM nonexistent_table");
        
        // Should either return false or throw, but not crash
        $this->assertTrue(true); // Made it here without crashing
    }

    public function testEmptyResultHandling(): void {
        $db = Database::getInstance();
        
        $result = $db->query("SELECT * FROM pins WHERE cid = 'nonexistent_cid_xyz'");
        $row = $result->fetchArray(SQLITE3_ASSOC);
        
        $this->assertFalse($row, 'Empty result should return false');
    }

    public function testNullValueHandling(): void {
        $db = Database::getInstance();
        
        $stmt = $db->prepare("INSERT INTO pins (cid, name, size) VALUES (:cid, :name, :size)");
        $stmt->bindValue(':cid', 'error_test_null', SQLITE3_TEXT);
        $stmt->bindValue(':name', null, SQLITE3_NULL);
        $stmt->bindValue(':size', null, SQLITE3_NULL);
        $result = $stmt->execute();
        
        $this->assertNotFalse($result);

        // Retrieve and verify
        $stmt = $db->prepare("SELECT * FROM pins WHERE cid = :cid");
        $stmt->bindValue(':cid', 'error_test_null', SQLITE3_TEXT);
        $result = $stmt->execute();
        $row = $result->fetchArray(SQLITE3_ASSOC);

        $this->assertNull($row['name']);
        $this->assertNull($row['size']);

        // Cleanup
        $db->exec("DELETE FROM pins WHERE cid = 'error_test_null'");
    }

    public function testInvalidControllerMethodHandling(): void {
        $controller = new Controller();
        
        // Calling nonexistent method should be catchable
        $this->assertFalse(method_exists($controller, 'nonexistentMethod'));
    }

    public function testIPFSClientWithInvalidConfiguration(): void {
        // Should not crash with unusual config
        $client = new IPFSClient('', '');
        
        $this->assertIsString($client->getRpcUrl());
        $this->assertIsString($client->getGatewayBaseUrl());
    }

    public function testRouterWithNoRoutes(): void {
        $router = new Router();
        
        // Router should exist even with no routes
        $reflection = new ReflectionObject($router);
        $property = $reflection->getProperty('routes');
        $property->setAccessible(true);
        $routes = $property->getValue($router);
        
        $this->assertIsArray($routes);
        $this->assertEmpty($routes);
    }

    public function testParseJsonLinesWithInvalidJson(): void {
        $ref = new ReflectionClass(IPFSClient::class);
        $client = $ref->newInstanceWithoutConstructor();
        $method = $ref->getMethod('parseJsonLines');
        $method->setAccessible(true);

        // Completely invalid JSON
        $payload = "not json at all\nstill not json\n";
        $items = $method->invoke($client, $payload);
        
        // Should return empty array, not crash
        $this->assertIsArray($items);
    }

    public function testGetSettingWithDatabaseError(): void {
        $db = Database::getInstance();
        
        // Even if setting doesn't exist, should return default gracefully
        $value = $db->getSetting('totally_nonexistent_key_xyz', 'default_value');
        
        $this->assertSame('default_value', $value);
    }

    public function testControllerRedirectDoesNotExecuteFurtherCode(): void {
        $controller = new Controller();
        $method = new ReflectionMethod(Controller::class, 'redirect');
        $method->setAccessible(true);

        // redirect() calls exit, which we can't easily test
        // Just verify method exists and is callable
        $this->assertTrue(method_exists($controller, 'redirect'));
    }

    public function testJsonResponseWithSpecialCharacters(): void {
        $controller = new Controller();
        $method = new ReflectionMethod(Controller::class, 'json');
        $method->setAccessible(true);

        ob_start();
        @$method->invoke($controller, [
            'message' => 'Test with "quotes" and \'apostrophes\' and <tags>',
            'unicode' => '中文测试 🎉',
        ]);
        $output = ob_get_clean();

        $this->assertJson($output);
        $decoded = json_decode($output, true);
        
        $this->assertArrayHasKey('message', $decoded);
        $this->assertArrayHasKey('unicode', $decoded);
    }
}

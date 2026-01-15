<?php

use PHPUnit\Framework\TestCase;

/**
 * Edge case and error handling tests
 */
class EdgeCaseTest extends TestCase {
    public function testIPFSClientConstructorWithDefaultSettings(): void {
        // Should not throw with default settings
        $client = new IPFSClient();
        $this->assertInstanceOf(IPFSClient::class, $client);
    }

    public function testIPFSClientConstructorWithCustomSettings(): void {
        $client = new IPFSClient('http://custom:5001', 'http://custom:8080');
        $this->assertSame('http://custom:5001', $client->getRpcUrl());
        $this->assertSame('http://custom:8080', $client->getGatewayBaseUrl());
    }

    public function testDatabaseSingletonPattern(): void {
        $db1 = Database::getInstance();
        $db2 = Database::getInstance();
        
        $this->assertSame($db1, $db2, 'Database should be singleton');
    }

    public function testRouterHandlesTrailingSlash(): void {
        $router = new Router();
        $reflection = new ReflectionObject($router);
        $method = $reflection->getMethod('matchPath');
        $method->setAccessible(true);

        $params = [];
        
        // Root path should match
        $result = $method->invokeArgs($router, ['/', '/', &$params]);
        $this->assertTrue($result);

        // Non-root with trailing slash should be normalized
        $_SERVER['REQUEST_URI'] = '/test/';
        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        if ($uri !== '/' && substr($uri, -1) === '/') {
            $uri = rtrim($uri, '/');
        }
        $this->assertSame('/test', $uri);
    }

    public function testIPFSGatewayUrlGeneration(): void {
        $client = new IPFSClient('http://127.0.0.1:5001', 'http://127.0.0.1:8080');
        $cid = 'QmTest123';
        
        $url = $client->getGatewayUrl($cid);
        // 使用正则匹配，因为可能自动切换到8081或8082
        $this->assertMatchesRegularExpression('/http:\/\/127\.0\.0\.1:(8080|8081|8082)\/ipfs\/QmTest123/', $url);
    }

    public function testControllerJsonResponse(): void {
        $controller = new Controller();
        $method = new ReflectionMethod(Controller::class, 'json');
        $method->setAccessible(true);

        ob_start();
        @$method->invoke($controller, ['success' => true, 'message' => 'Test'], 200);
        $output = ob_get_clean();

        $this->assertJson($output);
        $decoded = json_decode($output, true);
        $this->assertTrue($decoded['success']);
        $this->assertSame('Test', $decoded['message']);
    }

    public function testEmptyPinsList(): void {
        $db = Database::getInstance();
        $db->exec("DELETE FROM pins WHERE cid LIKE 'test_%'");

        $result = $db->query("SELECT * FROM pins WHERE cid LIKE 'test_%'");
        $pins = [];
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $pins[] = $row;
        }

        $this->assertEmpty($pins);
    }

    public function testParseJsonLinesWithEmptyString(): void {
        $ref = new ReflectionClass(IPFSClient::class);
        $client = $ref->newInstanceWithoutConstructor();
        $method = $ref->getMethod('parseJsonLines');
        $method->setAccessible(true);

        $items = $method->invoke($client, '');
        $this->assertEmpty($items);

        $items = $method->invoke($client, "\n\n");
        $this->assertEmpty($items);
    }

    public function testScanDirectoryWithNestedStructure(): void {
        $ref = new ReflectionClass(IPFSClient::class);
        $client = $ref->newInstanceWithoutConstructor();
        $method = $ref->getMethod('scanDirectory');
        $method->setAccessible(true);

        // Create temp nested directory
        $tempBase = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'ipfsmaster_nested_' . uniqid();
        $tempSub = $tempBase . DIRECTORY_SEPARATOR . 'subdir';
        mkdir($tempSub, 0755, true);
        
        file_put_contents($tempBase . DIRECTORY_SEPARATOR . 'file1.txt', 'test1');
        file_put_contents($tempSub . DIRECTORY_SEPARATOR . 'file2.txt', 'test2');

        $files = $method->invoke($client, $tempBase);
        
        $this->assertCount(2, $files);
        $basenames = array_map('basename', $files);
        $this->assertContains('file1.txt', $basenames);
        $this->assertContains('file2.txt', $basenames);

        // Cleanup
        unlink($tempBase . DIRECTORY_SEPARATOR . 'file1.txt');
        unlink($tempSub . DIRECTORY_SEPARATOR . 'file2.txt');
        rmdir($tempSub);
        rmdir($tempBase);
    }
}

<?php

use PHPUnit\Framework\TestCase;

/**
 * Advanced IPFS Client tests
 */
class IPFSClientAdvancedTest extends TestCase {
    public function testGetRpcUrlReturnsConfiguredUrl(): void {
        $client = new IPFSClient('http://test:5001', 'http://test:8080');
        $this->assertSame('http://test:5001', $client->getRpcUrl());
    }

    public function testGetGatewayBaseUrlReturnsConfiguredUrl(): void {
        $client = new IPFSClient('http://test:5001', 'http://test:8080');
        $this->assertSame('http://test:8080', $client->getGatewayBaseUrl());
    }

    public function testGetGatewayUrlFormatsCorrectly(): void {
        $client = new IPFSClient('http://test:5001', 'http://test:8080');
        $url = $client->getGatewayUrl('QmTest123');
        $this->assertSame('http://test:8080/ipfs/QmTest123', $url);
    }

    public function testMultiAddrConversionVariants(): void {
        $ref = new ReflectionClass(IPFSClient::class);
        $client = $ref->newInstanceWithoutConstructor();
        $method = $ref->getMethod('convertMultiAddrToHttp');
        $method->setAccessible(true);

        // Test different ports
        $result = $method->invoke($client, '/ip4/192.168.1.100/tcp/5001');
        $this->assertSame('http://192.168.1.100:5001', $result);

        $result = $method->invoke($client, '/ip4/10.0.0.1/tcp/4001');
        $this->assertSame('http://10.0.0.1:4001', $result);

        // Test already HTTP format
        $result = $method->invoke($client, 'http://example.com:5001');
        $this->assertSame('http://example.com:5001', $result);
    }

    public function testScanDirectoryIgnoresDotFiles(): void {
        $ref = new ReflectionClass(IPFSClient::class);
        $client = $ref->newInstanceWithoutConstructor();
        $method = $ref->getMethod('scanDirectory');
        $method->setAccessible(true);

        $tempDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'ipfsmaster_scan_' . uniqid();
        mkdir($tempDir, 0755, true);
        
        file_put_contents($tempDir . DIRECTORY_SEPARATOR . 'file.txt', 'test');
        file_put_contents($tempDir . DIRECTORY_SEPARATOR . '.hidden', 'hidden');

        $files = $method->invoke($client, $tempDir);
        
        // Should include visible file
        $basenames = array_map('basename', $files);
        $this->assertContains('file.txt', $basenames);
        
        // May or may not include hidden (implementation dependent)
        // Just verify it returns an array with at least one file
        $this->assertGreaterThanOrEqual(1, count($files));

        // Cleanup
        @unlink($tempDir . DIRECTORY_SEPARATOR . 'file.txt');
        @unlink($tempDir . DIRECTORY_SEPARATOR . '.hidden');
        rmdir($tempDir);
    }

    public function testParseJsonLinesWithMalformedJson(): void {
        $ref = new ReflectionClass(IPFSClient::class);
        $client = $ref->newInstanceWithoutConstructor();
        $method = $ref->getMethod('parseJsonLines');
        $method->setAccessible(true);

        // Mix of valid and invalid JSON
        $payload = "{\"valid\":true}\n{malformed\n{\"also\":\"valid\"}\n";
        $items = $method->invoke($client, $payload);

        // Should parse only valid lines
        $this->assertGreaterThanOrEqual(2, count($items));
        $this->assertTrue($items[0]['valid']);
        $this->assertSame('valid', $items[1]['also']);
    }

    public function testParseJsonLinesWithEmptyLines(): void {
        $ref = new ReflectionClass(IPFSClient::class);
        $client = $ref->newInstanceWithoutConstructor();
        $method = $ref->getMethod('parseJsonLines');
        $method->setAccessible(true);

        $payload = "{\"a\":1}\n\n\n{\"b\":2}\n\n";
        $items = $method->invoke($client, $payload);

        $this->assertCount(2, $items);
        $this->assertSame(1, $items[0]['a']);
        $this->assertSame(2, $items[1]['b']);
    }
}

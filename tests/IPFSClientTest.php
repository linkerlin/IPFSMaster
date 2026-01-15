<?php

use PHPUnit\Framework\TestCase;

class IPFSClientTest extends TestCase {
    public function testConvertMultiAddrToHttp(): void {
        $ref = new ReflectionClass(IPFSClient::class);
        $client = $ref->newInstanceWithoutConstructor();
        $method = $ref->getMethod('convertMultiAddrToHttp');
        $method->setAccessible(true);

        $result = $method->invoke($client, '/ip4/127.0.0.1/tcp/5001');
        $this->assertSame('http://127.0.0.1:5001', $result);

        $result2 = $method->invoke($client, 'http://100.113.134.128:5001');
        $this->assertSame('http://100.113.134.128:5001', $result2);
    }

    public function testParseJsonLines(): void {
        $ref = new ReflectionClass(IPFSClient::class);
        $client = $ref->newInstanceWithoutConstructor();
        $method = $ref->getMethod('parseJsonLines');
        $method->setAccessible(true);

        $payload = "{\"Name\":\"a\",\"Hash\":\"Qm1\"}\n{\"Name\":\"b\",\"Hash\":\"Qm2\"}\n";
        $items = $method->invoke($client, $payload);

        $this->assertCount(2, $items);
        $this->assertSame('a', $items[0]['Name']);
        $this->assertSame('Qm2', $items[1]['Hash']);
    }

    public function testAddDirectoryEmptyThrows(): void {
        $ref = new ReflectionClass(IPFSClient::class);
        $client = $ref->newInstanceWithoutConstructor();

        $tempDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'ipfsmaster_empty_' . uniqid();
        mkdir($tempDir, 0755, true);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Folder is empty');

        try {
            $client->addDirectory($tempDir, false);
        } finally {
            rmdir($tempDir);
        }
    }
}

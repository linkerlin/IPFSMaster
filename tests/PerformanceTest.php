<?php

use PHPUnit\Framework\TestCase;

/**
 * Performance and scalability tests
 */
class PerformanceTest extends TestCase {
    public function testDatabaseQueryPerformance(): void {
        $db = Database::getInstance();
        
        // Warm up
        $db->query("SELECT * FROM pins LIMIT 1");
        
        $startTime = microtime(true);
        
        for ($i = 0; $i < 100; $i++) {
            $result = $db->query("SELECT * FROM pins LIMIT 10");
            while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
                // Process row
            }
        }
        
        $endTime = microtime(true);
        $duration = $endTime - $startTime;
        
        // Should complete 100 queries in under 1 second
        $this->assertLessThan(1.0, $duration, 
            "100 queries took {$duration}s, expected < 1s");
    }

    public function testPreparedStatementReuse(): void {
        $db = Database::getInstance();
        
        $startTime = microtime(true);
        
        // Prepare once, execute multiple times
        $stmt = $db->prepare("SELECT * FROM settings WHERE key = :key");
        
        for ($i = 0; $i < 50; $i++) {
            $stmt->bindValue(':key', 'ipfs_rpc_url', SQLITE3_TEXT);
            $result = $stmt->execute();
            $row = $result->fetchArray(SQLITE3_ASSOC);
        }
        
        $endTime = microtime(true);
        $duration = $endTime - $startTime;
        
        // Prepared statement reuse should be fast
        $this->assertLessThan(0.5, $duration,
            "50 prepared queries took {$duration}s, expected < 0.5s");
    }

    public function testLargePinsList(): void {
        $db = Database::getInstance();
        
        // Insert test data
        $stmt = $db->prepare("INSERT INTO pins (cid, name, size, type) VALUES (:cid, :name, :size, :type)");
        
        for ($i = 0; $i < 100; $i++) {
            $stmt->bindValue(':cid', 'perf_test_' . $i, SQLITE3_TEXT);
            $stmt->bindValue(':name', 'Test Pin ' . $i, SQLITE3_TEXT);
            $stmt->bindValue(':size', rand(1000, 1000000), SQLITE3_INTEGER);
            $stmt->bindValue(':type', 'recursive', SQLITE3_TEXT);
            $stmt->execute();
        }
        
        $startTime = microtime(true);
        
        $result = $db->query("SELECT * FROM pins WHERE cid LIKE 'perf_test_%' ORDER BY pinned_at DESC");
        $count = 0;
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $count++;
        }
        
        $endTime = microtime(true);
        $duration = $endTime - $startTime;
        
        $this->assertSame(100, $count);
        $this->assertLessThan(0.1, $duration,
            "Fetching 100 pins took {$duration}s, expected < 0.1s");
        
        // Cleanup
        $db->exec("DELETE FROM pins WHERE cid LIKE 'perf_test_%'");
    }

    public function testJsonParsingPerformance(): void {
        $ref = new ReflectionClass(IPFSClient::class);
        $client = $ref->newInstanceWithoutConstructor();
        $method = $ref->getMethod('parseJsonLines');
        $method->setAccessible(true);
        
        // Generate large JSON-lines payload
        $lines = [];
        for ($i = 0; $i < 1000; $i++) {
            $lines[] = json_encode(['Name' => "file{$i}.txt", 'Hash' => "Qm{$i}", 'Size' => $i * 1024]);
        }
        $payload = implode("\n", $lines);
        
        $startTime = microtime(true);
        $items = $method->invoke($client, $payload);
        $endTime = microtime(true);
        $duration = $endTime - $startTime;
        
        $this->assertCount(1000, $items);
        $this->assertLessThan(0.2, $duration,
            "Parsing 1000 JSON lines took {$duration}s, expected < 0.2s");
    }
}

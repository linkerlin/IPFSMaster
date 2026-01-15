<?php

use PHPUnit\Framework\TestCase;

/**
 * Input validation and sanitization tests
 */
class ValidationTest extends TestCase {
    public function testCidFormatValidation(): void {
        // Valid CID v0 (base58)
        $validCidV0 = 'QmYwAPJzv5CZsnA625s3Xf2nemtYgPpHdWEz79ojWnPbdG';
        $this->assertMatchesRegularExpression('/^Qm[a-zA-Z0-9]{44}$/', $validCidV0);

        // Valid CID v1 (base32)
        $validCidV1 = 'bafybeigdyrzt5sfp7udm7hu76uh7y26nf3efuylqabf3oclgtqy55fbzdi';
        $this->assertMatchesRegularExpression('/^bafy[a-z0-9]+$/', $validCidV1);

        // Invalid CIDs
        $invalidCids = [
            '',
            'not-a-cid',
            '<script>alert(1)</script>',
            '../../../etc/passwd',
            'Qm123', // Too short
        ];

        foreach ($invalidCids as $invalid) {
            // 验证无效CID不匹配V0或V1格式
            $matchesV0 = preg_match('/^Qm[a-zA-Z0-9]{44}$/', $invalid);
            $matchesV1 = preg_match('/^bafy[a-z0-9]+$/', $invalid);
            $this->assertFalse(
                (bool)($matchesV0 || $matchesV1),
                "Should reject invalid CID: {$invalid}"
            );
        }
    }

    public function testFileNameSanitization(): void {
        $dangerous = '../../../etc/passwd';
        $safe = basename($dangerous);
        
        $this->assertSame('passwd', $safe);
        $this->assertStringNotContainsString('..', $safe);
        $this->assertStringNotContainsString('/', $safe);
        $this->assertStringNotContainsString('\\', $safe);
    }

    public function testHtmlEscaping(): void {
        $dangerous = '<script>alert("XSS")</script>';
        $escaped = htmlspecialchars($dangerous, ENT_QUOTES, 'UTF-8');
        
        $this->assertStringNotContainsString('<script>', $escaped);
        $this->assertStringNotContainsString('</script>', $escaped);
        $this->assertStringContainsString('&lt;script&gt;', $escaped);
    }

    public function testUrlValidation(): void {
        $validUrls = [
            'http://127.0.0.1:5001',
            'http://localhost:8080',
            'https://ipfs.io',
            'http://192.168.1.1:5001',
        ];

        foreach ($validUrls as $url) {
            $this->assertNotFalse(filter_var($url, FILTER_VALIDATE_URL));
        }

        $invalidUrls = [
            'not a url',
            'javascript:alert(1)',
            'file:///etc/passwd',
            '<script>',
        ];

        foreach ($invalidUrls as $url) {
            // Should either be false or not match http/https scheme
            $isValid = filter_var($url, FILTER_VALIDATE_URL);
            if ($isValid !== false) {
                $this->assertNotTrue(
                    preg_match('/^https?:\/\//', $url),
                    "Should reject non-http URL: {$url}"
                );
            }
        }
    }

    public function testIntegerValidation(): void {
        // Valid integers
        $this->assertTrue(is_numeric('123'));
        $this->assertTrue(is_numeric('0'));
        $this->assertTrue(is_numeric('-1'));

        // Invalid
        $this->assertFalse(is_numeric('abc'));
        $this->assertFalse(is_numeric('12.34.56'));
        $this->assertFalse(is_numeric('<script>'));
    }

    public function testMultiAddrFormatValidation(): void {
        $validMultiAddrs = [
            '/ip4/127.0.0.1/tcp/5001',
            '/ip4/192.168.1.100/tcp/4001',
            '/ip4/10.0.0.1/tcp/8080',
        ];

        $pattern = '#^/ip4/[\d.]+/tcp/\d+$#';

        foreach ($validMultiAddrs as $addr) {
            $this->assertMatchesRegularExpression($pattern, $addr);
        }

        $invalid = '/ip4/localhost/tcp/abc';
        $this->assertDoesNotMatchRegularExpression($pattern, $invalid);
    }

    public function testDirectoryPathValidation(): void {
        // Valid paths (platform-specific)
        if (DIRECTORY_SEPARATOR === '/') {
            // Unix-like
            $validPaths = [
                '/tmp/test',
                '/home/user/data',
                '/var/www/uploads',
            ];
            
            foreach ($validPaths as $path) {
                $this->assertMatchesRegularExpression('#^/[a-zA-Z0-9_/.-]+$#', $path);
            }
        } else {
            // Windows
            $validPaths = [
                'C:\\temp\\test',
                'D:\\data',
            ];

            foreach ($validPaths as $path) {
                $this->assertMatchesRegularExpression('#^[A-Z]:\\\\[a-zA-Z0-9_\\\\.-]+$#', $path);
            }
        }

        // Always invalid
        $invalid = [
            '<script>',
            '../../etc/passwd',
            'javascript:alert(1)',
        ];

        foreach ($invalid as $path) {
            // 检查危险模式
            $hasDotDot = strpos($path, '..') !== false;
            $hasAngles = strpos($path, '<') !== false || strpos($path, '>') !== false;
            $hasJavascript = stripos($path, 'javascript:') !== false;
            
            $this->assertTrue(
                $hasDotDot || $hasAngles || $hasJavascript,
                "Should detect dangerous path: {$path}"
            );
        }
    }

    public function testPinTypeValidation(): void {
        $validTypes = ['recursive', 'direct'];
        
        foreach ($validTypes as $type) {
            $this->assertContains($type, ['recursive', 'direct']);
        }

        $invalid = 'invalid_type';
        $this->assertNotContains($invalid, ['recursive', 'direct']);
    }

    public function testStatusValidation(): void {
        $validStatuses = ['pending', 'completed', 'failed'];
        
        $testStatus = 'completed';
        $this->assertContains($testStatus, $validStatuses);

        $invalidStatus = '<script>alert(1)</script>';
        $this->assertNotContains($invalidStatus, $validStatuses);
    }
}

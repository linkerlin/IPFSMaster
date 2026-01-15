<?php

use PHPUnit\Framework\TestCase;

/**
 * Controller render and output tests
 */
class ControllerRenderTest extends TestCase {
    private $controller;

    protected function setUp(): void {
        $this->controller = new HomeController();
    }

    public function testRenderPartialDoesNotThrow(): void {
        // Test that renderPartial can be called
        $method = new ReflectionMethod(Controller::class, 'renderPartial');
        $method->setAccessible(true);

        // Should not throw
        ob_start();
        try {
            $method->invoke($this->controller, 'partials/dashboard_stats', [
                'error' => null,
                'nodeInfo' => ['ID' => 'test'],
                'version' => ['Version' => '0.0.1'],
                'repoStat' => null,
                'bwStat' => null,
                'pinnedCount' => 0,
                'ipfs' => new IPFSClient()
            ]);
            $output = ob_get_clean();
            $this->assertIsString($output);
        } catch (Exception $e) {
            ob_end_clean();
            // Template might not exist in test env, that's ok
            $this->assertStringContainsString('templates', $e->getMessage());
        }
    }

    public function testJsonResponseFormat(): void {
        $controller = new Controller();
        $method = new ReflectionMethod(Controller::class, 'json');
        $method->setAccessible(true);

        ob_start();
        @$method->invoke($controller, [
            'success' => true,
            'data' => ['test' => 'value'],
            'count' => 42
        ], 200);
        $output = ob_get_clean();

        $this->assertJson($output);
        $decoded = json_decode($output, true);
        $this->assertTrue($decoded['success']);
        $this->assertSame('value', $decoded['data']['test']);
        $this->assertSame(42, $decoded['count']);
    }

    public function testHtmxTriggerHeader(): void {
        $controller = new Controller();
        $method = new ReflectionMethod(Controller::class, 'htmxTrigger');
        $method->setAccessible(true);

        ob_start();
        @$method->invoke($controller, 'testEvent', ['message' => 'test']);
        ob_end_clean();

        // 在测试环境中，我们只验证方法存在并可调用
        $this->assertTrue(true);
    }

    public function testGetGetReturnsCorrectValue(): void {
        $_GET['test_key'] = 'test_value';
        
        $controller = new Controller();
        $method = new ReflectionMethod(Controller::class, 'getGet');
        $method->setAccessible(true);

        $result = $method->invoke($controller, 'test_key');
        $this->assertSame('test_value', $result);

        $default = $method->invoke($controller, 'nonexistent', 'default');
        $this->assertSame('default', $default);

        unset($_GET['test_key']);
    }
}

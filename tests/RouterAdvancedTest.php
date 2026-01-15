<?php

use PHPUnit\Framework\TestCase;

/**
 * Router advanced matching and parameter tests
 */
class RouterAdvancedTest extends TestCase {
    private $router;

    protected function setUp(): void {
        $this->router = new Router();
    }

    public function testMultipleParametersInPath(): void {
        $reflection = new ReflectionObject($this->router);
        $method = $reflection->getMethod('matchPath');
        $method->setAccessible(true);

        $params = [];
        $result = $method->invokeArgs($this->router, [
            '/user/{userId}/post/{postId}',
            '/user/123/post/456',
            &$params
        ]);

        $this->assertTrue($result);
        $this->assertArrayHasKey('userId', $params);
        $this->assertArrayHasKey('postId', $params);
        $this->assertSame('123', $params['userId']);
        $this->assertSame('456', $params['postId']);
    }

    public function testParameterWithHyphen(): void {
        $reflection = new ReflectionObject($this->router);
        $method = $reflection->getMethod('matchPath');
        $method->setAccessible(true);

        $params = [];
        $result = $method->invokeArgs($this->router, [
            '/item/{item_id}',
            '/item/test-123',
            &$params
        ]);

        $this->assertTrue($result);
        $this->assertSame('test-123', $params['item_id']);
    }

    public function testPathWithQueryString(): void {
        $_SERVER['REQUEST_URI'] = '/test?foo=bar&baz=qux';
        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        
        $this->assertSame('/test', $uri);
        $this->assertStringNotContainsString('?', $uri);
    }

    public function testMultipleRoutesWithSameMethod(): void {
        $this->router->get('/test1', function() { echo 'test1'; });
        $this->router->get('/test2', function() { echo 'test2'; });
        $this->router->get('/test3', function() { echo 'test3'; });

        $reflection = new ReflectionObject($this->router);
        $property = $reflection->getProperty('routes');
        $property->setAccessible(true);
        $routes = $property->getValue($this->router);

        $this->assertCount(3, $routes);
        
        $getPaths = array_filter($routes, fn($r) => $r['method'] === 'GET');
        $this->assertCount(3, $getPaths);
    }

    public function testMixedGetAndPostRoutes(): void {
        $this->router->get('/resource', function() { echo 'get'; });
        $this->router->post('/resource', function() { echo 'post'; });

        $reflection = new ReflectionObject($this->router);
        $property = $reflection->getProperty('routes');
        $property->setAccessible(true);
        $routes = $property->getValue($this->router);

        $this->assertCount(2, $routes);
        
        $getMethods = array_column($routes, 'method');
        $this->assertContains('GET', $getMethods);
        $this->assertContains('POST', $getMethods);
    }

    public function testParameterMatchDoesNotMatchSlash(): void {
        $reflection = new ReflectionObject($this->router);
        $method = $reflection->getMethod('matchPath');
        $method->setAccessible(true);

        $params = [];
        $result = $method->invokeArgs($this->router, [
            '/file/{filename}',
            '/file/path/to/file.txt',
            &$params
        ]);

        // Should not match because parameter doesn't cross slashes
        $this->assertFalse($result);
    }

    public function testEmptyPathMatching(): void {
        $reflection = new ReflectionObject($this->router);
        $method = $reflection->getMethod('matchPath');
        $method->setAccessible(true);

        $params = [];
        
        // Root should match root
        $result = $method->invokeArgs($this->router, ['/', '/', &$params]);
        $this->assertTrue($result);

        // Empty should not match non-empty
        $result = $method->invokeArgs($this->router, ['/', '/test', &$params]);
        $this->assertFalse($result);
    }

    public function testControllerArrayHandler(): void {
        $this->router->get('/test', [HomeController::class, 'index']);

        $reflection = new ReflectionObject($this->router);
        $property = $reflection->getProperty('routes');
        $property->setAccessible(true);
        $routes = $property->getValue($this->router);

        $this->assertIsArray($routes[0]['handler']);
        $this->assertSame(HomeController::class, $routes[0]['handler'][0]);
        $this->assertSame('index', $routes[0]['handler'][1]);
    }

    public function testCallableHandler(): void {
        $this->router->get('/test', function() {
            return 'callback result';
        });

        $reflection = new ReflectionObject($this->router);
        $property = $reflection->getProperty('routes');
        $property->setAccessible(true);
        $routes = $property->getValue($this->router);

        $this->assertIsCallable($routes[0]['handler']);
    }
}

<?php

use PHPUnit\Framework\TestCase;

class RouterTest extends TestCase {
    private $router;

    protected function setUp(): void {
        $this->router = new Router();
    }

    public function testAddGetRoute(): void {
        $this->router->get('/test', function() {
            echo 'test';
        });

        $reflection = new ReflectionObject($this->router);
        $property = $reflection->getProperty('routes');
        $property->setAccessible(true);
        $routes = $property->getValue($this->router);

        $this->assertCount(1, $routes);
        $this->assertSame('GET', $routes[0]['method']);
        $this->assertSame('/test', $routes[0]['path']);
    }

    public function testAddPostRoute(): void {
        $this->router->post('/submit', function() {
            echo 'submit';
        });

        $reflection = new ReflectionObject($this->router);
        $property = $reflection->getProperty('routes');
        $property->setAccessible(true);
        $routes = $property->getValue($this->router);

        $this->assertCount(1, $routes);
        $this->assertSame('POST', $routes[0]['method']);
        $this->assertSame('/submit', $routes[0]['path']);
    }

    public function testMatchPathSimple(): void {
        $reflection = new ReflectionObject($this->router);
        $method = $reflection->getMethod('matchPath');
        $method->setAccessible(true);

        $params = [];
        $result = $method->invokeArgs($this->router, ['/test', '/test', &$params]);
        
        $this->assertTrue($result);
        $this->assertEmpty($params);
    }

    public function testMatchPathWithParameter(): void {
        $reflection = new ReflectionObject($this->router);
        $method = $reflection->getMethod('matchPath');
        $method->setAccessible(true);

        $params = [];
        $result = $method->invokeArgs($this->router, ['/user/{id}', '/user/123', &$params]);
        
        $this->assertTrue($result);
        $this->assertArrayHasKey('id', $params);
        $this->assertSame('123', $params['id']);
    }

    public function testMatchPathNoMatch(): void {
        $reflection = new ReflectionObject($this->router);
        $method = $reflection->getMethod('matchPath');
        $method->setAccessible(true);

        $params = [];
        $result = $method->invokeArgs($this->router, ['/test', '/different', &$params]);
        
        $this->assertFalse($result);
    }
}

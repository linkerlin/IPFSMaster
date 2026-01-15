<?php

use PHPUnit\Framework\TestCase;

class UploadControllerTest extends TestCase {
    private $controller;

    protected function setUp(): void {
        $this->controller = new UploadController();
    }

    public function testIsPostReturnsFalseForGet(): void {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        
        $method = new ReflectionMethod(Controller::class, 'isPost');
        $method->setAccessible(true);
        
        $result = $method->invoke($this->controller);
        $this->assertFalse($result);
    }

    public function testIsPostReturnsTrueForPost(): void {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        
        $method = new ReflectionMethod(Controller::class, 'isPost');
        $method->setAccessible(true);
        
        $result = $method->invoke($this->controller);
        $this->assertTrue($result);
    }

    public function testIsHtmxDetectsHeader(): void {
        unset($_SERVER['HTTP_HX_REQUEST']);
        
        $method = new ReflectionMethod(Controller::class, 'isHtmx');
        $method->setAccessible(true);
        
        $result = $method->invoke($this->controller);
        $this->assertFalse($result);

        $_SERVER['HTTP_HX_REQUEST'] = 'true';
        $result = $method->invoke($this->controller);
        $this->assertTrue($result);
    }

    public function testGetPostReturnsDefault(): void {
        $_POST = [];
        
        $method = new ReflectionMethod(Controller::class, 'getPost');
        $method->setAccessible(true);
        
        $result = $method->invoke($this->controller, 'nonexistent', 'default');
        $this->assertSame('default', $result);
    }

    public function testGetPostReturnsValue(): void {
        $_POST['key'] = 'value';
        
        $method = new ReflectionMethod(Controller::class, 'getPost');
        $method->setAccessible(true);
        
        $result = $method->invoke($this->controller, 'key');
        $this->assertSame('value', $result);
    }
}

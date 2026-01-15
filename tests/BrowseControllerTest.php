<?php

use PHPUnit\Framework\TestCase;

class BrowseControllerTest extends TestCase {
    private $controller;

    protected function setUp(): void {
        $this->controller = new BrowseController();
    }

    public function testControllerExists(): void {
        $this->assertInstanceOf(BrowseController::class, $this->controller);
        $this->assertTrue(method_exists($this->controller, 'view'));
    }
}

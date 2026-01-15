<?php

use PHPUnit\Framework\TestCase;

class HomeControllerTest extends TestCase {
    private $controller;

    protected function setUp(): void {
        $this->controller = new HomeController();
    }

    public function testIndexMethodExists(): void {
        $this->assertTrue(method_exists($this->controller, 'index'));
    }

    public function testStatsMethodExists(): void {
        $this->assertTrue(method_exists($this->controller, 'stats'));
    }
}

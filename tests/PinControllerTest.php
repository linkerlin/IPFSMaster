<?php

use PHPUnit\Framework\TestCase;

class PinControllerTest extends TestCase {
    public function testFetchPinsReturnsArray(): void {
        $controller = new PinController();
        $method = new ReflectionMethod(PinController::class, 'fetchPins');
        $method->setAccessible(true);

        $result = $method->invoke($controller);
        $this->assertIsArray($result);
    }
}

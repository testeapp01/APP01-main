<?php

use PHPUnit\Framework\TestCase;
use App\Routing\Router;

final class RouterTest extends TestCase
{
    public function testMatchesPathParams(): void
    {
        $router = new Router();
        $captured = null;

        $router->map('GET', '/api/v1/items/{id}', function (array $params) use (&$captured): void {
            $captured = $params;
        });

        $matched = $router->dispatch('GET', '/api/v1/items/42');

        $this->assertTrue($matched);
        $this->assertSame(['id' => '42'], $captured);
    }

    public function testReturnsFalseWhenNotMatched(): void
    {
        $router = new Router();
        $router->map('GET', '/api/v1/items/{id}', static function (): void {});

        $matched = $router->dispatch('POST', '/api/v1/items/42');

        $this->assertFalse($matched);
    }
}

<?php

namespace Libinkk\ApiStarter\Tests\Http\Middleware;

use Libinkk\ApiStarter\Facades\Api;
use Libinkk\ApiStarter\Http\Middleware\AssignRequestId;
use Libinkk\ApiStarter\Tests\TestCase;

class AssignRequestIdTest extends TestCase
{
    protected function defineRoutes($router): void
    {
        $router->get('/api/ping', function () {
            return Api::success(['pong' => true]);
        })->middleware(AssignRequestId::class);
    }

    public function test_middleware_assigns_request_id(): void
    {
        $response = $this->getJson('/api/ping');

        $response->assertOk();
        $this->assertNotEmpty($response->headers->get('X-Request-ID'));
        $this->assertSame(
            $response->headers->get('X-Request-ID'),
            $response->json('request_id')
        );
        $this->assertStringStartsWith('REQ-', (string) $response->json('request_id'));
    }

    public function test_middleware_keeps_incoming_request_id(): void
    {
        $response = $this->withHeader('X-Request-ID', 'REQ-CUSTOM01')
            ->getJson('/api/ping');

        $response->assertOk();
        $this->assertSame('REQ-CUSTOM01', $response->headers->get('X-Request-ID'));
        $this->assertSame('REQ-CUSTOM01', $response->json('request_id'));
    }
}

<?php

namespace Libinkk\ApiStarter\Tests\Http\Middleware;

use Libinkk\ApiStarter\Facades\Api;
use Libinkk\ApiStarter\Http\Middleware\MeasurePerformance;
use Libinkk\ApiStarter\Tests\TestCase;

class MeasurePerformanceTest extends TestCase
{
    protected function defineRoutes($router): void
    {
        $router->get('/api/perf', function () {
            usleep(20_000); // ~20ms

            return Api::success(['ok' => true], 'OK');
        })->middleware(MeasurePerformance::class);
    }

    public function test_response_includes_performance_header_and_body(): void
    {
        $response = $this->getJson('/api/perf');

        $response->assertOk();

        $header = $response->headers->get('X-Response-Time');
        $this->assertNotNull($header);
        $this->assertMatchesRegularExpression('/^\d+(\.\d+)?ms$/', (string) $header);

        $this->assertMatchesRegularExpression('/^\d+(\.\d+)?ms$/', (string) $response->json('response_time'));
        $this->assertIsNumeric($response->json('performance.duration_ms'));
        $this->assertGreaterThanOrEqual(15, (float) $response->json('performance.duration_ms'));
        $this->assertSame($response->json('response_time'), $response->json('performance.duration'));
    }

    public function test_performance_can_be_header_only(): void
    {
        config(['api-starter.performance.include_in_body' => false]);

        $response = $this->getJson('/api/perf');

        $response->assertOk();
        $this->assertNotNull($response->headers->get('X-Response-Time'));
        $this->assertArrayNotHasKey('response_time', $response->json());
        $this->assertArrayNotHasKey('performance', $response->json());
    }

    public function test_performance_feature_can_be_disabled(): void
    {
        config(['api-starter.features.performance' => false]);

        $response = $this->getJson('/api/perf');

        $response->assertOk();
        $this->assertNull($response->headers->get('X-Response-Time'));
        $this->assertArrayNotHasKey('response_time', $response->json());
    }
}

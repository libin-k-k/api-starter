<?php

namespace Libinkk\ApiStarter\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Symfony\Component\HttpFoundation\Response;

class MeasurePerformance
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! Config::get('api-starter.features.performance', true)) {
            return $next($request);
        }

        $startedAt = hrtime(true);
        $attribute = Config::get('api-starter.performance.attribute', 'api_response_time_ms');

        $request->attributes->set('api_performance_started_at', $startedAt);

        /** @var Response $response */
        $response = $next($request);

        $durationMs = $this->durationMs($startedAt);
        $formatted = $this->formatDuration($durationMs);

        $request->attributes->set($attribute, $durationMs);

        $header = Config::get('api-starter.performance.header', 'X-Response-Time');
        $response->headers->set($header, $formatted);

        if (Config::get('api-starter.performance.include_in_body', true)) {
            $this->injectIntoJsonBody($response, $durationMs, $formatted);
        }

        return $response;
    }

    protected function durationMs(int $startedAt): float
    {
        return (hrtime(true) - $startedAt) / 1_000_000;
    }

    protected function formatDuration(float $durationMs): string
    {
        $precision = (int) Config::get('api-starter.performance.precision', 2);

        return round($durationMs, $precision).'ms';
    }

    protected function injectIntoJsonBody(Response $response, float $durationMs, string $formatted): void
    {
        $content = $response->getContent();

        if (! is_string($content) || $content === '') {
            return;
        }

        $payload = json_decode($content, true);

        if (! is_array($payload) || json_last_error() !== JSON_ERROR_NONE) {
            return;
        }

        $precision = (int) Config::get('api-starter.performance.precision', 2);

        $payload['response_time'] = $formatted;
        $payload['performance'] = [
            'duration_ms' => round($durationMs, $precision),
            'duration' => $formatted,
        ];

        $encoded = json_encode(
            $payload,
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        );

        if ($encoded === false) {
            return;
        }

        $response->setContent($encoded);

        if ($response instanceof JsonResponse) {
            $response->setData($payload);
        }
    }
}

<?php

namespace Libinkk\ApiStarter\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class AssignRequestId
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! Config::get('api-starter.features.request_id', true)) {
            return $next($request);
        }

        $header = Config::get('api-starter.request_id.header', 'X-Request-ID');
        $attribute = Config::get('api-starter.request_id.attribute', 'api_request_id');
        $prefix = Config::get('api-starter.request_id.prefix', 'REQ-');

        $requestId = $request->headers->get($header);

        if (! is_string($requestId) || $requestId === '') {
            $requestId = $prefix.Str::upper(Str::random(8));
        }

        $request->attributes->set($attribute, $requestId);
        $request->headers->set($header, $requestId);

        /** @var Response $response */
        $response = $next($request);
        $response->headers->set($header, $requestId);

        return $response;
    }
}

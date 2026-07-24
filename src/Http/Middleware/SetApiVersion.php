<?php

namespace Libinkk\ApiStarter\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Libinkk\ApiStarter\Support\ErrorCode;
use Libinkk\ApiStarter\Facades\Api;
use Libinkk\ApiStarter\Versioning\ApiVersion;
use Symfony\Component\HttpFoundation\Response;

class SetApiVersion
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! Config::get('api-starter.features.versioning', true)) {
            return $next($request);
        }

        $version = $this->resolveVersion($request);
        $default = Config::get('api-starter.versioning.default', 'v1');

        if ($version === null || $version === '') {
            $version = $default;
        }

        if (! ApiVersion::isSupported($version)) {
            return Api::error(
                ErrorCode::message('UNSUPPORTED_API_VERSION', ['version' => $version], 'Unsupported API version.'),
                400,
                [],
                [],
                'UNSUPPORTED_API_VERSION'
            );
        }

        ApiVersion::set($version);

        $header = Config::get('api-starter.versioning.header', 'X-API-Version');

        /** @var Response $response */
        $response = $next($request);
        $response->headers->set($header, $version);

        return $response;
    }

    protected function resolveVersion(Request $request): ?string
    {
        $header = Config::get('api-starter.versioning.header', 'X-API-Version');
        $queryParam = Config::get('api-starter.versioning.query_parameter', 'api_version');

        if ($request->headers->has($header)) {
            return $this->normalize((string) $request->headers->get($header));
        }

        if ($request->filled($queryParam)) {
            return $this->normalize((string) $request->input($queryParam));
        }

        if (preg_match('#(?:^|/)api/(v\d+)(?:/|$)#i', $request->path(), $matches) === 1) {
            return $this->normalize($matches[1]);
        }

        return null;
    }

    protected function normalize(string $version): string
    {
        $version = trim($version);

        if ($version !== '' && ! str_starts_with(strtolower($version), 'v')) {
            $version = 'v'.$version;
        }

        return strtolower($version);
    }
}

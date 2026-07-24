<?php

namespace Libinkk\ApiStarter\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! Config::get('api-starter.features.localization', true)) {
            return $next($request);
        }

        $locale = $this->resolveLocale($request);
        $supported = (array) Config::get('api-starter.localization.supported', ['en']);
        $fallback = Config::get('api-starter.localization.fallback', 'en');

        if ($locale === null || ! in_array($locale, $supported, true)) {
            $locale = $fallback;
        }

        App::setLocale($locale);

        $header = Config::get('api-starter.localization.header', 'X-Locale');

        /** @var Response $response */
        $response = $next($request);
        $response->headers->set($header, $locale);

        return $response;
    }

    protected function resolveLocale(Request $request): ?string
    {
        $header = Config::get('api-starter.localization.header', 'X-Locale');
        $queryParam = Config::get('api-starter.localization.query_parameter', 'lang');

        if ($request->headers->has($header)) {
            return $this->normalize((string) $request->headers->get($header));
        }

        if ($request->filled($queryParam)) {
            return $this->normalize((string) $request->input($queryParam));
        }

        $accept = $request->header('Accept-Language');

        if (is_string($accept) && $accept !== '') {
            $primary = strtolower(trim(explode(',', $accept)[0]));
            $primary = explode(';', $primary)[0];

            return $this->normalize($primary);
        }

        return null;
    }

    protected function normalize(string $locale): string
    {
        $locale = strtolower(str_replace('_', '-', trim($locale)));

        if (str_contains($locale, '-')) {
            return explode('-', $locale)[0];
        }

        return $locale;
    }
}

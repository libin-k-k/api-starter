<?php

namespace Libinkk\ApiStarter\Support;

class ErrorCode
{
    public const VALIDATION_FAILED = 'VALIDATION_FAILED';

    public const MODEL_NOT_FOUND = 'MODEL_NOT_FOUND';

    public const UNAUTHENTICATED = 'UNAUTHENTICATED';

    public const FORBIDDEN = 'FORBIDDEN';

    public const ROUTE_NOT_FOUND = 'ROUTE_NOT_FOUND';

    public const QUERY_EXCEPTION = 'QUERY_EXCEPTION';

    public const HTTP_EXCEPTION = 'HTTP_EXCEPTION';

    public const SERVER_ERROR = 'SERVER_ERROR';

    /**
     * Resolve a localized message for an error code.
     *
     * @param  array<string, mixed>  $replace
     */
    public static function message(string $code, array $replace = [], ?string $fallback = null): string
    {
        $key = 'api-starter::errors.'.$code;
        $translated = __($key, $replace);

        if ($translated !== $key) {
            return $translated;
        }

        $configured = config('api-starter.error_codes.messages.'.$code);

        if (is_string($configured) && $configured !== '') {
            return $configured;
        }

        return $fallback ?? $code;
    }

    /**
     * @return list<string>
     */
    public static function all(): array
    {
        return array_values(array_unique(array_merge(
            [
                self::VALIDATION_FAILED,
                self::MODEL_NOT_FOUND,
                self::UNAUTHENTICATED,
                self::FORBIDDEN,
                self::ROUTE_NOT_FOUND,
                self::QUERY_EXCEPTION,
                self::HTTP_EXCEPTION,
                self::SERVER_ERROR,
            ],
            array_keys((array) config('api-starter.error_codes.messages', []))
        )));
    }
}

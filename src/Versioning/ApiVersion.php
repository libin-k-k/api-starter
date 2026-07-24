<?php

namespace Libinkk\ApiStarter\Versioning;

use Illuminate\Support\Facades\Config;

class ApiVersion
{
    public const ATTRIBUTE = 'api_version';

    public static function current(): ?string
    {
        if (app()->bound('request')) {
            $fromRequest = request()->attributes->get(self::ATTRIBUTE);

            if (is_string($fromRequest) && $fromRequest !== '') {
                return $fromRequest;
            }
        }

        return Config::get('api-starter.versioning.default', 'v1');
    }

    public static function set(string $version): void
    {
        if (app()->bound('request')) {
            request()->attributes->set(self::ATTRIBUTE, $version);
        }
    }

    /**
     * @return list<string>
     */
    public static function supported(): array
    {
        return array_values((array) Config::get('api-starter.versioning.supported', ['v1']));
    }

    public static function isSupported(string $version): bool
    {
        return in_array($version, self::supported(), true);
    }
}

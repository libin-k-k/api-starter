<?php

namespace Libinkk\ApiStarter;

final class Version
{
    public const VERSION = '1.0.0';

    public static function current(): string
    {
        return self::VERSION;
    }
}

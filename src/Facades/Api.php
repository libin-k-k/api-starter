<?php

namespace Libinkk\ApiStarter\Facades;

use Illuminate\Support\Facades\Facade;
use Libinkk\ApiStarter\Contracts\ApiResponderInterface;

/**
 * @method static \Illuminate\Http\JsonResponse success(mixed $data = null, ?string $message = null, int $status = 200, array $meta = [], array $links = [])
 * @method static \Illuminate\Http\JsonResponse created(mixed $data = null, ?string $message = null, array $meta = [])
 * @method static \Illuminate\Http\JsonResponse updated(mixed $data = null, ?string $message = null, array $meta = [])
 * @method static \Illuminate\Http\JsonResponse deleted(?string $message = null, array $meta = [])
 * @method static \Illuminate\Http\JsonResponse error(string $message, int $status = 400, array $errors = [], array $meta = [], ?string $errorCode = null)
 * @method static \Illuminate\Http\JsonResponse validation(array $errors, ?string $message = null, int $status = 422, array $meta = [])
 *
 * @see \Libinkk\ApiStarter\Services\ApiResponder
 */
class Api extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return ApiResponderInterface::class;
    }
}

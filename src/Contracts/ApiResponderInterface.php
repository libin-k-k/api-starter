<?php

namespace Libinkk\ApiStarter\Contracts;

use Illuminate\Http\JsonResponse;

interface ApiResponderInterface
{
    public function success(
        mixed $data = null,
        ?string $message = null,
        int $status = 200,
        array $meta = [],
        array $links = []
    ): JsonResponse;

    public function created(mixed $data = null, ?string $message = null, array $meta = []): JsonResponse;

    public function updated(mixed $data = null, ?string $message = null, array $meta = []): JsonResponse;

    public function deleted(?string $message = null, array $meta = []): JsonResponse;

    public function error(
        string $message,
        int $status = 400,
        array $errors = [],
        array $meta = [],
        ?string $errorCode = null
    ): JsonResponse;

    public function validation(
        array $errors,
        ?string $message = null,
        int $status = 422,
        array $meta = []
    ): JsonResponse;
}

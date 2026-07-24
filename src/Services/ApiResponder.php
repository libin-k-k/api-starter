<?php

namespace Libinkk\ApiStarter\Services;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Config;
use Libinkk\ApiStarter\Contracts\ApiResponderInterface;
use Libinkk\ApiStarter\Http\Responses\ApiResponse;
use Libinkk\ApiStarter\Support\ErrorCode;

class ApiResponder implements ApiResponderInterface
{
    public function success(
        mixed $data = null,
        ?string $message = null,
        int $status = 200,
        array $meta = [],
        array $links = []
    ): JsonResponse {
        return ApiResponse::make()
            ->success(true)
            ->status($status)
            ->message($message ?? $this->defaultMessage('success'))
            ->data($data)
            ->meta($meta)
            ->links($links)
            ->toResponse();
    }

    public function created(mixed $data = null, ?string $message = null, array $meta = []): JsonResponse
    {
        return $this->success(
            $data,
            $message ?? $this->defaultMessage('created'),
            201,
            $meta
        );
    }

    public function updated(mixed $data = null, ?string $message = null, array $meta = []): JsonResponse
    {
        return $this->success(
            $data,
            $message ?? $this->defaultMessage('updated'),
            200,
            $meta
        );
    }

    public function deleted(?string $message = null, array $meta = []): JsonResponse
    {
        return ApiResponse::make()
            ->success(true)
            ->status(200)
            ->message($message ?? $this->defaultMessage('deleted'))
            ->data(null)
            ->meta($meta)
            ->toResponse();
    }

    public function error(
        string $message,
        int $status = 400,
        array $errors = [],
        array $meta = [],
        ?string $errorCode = null
    ): JsonResponse {
        return ApiResponse::make()
            ->success(false)
            ->status($status)
            ->message($message !== '' ? $message : $this->defaultMessage('error'))
            ->data(null)
            ->errors($errors)
            ->meta($meta)
            ->errorCode($errorCode)
            ->toResponse();
    }

    public function validation(
        array $errors,
        ?string $message = null,
        int $status = 422,
        array $meta = []
    ): JsonResponse {
        return $this->error(
            $message ?? $this->defaultMessage('validation'),
            $status,
            $errors,
            $meta,
            ErrorCode::VALIDATION_FAILED
        );
    }

    protected function defaultMessage(string $key): string
    {
        if (Config::get('api-starter.features.localization', true)) {
            $translated = __('api-starter::messages.'.$key);

            if ($translated !== 'api-starter::messages.'.$key) {
                return $translated;
            }
        }

        return (string) Config::get("api-starter.response.default_messages.{$key}", 'Success');
    }
}

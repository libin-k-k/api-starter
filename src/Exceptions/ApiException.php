<?php

namespace Libinkk\ApiStarter\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;
use Libinkk\ApiStarter\Facades\Api;

class ApiException extends Exception
{
    protected int $status;

    protected array $errors;

    protected ?string $errorCode;

    protected array $meta;

    public function __construct(
        string $message = 'An error occurred',
        int $status = 400,
        array $errors = [],
        ?string $errorCode = null,
        array $meta = [],
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $status, $previous);

        $this->status = $status;
        $this->errors = $errors;
        $this->errorCode = $errorCode;
        $this->meta = $meta;
    }

    public function getStatus(): int
    {
        return $this->status;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function getErrorCode(): ?string
    {
        return $this->errorCode;
    }

    public function getMeta(): array
    {
        return $this->meta;
    }

    public function render(): JsonResponse
    {
        return Api::error(
            $this->getMessage(),
            $this->getStatus(),
            $this->getErrors(),
            $this->getMeta(),
            $this->getErrorCode()
        );
    }
}

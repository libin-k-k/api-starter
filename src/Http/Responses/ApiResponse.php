<?php

namespace Libinkk\ApiStarter\Http\Responses;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Pagination\AbstractPaginator;
use Illuminate\Support\Facades\Config;

class ApiResponse
{
    protected bool $success = true;

    protected int $status = 200;

    protected ?string $message = null;

    protected mixed $data = null;

    protected array $meta = [];

    protected array $errors = [];

    protected array $links = [];

    protected ?string $errorCode = null;

    public static function make(): self
    {
        return new self;
    }

    public function success(bool $success = true): self
    {
        $this->success = $success;

        return $this;
    }

    public function status(int $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function message(?string $message): self
    {
        $this->message = $message;

        return $this;
    }

    public function data(mixed $data): self
    {
        $this->data = $data;

        return $this;
    }

    public function meta(array $meta): self
    {
        $this->meta = array_merge($this->meta, $meta);

        return $this;
    }

    public function errors(array $errors): self
    {
        $this->errors = $errors;

        return $this;
    }

    public function links(array $links): self
    {
        $this->links = array_merge($this->links, $links);

        return $this;
    }

    public function errorCode(?string $errorCode): self
    {
        $this->errorCode = $errorCode;

        return $this;
    }

    public function toArray(): array
    {
        [$data, $meta, $links] = $this->normalizePayload($this->data);

        $payload = [
            'success' => $this->success,
            'status' => $this->status,
            'message' => $this->message,
            'data' => $data,
        ];

        if (Config::get('api-starter.response.include_meta', true)) {
            $payload['meta'] = array_merge($meta, $this->meta);
        }

        if (Config::get('api-starter.response.include_errors', true)) {
            $payload['errors'] = $this->errors;
        }

        if (Config::get('api-starter.response.include_links', true)) {
            $payload['links'] = array_merge($links, $this->links);
        }

        if (Config::get('api-starter.response.include_request_id', true)) {
            $payload['request_id'] = $this->resolveRequestId();
        }

        if (Config::get('api-starter.response.include_timestamp', false)
            || Config::get('api-starter.features.timestamp', false)) {
            $payload['timestamp'] = now()->toIso8601String();
        }

        if ($this->errorCode !== null && Config::get('api-starter.features.error_codes', true)) {
            $payload['error_code'] = $this->errorCode;
        }

        return $payload;
    }

    public function toResponse(): JsonResponse
    {
        return response()->json($this->toArray(), $this->status);
    }

    /**
     * @return array{0: mixed, 1: array, 2: array}
     */
    protected function normalizePayload(mixed $data): array
    {
        $meta = [];
        $links = [];

        if ($data instanceof ResourceCollection && $data->resource instanceof AbstractPaginator) {
            $paginator = $data->resource;
            $meta = $this->paginatorMeta($paginator);
            $links = $this->paginatorLinks($paginator);

            return [$data->resolve(), $meta, $links];
        }

        if ($data instanceof JsonResource) {
            return [$data->resolve(), $meta, $links];
        }

        if ($data instanceof AbstractPaginator) {
            $meta = $this->paginatorMeta($data);
            $links = $this->paginatorLinks($data);

            return [$data->items(), $meta, $links];
        }

        return [$data, $meta, $links];
    }

    protected function paginatorMeta(AbstractPaginator $paginator): array
    {
        return [
            'current_page' => $paginator->currentPage(),
            'last_page' => method_exists($paginator, 'lastPage') ? $paginator->lastPage() : null,
            'per_page' => $paginator->perPage(),
            'total' => method_exists($paginator, 'total') ? $paginator->total() : null,
        ];
    }

    protected function paginatorLinks(AbstractPaginator $paginator): array
    {
        return [
            'first' => method_exists($paginator, 'url') ? $paginator->url(1) : null,
            'last' => method_exists($paginator, 'url') && method_exists($paginator, 'lastPage')
                ? $paginator->url($paginator->lastPage())
                : null,
            'prev' => $paginator->previousPageUrl(),
            'next' => $paginator->nextPageUrl(),
        ];
    }

    protected function resolveRequestId(): ?string
    {
        if (! Config::get('api-starter.features.request_id', true)) {
            return null;
        }

        $attribute = Config::get('api-starter.request_id.attribute', 'api_request_id');
        $header = Config::get('api-starter.request_id.header', 'X-Request-ID');

        if (app()->bound('request')) {
            $request = request();

            return $request->attributes->get($attribute)
                ?? $request->headers->get($header);
        }

        return null;
    }
}

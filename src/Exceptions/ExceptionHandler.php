<?php

namespace Libinkk\ApiStarter\Exceptions;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Validation\ValidationException;
use Libinkk\ApiStarter\Contracts\ApiResponderInterface;
use Libinkk\ApiStarter\Support\ErrorCode;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

class ExceptionHandler
{
    public function __construct(
        protected ApiResponderInterface $responder
    ) {}

    public function shouldHandle(Request $request): bool
    {
        if (! Config::get('api-starter.features.exceptions', true)) {
            return false;
        }

        return $request->expectsJson()
            || $request->is('api/*')
            || $request->is('api');
    }

    public function handle(Throwable $e, Request $request): ?JsonResponse
    {
        if (! $this->shouldHandle($request)) {
            return null;
        }

        return match (true) {
            $e instanceof ApiException => $e->render(),
            $e instanceof ValidationException => $this->validation($e),
            $e instanceof ModelNotFoundException => $this->modelNotFound($e),
            $e instanceof AuthenticationException => $this->unauthenticated($e),
            $e instanceof AuthorizationException => $this->forbidden($e),
            $e instanceof NotFoundHttpException => $this->notFound($e),
            $e instanceof AccessDeniedHttpException => $this->accessDenied($e),
            $e instanceof QueryException => $this->query($e),
            $e instanceof HttpExceptionInterface => $this->http($e),
            default => $this->generic($e),
        };
    }

    protected function validation(ValidationException $e): JsonResponse
    {
        return $this->responder->validation(
            $e->errors(),
            null,
            $e->status
        );
    }

    protected function modelNotFound(ModelNotFoundException $e): JsonResponse
    {
        $model = class_basename($e->getModel() ?: 'Resource');

        return $this->responder->error(
            ErrorCode::message(ErrorCode::MODEL_NOT_FOUND, ['model' => $model], "{$model} not found."),
            404,
            [],
            [],
            ErrorCode::MODEL_NOT_FOUND
        );
    }

    protected function unauthenticated(AuthenticationException $e): JsonResponse
    {
        return $this->responder->error(
            ErrorCode::message(ErrorCode::UNAUTHENTICATED, [], $e->getMessage() ?: 'Unauthenticated.'),
            401,
            [],
            [],
            ErrorCode::UNAUTHENTICATED
        );
    }

    protected function forbidden(AuthorizationException $e): JsonResponse
    {
        return $this->responder->error(
            ErrorCode::message(ErrorCode::FORBIDDEN, [], $e->getMessage() ?: 'This action is unauthorized.'),
            403,
            [],
            [],
            ErrorCode::FORBIDDEN
        );
    }

    protected function accessDenied(AccessDeniedHttpException $e): JsonResponse
    {
        $previous = $e->getPrevious();

        if ($previous instanceof AuthorizationException) {
            return $this->forbidden($previous);
        }

        return $this->responder->error(
            ErrorCode::message(ErrorCode::FORBIDDEN, [], $e->getMessage() ?: 'This action is unauthorized.'),
            403,
            [],
            [],
            ErrorCode::FORBIDDEN
        );
    }

    protected function notFound(NotFoundHttpException $e): JsonResponse
    {
        $previous = $e->getPrevious();

        if ($previous instanceof ModelNotFoundException) {
            return $this->modelNotFound($previous);
        }

        return $this->responder->error(
            ErrorCode::message(ErrorCode::ROUTE_NOT_FOUND, [], $e->getMessage() ?: 'Route not found.'),
            404,
            [],
            [],
            ErrorCode::ROUTE_NOT_FOUND
        );
    }

    protected function query(QueryException $e): JsonResponse
    {
        $message = Config::get('api-starter.debug', false)
            ? $e->getMessage()
            : ErrorCode::message(ErrorCode::QUERY_EXCEPTION, [], 'A database error occurred.');

        return $this->responder->error(
            $message,
            500,
            [],
            Config::get('api-starter.debug', false) ? ['sql_state' => $e->errorInfo[0] ?? null] : [],
            ErrorCode::QUERY_EXCEPTION
        );
    }

    protected function http(HttpExceptionInterface $e): JsonResponse
    {
        $message = $e->getMessage() !== ''
            ? $e->getMessage()
            : ErrorCode::message(ErrorCode::HTTP_EXCEPTION, [], 'HTTP error.');

        return $this->responder->error(
            $message,
            $e->getStatusCode(),
            [],
            [],
            ErrorCode::HTTP_EXCEPTION
        );
    }

    protected function generic(Throwable $e): JsonResponse
    {
        $debug = (bool) Config::get('api-starter.debug', false);
        $message = $debug
            ? $e->getMessage()
            : ErrorCode::message(ErrorCode::SERVER_ERROR, [], 'Server Error');
        $meta = $debug ? [
            'exception' => class_basename($e),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
        ] : [];

        return $this->responder->error(
            $message !== '' ? $message : ErrorCode::message(ErrorCode::SERVER_ERROR, [], 'Server Error'),
            500,
            [],
            $meta,
            ErrorCode::SERVER_ERROR
        );
    }
}

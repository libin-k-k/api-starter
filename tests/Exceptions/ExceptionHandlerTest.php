<?php

namespace Libinkk\ApiStarter\Tests\Exceptions;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;
use Libinkk\ApiStarter\Exceptions\ApiException;
use Libinkk\ApiStarter\Tests\TestCase;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ExceptionHandlerTest extends TestCase
{
    protected function defineRoutes($router): void
    {
        $router->get('/api/validation', function () {
            throw ValidationException::withMessages([
                'email' => ['The email field is required.'],
            ]);
        });

        $router->get('/api/not-found-model', function () {
            throw (new ModelNotFoundException)->setModel('App\\Models\\User');
        });

        $router->get('/api/unauthenticated', function () {
            throw new AuthenticationException('Unauthenticated.');
        });

        $router->get('/api/forbidden', function () {
            throw new AuthorizationException('This action is unauthorized.');
        });

        $router->get('/api/route-missing', function () {
            throw new NotFoundHttpException('Route not found.');
        });

        $router->get('/api/custom', function () {
            throw new ApiException('Payment required', 402, [], 'PAYMENT_REQUIRED');
        });
    }

    public function test_validation_exception_uses_package_envelope(): void
    {
        $response = $this->getJson('/api/validation');

        $response->assertStatus(422);
        $response->assertJson([
            'success' => false,
            'status' => 422,
            'message' => 'Validation failed',
            'error_code' => 'VALIDATION_FAILED',
            'errors' => [
                'email' => ['The email field is required.'],
            ],
        ]);
    }

    public function test_model_not_found_exception(): void
    {
        $response = $this->getJson('/api/not-found-model');

        $response->assertNotFound();
        $response->assertJson([
            'success' => false,
            'status' => 404,
            'error_code' => 'MODEL_NOT_FOUND',
        ]);
        $this->assertStringContainsString('User', $response->json('message'));
    }

    public function test_authentication_exception(): void
    {
        $response = $this->getJson('/api/unauthenticated');

        $response->assertUnauthorized();
        $response->assertJson([
            'success' => false,
            'status' => 401,
            'error_code' => 'UNAUTHENTICATED',
        ]);
    }

    public function test_authorization_exception(): void
    {
        $response = $this->getJson('/api/forbidden');

        $response->assertForbidden();
        $response->assertJson([
            'success' => false,
            'status' => 403,
            'error_code' => 'FORBIDDEN',
        ]);
    }

    public function test_not_found_http_exception(): void
    {
        $response = $this->getJson('/api/route-missing');

        $response->assertNotFound();
        $response->assertJson([
            'success' => false,
            'error_code' => 'ROUTE_NOT_FOUND',
        ]);
    }

    public function test_custom_api_exception(): void
    {
        $response = $this->getJson('/api/custom');

        $response->assertStatus(402);
        $response->assertJson([
            'success' => false,
            'message' => 'Payment required',
            'error_code' => 'PAYMENT_REQUIRED',
        ]);
    }
}

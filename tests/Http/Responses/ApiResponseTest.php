<?php

namespace Libinkk\ApiStarter\Tests\Http\Responses;

use Illuminate\Testing\TestResponse;
use Libinkk\ApiStarter\Facades\Api;
use Libinkk\ApiStarter\Tests\TestCase;

class ApiResponseTest extends TestCase
{
    protected function apiResponse($response): TestResponse
    {
        return TestResponse::fromBaseResponse($response);
    }

    public function test_success_response_envelope(): void
    {
        $response = $this->apiResponse(
            Api::success(['id' => 1, 'name' => 'Libin'], 'User fetched successfully')
        );

        $response->assertOk();
        $response->assertExactJson([
            'success' => true,
            'status' => 200,
            'message' => 'User fetched successfully',
            'data' => ['id' => 1, 'name' => 'Libin'],
            'meta' => [],
            'errors' => [],
            'links' => [],
            'request_id' => null,
        ]);
    }

    public function test_created_response(): void
    {
        $response = $this->apiResponse(Api::created(['id' => 2]));

        $response->assertCreated();
        $this->assertTrue($response->json('success'));
        $this->assertSame(201, $response->json('status'));
        $this->assertSame('Resource created successfully', $response->json('message'));
    }

    public function test_updated_response(): void
    {
        $response = $this->apiResponse(
            Api::updated(['id' => 2], 'User updated successfully')
        );

        $response->assertOk();
        $this->assertSame('User updated successfully', $response->json('message'));
        $this->assertSame(['id' => 2], $response->json('data'));
    }

    public function test_deleted_response(): void
    {
        $response = $this->apiResponse(Api::deleted());

        $response->assertOk();
        $this->assertTrue($response->json('success'));
        $this->assertNull($response->json('data'));
        $this->assertSame('Resource deleted successfully', $response->json('message'));
    }

    public function test_error_response(): void
    {
        $response = $this->apiResponse(
            Api::error('Something went wrong', 400, [], [], 'ERR_001')
        );

        $response->assertStatus(400);
        $response->assertJson([
            'success' => false,
            'status' => 400,
            'message' => 'Something went wrong',
            'data' => null,
            'errors' => [],
            'error_code' => 'ERR_001',
        ]);
    }

    public function test_validation_response(): void
    {
        $errors = [
            'email' => ['The email field is required.'],
        ];

        $response = $this->apiResponse(Api::validation($errors));

        $response->assertStatus(422);
        $response->assertJson([
            'success' => false,
            'status' => 422,
            'message' => 'Validation failed',
            'errors' => $errors,
            'error_code' => 'VALIDATION_FAILED',
        ]);
    }

    public function test_request_id_from_header(): void
    {
        $this->app['request']->headers->set('X-Request-ID', 'REQ-TEST123');

        $response = $this->apiResponse(Api::success(['ok' => true]));

        $this->assertSame('REQ-TEST123', $response->json('request_id'));
    }

    public function test_timestamp_when_enabled(): void
    {
        config(['api-starter.response.include_timestamp' => true]);

        $response = $this->apiResponse(Api::success());

        $this->assertNotEmpty($response->json('timestamp'));
    }
}

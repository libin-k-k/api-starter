<?php

namespace Libinkk\ApiStarter\Tests\Http\Middleware;

use Illuminate\Support\Facades\App;
use Libinkk\ApiStarter\Http\Middleware\SetApiVersion;
use Libinkk\ApiStarter\Http\Middleware\SetLocale;
use Libinkk\ApiStarter\Support\ErrorCode;
use Libinkk\ApiStarter\Tests\TestCase;
use Libinkk\ApiStarter\Versioning\ApiVersion;

class LocaleAndVersionTest extends TestCase
{
    protected function defineRoutes($router): void
    {
        $router->get('/api/locale-version', function () {
            return response()->json([
                'locale' => App::getLocale(),
                'version' => ApiVersion::current(),
                'message' => __('api-starter::messages.success'),
                'error' => ErrorCode::message(ErrorCode::FORBIDDEN),
            ]);
        })->middleware([SetLocale::class, SetApiVersion::class]);

        $router->get('/api/v2/ping', function () {
            return response()->json([
                'version' => ApiVersion::current(),
            ]);
        })->middleware([SetApiVersion::class]);
    }

    public function test_locale_middleware_sets_tamil(): void
    {
        $response = $this->withHeader('X-Locale', 'ta')
            ->getJson('/api/locale-version');

        $response->assertOk();
        $this->assertSame('ta', $response->json('locale'));
        $this->assertSame('ta', $response->headers->get('X-Locale'));
        $this->assertSame('வெற்றி', $response->json('message'));
    }

    public function test_locale_from_query_parameter(): void
    {
        $response = $this->getJson('/api/locale-version?lang=hi');

        $response->assertOk();
        $this->assertSame('hi', $response->json('locale'));
        $this->assertSame('सफल', $response->json('message'));
    }

    public function test_api_version_from_header(): void
    {
        $response = $this->withHeader('X-API-Version', 'v2')
            ->getJson('/api/locale-version');

        $response->assertOk();
        $this->assertSame('v2', $response->json('version'));
        $this->assertSame('v2', $response->headers->get('X-API-Version'));
    }

    public function test_api_version_from_path(): void
    {
        $response = $this->getJson('/api/v2/ping');

        $response->assertOk();
        $this->assertSame('v2', $response->json('version'));
    }

    public function test_unsupported_api_version_returns_error(): void
    {
        $response = $this->withHeader('X-API-Version', 'v9')
            ->getJson('/api/locale-version');

        $response->assertStatus(400);
        $response->assertJson([
            'success' => false,
            'error_code' => 'UNSUPPORTED_API_VERSION',
        ]);
    }

    public function test_error_code_localization(): void
    {
        App::setLocale('ml');

        $this->assertSame('ഈ പ്രവൃത്തി അനുവദനീയമല്ല.', ErrorCode::message(ErrorCode::FORBIDDEN));
    }

    public function test_new_european_locales(): void
    {
        App::setLocale('de');
        $this->assertSame('Erfolg', __('api-starter::messages.success'));

        App::setLocale('it');
        $this->assertSame('Successo', __('api-starter::messages.success'));

        App::setLocale('es');
        $this->assertSame('Éxito', __('api-starter::messages.success'));

        App::setLocale('nl');
        $this->assertSame('Geslaagd', __('api-starter::messages.success'));
    }
}

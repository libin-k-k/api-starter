<?php

namespace Libinkk\ApiStarter\Tests\Commands;

use Illuminate\Support\Facades\File;
use Libinkk\ApiStarter\Tests\TestCase;

class ArtisanCommandsTest extends TestCase
{
    public function test_doctor_command_runs(): void
    {
        $this->artisan('api-starter:doctor')
            ->expectsOutputToContain('API Starter Doctor')
            ->assertSuccessful();
    }

    public function test_publish_command_publishes_config(): void
    {
        $this->artisan('api-starter:publish', ['--tag' => 'api-starter-config'])
            ->assertSuccessful();

        $this->assertTrue(File::exists(config_path('api-starter.php')));
    }

    public function test_make_filter_command_creates_class(): void
    {
        $path = app_path('Filters/StatusFilter.php');

        if (File::exists($path)) {
            File::delete($path);
        }

        $this->artisan('api-starter:make-filter', ['name' => 'StatusFilter'])
            ->assertSuccessful();

        $this->assertTrue(File::exists($path));
        $this->assertStringContainsString('implements Filter', File::get($path));

        File::delete($path);
    }

    public function test_make_sort_command_creates_class(): void
    {
        $path = app_path('Sorts/NameSort.php');

        if (File::exists($path)) {
            File::delete($path);
        }

        $this->artisan('api-starter:make-sort', ['name' => 'NameSort'])
            ->assertSuccessful();

        $this->assertTrue(File::exists($path));
        File::delete($path);
    }

    public function test_make_transformer_command_creates_class(): void
    {
        $path = app_path('Transformers/UserTransformer.php');

        if (File::exists($path)) {
            File::delete($path);
        }

        $this->artisan('api-starter:make-transformer', ['name' => 'UserTransformer'])
            ->assertSuccessful();

        $this->assertTrue(File::exists($path));
        File::delete($path);
    }

    public function test_install_command_runs(): void
    {
        $this->artisan('api-starter:install')
            ->expectsOutputToContain('API Starter installed.')
            ->assertSuccessful();
    }
}

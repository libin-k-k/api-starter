<?php

namespace Libinkk\ApiStarter\Tests;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Libinkk\ApiStarter\Providers\ApiStarterServiceProvider;
use Libinkk\ApiStarter\Tests\Models\TestUser;
use Orchestra\Testbench\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function getPackageProviders($app): array
    {
        return [
            ApiStarterServiceProvider::class,
        ];
    }

    protected function getPackageAliases($app): array
    {
        return [
            'Api' => \Libinkk\ApiStarter\Facades\Api::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        $app['config']->set('api-starter.features.response', true);
        $app['config']->set('api-starter.response.include_timestamp', false);
    }

    protected function defineDatabaseMigrations(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('status')->default('active');
            $table->boolean('is_active')->default(true);
            $table->decimal('price', 8, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id');
            $table->string('title');
        });
    }

    protected function seedUsers(): void
    {
        TestUser::query()->insert([
            [
                'name' => 'Alice Smith',
                'email' => 'alice@example.com',
                'status' => 'active',
                'is_active' => 1,
                'price' => 10,
                'created_at' => '2024-01-10 10:00:00',
                'updated_at' => '2024-01-10 10:00:00',
            ],
            [
                'name' => 'Bob Jones',
                'email' => 'bob@example.com',
                'status' => 'inactive',
                'is_active' => 0,
                'price' => 50,
                'created_at' => '2024-02-15 10:00:00',
                'updated_at' => '2024-02-15 10:00:00',
            ],
            [
                'name' => 'Charlie John',
                'email' => 'charlie@example.com',
                'status' => 'active',
                'is_active' => 1,
                'price' => 100,
                'created_at' => '2024-03-20 10:00:00',
                'updated_at' => '2024-03-20 10:00:00',
            ],
            [
                'name' => 'Dana Lee',
                'email' => 'dana@example.com',
                'status' => 'pending',
                'is_active' => 1,
                'price' => 75,
                'created_at' => '2024-04-01 10:00:00',
                'updated_at' => '2024-04-01 10:00:00',
            ],
        ]);
    }
}

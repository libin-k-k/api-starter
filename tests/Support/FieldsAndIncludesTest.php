<?php

namespace Libinkk\ApiStarter\Tests\Support;

use Libinkk\ApiStarter\Support\ApiQuery;
use Libinkk\ApiStarter\Tests\Models\TestPost;
use Libinkk\ApiStarter\Tests\Models\TestUser;
use Libinkk\ApiStarter\Tests\TestCase;

class FieldsAndIncludesTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->seedUsers();

        TestPost::query()->insert([
            ['user_id' => 1, 'title' => 'Alice Post'],
            ['user_id' => 2, 'title' => 'Bob Post'],
        ]);
    }

    public function test_sparse_field_selection(): void
    {
        $request = request()->merge(['fields' => 'name,email']);

        $user = ApiQuery::for(TestUser::class, $request)
            ->allowedFields(['id', 'name', 'email', 'status'])
            ->first();

        $this->assertNotNull($user);
        $this->assertSame(['id', 'name', 'email'], array_keys($user->getAttributes()));
    }

    public function test_disallowed_fields_are_ignored(): void
    {
        $request = request()->merge(['fields' => 'password,name']);

        $user = ApiQuery::for(TestUser::class, $request)
            ->allowedFields(['id', 'name', 'email'])
            ->first();

        $this->assertArrayHasKey('name', $user->getAttributes());
        $this->assertArrayNotHasKey('password', $user->getAttributes());
    }

    public function test_includes_eager_load_allowed_relations(): void
    {
        $request = request()->merge(['include' => 'posts']);

        $user = ApiQuery::for(TestUser::class, $request)
            ->allowedIncludes(['posts'])
            ->get()
            ->firstWhere('id', 1);

        $this->assertTrue($user->relationLoaded('posts'));
        $this->assertCount(1, $user->posts);
        $this->assertSame('Alice Post', $user->posts->first()->title);
    }

    public function test_disallowed_includes_are_ignored(): void
    {
        $request = request()->merge(['include' => 'posts,secret']);

        $user = ApiQuery::for(TestUser::class, $request)
            ->allowedIncludes(['posts'])
            ->first();

        $this->assertTrue($user->relationLoaded('posts'));
        $this->assertFalse($user->relationLoaded('secret'));
    }
}

<?php

namespace Libinkk\ApiStarter\Tests\Support;

use Libinkk\ApiStarter\Facades\Api;
use Libinkk\ApiStarter\Filters\AllowedFilter;
use Libinkk\ApiStarter\Sorts\AllowedSort;
use Libinkk\ApiStarter\Support\ApiQuery;
use Libinkk\ApiStarter\Tests\Models\TestUser;
use Libinkk\ApiStarter\Tests\TestCase;

class ApiQueryTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->seedUsers();
    }

    public function test_exact_filter(): void
    {
        $request = request()->merge(['status' => 'active']);

        $results = ApiQuery::for(TestUser::class, $request)
            ->allowedFilters(['status'])
            ->get();

        $this->assertCount(2, $results);
        $this->assertTrue($results->every(fn (TestUser $user) => $user->status === 'active'));
    }

    public function test_partial_filter(): void
    {
        $request = request()->merge(['name' => 'John']);

        $results = ApiQuery::for(TestUser::class, $request)
            ->allowedFilters([AllowedFilter::partial('name')])
            ->get();

        $this->assertCount(1, $results);
        $this->assertSame('Charlie John', $results->first()->name);
    }

    public function test_boolean_filter(): void
    {
        $request = request()->merge(['is_active' => 'false']);

        $results = ApiQuery::for(TestUser::class, $request)
            ->allowedFilters([AllowedFilter::boolean('is_active')])
            ->get();

        $this->assertCount(1, $results);
        $this->assertSame('Bob Jones', $results->first()->name);
    }

    public function test_between_filter(): void
    {
        $request = request()->merge(['price' => '40,90']);

        $results = ApiQuery::for(TestUser::class, $request)
            ->allowedFilters([AllowedFilter::between('price')])
            ->get();

        $this->assertCount(2, $results);
        $this->assertEqualsCanonicalizing(['Bob Jones', 'Dana Lee'], $results->pluck('name')->all());
    }

    public function test_date_range_filter_with_from_to_params(): void
    {
        $request = request()->merge([
            'created_at_from' => '2024-02-01',
            'created_at_to' => '2024-03-31',
        ]);

        $results = ApiQuery::for(TestUser::class, $request)
            ->allowedFilters([AllowedFilter::dateRange('created_at')])
            ->get();

        $this->assertCount(2, $results);
        $this->assertEqualsCanonicalizing(['Bob Jones', 'Charlie John'], $results->pluck('name')->all());
    }

    public function test_callback_filter(): void
    {
        $request = request()->merge(['vip' => '1']);

        $results = ApiQuery::for(TestUser::class, $request)
            ->allowedFilters([
                AllowedFilter::callback('vip', function ($query, $value) {
                    if (filter_var($value, FILTER_VALIDATE_BOOLEAN)) {
                        $query->where('price', '>=', 80);
                    }
                }),
            ])
            ->get();

        $this->assertCount(1, $results);
        $this->assertSame('Charlie John', $results->first()->name);
    }

    public function test_search_like(): void
    {
        $request = request()->merge(['search' => 'alice']);

        $results = ApiQuery::for(TestUser::class, $request)
            ->allowedSearch(['name', 'email'])
            ->get();

        $this->assertCount(1, $results);
        $this->assertSame('Alice Smith', $results->first()->name);
    }

    public function test_sort_ascending_and_descending(): void
    {
        $asc = ApiQuery::for(TestUser::class, request()->merge(['sort' => 'name']))
            ->allowedSorts(['name'])
            ->get();

        $this->assertSame(['Alice Smith', 'Bob Jones', 'Charlie John', 'Dana Lee'], $asc->pluck('name')->all());

        $desc = ApiQuery::for(TestUser::class, request()->replace(['sort' => '-name']))
            ->allowedSorts(['name'])
            ->get();

        $this->assertSame(['Dana Lee', 'Charlie John', 'Bob Jones', 'Alice Smith'], $desc->pluck('name')->all());
    }

    public function test_multiple_sorts_and_default_sort(): void
    {
        $request = request()->merge(['sort' => 'status,-price']);

        $results = ApiQuery::for(TestUser::class, $request)
            ->allowedSorts(['status', 'price'])
            ->get();

        $this->assertSame('Charlie John', $results->first()->name);

        $default = ApiQuery::for(TestUser::class, request()->replace([]))
            ->allowedSorts(['name'])
            ->defaultSort('-name')
            ->get();

        $this->assertSame('Dana Lee', $default->first()->name);
    }

    public function test_custom_sort_callback(): void
    {
        $request = request()->merge(['sort' => '-price_rank']);

        $results = ApiQuery::for(TestUser::class, $request)
            ->allowedSorts([
                AllowedSort::callback('price_rank', function ($query, $direction) {
                    $query->orderBy('price', $direction);
                }),
            ])
            ->get();

        $this->assertSame('Charlie John', $results->first()->name);
    }

    public function test_pagination_helpers_and_response_envelope(): void
    {
        $request = request()->merge(['per_page' => 2, 'page' => 2]);

        $paginator = ApiQuery::for(TestUser::class, $request)
            ->allowedSorts(['name'])
            ->defaultSort('name')
            ->paginate();

        $this->assertSame(2, $paginator->perPage());
        $this->assertSame(2, $paginator->currentPage());
        $this->assertSame(4, $paginator->total());
        $this->assertCount(2, $paginator->items());

        $response = Api::success($paginator, 'Users fetched successfully');
        $json = $response->getData(true);

        $this->assertTrue($json['success']);
        $this->assertSame(2, $json['meta']['current_page']);
        $this->assertSame(4, $json['meta']['total']);
        $this->assertArrayHasKey('next', $json['links']);
        $this->assertArrayHasKey('prev', $json['links']);
    }

    public function test_ignores_disallowed_filters_and_sorts(): void
    {
        $request = request()->merge([
            'status' => 'active',
            'hack' => '1',
            'sort' => 'email,-name',
        ]);

        $results = ApiQuery::for(TestUser::class, $request)
            ->allowedFilters(['status'])
            ->allowedSorts(['name'])
            ->get();

        $this->assertCount(2, $results);
        $this->assertSame(['Charlie John', 'Alice Smith'], $results->pluck('name')->all());
    }

    public function test_model_api_query_trait(): void
    {
        $request = request()->merge(['search' => 'bob']);

        $results = TestUser::apiQuery(null, $request)
            ->allowedSearch(['name'])
            ->get();

        $this->assertCount(1, $results);
        $this->assertSame('Bob Jones', $results->first()->name);
    }

    public function test_nested_filter_parameter_when_configured(): void
    {
        config(['api-starter.query.filter_parameter' => 'filter']);

        $request = request()->merge([
            'filter' => ['status' => 'pending'],
        ]);

        $results = ApiQuery::for(TestUser::class, $request)
            ->allowedFilters(['status'])
            ->get();

        $this->assertCount(1, $results);
        $this->assertSame('Dana Lee', $results->first()->name);
    }

    public function test_max_per_page_is_capped(): void
    {
        config(['api-starter.pagination.max_per_page' => 3]);

        $request = request()->merge(['per_page' => 999]);

        $paginator = ApiQuery::for(TestUser::class, $request)->paginate();

        $this->assertSame(3, $paginator->perPage());
    }
}

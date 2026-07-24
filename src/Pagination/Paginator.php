<?php

namespace Libinkk\ApiStarter\Pagination;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\Pagination\Paginator as PaginatorContract;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;

class Paginator
{
    public static function perPage(?Request $request = null): int
    {
        $request ??= request();
        $param = Config::get('api-starter.pagination.per_page_parameter', 'per_page');
        $default = (int) Config::get('api-starter.pagination.default_per_page', 15);
        $max = (int) Config::get('api-starter.pagination.max_per_page', 100);

        $perPage = (int) $request->input($param, $default);

        if ($perPage < 1) {
            $perPage = $default;
        }

        return min($perPage, $max);
    }

    public static function page(?Request $request = null): int
    {
        $request ??= request();
        $param = Config::get('api-starter.pagination.page_parameter', 'page');
        $page = (int) $request->input($param, 1);

        return max($page, 1);
    }

    /**
     * @param  Builder<\Illuminate\Database\Eloquent\Model>  $query
     */
    public static function paginate(Builder $query, ?int $perPage = null, ?Request $request = null): LengthAwarePaginator
    {
        $request ??= request();
        $perPage ??= static::perPage($request);
        $pageName = Config::get('api-starter.pagination.page_parameter', 'page');

        return $query->paginate($perPage, ['*'], $pageName, static::page($request));
    }

    /**
     * @param  Builder<\Illuminate\Database\Eloquent\Model>  $query
     */
    public static function simplePaginate(Builder $query, ?int $perPage = null, ?Request $request = null): PaginatorContract
    {
        $request ??= request();
        $perPage ??= static::perPage($request);
        $pageName = Config::get('api-starter.pagination.page_parameter', 'page');

        return $query->simplePaginate($perPage, ['*'], $pageName, static::page($request));
    }

    public static function meta(LengthAwarePaginator $paginator): array
    {
        return [
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'per_page' => $paginator->perPage(),
            'total' => $paginator->total(),
            'from' => $paginator->firstItem(),
            'to' => $paginator->lastItem(),
        ];
    }

    public static function links(LengthAwarePaginator $paginator): array
    {
        return [
            'first' => $paginator->url(1),
            'last' => $paginator->url($paginator->lastPage()),
            'prev' => $paginator->previousPageUrl(),
            'next' => $paginator->nextPageUrl(),
        ];
    }
}

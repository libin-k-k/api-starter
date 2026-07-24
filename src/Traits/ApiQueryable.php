<?php

namespace Libinkk\ApiStarter\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Libinkk\ApiStarter\Support\ApiQuery;

/**
 * @mixin \Illuminate\Database\Eloquent\Model
 */
trait ApiQueryable
{
    /**
     * @param  Builder<\Illuminate\Database\Eloquent\Model>|null  $query
     */
    public static function apiQuery(?Builder $query = null, ?Request $request = null): ApiQuery
    {
        return ApiQuery::for($query ?? static::query(), $request);
    }
}

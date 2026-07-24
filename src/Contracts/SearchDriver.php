<?php

namespace Libinkk\ApiStarter\Contracts;

use Illuminate\Database\Eloquent\Builder;

interface SearchDriver
{
    /**
     * @param  Builder<\Illuminate\Database\Eloquent\Model>  $query
     * @param  list<string>  $columns
     */
    public function apply(Builder $query, string $term, array $columns): void;
}

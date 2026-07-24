<?php

namespace Libinkk\ApiStarter\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Libinkk\ApiStarter\Contracts\SearchDriver;

class LikeSearchDriver implements SearchDriver
{
    /**
     * @param  Builder<\Illuminate\Database\Eloquent\Model>  $query
     * @param  list<string>  $columns
     */
    public function apply(Builder $query, string $term, array $columns): void
    {
        if ($term === '' || $columns === []) {
            return;
        }

        $query->where(function (Builder $builder) use ($term, $columns) {
            foreach ($columns as $index => $column) {
                $method = $index === 0 ? 'where' : 'orWhere';
                $builder->{$method}($column, 'like', '%'.$term.'%');
            }
        });
    }
}

<?php

namespace Libinkk\ApiStarter\Contracts;

use Illuminate\Database\Eloquent\Builder;

interface Sort
{
    public function name(): string;

    /**
     * @param  Builder<\Illuminate\Database\Eloquent\Model>  $query
     * @param  'asc'|'desc'  $direction
     */
    public function apply(Builder $query, string $direction): void;
}

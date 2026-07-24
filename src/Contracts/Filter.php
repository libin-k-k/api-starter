<?php

namespace Libinkk\ApiStarter\Contracts;

use Illuminate\Database\Eloquent\Builder;

interface Filter
{
    public function name(): string;

    /**
     * @param  Builder<\Illuminate\Database\Eloquent\Model>  $query
     */
    public function apply(Builder $query, mixed $value): void;
}

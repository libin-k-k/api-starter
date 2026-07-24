<?php

namespace Libinkk\ApiStarter\Sorts;

use Closure;
use Illuminate\Database\Eloquent\Builder;
use Libinkk\ApiStarter\Contracts\Sort;

class AllowedSort implements Sort
{
    public function __construct(
        protected string $name,
        protected string $internalName,
        protected ?Closure $callback = null
    ) {}

    public static function field(string $name, ?string $internalName = null): self
    {
        return new self($name, $internalName ?? $name);
    }

    public static function callback(string $name, Closure $callback): self
    {
        return new self($name, $name, $callback);
    }

    public function name(): string
    {
        return $this->name;
    }

    public function apply(Builder $query, string $direction): void
    {
        if ($this->callback) {
            ($this->callback)($query, $direction);

            return;
        }

        $query->orderBy($this->internalName, $direction);
    }
}

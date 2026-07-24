<?php

namespace Libinkk\ApiStarter\Filters;

use Closure;
use Illuminate\Database\Eloquent\Builder;
use Libinkk\ApiStarter\Contracts\Filter;

class AllowedFilter implements Filter
{
    public function __construct(
        protected string $name,
        protected string $internalName,
        protected string $type = 'exact',
        protected ?Closure $callback = null
    ) {}

    public static function exact(string $name, ?string $internalName = null): self
    {
        return new self($name, $internalName ?? $name, 'exact');
    }

    public static function partial(string $name, ?string $internalName = null): self
    {
        return new self($name, $internalName ?? $name, 'partial');
    }

    public static function boolean(string $name, ?string $internalName = null): self
    {
        return new self($name, $internalName ?? $name, 'boolean');
    }

    public static function between(string $name, ?string $internalName = null): self
    {
        return new self($name, $internalName ?? $name, 'between');
    }

    public static function dateRange(string $name, ?string $internalName = null): self
    {
        return new self($name, $internalName ?? $name, 'date_range');
    }

    public static function callback(string $name, Closure $callback): self
    {
        return new self($name, $name, 'callback', $callback);
    }

    public function name(): string
    {
        return $this->name;
    }

    public function apply(Builder $query, mixed $value): void
    {
        if ($value === null || $value === '') {
            return;
        }

        match ($this->type) {
            'exact' => $this->applyExact($query, $value),
            'partial' => $this->applyPartial($query, $value),
            'boolean' => $this->applyBoolean($query, $value),
            'between' => $this->applyBetween($query, $value),
            'date_range' => $this->applyDateRange($query, $value),
            'callback' => ($this->callback)($query, $value),
            default => $this->applyExact($query, $value),
        };
    }

    protected function applyExact(Builder $query, mixed $value): void
    {
        if (is_string($value) && str_contains($value, ',')) {
            $query->whereIn($this->internalName, array_map('trim', explode(',', $value)));

            return;
        }

        if (is_array($value)) {
            $query->whereIn($this->internalName, $value);

            return;
        }

        $query->where($this->internalName, '=', $value);
    }

    protected function applyPartial(Builder $query, mixed $value): void
    {
        $query->where($this->internalName, 'like', '%'.$value.'%');
    }

    protected function applyBoolean(Builder $query, mixed $value): void
    {
        $bool = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

        if ($bool === null) {
            return;
        }

        $query->where($this->internalName, '=', $bool);
    }

    protected function applyBetween(Builder $query, mixed $value): void
    {
        [$from, $to] = $this->splitRange($value);

        if ($from !== null && $to !== null) {
            $query->whereBetween($this->internalName, [$from, $to]);

            return;
        }

        if ($from !== null) {
            $query->where($this->internalName, '>=', $from);
        }

        if ($to !== null) {
            $query->where($this->internalName, '<=', $to);
        }
    }

    protected function applyDateRange(Builder $query, mixed $value): void
    {
        [$from, $to] = $this->splitRange($value);

        if ($from !== null) {
            $query->whereDate($this->internalName, '>=', $from);
        }

        if ($to !== null) {
            $query->whereDate($this->internalName, '<=', $to);
        }
    }

    /**
     * @return array{0: mixed, 1: mixed}
     */
    protected function splitRange(mixed $value): array
    {
        if (is_array($value)) {
            return [$value['from'] ?? $value[0] ?? null, $value['to'] ?? $value[1] ?? null];
        }

        if (is_string($value) && str_contains($value, ',')) {
            $parts = array_map('trim', explode(',', $value, 2));

            return [$parts[0] !== '' ? $parts[0] : null, $parts[1] !== '' ? $parts[1] : null];
        }

        return [$value, null];
    }
}

<?php

namespace Libinkk\ApiStarter\Support;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\Pagination\Paginator as PaginatorContract;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Libinkk\ApiStarter\Contracts\Filter;
use Libinkk\ApiStarter\Contracts\SearchDriver;
use Libinkk\ApiStarter\Contracts\Sort;
use Libinkk\ApiStarter\Filters\AllowedFilter;
use Libinkk\ApiStarter\Pagination\Paginator;
use Libinkk\ApiStarter\Sorts\AllowedSort;

class ApiQuery
{
    /** @var Builder<\Illuminate\Database\Eloquent\Model> */
    protected Builder $query;

    protected Request $request;

    /** @var array<string, Filter> */
    protected array $allowedFilters = [];

    /** @var array<string, Sort> */
    protected array $allowedSorts = [];

    /** @var list<string> */
    protected array $searchColumns = [];

    protected ?SearchDriver $searchDriver = null;

    /** @var list<string> */
    protected array $defaultSorts = [];

    /** @var list<string> */
    protected array $allowedFields = [];

    /** @var list<string> */
    protected array $allowedIncludes = [];

    /**
     * @param  Builder<\Illuminate\Database\Eloquent\Model>|class-string<Model>  $subject
     */
    public function __construct(Builder|string $subject, ?Request $request = null)
    {
        $this->query = is_string($subject) ? $subject::query() : $subject;
        $this->request = $request ?? request();
        $this->searchDriver = new LikeSearchDriver;
    }

    /**
     * @param  Builder<\Illuminate\Database\Eloquent\Model>|class-string<Model>  $subject
     */
    public static function for(Builder|string $subject, ?Request $request = null): self
    {
        return new self($subject, $request);
    }

    /**
     * @param  list<Filter|string>  $filters
     */
    public function allowedFilters(array $filters): self
    {
        foreach ($filters as $filter) {
            if (is_string($filter)) {
                $filter = AllowedFilter::exact($filter);
            }

            $this->allowedFilters[$filter->name()] = $filter;
        }

        return $this;
    }

    /**
     * @param  list<Sort|string>  $sorts
     */
    public function allowedSorts(array $sorts): self
    {
        foreach ($sorts as $sort) {
            if (is_string($sort)) {
                $sort = AllowedSort::field($sort);
            }

            $this->allowedSorts[$sort->name()] = $sort;
        }

        return $this;
    }

    /**
     * @param  list<string>  $columns
     */
    public function allowedSearch(array $columns, ?SearchDriver $driver = null): self
    {
        $this->searchColumns = array_values($columns);

        if ($driver !== null) {
            $this->searchDriver = $driver;
        }

        return $this;
    }

    /**
     * @param  list<string>  $fields
     */
    public function allowedFields(array $fields): self
    {
        $this->allowedFields = array_values($fields);

        return $this;
    }

    /**
     * @param  list<string>  $includes
     */
    public function allowedIncludes(array $includes): self
    {
        $this->allowedIncludes = array_values($includes);

        return $this;
    }

    public function searchDriver(SearchDriver $driver): self
    {
        $this->searchDriver = $driver;

        return $this;
    }

    /**
     * @param  string|list<string>  $sorts
     */
    public function defaultSort(string|array $sorts): self
    {
        $this->defaultSorts = is_array($sorts) ? $sorts : [$sorts];

        return $this;
    }

    /**
     * @return Builder<\Illuminate\Database\Eloquent\Model>
     */
    public function getEloquentBuilder(): Builder
    {
        $this->applyFilters();
        $this->applySearch();
        $this->applySorts();
        $this->applyIncludes();
        $this->applyFields();

        return $this->query;
    }

    /**
     * @return Collection<int, Model>
     */
    public function get(): Collection
    {
        return $this->getEloquentBuilder()->get();
    }

    public function first(): ?Model
    {
        return $this->getEloquentBuilder()->first();
    }

    public function paginate(?int $perPage = null): LengthAwarePaginator
    {
        return Paginator::paginate($this->getEloquentBuilder(), $perPage, $this->request);
    }

    public function simplePaginate(?int $perPage = null): PaginatorContract
    {
        return Paginator::simplePaginate($this->getEloquentBuilder(), $perPage, $this->request);
    }

    protected function applyFilters(): void
    {
        if (! Config::get('api-starter.features.filtering', true) || $this->allowedFilters === []) {
            return;
        }

        $namespace = Config::get('api-starter.query.filter_parameter');

        foreach ($this->allowedFilters as $name => $filter) {
            $value = $this->resolveFilterValue($name, $namespace);

            if ($value === null || $value === '') {
                continue;
            }

            $filter->apply($this->query, $value);
        }
    }

    protected function resolveFilterValue(string $name, mixed $namespace): mixed
    {
        if (is_string($namespace) && $namespace !== '') {
            $nested = $this->request->input($namespace.'.'.$name);

            if ($nested !== null && $nested !== '') {
                return $nested;
            }
        }

        // Support ?created_at_from=&created_at_to= for range filters
        $from = $this->request->input($name.'_from');
        $to = $this->request->input($name.'_to');

        if ($from !== null || $to !== null) {
            return [
                'from' => $from,
                'to' => $to,
            ];
        }

        return $this->request->input($name);
    }

    protected function applySearch(): void
    {
        if (! Config::get('api-starter.features.search', true) || $this->searchColumns === []) {
            return;
        }

        $param = Config::get('api-starter.query.search_parameter', 'search');
        $term = $this->request->input($param);

        if (! is_string($term) || trim($term) === '') {
            return;
        }

        $this->searchDriver?->apply($this->query, trim($term), $this->searchColumns);
    }

    protected function applySorts(): void
    {
        if (! Config::get('api-starter.features.sorting', true) || $this->allowedSorts === []) {
            return;
        }

        $param = Config::get('api-starter.query.sort_parameter', 'sort');
        $requested = $this->request->input($param);

        $sorts = [];

        if (is_string($requested) && $requested !== '') {
            $sorts = array_filter(array_map('trim', explode(',', $requested)));
        } elseif (is_array($requested)) {
            $sorts = $requested;
        }

        if ($sorts === []) {
            $sorts = $this->defaultSorts;
        }

        foreach ($sorts as $sort) {
            if (! is_string($sort) || $sort === '') {
                continue;
            }

            $direction = 'asc';
            $name = $sort;

            if (str_starts_with($sort, '-')) {
                $direction = 'desc';
                $name = substr($sort, 1);
            }

            if (! isset($this->allowedSorts[$name])) {
                continue;
            }

            $this->allowedSorts[$name]->apply($this->query, $direction);
        }
    }

    protected function applyIncludes(): void
    {
        if (! Config::get('api-starter.features.includes', true) || $this->allowedIncludes === []) {
            return;
        }

        $param = Config::get('api-starter.query.include_parameter', 'include');
        $requested = $this->request->input($param);

        $includes = $this->parseList($requested);

        if ($includes === []) {
            return;
        }

        $eager = [];

        foreach ($includes as $include) {
            if (in_array($include, $this->allowedIncludes, true)) {
                $eager[] = $include;
            }
        }

        if ($eager !== []) {
            $this->query->with($eager);
        }
    }

    protected function applyFields(): void
    {
        if (! Config::get('api-starter.features.fields', true) || $this->allowedFields === []) {
            return;
        }

        $param = Config::get('api-starter.query.fields_parameter', 'fields');
        $requested = $this->request->input($param);
        $fields = $this->parseList($requested);

        if ($fields === []) {
            return;
        }

        $selected = array_values(array_intersect($fields, $this->allowedFields));

        if ($selected === []) {
            return;
        }

        $keyName = $this->query->getModel()->getKeyName();

        if (! in_array($keyName, $selected, true)) {
            array_unshift($selected, $keyName);
        }

        $qualified = array_map(
            fn (string $field) => $this->query->getModel()->qualifyColumn($field),
            $selected
        );

        $this->query->select($qualified);
    }

    /**
     * @return list<string>
     */
    protected function parseList(mixed $value): array
    {
        if (is_string($value) && $value !== '') {
            return array_values(array_filter(array_map('trim', explode(',', $value))));
        }

        if (is_array($value)) {
            return array_values(array_filter(array_map(
                static fn ($item) => is_string($item) ? trim($item) : '',
                $value
            )));
        }

        return [];
    }
}

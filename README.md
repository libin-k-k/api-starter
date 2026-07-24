# libinkk/api-starter

A production-ready API foundation for Laravel applications with standardized responses, filtering, sorting, searching, pagination, sparse fields, relationship includes, validation, exception handling, request IDs, error codes, localization, versioning, and Artisan tooling.

It extends Laravel with opinionated best practices. It does not replace Laravel.

---

## Table of contents

- [Requirements](#requirements)
- [Installation](#installation)
- [Quick start](#quick-start)
- [Response envelope](#response-envelope)
- [Api facade helpers](#api-facade-helpers)
- [Exception handling](#exception-handling)
- [Validation responses](#validation-responses)
- [ApiQuery tutorial](#apiquery-tutorial)
  - [Filtering](#filtering)
  - [Searching](#searching)
  - [Sorting](#sorting)
  - [Pagination](#pagination)
  - [Sparse fields](#sparse-fields)
  - [Relationship includes](#relationship-includes)
- [Request ID](#request-id)
- [Performance (response time)](#performance-response-time)
- [Error codes](#error-codes)
- [Localization](#localization)
- [API versioning](#api-versioning)
- [Middleware](#middleware)
- [Artisan commands](#artisan-commands)
- [Configuration reference](#configuration-reference)
- [End-to-end controller tutorial](#end-to-end-controller-tutorial)
- [Testing](#testing)
- [Out of scope](#out-of-scope)
- [Changelog and license](#changelog-and-license)

---

## Requirements

| Requirement | Version |
|-------------|---------|
| PHP | 8.1+ (8.2, 8.3, 8.4 supported) |
| Laravel | 9, 10, 11, 12, 13 |

---

## Installation

```bash
composer require libinkk/api-starter
```

Publish config and language files:

```bash
php artisan api-starter:install
```

Or publish manually:

```bash
php artisan vendor:publish --tag=api-starter-config
php artisan vendor:publish --tag=api-starter-lang
```

Run the doctor to verify setup:

```bash
php artisan api-starter:doctor
```

### Register middleware (recommended)

In Laravel 11+ (`bootstrap/app.php`):

```php
$middleware->prependToGroup('api', [
        \Libinkk\ApiStarter\Http\Middleware\MeasurePerformance::class,
    ]);

    $middleware->appendToGroup('api', [
        \Libinkk\ApiStarter\Http\Middleware\AssignRequestId::class,
        \Libinkk\ApiStarter\Http\Middleware\SetLocale::class,
        \Libinkk\ApiStarter\Http\Middleware\SetApiVersion::class,
    ]);
})
```

Or use aliases on specific routes:

```php
Route::middleware(['api.performance', 'api.request-id', 'api.locale', 'api.version'])
    ->prefix('api')
    ->group(function () {
        //
    });
```

Aliases registered by the package:

| Alias | Class |
|-------|--------|
| `api.performance` | `MeasurePerformance` |
| `api.request-id` | `AssignRequestId` |
| `api.locale` | `SetLocale` |
| `api.version` | `SetApiVersion` |

---

## Quick start

```php
use Libinkk\ApiStarter\Facades\Api;
use Libinkk\ApiStarter\Filters\AllowedFilter;
use Libinkk\ApiStarter\Support\ApiQuery;
use App\Models\User;

public function index()
{
    $users = ApiQuery::for(User::class)
        ->allowedFilters([
            'status',
            AllowedFilter::partial('name'),
        ])
        ->allowedSearch(['name', 'email'])
        ->allowedSorts(['name', 'created_at'])
        ->allowedFields(['id', 'name', 'email', 'status'])
        ->allowedIncludes(['posts'])
        ->defaultSort('-created_at')
        ->paginate();

    return Api::success($users, 'Users fetched successfully');
}

public function store(StoreUserRequest $request)
{
    $user = User::create($request->validated());

    return Api::created($user, 'User created successfully');
}
```

Example request:

```http
GET /api/users?status=active&search=john&sort=-created_at&fields=id,name,email&include=posts&page=1&per_page=15
```

---

## Response envelope

### Success

```json
{
  "success": true,
  "status": 200,
  "message": "User fetched successfully",
  "data": {},
  "meta": {},
  "errors": [],
  "links": {},
  "request_id": "REQ-ABC12345"
}
```

### Error

```json
{
  "success": false,
  "status": 422,
  "message": "Validation failed",
  "data": null,
  "meta": [],
  "errors": {
    "email": ["The email field is required."]
  },
  "links": [],
  "request_id": "REQ-ABC12345",
  "error_code": "VALIDATION_FAILED"
}
```

Paginated responses automatically fill `meta` (current page, last page, total, per page) and `links` (first, last, prev, next).

Optional `timestamp` can be enabled in config:

```php
'response' => [
    'include_timestamp' => true,
],
```

---

## Api facade helpers

```php
use Libinkk\ApiStarter\Facades\Api;

Api::success($data, 'OK');
Api::created($data, 'Created');
Api::updated($data, 'Updated');
Api::deleted('Deleted');
Api::validation($errors, 'Validation failed');
Api::error('Something went wrong', 400, [], [], 'ERR_001');
```

### Method reference

| Method | HTTP status | Purpose |
|--------|-------------|---------|
| `success($data, $message, $status = 200, $meta, $links)` | 200 (default) | Generic success |
| `created($data, $message, $meta)` | 201 | Resource created |
| `updated($data, $message, $meta)` | 200 | Resource updated |
| `deleted($message, $meta)` | 200 | Resource deleted |
| `validation($errors, $message, $status = 422, $meta)` | 422 | Validation failure |
| `error($message, $status, $errors, $meta, $errorCode)` | 400 (default) | Generic error |

Laravel API Resources and paginators are supported as `$data`.

---

## Exception handling

When enabled, API requests (`Accept: application/json`, or paths under `/api/*`) receive the package envelope automatically.

| Exception | Status | Error code |
|-----------|--------|------------|
| `ValidationException` | 422 | `VALIDATION_FAILED` |
| `ModelNotFoundException` | 404 | `MODEL_NOT_FOUND` |
| `AuthenticationException` | 401 | `UNAUTHENTICATED` |
| `AuthorizationException` | 403 | `FORBIDDEN` |
| `NotFoundHttpException` | 404 | `ROUTE_NOT_FOUND` |
| `QueryException` | 500 | `QUERY_EXCEPTION` |
| Other HTTP exceptions | varies | `HTTP_EXCEPTION` |
| Uncaught errors | 500 | `SERVER_ERROR` |

### Custom API exceptions

```php
use Libinkk\ApiStarter\Exceptions\ApiException;

throw new ApiException(
    message: 'Payment required',
    status: 402,
    errors: [],
    errorCode: 'PAYMENT_REQUIRED'
);
```

Toggle with:

```php
'features' => [
    'exceptions' => true,
],
```

Set `'debug' => true` (or `API_STARTER_DEBUG=true`) to expose detailed exception meta in development.

---

## Validation responses

Form request / validator failures are converted to:

```json
{
  "success": false,
  "status": 422,
  "message": "Validation failed",
  "errors": {
    "email": ["The email field is required."]
  },
  "error_code": "VALIDATION_FAILED"
}
```

You can also return validation manually:

```php
return Api::validation([
    'email' => ['The email field is required.'],
]);
```

---

## ApiQuery tutorial

`ApiQuery` is an allowlist-based query pipeline for Eloquent.

```php
use Libinkk\ApiStarter\Support\ApiQuery;
use Libinkk\ApiStarter\Traits\ApiQueryable;

// Option A: static helper
ApiQuery::for(User::class)

// Option B: model trait
class User extends Model
{
    use ApiQueryable;
}

User::apiQuery()
```

### Filtering

```php
use Libinkk\ApiStarter\Filters\AllowedFilter;

ApiQuery::for(User::class)
    ->allowedFilters([
        'status',                              // exact match
        AllowedFilter::partial('name'),        // LIKE %value%
        AllowedFilter::boolean('is_active'),   // true/false/1/0
        AllowedFilter::between('price'),       // 10,100
        AllowedFilter::dateRange('created_at'),
        AllowedFilter::callback('vip', function ($query, $value) {
            if (filter_var($value, FILTER_VALIDATE_BOOLEAN)) {
                $query->where('price', '>=', 80);
            }
        }),
        AllowedFilter::exact('role_id', 'roles.id'), // map request key -> column
    ])
    ->get();
```

#### Query examples

| Type | Request |
|------|---------|
| Exact | `?status=active` |
| Exact (IN) | `?status=active,pending` |
| Partial | `?name=john` |
| Boolean | `?is_active=true` |
| Between | `?price=10,100` |
| Date range | `?created_at=2024-01-01,2024-12-31` |
| Date from/to | `?created_at_from=2024-01-01&created_at_to=2024-12-31` |

Nested filter keys (optional):

```php
'query' => [
    'filter_parameter' => 'filter', // ?filter[status]=active
],
```

Only allowlisted filters are applied. Unknown query keys are ignored.

### Searching

```php
ApiQuery::for(User::class)
    ->allowedSearch(['name', 'email'])
    ->get();
```

```http
GET /api/users?search=john
```

Default driver is SQL `LIKE` across the given columns (`OR`).

Custom search driver:

```php
use Libinkk\ApiStarter\Contracts\SearchDriver;

class FullTextSearchDriver implements SearchDriver
{
    public function apply($query, string $term, array $columns): void
    {
        // your full-text logic
    }
}

ApiQuery::for(User::class)
    ->allowedSearch(['name', 'email'], new FullTextSearchDriver())
    ->get();
```

### Sorting

```php
use Libinkk\ApiStarter\Sorts\AllowedSort;

ApiQuery::for(User::class)
    ->allowedSorts([
        'name',
        'created_at',
        AllowedSort::field('email'),
        AllowedSort::callback('price_rank', function ($query, $direction) {
            $query->orderBy('price', $direction);
        }),
    ])
    ->defaultSort('-created_at')
    ->get();
```

| Request | Meaning |
|---------|---------|
| `?sort=name` | Ascending by name |
| `?sort=-created_at` | Descending by created_at |
| `?sort=name,-price` | Multiple sorts |

Disallowed sort fields are ignored.

### Pagination

```php
$users = ApiQuery::for(User::class)
    ->allowedSorts(['name'])
    ->paginate(); // respects ?page=&per_page=

return Api::success($users);
```

Also available:

```php
->simplePaginate();
->get();
->first();
```

Config defaults:

```php
'pagination' => [
    'page_parameter' => 'page',
    'per_page_parameter' => 'per_page',
    'default_per_page' => 15,
    'max_per_page' => 100, // hard cap
],
```

Helper utilities:

```php
use Libinkk\ApiStarter\Pagination\Paginator;

Paginator::perPage();
Paginator::page();
Paginator::meta($paginator);
Paginator::links($paginator);
```

### Sparse fields

Reduce payload size by selecting only allowlisted columns.

```php
ApiQuery::for(User::class)
    ->allowedFields(['id', 'name', 'email', 'status'])
    ->paginate();
```

```http
GET /api/users?fields=id,name,email
```

Notes:

- Only allowlisted fields are selected.
- Primary key is always included automatically when fields are requested.
- Disallowed fields (for example `password`) are ignored.

### Relationship includes

Eager-load allowlisted relations only (prevents arbitrary relation loading).

```php
ApiQuery::for(User::class)
    ->allowedIncludes(['posts', 'roles', 'posts.comments'])
    ->paginate();
```

```http
GET /api/users?include=posts,roles
```

---

## Request ID

Middleware `api.request-id` assigns a unique ID per request.

- Generates values like `REQ-8A7C9D2X` when missing
- Keeps an incoming `X-Request-ID` when provided
- Adds the ID to the response header and response envelope `request_id`

```php
'request_id' => [
    'header' => 'X-Request-ID',
    'prefix' => 'REQ-',
    'attribute' => 'api_request_id',
],
```

Useful for debugging, logging, support tickets, and monitoring.

---

## Performance (response time)

Middleware `api.performance` measures how long the request took and exposes it like `40.12ms`.

### Output

**Header**

```http
X-Response-Time: 40.12ms
```

**JSON body**

```json
{
  "success": true,
  "status": 200,
  "message": "OK",
  "data": {},
  "response_time": "40.12ms",
  "performance": {
    "duration_ms": 40.12,
    "duration": "40.12ms"
  }
}
```

### Setup

Register `api.performance` as the **outermost** API middleware so timing covers the full request:

```php
Route::middleware(['api.performance', 'api.request-id', 'api.locale', 'api.version'])
```

### Config

```php
'features' => [
    'performance' => true,
],

'performance' => [
    'header' => 'X-Response-Time',
    'include_in_body' => true, // set false for header-only
    'precision' => 2,
    'attribute' => 'api_response_time_ms',
],
```

---

## Error codes

Stable codes make client-side branching easier.

Built-in codes:

- `VALIDATION_FAILED`
- `MODEL_NOT_FOUND`
- `UNAUTHENTICATED`
- `FORBIDDEN`
- `ROUTE_NOT_FOUND`
- `QUERY_EXCEPTION`
- `HTTP_EXCEPTION`
- `SERVER_ERROR`
- `UNSUPPORTED_API_VERSION`

```php
use Libinkk\ApiStarter\Support\ErrorCode;

ErrorCode::message(ErrorCode::FORBIDDEN);
// localized: "This action is unauthorized." / Tamil / German / etc.

return Api::error(
    ErrorCode::message('USER_001', [], 'User account is suspended.'),
    403,
    [],
    [],
    'USER_001'
);
```

Add custom codes in config:

```php
'error_codes' => [
    'messages' => [
        'USER_001' => 'User account is suspended.',
        'ORDER_101' => 'Order cannot be cancelled.',
    ],
],
```

Prefer language files for localized custom codes:

```php
// lang/en/errors.php (published) or package lang files
'USER_001' => 'User account is suspended.',
```

---

## Localization

Supported locales out of the box:

| Code | Language |
|------|----------|
| `en` | English |
| `ta` | Tamil |
| `ml` | Malayalam |
| `hi` | Hindi |
| `de` | German |
| `it` | Italian |
| `es` | Spanish |
| `nl` | Dutch |

Set locale via (in order of priority):

1. Header `X-Locale: de`
2. Query `?lang=es`
3. `Accept-Language`

```http
GET /api/users
X-Locale: de
```

Success/error messages use:

```text
api-starter::messages.*
api-starter::errors.*
```

Publish and customize:

```bash
php artisan vendor:publish --tag=api-starter-lang
```

Config:

```php
'localization' => [
    'supported' => ['en', 'ta', 'ml', 'hi', 'de', 'it', 'es', 'nl'],
    'fallback' => 'en',
    'header' => 'X-Locale',
    'query_parameter' => 'lang',
],
```

---

## API versioning

Resolve version from:

1. Header `X-API-Version: v2`
2. Query `?api_version=v2`
3. Path `/api/v2/...`

```php
use Libinkk\ApiStarter\Versioning\ApiVersion;

ApiVersion::current();   // "v1"
ApiVersion::supported(); // ["v1", "v2"]
```

Unsupported versions return `400` with `UNSUPPORTED_API_VERSION`.

```php
'versioning' => [
    'default' => 'v1',
    'supported' => ['v1', 'v2'],
    'header' => 'X-API-Version',
    'query_parameter' => 'api_version',
],
```

Example route groups:

```php
Route::prefix('api/v1')->middleware(['api.version'])->group(base_path('routes/api_v1.php'));
Route::prefix('api/v2')->middleware(['api.version'])->group(base_path('routes/api_v2.php'));
```

---

## Middleware

| Middleware | Alias | Responsibility |
|------------|-------|----------------|
| `MeasurePerformance` | `api.performance` | Measure and expose response time (e.g. `40ms`) |
| `AssignRequestId` | `api.request-id` | Generate/propagate request ID |
| `SetLocale` | `api.locale` | Set app locale from header/query/Accept-Language |
| `SetApiVersion` | `api.version` | Resolve and validate API version |

Each feature can be disabled independently via `features.*` flags.

---

## Artisan commands

```bash
# Install config + lang and print next steps
php artisan api-starter:install

# Publish config, lang, and/or stubs
php artisan api-starter:publish
php artisan api-starter:publish --tag=api-starter-config
php artisan api-starter:publish --tag=api-starter-lang
php artisan api-starter:publish --tag=api-starter-stubs

# Diagnose installation / feature flags
php artisan api-starter:doctor

# Generators
php artisan api-starter:make-filter StatusFilter
php artisan api-starter:make-sort NameSort
php artisan api-starter:make-transformer UserTransformer
```

Generated classes land under `app/Filters`, `app/Sorts`, and `app/Transformers` by default.

Use a custom filter with ApiQuery:

```php
use App\Filters\StatusFilter;

ApiQuery::for(User::class)
    ->allowedFilters([new StatusFilter()])
    ->get();
```

---

## Configuration reference

File: `config/api-starter.php`

### Feature flags

```php
'features' => [
    'response' => true,
    'filtering' => true,
    'sorting' => true,
    'search' => true,
    'pagination' => true,
    'includes' => true,
    'fields' => true,
    'request_id' => true,
    'error_codes' => true,
    'exceptions' => true,
    'validation' => true,
    'localization' => true,
    'versioning' => true,
    'performance' => true,
],
```

Nothing is mandatory. Disable what you do not need without editing package source.

### Query parameters

```php
'query' => [
    'filter_parameter' => null, // or 'filter'
    'search_parameter' => 'search',
    'sort_parameter' => 'sort',
    'fields_parameter' => 'fields',
    'include_parameter' => 'include',
],
```

---

## End-to-end controller tutorial

### 1. Model

```php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Libinkk\ApiStarter\Traits\ApiQueryable;

class User extends Model
{
    use ApiQueryable;

    protected $fillable = ['name', 'email', 'status'];

    public function posts()
    {
        return $this->hasMany(Post::class);
    }
}
```

### 2. Controller

```php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUserRequest;
use App\Models\User;
use Libinkk\ApiStarter\Facades\Api;
use Libinkk\ApiStarter\Filters\AllowedFilter;

class UserController extends Controller
{
    public function index()
    {
        $users = User::apiQuery()
            ->allowedFilters([
                'status',
                AllowedFilter::partial('name'),
                AllowedFilter::boolean('is_active'),
            ])
            ->allowedSearch(['name', 'email'])
            ->allowedSorts(['name', 'created_at', 'email'])
            ->allowedFields(['id', 'name', 'email', 'status', 'created_at'])
            ->allowedIncludes(['posts'])
            ->defaultSort('-created_at')
            ->paginate();

        return Api::success($users, 'Users fetched successfully');
    }

    public function store(StoreUserRequest $request)
    {
        $user = User::create($request->validated());

        return Api::created($user);
    }

    public function show(User $user)
    {
        return Api::success($user, 'User fetched successfully');
    }

    public function update(StoreUserRequest $request, User $user)
    {
        $user->update($request->validated());

        return Api::updated($user);
    }

    public function destroy(User $user)
    {
        $user->delete();

        return Api::deleted();
    }
}
```

### 3. Routes

```php
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

Route::prefix('api/v1')
    ->middleware(['api', 'api.performance', 'api.request-id', 'api.locale', 'api.version'])
    ->group(function () {
        Route::apiResource('users', UserController::class);
    });
```

### 4. Try it

```bash
# List + filter + search + sort + fields + include + page
curl "http://localhost/api/v1/users?status=active&search=ali&sort=-created_at&fields=id,name,email&include=posts&per_page=10" \
  -H "Accept: application/json" \
  -H "X-Locale: en" \
  -H "X-API-Version: v1"

# Create
curl -X POST "http://localhost/api/v1/users" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d "{\"name\":\"Ada\",\"email\":\"ada@example.com\",\"status\":\"active\"}"
```

Check the response header and body for timing:

```http
X-Response-Time: 40.12ms
```

```json
"response_time": "40.12ms"
```
---

## Testing

Package tests use Orchestra Testbench + PHPUnit.

```bash
composer install
vendor/bin/phpunit
```

In your app tests you can assert JSON structure:

```php
$response->assertOk()
    ->assertJsonPath('success', true)
    ->assertJsonStructure([
        'success',
        'status',
        'message',
        'data',
        'meta',
        'errors',
        'links',
        'request_id',
    ]);
```

---

## Out of scope

This package intentionally does **not** include:

- Authentication / Authorization / RBAC
- Payment gateways
- File uploads
- Notifications
- Queue management
- Admin panels
- Business domain logic

Keep those in dedicated packages or your application.

---

## Design principles

- Modular and optional
- Configurable (no source edits required)
- Consistent response envelopes
- Framework-friendly (PSR-12, SOLID, DI, interfaces)
- Compatible with Laravel API Resources, config cache, and route cache

---

## Support

- Author: Libin K K
- Website: [https://www.libinkk.in](https://www.libinkk.in)
- Email: libinkk1999@gmail.com
- Mobile: +91 77087 82197

---

## Changelog and license

See [CHANGELOG.md](CHANGELOG.md) for release notes.

Current package version: **1.0.0**

Licensed under the [MIT license](LICENSE).

# Changelog

All notable changes to `libinkk/api-starter` are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.0] - 2026-07-24

### Added

- Standardized API response envelope with `Api` facade helpers (`success`, `created`, `updated`, `deleted`, `validation`, `error`)
- Global exception handling for API requests with stable error codes
- `ApiQuery` pipeline: filtering, searching, sorting, pagination, sparse fields, and relationship includes
- Request ID middleware (`api.request-id`)
- Localization for `en`, `ta`, `ml`, `hi`, `de`, `it`, `es`, `nl` (`api.locale`)
- API versioning middleware and helper (`api.version`)
- Performance / response-time middleware (`api.performance`) exposing values like `40.12ms`
- Artisan commands: `install`, `publish`, `doctor`, `make-filter`, `make-sort`, `make-transformer`
- Configurable feature flags via `config/api-starter.php`
- PHPUnit test suite with Orchestra Testbench
- MIT license and full README documentation

[1.0.0]: https://github.com/libinkk/api-starter/releases/tag/v1.0.0

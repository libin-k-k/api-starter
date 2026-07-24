<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Feature Flags
    |--------------------------------------------------------------------------
    |
    | Toggle major package features. Nothing is mandatory - disable anything
    | you do not need without editing package source.
    |
    */

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

    /*
    |--------------------------------------------------------------------------
    | Response Envelope
    |--------------------------------------------------------------------------
    */

    'response' => [
        'include_meta' => true,
        'include_links' => true,
        'include_errors' => true,
        'include_request_id' => true,
        'include_timestamp' => false,

        'default_messages' => [
            'success' => 'Success',
            'created' => 'Resource created successfully',
            'updated' => 'Resource updated successfully',
            'deleted' => 'Resource deleted successfully',
            'error' => 'An error occurred',
            'validation' => 'Validation failed',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Request ID
    |--------------------------------------------------------------------------
    */

    'request_id' => [
        'header' => 'X-Request-ID',
        'prefix' => 'REQ-',
        'attribute' => 'api_request_id',
    ],

    /*
    |--------------------------------------------------------------------------
    | Query (filtering, search, sorting, fields, includes)
    |--------------------------------------------------------------------------
    |
    | filter_parameter:
    |   null  -> flat query keys (?status=active)
    |   'filter' -> nested keys (?filter[status]=active)
    |
    */

    'query' => [
        'filter_parameter' => null,
        'search_parameter' => 'search',
        'sort_parameter' => 'sort',
        'fields_parameter' => 'fields',
        'include_parameter' => 'include',
    ],

    /*
    |--------------------------------------------------------------------------
    | Pagination
    |--------------------------------------------------------------------------
    */

    'pagination' => [
        'page_parameter' => 'page',
        'per_page_parameter' => 'per_page',
        'default_per_page' => 15,
        'max_per_page' => 100,
    ],

    /*
    |--------------------------------------------------------------------------
    | Error Codes
    |--------------------------------------------------------------------------
    */

    'error_codes' => [
        'messages' => [
            // 'USER_001' => 'User account is suspended.',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Localization
    |--------------------------------------------------------------------------
    */

    'localization' => [
        'supported' => ['en', 'ta', 'ml', 'hi', 'de', 'it', 'es', 'nl'],
        'fallback' => 'en',
        'header' => 'X-Locale',
        'query_parameter' => 'lang',
    ],

    /*
    |--------------------------------------------------------------------------
    | API Versioning
    |--------------------------------------------------------------------------
    */

    'versioning' => [
        'default' => 'v1',
        'supported' => ['v1', 'v2'],
        'header' => 'X-API-Version',
        'query_parameter' => 'api_version',
    ],

    /*
    |--------------------------------------------------------------------------
    | Performance (response time)
    |--------------------------------------------------------------------------
    |
    | Measures request duration and exposes it as:
    | - Header: X-Response-Time: 40.12ms
    | - Body: response_time + performance.duration_ms
    |
    | Register api.performance as the outermost API middleware so timing
    | includes the full request lifecycle.
    |
    */

    'performance' => [
        'header' => 'X-Response-Time',
        'include_in_body' => true,
        'precision' => 2,
        'attribute' => 'api_response_time_ms',
    ],

    /*
    |--------------------------------------------------------------------------
    | Debug
    |--------------------------------------------------------------------------
    */

    'debug' => env('API_STARTER_DEBUG', false),

];

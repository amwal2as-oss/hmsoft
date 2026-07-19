<?php

return [
    /*
    |--------------------------------------------------------------------------
    | DateTime Feature Enabled
    |--------------------------------------------------------------------------
    */
    'enabled' => env('CMS_DATETIME_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Register API Routes
    |--------------------------------------------------------------------------
    | GET /api/datetime/config
    | GET /api/datetime/now
    | POST /api/datetime/convert
    */
    'register_routes' => env('CMS_DATETIME_REGISTER_ROUTES', true),

    /*
    |--------------------------------------------------------------------------
    | Storage Timezone (Database / Application)
    |--------------------------------------------------------------------------
    | All timestamps should be stored in this timezone (UTC recommended).
    */
    'storage_timezone' => env('APP_TIMEZONE', 'UTC'),

    /*
    |--------------------------------------------------------------------------
    | Default API Output Timezone
    |--------------------------------------------------------------------------
    | Fallback when the resolver returns null or an invalid timezone.
    */
    'api_timezone' => env('APP_API_TIMEZONE', 'Asia/Damascus'),

    /*
    |--------------------------------------------------------------------------
    | API Timezone Resolver
    |--------------------------------------------------------------------------
    | config   — always use api_timezone above
    | callback — use CmsDateTime::resolveApiTimezoneUsing() in your app
    | class    — use resolver_class (implements DateTimeResolverInterface)
    |
    | The package does not read users, columns, or external storage.
    | Use callback or class to plug in any source you need.
    */
    'resolver' => env('CMS_DATETIME_RESOLVER', 'config'),

    /*
    |--------------------------------------------------------------------------
    | Resolver Class (resolver = class)
    |--------------------------------------------------------------------------
    | Must implement HMsoft\Tools\Features\DateTime\Contracts\DateTimeResolverInterface
    */
    'resolver_class' => env('CMS_DATETIME_RESOLVER_CLASS'),

    /*
    |--------------------------------------------------------------------------
    | Spatie Laravel Data
    |--------------------------------------------------------------------------
    */
    'date_format' => DATE_ATOM,

    /*
    |--------------------------------------------------------------------------
    | transformArray() extra keys
    |--------------------------------------------------------------------------
    | Keys ending in "_at" are always transformed. Add more keys here if needed.
    */
    'transform_keys' => [],
];

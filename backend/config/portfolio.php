<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Templates
    |--------------------------------------------------------------------------
    */

    'templates' => [
        'path' => resource_path('templates'),
        'base_path' => resource_path('templates/_base'),
        'default' => 'minimal',
    ],

    /*
    |--------------------------------------------------------------------------
    | Uploads
    |--------------------------------------------------------------------------
    |
    | The disk is configurable per environment so moving to S3 or R2 is a config
    | change. Every media row records the disk it was written to, so switching
    | does not orphan existing files.
    |
    */

    'uploads' => [
        'disk' => env('PORTFOLIO_UPLOAD_DISK', 'public'),
        'max_kilobytes' => (int) env('PORTFOLIO_UPLOAD_MAX_KB', 8192),
        'mimes' => ['image/jpeg', 'image/png', 'image/webp'],
        'max_dimension' => 2400,
        // Uploads are re-encoded to WebP: it strips any smuggled payload from
        // the original container and cuts the size of a typical export.
        'convert_to_webp' => (bool) env('PORTFOLIO_UPLOAD_WEBP', true),
        'quality' => 82,
        'per_user_limit' => 400,
    ],

    /*
    |--------------------------------------------------------------------------
    | Exports
    |--------------------------------------------------------------------------
    */

    'exports' => [
        'disk' => env('PORTFOLIO_EXPORT_DISK', 'local'),
        'ttl_hours' => (int) env('PORTFOLIO_EXPORT_TTL_HOURS', 24),
        // Flip to true once export volume justifies a worker; the service and
        // the API contract do not change.
        'queue' => (bool) env('PORTFOLIO_EXPORT_QUEUE', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Slugs
    |--------------------------------------------------------------------------
    |
    | Reserved now because portfolio slugs become subdomains later.
    |
    */

    'reserved_slugs' => [
        'www', 'api', 'app', 'admin', 'dashboard', 'assets', 'static', 'cdn',
        'mail', 'support', 'help', 'blog', 'status', 'docs', 'about', 'login',
        'register', 'settings', 'account', 'billing', 'templates', 'preview',
    ],

];

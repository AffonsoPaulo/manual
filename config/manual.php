<?php

declare(strict_types=1);

return [
    'source_path' => 'docs/manual',

    'route_prefix' => 'manual',

    'site_title' => env('APP_NAME', 'Documentation'),

    'cache_store' => env('MANUAL_CACHE_STORE'),

    'cache_ttl' => 3600,

    'view' => 'manual::page',

    'middleware' => ['web'],

    'assets' => [
        'enabled' => true,
    ],

    'search' => [
        'enabled' => true,
        'endpoint' => '_manual/search.json',
    ],
];

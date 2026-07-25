<?php

declare(strict_types=1);

/*
 * CORS is restricted to explicitly configured origins per the v3.0
 * remote-admin API plan (V2_REFACTOR_PLAN.md §8.2, §9.6 #43). No
 * wildcards — production is the artisanpackui.dev consumer only.
 * Origins are supplied as a comma-separated list in
 * CORS_ALLOWED_ORIGINS so ops can rotate them without a deploy.
 */
return [
    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['GET', 'POST', 'PATCH', 'PUT', 'DELETE', 'OPTIONS'],

    'allowed_origins' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('CORS_ALLOWED_ORIGINS', 'https://artisanpackui.dev'))
    ))),

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['Accept', 'Authorization', 'Content-Type', 'X-Requested-With'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => false,
];

<?php

declare(strict_types=1);

return [

    'ssr' => [
        'enabled' => (bool) env('INERTIA_SSR_ENABLED', true),
        'url' => env('INERTIA_SSR_URL', 'http://127.0.0.1:13714'),
        'ensure_bundle_exists' => (bool) env('INERTIA_SSR_ENSURE_BUNDLE_EXISTS', true),
    ],

    /*
    | Inertia's default page paths point at resource_path('js/Pages') (capital P).
    | This project uses lowercase resources/js/pages/, which resolves on
    | case-insensitive filesystems (macOS default) but fails on case-sensitive
    | ones (Linux CI). Override both the runtime and testing view-finder paths
    | to match the actual on-disk directory.
    */
    'page_paths' => [
        resource_path('js/pages'),
    ],

    'page_extensions' => [
        'tsx',
        'jsx',
        'ts',
        'js',
    ],

    'testing' => [
        'ensure_pages_exist' => true,
        'page_paths' => [
            resource_path('js/pages'),
        ],
        'page_extensions' => [
            'tsx',
            'jsx',
            'ts',
            'js',
        ],
    ],

];

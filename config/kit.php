<?php

use SmartCms\Kit\Models\Admin;
use SmartCms\Kit\Models\Tag;

// config for SmartCms/Kit
return [
    'admins_table_name' => 'admins',
    'contact_forms_table_name' => 'contact_forms',
    'pages_table_name' => 'pages',
    'blocks_table_name' => 'blocks',
    'notifications' => [
        'update' => 'kit::admin.update',
        'new_contact_form' => 'kit::admin.new_contact_form',
    ],
    'register_routes' => true,
    'auth_model' => Admin::class,

    /*
    |--------------------------------------------------------------------------
    | Media Library Configuration
    |--------------------------------------------------------------------------
    |
    | Configure image conversions and media library behavior.
    | Conversions are automatically generated when media is uploaded.
    |
    */
    'media' => [
        'collection_name' => 'library',
        'disk' => env('MEDIA_DISK', 'public'),
        'conversions_disk' => env('MEDIA_CONVERSIONS_DISK', 'public'),
        'auto_convert_to_webp' => env('MEDIA_AUTO_CONVERT_TO_WEBP', true),
        'queue_connection' => env('MEDIA_QUEUE_CONNECTION', 'sync'),
        'conversions' => [
            'thumb' => [
                'width' => 150,
                'height' => 150,
                'format' => null, // null = keep original format
            ],
            'preview' => [
                'width' => 400,
                'height' => 400,
                'format' => null,
            ],
            'medium' => [
                'width' => 800,
                'height' => 800,
                'format' => null,
            ],
            'large' => [
                'width' => 1600,
                'height' => 1600,
                'format' => null,
            ],
            'webp' => [
                'width' => 1920,
                'height' => 1920,
                'format' => 'webp',
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Unsplash API Configuration
    |--------------------------------------------------------------------------
    |
    | Configure Unsplash integration for stock photos.
    | Get your API key from: https://unsplash.com/developers
    |
    */
    'unsplash' => [
        'access_key' => env('UNSPLASH_ACCESS_KEY'),
        'enabled' => env('UNSPLASH_ENABLED', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Maximum Page Nesting Depth
    |--------------------------------------------------------------------------
    |
    | This value controls the maximum depth of page hierarchy allowed.
    | A depth of 5 means you can have up to 5 levels of nested pages.
    | Example: /level1/level2/level3/level4/level5
    |
    | Setting this too high may impact SEO and user experience.
    |
    */
    'max_page_depth' => 5,

    /*
    |--------------------------------------------------------------------------
    | Additional Translatable Models
    |--------------------------------------------------------------------------
    |
    | Additional models with translatable JSON fields that should be processed
    | during language key operations (rename, cleanup).
    | Page and Block are always included by default.
    |
    */
    'translatable_models' => [
        Tag::class,
    ],

    'updates' => [
        'enabled' => env('KIT_UPDATES_ENABLED', true),
        'github_repository' => 's-cms/kit',
        'check_frequency' => 'login', // 'login', 'daily', 'disabled'
        'cache_duration' => 3600, // 1 hour in seconds
        'timeout' => 30, // GitHub API timeout
    ],
];

<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default Storage Disk
    |--------------------------------------------------------------------------
    | The default disk to use for uploading media.
    */
    'disk' => env('MEDIA_DISK', 'public'),

    /*
    |--------------------------------------------------------------------------
    | Overridable Models
    |--------------------------------------------------------------------------
    | Swap these models with your own if you need to extend the functionality.
    */
    'models' => [
        'medium' => \HMsoft\Tools\Features\Media\Models\Medium::class,
        'translation' => \HMsoft\Tools\Features\Media\Models\MediumTranslation::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Image Placeholders
    |--------------------------------------------------------------------------
    | Fallback images for models or specific fields when no media is found.
    */
    'placeholders' => [
        'default' => 'assets/images/placeholder.png',
        'models' => [
            'user' => 'assets/images/user-avatar.png',
        ],
        'fields' => [
            'icon' => 'assets/images/default-icon.png',
        ]
    ],



    /*
    |--------------------------------------------------------------------------
    | Max Image Dimension
    |--------------------------------------------------------------------------
    | Images larger than this (width or height) are scaled down before WebP
    | conversion to prevent memory exhaustion on bulk uploads.
    | Set to 0 to disable the limit.
    */
    'max_image_dimension' => env('MEDIA_MAX_IMAGE_DIMENSION', 4096),

    /*
    |--------------------------------------------------------------------------
    | Image Size Sets (Thumbnails)
    |--------------------------------------------------------------------------
    | Defined sets for automatic resizing.
    */
    'image_sets' => [
        'default' => [
            'thumb' => ['width' => 150, 'height' => 150],
            'medium' => ['width' => 600, 'height' => null],
        ],
        'avatar' => [
            'small' => ['width' => 50, 'height' => 50],
        ]
    ]
];

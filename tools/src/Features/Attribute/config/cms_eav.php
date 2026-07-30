<?php

return [
    /*
    |--------------------------------------------------------------------------
    | EAV System Enabled
    |--------------------------------------------------------------------------
    */
    'enabled' => env('CMS_EAV_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Filter key prefix for AutoFilterAndSortService
    |--------------------------------------------------------------------------
    | Example: eav.weight, eav.material
    */
    'filter_prefix' => env('CMS_EAV_FILTER_PREFIX', 'eav'),

    /*
    |--------------------------------------------------------------------------
    | Cache TTL (seconds) for attribute definitions per entity_type
    |--------------------------------------------------------------------------
    */
    'definition_cache_ttl' => env('CMS_EAV_DEFINITION_CACHE_TTL', 3600),

    /*
    |--------------------------------------------------------------------------
    | Table names (override only if renamed)
    |--------------------------------------------------------------------------
    */
    'tables' => [
        'attributes' => 'eav_attributes',
        'attribute_translations' => 'eav_attribute_translations',
        'attribute_options' => 'eav_attribute_options',
        'attribute_option_translations' => 'eav_attribute_option_translations',
        'attribute_categories' => 'eav_attribute_categories',
        'values' => 'eav_values',
        'value_translations' => 'eav_value_translations',
        'value_options' => 'eav_value_options',
    ],


    /*
    |--------------------------------------------------------------------------
    | Category Morph Map (Config-Driven Mapping)
    |--------------------------------------------------------------------------
    | Map the entity_type to its corresponding category morph class.
    */
    'category_map' => [],
];

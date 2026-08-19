<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Fallback locale
    |--------------------------------------------------------------------------
    |
    | Used when the requested locale is missing or the field is empty.
    | null = cms_localization.fallback_locale then app.fallback_locale.
    |
    */
    'fallback_locale' => null,

    /*
    |--------------------------------------------------------------------------
    | Supported locales
    |--------------------------------------------------------------------------
    |
    | Tried after the requested locale and the fallback locale, before any
    | remaining translation rows. null = cms_localization.supported_locales.
    |
    */
    'supported_locales' => null,

    /*
    |--------------------------------------------------------------------------
    | Keys excluded from resolved / mapped translatable fields
    |--------------------------------------------------------------------------
    */
    'ignored_keys' => [
        'id',
        'locale',
        'created_at',
        'updated_at',
        'deleted_at',
        'created_by',
        'updated_by',
        'deleted_by',
    ],

    /*
    |--------------------------------------------------------------------------
    | Treat columns ending with _id as non-translatable
    |--------------------------------------------------------------------------
    */
    'ignore_foreign_keys' => true,

    /*
    |--------------------------------------------------------------------------
    | Eloquent relation names on parent models
    |--------------------------------------------------------------------------
    */
    'relations' => [
        'many' => 'translations',
        'one' => 'translation',
    ],

    /*
    |--------------------------------------------------------------------------
    | Empty-value rules
    |--------------------------------------------------------------------------
    |
    | null is always empty. Blank strings are empty when this flag is true.
    | 0 and false are never treated as empty.
    |
    */
    'treat_blank_string_as_empty' => true,
];

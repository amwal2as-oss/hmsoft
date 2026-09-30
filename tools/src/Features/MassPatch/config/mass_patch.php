<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Extra datetime cast classes
    |--------------------------------------------------------------------------
    |
    | FQCN strings treated as datetime for field discovery (e.g. an app
    | schedule cast). Empty by default — the consuming app adds its own.
    |
    */
    'datetime_cast_classes' => [],

    /*
    |--------------------------------------------------------------------------
    | Translation keys
    |--------------------------------------------------------------------------
    */
    'messages' => [
        'updated' => 'mass_patch.updated',
        'values_required' => 'mass_patch.values_required',
        'unknown_fields' => 'mass_patch.unknown_fields',
    ],

    /*
    |--------------------------------------------------------------------------
    | Schedule field pairs
    |--------------------------------------------------------------------------
    |
    | When both names are mass-patchable, the later field gets
    | after_or_equal:{earlier}.
    |
    */
    'schedule_pairs' => [
        ['from_time', 'to_time'],
        ['start_date', 'end_date'],
    ],

];

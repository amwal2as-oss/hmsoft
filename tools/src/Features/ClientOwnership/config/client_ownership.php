<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Device / guest client header
    |--------------------------------------------------------------------------
    |
    | A clone with a different header only changes this file.
    |
    */
    'header' => 'X-Client-Id',

    /*
    |--------------------------------------------------------------------------
    | Ownership columns
    |--------------------------------------------------------------------------
    |
    | Used by applyOwnershipScope(). Default matches a users-or-guest-device
    | table with user_id + client_id.
    |
    */
    'user_id_column' => 'user_id',
    'client_id_column' => 'client_id',

];

<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Shift on sort_number collision
    |--------------------------------------------------------------------------
    |
    | When saving a model onto a sort_number that is already used in the same
    | sort context, bump that row and every later row by +1, then keep the
    | saved value. Set false (or SORT_NUMBER_SHIFT_ON_COLLISION=false) to
    | disable without removing the trait.
    |
    */
    'shift_on_collision' => filter_var(env('SORT_NUMBER_SHIFT_ON_COLLISION', true), FILTER_VALIDATE_BOOLEAN),

];

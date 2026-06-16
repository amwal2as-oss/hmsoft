<?php

return [
    'at_least_one_title' => 'At least one title must be provided for the attribute in any language.',
    'attributes' => [
        'id'                        => 'ID',
        'scope'                     => 'scope',
        'type'                      => 'attribute type',
        'category_ids'              => 'categories',
        'category_ids.*'            => 'category ID',
        'is_active'                 => 'active status',
        'is_filterable'             => 'filterable status',
        'is_required'               => 'required status',
        'sort_number'               => 'sort number',
        'image'                     => 'image',
        'delete_image'              => 'delete image flag',
        'locales'                   => 'languages',
        'locales.*.locale'          => 'language code',
        'locales.*.title'           => 'title',
        'options'                   => 'options',
        'options.*.id'              => 'option ID',
        'options.*.is_active'       => 'option active status',
        'options.*.sort_number'     => 'option sort number',
        'options.*.locales'         => 'option languages',
        'options.*.locales.*.title' => 'option title',
    ],
];

<?php

return [
    'at_least_one_title' => 'At least one title must be provided for the attribute in any language.',

    'messages' => [
        'invalid_category' => 'The selected category does not exist in the system.',
        'morph_not_found'  => 'The specified category type is not registered: :type',
        'category_conditions_failed' => 'The selected category does not meet the attachment conditions.',
    ],

    'attributes' => [
        'id'                        => 'ID',
        'scope'                     => 'scope',
        'type'                      => 'attribute type',
        'input_type'                => 'input type',
        'categories'                => 'categories',
        'categories.*'              => 'category item',
        'categories.*.category_type' => 'category type',
        'categories.*.category_id'  => 'category ID',
        'is_active'                 => 'active status',
        'is_filterable'             => 'filterable status',
        'is_sortable'               => 'sortable status',
        'is_searchable'             => 'searchable status',
        'is_required'               => 'required status',
        'sort_number'               => 'sort number',
        'icon'                     => 'icon',
        'delete_icon'              => 'delete icon flag',
        'icon'                      => 'icon',
        'locales'                   => 'languages',
        'locales.*.locale'          => 'language code',
        'locales.*.title'           => 'title',
        'locales.*.placeholder'     => 'placeholder',
        'locales.*.help_text'       => 'help text',
        'options'                   => 'options',
        'options.*.id'              => 'option ID',
        'options.*.is_active'       => 'option active status',
        'options.*.is_default'      => 'option default status',
        'options.*.sort_number'     => 'option sort number',
        'options.*.locales'         => 'option languages',
        'options.*.locales.*.label' => 'option label',
    ],
];

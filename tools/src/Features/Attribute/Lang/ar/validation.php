<?php

return [
    'at_least_one_title' => 'يجب إدخال عنوان واحد على الأقل للسمة في أي لغة.',
    'attributes' => [
        'id'                        => 'المعرف',
        'scope'                     => 'النطاق',
        'type'                      => 'نوع السمة',
        'category_ids'              => 'الفئات المخصصة',
        'category_ids.*'            => 'معرف الفئة',
        'is_active'                 => 'حالة التفعيل',
        'is_filterable'             => 'قابلية الفلترة',
        'is_required'               => 'حالة الإلزام',
        'sort_number'               => 'رقم الترتيب',
        'image'                     => 'الصورة',
        'delete_image'              => 'حذف الصورة',
        'locales'                   => 'اللغات',
        'locales.*.locale'          => 'رمز اللغة',
        'locales.*.title'           => 'عنوان السمة',
        'options'                   => 'الخيارات',
        'options.*.id'              => 'معرف الخيار',
        'options.*.is_active'       => 'حالة تفعيل الخيار',
        'options.*.sort_number'     => 'ترتيب الخيار',
        'options.*.locales'         => 'لغات الخيار',
        'options.*.locales.*.title' => 'عنوان الخيار',
    ],
];

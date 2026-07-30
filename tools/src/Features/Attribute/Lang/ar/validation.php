<?php

return [
    'at_least_one_title' => 'يجب إدخال عنوان واحد على الأقل للخاصية في أي لغة.',

    'messages' => [
        'invalid_category' => 'الفئة المحددة غير موجودة في النظام.',
        'morph_not_found'  => 'نوع الفئة المحددة غير مسجل في النظام: :type',
        'category_conditions_failed' => 'الفئة المحددة لا تستوفي شروط الارتباط الخاصة بالنظام.',
    ],

    'attributes' => [
        'id'                        => 'المعرف (ID)',
        'scope'                     => 'النطاق (Scope)',
        'type'                      => 'نوع الخاصية',
        'input_type'                => 'نوع الإدخال',
        'categories'                => 'الفئات',
        'categories.*'              => 'عنصر الفئة',
        'categories.*.category_type' => 'نوع الفئة',
        'categories.*.category_id'  => 'معرف الفئة',
        'is_active'                 => 'حالة التفعيل',
        'is_filterable'             => 'قابلية الفلترة',
        'is_sortable'               => 'قابلية الترتيب',
        'is_searchable'             => 'قابلية البحث',
        'is_required'               => 'حالة الإلزام',
        'sort_number'               => 'رقم الترتيب',
        'icon'                     => 'الصورة',
        'delete_icon'              => 'حذف الصورة',
        'icon'                      => 'الأيقونة',
        'locales'                   => 'اللغات/الترجمات',
        'locales.*.locale'          => 'رمز اللغة',
        'locales.*.title'           => 'العنوان',
        'locales.*.placeholder'     => 'النص الإرشادي',
        'locales.*.help_text'       => 'نص المساعدة',
        'options'                   => 'الخيارات',
        'options.*.id'              => 'معرف الخيار',
        'options.*.is_active'       => 'حالة تفعيل الخيار',
        'options.*.is_default'      => 'الحالة الافتراضية للخيار',
        'options.*.sort_number'     => 'ترتيب الخيار',
        'options.*.locales'         => 'لغات الخيار',
        'options.*.locales.*.label' => 'تسمية الخيار',
    ],
];

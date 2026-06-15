<?php

return [
    // الرسائل المخصصة للـ Rules
    'file_or_url' => 'حقل :attribute يجب أن يكون ملفاً صالحاً للرفع أو رابطاً (URL) صحيحاً.',

    // أسماء الحقول المترجمة
    'attributes' => [
        'file'                        => 'الملف',
        'media'                       => 'الوسائط',
        'media.*.file'                => 'ملف الوسائط',
        'media.*.is_default'          => 'الحالة الافتراضية',
        'media.*.media_type'          => 'نوع الميديا',
        'is_default'                  => 'الصورة الافتراضية',
        'sort_number'                 => 'رقم الترتيب',
        'media_type'                  => 'نوع الوسائط',
        'locales'                     => 'اللغات',
        'locales.*.locale'            => 'رمز اللغة',
        'locales.*.title'             => 'العنوان',
        'locales.*.alt'               => 'النص البديل (Alt)',
        'locales.*.short_description' => 'الوصف القصير',
    ],
];

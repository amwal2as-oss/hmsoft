<?php

namespace HMsoft\Tools\Features\Translations\Contracts;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

interface Translatable
{
    /**
     * جلب علاقة الترجمات المتعددة الخاصة بالموديل.
     *
     * @return HasMany
     */
    public function translations(): HasMany;

    /**
     * جلب ترجمة واحدة للموديل بناءً على اللغة النشطة الحالية للتطبيق.
     *
     * @return HasOne
     */
    public function translation(): HasOne;

    /**
     * مزامنة وتحديث الترجمات الواردة مع تنظيف العناصر المحذوفة.
     *
     * @param Model $model الكيان الأب للترجمات
     * @param array|null $localesData مصفوفة الترجمات واللغات
     * @return void
     */
    public function syncTranslations(Model $model, ?array $localesData = null): void;

    /**
     * استنتاج أو تحديد اسم كلاس موديل الترجمة.
     *
     * @return string
     */
    public function getTranslationModelName(): string;

    /**
     * استنتاج أو تحديد اسم المفتاح الأجنبي لربط الترجمة بالموديل الأب.
     *
     * @return string
     */
    public function getTranslationRelationKey(): string;
}

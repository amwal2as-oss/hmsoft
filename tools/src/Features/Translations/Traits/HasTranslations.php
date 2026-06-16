<?php

namespace HMsoft\Tools\Features\Translations\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

trait HasTranslations
{
    /**
     * تهيئة الـ Trait تلقائياً عند تحميل الموديل في إطار العمل.
     * تضمن جلب علاقة الترجمات بشكل افتراضي لتفادي استعلامات N+1.
     *
     * @return void
     */
    public function initializeHasTranslations(): void
    {
        $this->with[] = 'translations';
    }

    /**
     * جلب الترجمات المتعددة المرتبطة بالموديل بناءً على القيم المستنتجة أو المعرفة.
     *
     * @return HasMany
     */
    public function translations(): HasMany
    {
        return $this->hasMany($this->getTranslationModelName(), $this->getTranslationRelationKey());
    }

    /**
     * 🚀 جلب ترجمة واحدة مخصصة للموديل بناءً على اللغة الحالية النشطة للتطبيق.
     * تعمل ديناميكياً مع أي موديل يتم استدعاء الـ Trait بداخله.
     *
     * @return HasOne
     */
    public function translation(): HasOne
    {
        return $this->hasOne($this->getTranslationModelName(), $this->getTranslationRelationKey())
            ->where('locale', app()->getLocale());
    }

    /**
     * مزامنة الترجمات الخاصة بالموديل (إضافة، تعديل، وحذف تلقائي للعناصر المزاحة).
     *
     * @param Model $model الكيان المراد تحديث ترجماته.
     * @param array|null $localesData مصفوفة بيانات اللغات والترجمات.
     * @return void
     */
    public function syncTranslations(Model $model, ?array $localesData = null): void
    {
        if ($localesData === null) {
            return;
        }

        if (!method_exists($model, 'translations')) {
            return;
        }

        // 1. استخراج الرموز البرمجية للغات المرسلة لتنظيف اللغات القديمة غير المتضمنة
        $locales = collect($localesData)->pluck('locale')->toArray();
        $model->translations()
            ->whereNotIn('locale', $locales)
            ->delete();

        // 2. تحديث السجلات الحالية أو إنشائها فوراً تبعاً للـ locale
        foreach ($localesData as $localeData) {
            if (!isset($localeData['locale'])) {
                continue;
            }

            $model->translations()->updateOrCreate(
                ['locale' => $localeData['locale']],
                Arr::except($localeData, 'locale')
            );
        }
    }

    /**
     * جلب اسم كلاس موديل الترجمة الافتراضي (اسم الموديل ملحوقاً بكلمة Translation).
     * يمكن للمطور عمل Override لهذه الدالة في الموديل لتغيير التسمية الافتراضية.
     *
     * @return string
     */
    public function getTranslationModelName(): string
    {
        return get_class($this) . 'Translation';
    }

    /**
     * جلب اسم المفتاح الأجنبي الافتراضي لجدول الترجمات (على سبيل المثال: feature_id).
     * يمكن للمطور عمل Override لهذه الدالة في الموديل لتخصيص اسم العمود المربوط.
     *
     * @return string
     */
    public function getTranslationRelationKey(): string
    {
        return Str::snake(Str::singular($this->getTable())) . '_id';
    }
}

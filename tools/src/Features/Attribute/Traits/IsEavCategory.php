<?php

namespace HMsoft\Tools\Features\Attribute\Traits;

use HMsoft\Tools\Features\Attribute\Models\Attribute;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

trait IsEavCategory
{
    /**
     * التنظيف التلقائي: يتم استدعاؤه بمجرد حذف أي فئة تستخدم هذا الـ Trait
     */
    public static function bootIsEavCategory(): void
    {
        static::deleting(function ($model) {
            // حذف جميع الروابط من جدول eav_attribute_categories عند حذف الفئة
            if (method_exists($model, 'eavAttributes')) {
                $model->eavAttributes()->detach();
            }
        });
    }

    /**
     * (EAV Attributes)
     *
     * @return MorphToMany
     */
    public function eavAttributes(): MorphToMany
    {
        return $this->morphToMany(
            Attribute::class,
            'category', // category_type, category_id
            'eav_attribute_categories',
            'category_id',
            'attribute_id'
        );
    }

    /**
     * الدالة المسؤولة عن تقديم الكائن لحزمة الـ EAV.
     */
    public function toEavResourceArray(): array
    {
        return $this->toArray();
    }
}
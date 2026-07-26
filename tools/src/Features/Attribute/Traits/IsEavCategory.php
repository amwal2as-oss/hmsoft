<?php

namespace HMsoft\Tools\Features\Attribute\Traits;

use HMsoft\Tools\Features\Attribute\Models\Attribute;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

trait IsEavCategory
{
    /**
     * جلب السمات (EAV Attributes) المرتبطة بهذا التصنيف.
     *
     * @return MorphToMany
     */
    public function eavAttributes(): MorphToMany
    {
        return $this->morphToMany(
            Attribute::class,
            'category', // يعكس الحقول: category_type و category_id في الجدول الوسيط
            'eav_attribute_categories',
            'category_id',
            'attribute_id'
        );
    }
}

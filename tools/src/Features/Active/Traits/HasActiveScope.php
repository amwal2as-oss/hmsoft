<?php

namespace HMsoft\Tools\Features\Active\Traits;

use Illuminate\Database\Eloquent\Builder;
use HMsoft\Tools\Features\Active\Contracts\Activable;

trait HasActiveScope
{
    /**
     * @var callable|null
     */
    public static $applyScopeCondition = null;

    protected static function bootHasActiveScope()
    {
        static::addGlobalScope('active_scope', function (Builder $builder) {
            $model = $builder->getModel();

            // نتحقق من الواجهة ومن شرط التفعيل لضمان عمل الـ Scope بشكل صحيح
            if ($model instanceof Activable && $model->shouldApplyActiveScope()) {
                $column = $model->qualifyColumn($model->getActiveColumnName());
                $builder->where($column, true);
            }
        });
    }

    /**
     * القيمة الافتراضية لاسم حقل التفعيل.
     */
    public function getActiveColumnName(): string
    {
        // يسمح للمطور بتعريف ثابت ACTIVE_COLUMN في المودل لتجاوز الاسم بسهولة
        return defined('static::ACTIVE_COLUMN') ? static::ACTIVE_COLUMN : 'is_active';
    }

    /**
     * الحالة الافتراضية لتطبيق الـ Scope.
     */
    public function shouldApplyActiveScope(): bool
    {
        if (is_callable(self::$applyScopeCondition)) {
            return call_user_func(self::$applyScopeCondition);
        }

        return true;
    }

    /**
     * النطاق المحلي: لجلب العناصر المفعلة فقط (في حال تعطيل النطاق العام).
     */
    public function scopeActive(Builder $query)
    {
        return $query->where($this->qualifyColumn($this->getActiveColumnName()), true);
    }

    /**
     * النطاق المحلي: لجلب العناصر غير المفعلة فقط.
     */
    public function scopeInactive(Builder $query)
    {
        return $query->where($this->qualifyColumn($this->getActiveColumnName()), false);
    }
}

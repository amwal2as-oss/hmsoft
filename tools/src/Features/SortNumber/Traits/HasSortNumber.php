<?php

namespace HMsoft\Tools\Features\SortNumber\Traits;

use Illuminate\Database\Eloquent\Model;

trait HasSortNumber
{
    public static function bootHasSortNumber()
    {
        static::creating(function (Model $model) {
            $column = $model->getSortNumberColumnName();

            // إذا لم يقم المطور بتمرير قيمة للترتيب (أو كانت null)، نقوم بحسابها تلقائياً
            if (is_null($model->getAttribute($column))) {
                $model->setAttribute($column, $model->calculateNextSortNumber($column));
            }
        });
    }

    /**
     * القيمة الافتراضية لاسم حقل الترتيب.
     */
    public function getSortNumberColumnName(): string
    {
        // يسمح للمطور بتعريف ثابت SORT_COLUMN في المودل أو تعريف الخاصية sortNumberColumn
        if (defined('static::SORT_COLUMN')) {
            return static::SORT_COLUMN;
        }

        return property_exists($this, 'sortNumberColumn') ? $this->sortNumberColumn : 'sort_number';
    }

    /**
     * دالة مساعدة لحساب رقم الترتيب التالي.
     */
    protected function calculateNextSortNumber(string $column): int
    {
        return (int) static::query()->max($column) + 1;
    }
}

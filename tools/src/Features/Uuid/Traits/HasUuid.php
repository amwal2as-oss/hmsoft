<?php

namespace HMsoft\Tools\Features\Uuid\Traits;

use Illuminate\Support\Str;

trait HasUuid
{
    public function initializeHasUuid(): void
    {
        // نقوم بتعطيل الزيادة التلقائية فقط إذا كان حقل الـ UUID هو المفتاح الأساسي للجدول
        if ($this->getUuidColumnName() === $this->getKeyName()) {
            $this->keyType = 'string';
            $this->incrementing = false;
        }
    }

    public static function bootHasUuid(): void
    {
        static::creating(function ($model) {
            $column = $model->getUuidColumnName();
            if (empty($model->{$column})) {
                $model->{$column} = $model->generateUuid();
            }
        });
    }

    /**
     * الافتراضي: استخدام المفتاح الأساسي (id).
     * يمكن للمطور عمل Override لهذه الدالة أو تعريف ثابت UUID_COLUMN.
     */
    public function getUuidColumnName(): string
    {
        return defined('static::UUID_COLUMN') ? static::UUID_COLUMN : $this->getKeyName();
    }

    /**
     * الافتراضي: توليد UUID V4.
     * يمكن للمطور عمل Override واستخدام Str::orderedUuid() لأداء أسرع في قواعد البيانات.
     */
    public function generateUuid(): string
    {
        return (string) Str::uuid();
    }
}

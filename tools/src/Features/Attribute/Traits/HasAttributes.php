<?php

namespace HMsoft\Tools\Features\Attribute\Traits;

use HMsoft\Tools\Features\Attribute\Models\AttributeValue;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait HasAttributes
{
    public static function bootHasAttributes()
    {
        static::deleting(function ($model) {
            $model->attributeValues()->delete();
        });
    }

    public function attributeValues(): MorphMany
    {
        return $this->morphMany(AttributeValue::class, 'owner');
    }
}

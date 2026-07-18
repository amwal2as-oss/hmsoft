<?php

namespace HMsoft\Tools\Features\Attribute\Models;

use HMsoft\Tools\Features\Attribute\Support\EavConfig;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EavValueTranslation extends Model
{
    protected $guarded = ['id'];

    public function getTable()
    {
        return EavConfig::table('value_translations') ?: 'eav_value_translations';
    }

    public function value(): BelongsTo
    {
        return $this->belongsTo(EavValue::class, 'value_id');
    }
}

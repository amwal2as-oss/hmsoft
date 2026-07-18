<?php

namespace HMsoft\Tools\Features\Attribute\Models;

use HMsoft\Tools\Features\Attribute\Support\EavConfig;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EavValueOption extends Model
{
    public $timestamps = false;
    protected $guarded = ['id'];
    const UPDATED_AT = null;

    public function getTable()
    {
        return EavConfig::table('value_options') ?: 'eav_value_options';
    }

    public function value(): BelongsTo
    {
        return $this->belongsTo(EavValue::class, 'value_id');
    }

    public function option(): BelongsTo
    {
        return $this->belongsTo(AttributeOption::class, 'attribute_option_id');
    }
}

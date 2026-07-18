<?php

namespace HMsoft\Tools\Features\Attribute\Models;

use HMsoft\Tools\Features\Attribute\Support\EavConfig;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttributeTranslation extends Model
{
    protected $guarded = ['id'];

    public function getTable()
    {
        return EavConfig::table('attribute_translations') ?: 'eav_attribute_translations';
    }

    public function attribute(): BelongsTo
    {
        return $this->belongsTo(Attribute::class, 'attribute_id');
    }
}

<?php

namespace HMsoft\Tools\Features\Attribute\Models;

use HMsoft\Tools\Features\Attribute\Support\EavConfig;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttributeCategory extends Model
{
    public $timestamps = false;
    protected $guarded = ['id'];

    public function getTable()
    {
        return EavConfig::table('attribute_categories') ?: 'eav_attribute_categories';
    }

    public function attribute(): BelongsTo
    {
        return $this->belongsTo(Attribute::class, 'attribute_id');
    }
}

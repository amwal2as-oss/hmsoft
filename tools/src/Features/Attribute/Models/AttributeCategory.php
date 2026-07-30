<?php

namespace HMsoft\Tools\Features\Attribute\Models;

use HMsoft\Tools\Features\Attribute\Support\EavConfig;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

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

    public function category(): MorphTo
    {
        // استخدام __FUNCTION__ يجعل Laravel يربط الأعمدة بشكل دقيق جداً
        return $this->morphTo(__FUNCTION__, 'category_type', 'category_id');
    }
}
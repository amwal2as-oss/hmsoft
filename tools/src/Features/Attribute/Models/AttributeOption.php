<?php

namespace HMsoft\Tools\Features\Attribute\Models;

use HMsoft\Tools\Features\Active\Contracts\Activable;
use HMsoft\Tools\Features\Active\Traits\HasActiveScope;
use HMsoft\Tools\Features\Attribute\Support\EavConfig;
use HMsoft\Tools\Features\Audit\Traits\HasDynamicSyncAndAudit;
use HMsoft\Tools\Features\Translations\Traits\HasTranslations;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AttributeOption extends Model implements Activable
{
    use HasActiveScope,
        HasDynamicSyncAndAudit,
        HasTranslations;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'is_active'  => 'boolean',
            'is_default' => 'boolean',
            'sort_number' => 'integer',
        ];
    }

    public function getTranslationRelationKey(): string
    {
        return "attribute_option_id";
    }

    public function getTable()
    {
        return EavConfig::table('attribute_options') ?: 'eav_attribute_options';
    }

    public function attribute(): BelongsTo
    {
        return $this->belongsTo(Attribute::class, 'attribute_id');
    }

    public function translations(): HasMany
    {
        return $this->hasMany(AttributeOptionTranslation::class, 'attribute_option_id');
    }

    public function translation()
    {
        return $this->hasOne(AttributeOptionTranslation::class, 'attribute_option_id')
            ->where('locale', app()->getLocale());
    }
}

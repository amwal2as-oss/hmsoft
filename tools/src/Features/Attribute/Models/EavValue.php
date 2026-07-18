<?php

namespace HMsoft\Tools\Features\Attribute\Models;

use HMsoft\Tools\Features\Attribute\Support\EavConfig;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class EavValue extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'value_number'  => 'decimal:6',
            'value_boolean' => 'boolean',
            'value_date'    => 'date',
        ];
    }

    public function getTable()
    {
        return EavConfig::table('values') ?: 'eav_values';
    }

    public function valuable(): MorphTo
    {
        return $this->morphTo();
    }

    public function attribute(): BelongsTo
    {
        return $this->belongsTo(Attribute::class, 'attribute_id');
    }

    public function translations(): HasMany
    {
        return $this->hasMany(EavValueTranslation::class, 'value_id');
    }

    public function translation()
    {
        return $this->hasOne(EavValueTranslation::class, 'value_id')
            ->where('locale', app()->getLocale());
    }

    public function selectedOptions(): HasMany
    {
        return $this->hasMany(EavValueOption::class, 'value_id');
    }
}

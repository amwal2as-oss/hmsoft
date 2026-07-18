<?php

namespace HMsoft\Tools\Features\Attribute\Models;

use HMsoft\Tools\Features\Active\Contracts\Activable;
use HMsoft\Tools\Features\Active\Traits\HasActiveScope;
use HMsoft\Tools\Features\Attribute\Enums\InputTypeEnum;
use HMsoft\Tools\Features\Attribute\Enums\ValueTypeEnum;
use HMsoft\Tools\Features\Attribute\Support\EavConfig;
use HMsoft\Tools\Features\DynamicFilters\Contracts\AutoFilterable;
use HMsoft\Tools\Features\DynamicFilters\Traits\IsAutoFilterable;
use HMsoft\Tools\Features\SortNumber\Contracts\Sortable;
use HMsoft\Tools\Features\SortNumber\Traits\HasSortNumber;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Attribute extends Model implements AutoFilterable, Activable, Sortable
{
    use IsAutoFilterable, HasActiveScope, HasSortNumber, SoftDeletes;

    protected $table = 'eav_attributes';
    protected $guarded = ['id'];

    public const DEFAULT_INCLUDES = ['translations', 'options.translations', 'categories'];

    protected function casts(): array
    {
        return [
            'input_type'    => InputTypeEnum::class,
            'value_type'    => ValueTypeEnum::class,
            'default_value' => 'array',
            'validation_rules' => 'array',
            'sort_number'   => 'integer',
            'is_required'   => 'boolean',
            'is_active'     => 'boolean',
            'is_filterable' => 'boolean',
            'is_sortable'   => 'boolean',
            'is_searchable' => 'boolean',
        ];
    }

    public function getTable()
    {
        return EavConfig::table('attributes') ?: parent::getTable();
    }

    public function translations(): HasMany
    {
        return $this->hasMany(AttributeTranslation::class, 'attribute_id');
    }

    public function translation()
    {
        return $this->hasOne(AttributeTranslation::class, 'attribute_id')
            ->where('locale', app()->getLocale());
    }

    public function options(): HasMany
    {
        return $this->hasMany(AttributeOption::class, 'attribute_id')->orderBy('sort_number');
    }

    public function categories(): HasMany
    {
        return $this->hasMany(AttributeCategory::class, 'attribute_id');
    }

    public function values(): HasMany
    {
        return $this->hasMany(EavValue::class, 'attribute_id');
    }

    public function scopeForEntity(Builder $query, string $entityType): Builder
    {
        return $query->where('entity_type', $entityType);
    }

    public function scopeFilterable(Builder $query): Builder
    {
        return $query->where('is_filterable', true);
    }

    public function getMorphClass(): string
    {
        return 'eav_attribute';
    }

    /** @deprecated Use entity_type */
    public function getScopeAttribute(): ?string
    {
        return $this->entity_type;
    }
}

<?php

namespace HMsoft\Tools\Features\Attribute\Models;

use HMsoft\Tools\Features\Active\Contracts\Activable;
use HMsoft\Tools\Features\Active\Traits\HasActiveScope;
use HMsoft\Tools\Features\Attribute\Enums\InputTypeEnum;
use HMsoft\Tools\Features\Attribute\Enums\ValueTypeEnum;
use HMsoft\Tools\Features\Attribute\Support\EavConfig;
use HMsoft\Tools\Features\Audit\Traits\HasDynamicSyncAndAudit;
use HMsoft\Tools\Features\DynamicFilters\Contracts\AutoFilterable;
use HMsoft\Tools\Features\DynamicFilters\Traits\IsAutoFilterable;
use HMsoft\Tools\Features\SortNumber\Contracts\Sortable;
use HMsoft\Tools\Features\SortNumber\Traits\HasSortNumber;
use HMsoft\Tools\Features\Media\Traits\HasMedia;
use HMsoft\Tools\Features\Translations\Traits\HasTranslations;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Attribute extends Model implements AutoFilterable, Activable, Sortable
{
    use IsAutoFilterable,
        HasActiveScope,
        HasSortNumber,
        HasDynamicSyncAndAudit,
        SoftDeletes,
        HasMedia,
        HasTranslations;

    protected $table = 'eav_attributes';
    protected $guarded = ['id'];

    public const DEFAULT_INCLUDES = [
        'translations',
        'options.translations',
        'categories.category'
    ];
    public const MEDIA_FOLDER = 'attributes';

    protected array $cmsMediaFields = ['icon'];
    public string $cmsMediaSet = 'attributes';

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

    public function getTranslationRelationKey(): string
    {
        return "attribute_id";
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

    public function getScopeAttribute(): ?string
    {
        return $this->entity_type;
    }

    public function getRelationshipsExtra(): array
    {
        return [
            'translation' => 'translation',
            'categories' => ['categories', 'categories.category'],
        ];
    }

    public function getFieldSelectionMapExtra(): array
    {
        return [
            'title' => 'translation.title',
            'category_id' => 'categories.category_id',
            'category_ids' => 'categories.category_id',
        ];
    }

    public function getFilterableExtra(): array
    {
        return [
            'title',
            'translation.title',
            'categories.category_id',
            'category_id',
            'category_ids',
            'input_type',
            'code',
            'entity_type',
            'is_active',
            'is_filterable',
            'is_sortable',
            'is_searchable',
            'is_required',
        ];
    }

    public function getSortableExtra(): array
    {
        return [
            'sort_number',
            'title',
            'translation.title',
            'created_at',
        ];
    }

    public function defineGlobalSearchRelatedAttributes(): array
    {
        return [
            'translation' => ['title', 'placeholder', 'help_text'],
        ];
    }
}

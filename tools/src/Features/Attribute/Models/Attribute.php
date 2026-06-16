<?php

namespace HMsoft\Tools\Features\Attribute\Models;

use HMsoft\Tools\Features\Active\Contracts\Activable;
use HMsoft\Tools\Features\Active\Traits\HasActiveScope;
use HMsoft\Tools\Features\DynamicFilters\Contracts\AutoFilterable;
use HMsoft\Tools\Features\DynamicFilters\Traits\IsAutoFilterable;
use HMsoft\Tools\Features\Media\Traits\HasMedia;
use HMsoft\Tools\Features\SortNumber\Contracts\Sortable;
use HMsoft\Tools\Features\SortNumber\Traits\HasSortNumber;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Attribute extends Model implements AutoFilterable, Activable, Sortable
{
    use HasMedia, IsAutoFilterable, HasActiveScope, HasSortNumber;

    protected $table = 'attributes';
    protected $guarded = ['id'];
    protected array $cmsMediaFields = ['image'];

    public const MEDIA_FOLDER = 'attribute';
    public const DEFAULT_INCLUDES = ['translations', 'options.translations'];

    protected function casts(): array
    {
        return [
            'sort_number'   => 'integer',
            'is_active'     => 'boolean',
            'is_filterable' => 'boolean',
            'is_required'   => 'boolean',
            'category_ids'  => 'array',
        ];
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

    public function values(): HasMany
    {
        return $this->hasMany(AttributeValue::class, 'attribute_id');
    }

    public function getMorphClass()
    {
        return 'attribute';
    }
}

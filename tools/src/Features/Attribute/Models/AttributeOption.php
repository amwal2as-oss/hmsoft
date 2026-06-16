<?php

namespace HMsoft\Tools\Features\Attribute\Models;

use HMsoft\Tools\Features\Active\Contracts\Activable;
use HMsoft\Tools\Features\Active\Traits\HasActiveScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AttributeOption extends Model implements Activable
{
    use HasActiveScope;

    protected $table = 'attribute_options';
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'is_active'   => 'boolean',
            'sort_number' => 'integer',
        ];
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

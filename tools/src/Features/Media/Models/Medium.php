<?php

namespace HMsoft\Tools\Features\Media\Models;

use HMsoft\Tools\Features\DynamicFilters\Contracts\AutoFilterable;
use HMsoft\Tools\Features\DynamicFilters\Traits\IsAutoFilterable;
use Illuminate\Database\Eloquent\Casts\Attribute as EloquentAttribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class Medium extends Model implements AutoFilterable
{

    use  IsAutoFilterable;

    public const DEFAULT_INCLUDES = ['translations'];

    protected $table = 'media';
    protected $guarded = ['id'];
    protected $appends = ['file_url'];

    protected $with = [
        'translations'
    ];

    protected function casts(): array
    {
        return [
            'is_default'  => 'boolean',
            'sort_number' => 'integer',
            'created_at'  => 'datetime',
            'updated_at'  => 'datetime',
        ];
    }

    public function owner(): MorphTo
    {
        return $this->morphTo();
    }

    public function translations(): HasMany
    {
        return $this->hasMany(MediumTranslation::class, 'medium_id');
    }

    protected function fileUrl(): EloquentAttribute
    {
        $defaultFile = config('cms_media.placeholders.default');
        $disk = config('cms_media.disk', 'public');

        return EloquentAttribute::make(
            get: function () use ($defaultFile, $disk) {
                if (!is_null($this->file_path)) {
                    return filter_var($this->file_path, FILTER_VALIDATE_URL)
                        ? $this->file_path
                        : Storage::disk($disk)->url($this->file_path);
                }
                return $defaultFile ? asset($defaultFile) : null;
            }
        );
    }
}

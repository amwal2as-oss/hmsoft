<?php

namespace HMsoft\Tools\Features\Media\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MediumTranslation extends Model
{
    protected $table = 'media_translations';
    protected $guarded = ['id'];
    public $timestamps = false;

    public function medium(): BelongsTo
    {
        return $this->belongsTo(Medium::class, 'medium_id');
    }
}

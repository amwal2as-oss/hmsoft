<?php

namespace HMsoft\Tools\Features\Audit\Models;

use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\User;
use HMsoft\Tools\Features\DynamicFilters\Contracts\AutoFilterable;
use HMsoft\Tools\Features\DynamicFilters\Traits\IsAutoFilterable;
use HMsoft\Tools\Features\SortNumber\Contracts\Sortable;
use HMsoft\Tools\Features\SortNumber\Traits\HasSortNumber;
use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model implements AutoFilterable
{
    use  IsAutoFilterable;

    // Disable updated_at since this is an append-only ledger
    public const UPDATED_AT = null;

    protected $guarded = [];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
    ];

    public function auditable(): MorphTo
    {
        return $this->morphTo();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

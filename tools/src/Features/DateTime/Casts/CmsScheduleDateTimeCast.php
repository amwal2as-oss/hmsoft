<?php

namespace HMsoft\Tools\Features\DateTime\Casts;

use HMsoft\Tools\Features\DateTime\Support\CmsDateTime;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Store schedule fields as UTC wall-clock in DATETIME columns.
 * Incoming API values (offset, Z, or naive store time) go through CmsDateTime::fromApi.
 *
 * This is an Eloquent CastsAttributes. It is not Spatie CmsDateTimeCast.
 */
class CmsScheduleDateTimeCast implements CastsAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes): ?Carbon
    {
        if ($value === null || $value === '') {
            return null;
        }

        return Carbon::parse($value, CmsDateTime::storageTimezone());
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return CmsDateTime::fromApi($value)
            ->timezone(CmsDateTime::storageTimezone())
            ->format('Y-m-d H:i:s');
    }
}

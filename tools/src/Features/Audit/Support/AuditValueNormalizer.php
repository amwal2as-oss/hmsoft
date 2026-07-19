<?php

namespace HMsoft\Tools\Features\Audit\Support;

use DateTimeInterface;
use HMsoft\Tools\Features\DateTime\Support\CmsDateTime;
use Illuminate\Support\Carbon;

final class AuditValueNormalizer
{
    /**
     * Normalize model attributes for audit storage (always UTC ISO8601 for datetimes).
     *
     * @param  array<string|int, mixed>  $values
     * @return array<string|int, mixed>
     */
    public static function normalize(array $values): array
    {
        $normalized = [];

        foreach ($values as $key => $value) {
            if ($value instanceof DateTimeInterface) {
                $normalized[$key] = Carbon::instance($value)
                    ->timezone(CmsDateTime::storageTimezone())
                    ->toIso8601String();
                continue;
            }

            if (is_array($value)) {
                $normalized[$key] = self::normalize($value);
                continue;
            }

            $normalized[$key] = $value;
        }

        return $normalized;
    }
}

<?php

namespace HMsoft\Tools\Features\DateTime\Actions;

use HMsoft\Tools\Features\DateTime\Data\DateTimeConvertData;
use HMsoft\Tools\Features\DateTime\Support\CmsDateTime;
use Illuminate\Support\Carbon;
use InvalidArgumentException;

final class ConvertDateTimeAction
{
    public function execute(string $value, string $direction): DateTimeConvertData
    {
        $direction = strtolower(trim($direction));

        $result = match ($direction) {
            'to_api' => CmsDateTime::toApi(
                Carbon::parse($value, CmsDateTime::storageTimezone()),
            ) ?? '',
            'to_storage', 'to_utc' => CmsDateTime::fromApi($value)->toIso8601String(),
            default => throw new InvalidArgumentException("Invalid direction [{$direction}]. Use: to_api, to_storage"),
        };

        return new DateTimeConvertData(
            input: $value,
            direction: $direction,
            storage_timezone: CmsDateTime::storageTimezone(),
            api_timezone: CmsDateTime::apiTimezone(),
            result: $result,
        );
    }
}

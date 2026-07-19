<?php

namespace HMsoft\Tools\Features\DateTime\Casts;

use Spatie\LaravelData\Casts\DateTimeInterfaceCast;

/**
 * Parses API datetime input in the resolved API timezone and stores as UTC.
 */
class CmsDateTimeCast extends DateTimeInterfaceCast
{
    public function __construct(
        null|string|array $format = null,
        ?string $type = null,
        ?string $setTimeZone = null,
        ?string $timeZone = null,
    ) {
        parent::__construct(
            format: $format,
            type: $type,
            setTimeZone: $setTimeZone ?? config('cms_datetime.storage_timezone', 'UTC'),
            timeZone: $timeZone ?? config('cms_datetime.api_timezone', 'Asia/Damascus'),
        );
    }
}

<?php

namespace HMsoft\Tools\Features\DateTime\Data;

use HMsoft\Tools\Features\DynamicFilters\Data\BaseData;

class DateTimeNowData extends BaseData
{
    public function __construct(
        public readonly string $storage_timezone,
        public readonly string $api_timezone,
        public readonly string $now_utc,
        public readonly string $now_api,
    ) {}
}

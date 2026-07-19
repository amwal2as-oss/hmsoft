<?php

namespace HMsoft\Tools\Features\DateTime\Data;

use HMsoft\Tools\Features\DynamicFilters\Data\BaseData;

class DateTimeConfigData extends BaseData
{
    public function __construct(
        public readonly string $storage_timezone,
        public readonly string $default_api_timezone,
        public readonly string $resolved_api_timezone,
        public readonly string $resolver,
        public readonly string $date_format,
    ) {}
}

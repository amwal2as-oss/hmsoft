<?php

namespace HMsoft\Tools\Features\DateTime\Data;

use HMsoft\Tools\Features\DynamicFilters\Data\BaseData;

class DateTimeConvertData extends BaseData
{
    public function __construct(
        public readonly string $input,
        public readonly string $direction,
        public readonly string $storage_timezone,
        public readonly string $api_timezone,
        public readonly string $result,
    ) {}
}

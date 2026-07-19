<?php

namespace HMsoft\Tools\Features\DateTime\Actions;

use HMsoft\Tools\Features\DateTime\Data\DateTimeConfigData;
use HMsoft\Tools\Features\DateTime\Support\CmsDateTime;
use HMsoft\Tools\Features\DateTime\Support\DateTimeConfig;

final class GetDateTimeConfigAction
{
    public function execute(): DateTimeConfigData
    {
        return new DateTimeConfigData(
            storage_timezone: CmsDateTime::storageTimezone(),
            default_api_timezone: DateTimeConfig::defaultApiTimezone(),
            resolved_api_timezone: CmsDateTime::apiTimezone(),
            resolver: DateTimeConfig::resolver(),
            date_format: DateTimeConfig::dateFormat(),
        );
    }
}

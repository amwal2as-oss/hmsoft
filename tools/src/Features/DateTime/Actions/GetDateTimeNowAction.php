<?php

namespace HMsoft\Tools\Features\DateTime\Actions;

use HMsoft\Tools\Features\DateTime\Data\DateTimeNowData;
use HMsoft\Tools\Features\DateTime\Support\CmsDateTime;

final class GetDateTimeNowAction
{
    public function execute(): DateTimeNowData
    {
        $nowUtc = CmsDateTime::nowUtc();

        return new DateTimeNowData(
            storage_timezone: CmsDateTime::storageTimezone(),
            api_timezone: CmsDateTime::apiTimezone(),
            now_utc: $nowUtc->toIso8601String(),
            now_api: CmsDateTime::toApi($nowUtc) ?? '',
        );
    }
}

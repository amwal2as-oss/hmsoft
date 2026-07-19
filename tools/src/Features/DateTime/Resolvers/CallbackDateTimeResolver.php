<?php

namespace HMsoft\Tools\Features\DateTime\Resolvers;

use HMsoft\Tools\Features\DateTime\Contracts\DateTimeResolverInterface;
use HMsoft\Tools\Features\DateTime\Support\CmsDateTime;
use HMsoft\Tools\Features\DateTime\Support\DateTimeConfig;
use RuntimeException;

final class CallbackDateTimeResolver implements DateTimeResolverInterface
{
    public function apiTimezone(): string
    {
        if (! CmsDateTime::hasApiTimezoneCallback()) {
            throw new RuntimeException(
                'CMS_DATETIME_RESOLVER=callback requires CmsDateTime::resolveApiTimezoneUsing() in your AppServiceProvider.'
            );
        }

        $timezone = CmsDateTime::resolveApiTimezone();

        if ($timezone !== null && DateTimeConfig::isValidTimezone($timezone)) {
            return $timezone;
        }

        return DateTimeConfig::defaultApiTimezone();
    }
}

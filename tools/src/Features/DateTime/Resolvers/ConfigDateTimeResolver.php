<?php

namespace HMsoft\Tools\Features\DateTime\Resolvers;

use HMsoft\Tools\Features\DateTime\Contracts\DateTimeResolverInterface;
use HMsoft\Tools\Features\DateTime\Support\DateTimeConfig;

final class ConfigDateTimeResolver implements DateTimeResolverInterface
{
    public function apiTimezone(): string
    {
        return DateTimeConfig::defaultApiTimezone();
    }
}

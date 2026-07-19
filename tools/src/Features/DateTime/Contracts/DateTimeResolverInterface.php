<?php

namespace HMsoft\Tools\Features\DateTime\Contracts;

interface DateTimeResolverInterface
{
    /**
     * Resolve the API output timezone for the current request/context.
     */
    public function apiTimezone(): string;
}

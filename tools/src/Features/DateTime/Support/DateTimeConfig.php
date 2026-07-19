<?php

namespace HMsoft\Tools\Features\DateTime\Support;

final class DateTimeConfig
{
    public static function isEnabled(): bool
    {
        return (bool) config('cms_datetime.enabled', true);
    }

    public static function shouldRegisterRoutes(): bool
    {
        return self::isEnabled() && (bool) config('cms_datetime.register_routes', true);
    }

    public static function storageTimezone(): string
    {
        return (string) config('cms_datetime.storage_timezone', 'UTC');
    }

    public static function defaultApiTimezone(): string
    {
        return (string) config('cms_datetime.api_timezone', 'Asia/Damascus');
    }

    public static function resolver(): string
    {
        return (string) config('cms_datetime.resolver', 'config');
    }

    public static function resolverClass(): ?string
    {
        $class = config('cms_datetime.resolver_class')
            ?? config('cms_datetime.custom_resolver');

        return is_string($class) && $class !== '' ? $class : null;
    }

    public static function dateFormat(): string
    {
        return (string) config('cms_datetime.date_format', DATE_ATOM);
    }

    /**
     * @return string[]
     */
    public static function transformKeys(): array
    {
        $keys = config('cms_datetime.transform_keys', []);

        return is_array($keys) ? array_values(array_filter($keys, is_string(...))) : [];
    }

    public static function isValidTimezone(string $timezone): bool
    {
        return in_array($timezone, timezone_identifiers_list(), true);
    }
}

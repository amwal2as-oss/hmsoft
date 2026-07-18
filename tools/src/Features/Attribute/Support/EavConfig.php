<?php

namespace HMsoft\Tools\Features\Attribute\Support;

class EavConfig
{
    public static function isEnabled(): bool
    {
        return (bool) config('cms_eav.enabled', true);
    }

    public static function filterPrefix(): string
    {
        return (string) config('cms_eav.filter_prefix', 'eav');
    }

    public static function filterKey(string $code): string
    {
        return self::filterPrefix() . '.' . $code;
    }

    public static function definitionCacheTtl(): int
    {
        return (int) config('cms_eav.definition_cache_ttl', 3600);
    }

    public static function table(string $key): string
    {
        return (string) config("cms_eav.tables.{$key}");
    }
}

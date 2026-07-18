<?php

namespace HMsoft\Tools\Features\Attribute\Services;

use HMsoft\Tools\Features\Attribute\Models\Attribute;
use HMsoft\Tools\Features\Attribute\Support\EavConfig;
use Illuminate\Support\Facades\Cache;

class EavFilterRegistrar
{
    public static function filterableKeysForEntity(string $entityType): array
    {
        if (! EavConfig::isEnabled()) {
            return [];
        }

        $prefix = EavConfig::filterPrefix();

        return Cache::remember(
            "eav_filter_keys_{$entityType}",
            EavConfig::definitionCacheTtl(),
            fn () => Attribute::query()
                ->forEntity($entityType)
                ->filterable()
                ->where('is_active', true)
                ->get(['id', 'code'])
                ->map(fn ($attr) => $attr->code
                    ? "{$prefix}.{$attr->code}"
                    : "{$prefix}.id_{$attr->id}")
                ->values()
                ->all()
        );
    }

    public static function sortableKeysForEntity(string $entityType): array
    {
        if (! EavConfig::isEnabled()) {
            return [];
        }

        $prefix = EavConfig::filterPrefix();

        return Cache::remember(
            "eav_sort_keys_{$entityType}",
            EavConfig::definitionCacheTtl(),
            fn () => Attribute::query()
                ->forEntity($entityType)
                ->where('is_sortable', true)
                ->where('is_active', true)
                ->get(['id', 'code'])
                ->map(fn ($attr) => $attr->code
                    ? "{$prefix}.{$attr->code}"
                    : "{$prefix}.id_{$attr->id}")
                ->values()
                ->all()
        );
    }

    public static function searchableKeysForEntity(string $entityType): array
    {
        if (! EavConfig::isEnabled()) {
            return [];
        }

        return Cache::remember(
            "eav_search_keys_{$entityType}",
            EavConfig::definitionCacheTtl(),
            fn () => Attribute::query()
                ->forEntity($entityType)
                ->where('is_searchable', true)
                ->where('is_active', true)
                ->get(['id', 'code'])
                ->map(fn ($attr) => $attr->code ?: (string) $attr->id)
                ->values()
                ->all()
        );
    }

    public static function flushEntityCache(string $entityType): void
    {
        Cache::forget("eav_filter_keys_{$entityType}");
        Cache::forget("eav_sort_keys_{$entityType}");
        Cache::forget("eav_search_keys_{$entityType}");
    }
}

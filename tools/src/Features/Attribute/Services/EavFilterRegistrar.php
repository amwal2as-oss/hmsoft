<?php

namespace HMsoft\Tools\Features\Attribute\Services;

use HMsoft\Tools\Features\Attribute\Models\Attribute;
use HMsoft\Tools\Features\Attribute\Support\EavConfig;
use Illuminate\Support\Facades\Cache;

class EavFilterRegistrar
{
    public static function filterableKeysForEntity(string $entityType): array
    {
        return self::keysForEntity($entityType, 'filterable');
    }

    public static function sortableKeysForEntity(string $entityType): array
    {
        return self::keysForEntity($entityType, 'sortable');
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

    public static function fieldMapForEntity(string $entityType): array
    {
        if (! EavConfig::isEnabled()) {
            return [];
        }

        return Cache::remember(
            "eav_field_map_{$entityType}",
            EavConfig::definitionCacheTtl(),
            function () use ($entityType) {
                $prefix = EavConfig::filterPrefix();
                $map = [];

                $attributes = Attribute::query()
                    ->forEntity($entityType)
                    ->where('is_active', true)
                    ->where(function ($query) {
                        $query->where('is_filterable', true)
                            ->orWhere('is_sortable', true);
                    })
                    ->get(['id', 'code']);

                foreach ($attributes as $attribute) {
                    $canonical = 'attribute_'.$attribute->id;
                    $map['attr_'.$attribute->id] = $canonical;
                    $map["{$prefix}.id_{$attribute->id}"] = $canonical;

                    if ($attribute->code) {
                        $map["{$prefix}.{$attribute->code}"] = $canonical;
                    }
                }

                return $map;
            }
        );
    }

    public static function flushEntityCache(string $entityType): void
    {
        Cache::forget("eav_filter_keys_{$entityType}");
        Cache::forget("eav_filter_keys_aliases_{$entityType}");
        Cache::forget("eav_sort_keys_{$entityType}");
        Cache::forget("eav_sort_keys_aliases_{$entityType}");
        Cache::forget("eav_search_keys_{$entityType}");
        Cache::forget("eav_field_map_{$entityType}");
    }

    /**
     * @return list<string>
     */
    protected static function keysForEntity(string $entityType, string $mode): array
    {
        if (! EavConfig::isEnabled()) {
            return [];
        }

        $cacheKey = $mode === 'sortable'
            ? "eav_sort_keys_aliases_{$entityType}"
            : "eav_filter_keys_aliases_{$entityType}";

        return Cache::remember(
            $cacheKey,
            EavConfig::definitionCacheTtl(),
            function () use ($entityType, $mode) {
                $prefix = EavConfig::filterPrefix();
                $query = Attribute::query()
                    ->forEntity($entityType)
                    ->where('is_active', true);

                if ($mode === 'sortable') {
                    $query->where('is_sortable', true);
                } else {
                    $query->filterable();
                }

                $keys = [];

                foreach ($query->get(['id', 'code']) as $attribute) {
                    $keys[] = 'attribute_'.$attribute->id;
                    $keys[] = 'attr_'.$attribute->id;
                    $keys[] = "{$prefix}.id_{$attribute->id}";

                    if ($attribute->code) {
                        $keys[] = "{$prefix}.{$attribute->code}";
                    }
                }

                return array_values(array_unique($keys));
            }
        );
    }
}

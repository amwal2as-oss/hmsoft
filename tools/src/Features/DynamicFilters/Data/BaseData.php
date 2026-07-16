<?php

namespace HMsoft\Tools\Features\DynamicFilters\Data;

use Spatie\LaravelData\Data;
use Spatie\LaravelData\DataCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Arr;
use ReflectionProperty;
use ReflectionUnionType;
use ReflectionNamedType;

/**
 * Base response DTO with optional JSON field pruning driven by query parameters.
 *
 * Works with Spatie Laravel Data and supports:
 * - `?fields=id,title,category.name` — whitelist (include only listed fields, supports dot notation)
 * - `?except=password,translations` — blacklist (exclude listed fields, supports dot notation)
 *
 * When both are present, `fields` is applied first (whitelist), then `except` removes any
 * remaining keys from the result.
 *
 * @see filterableCollect() For paginated lists / collections returned from controllers
 * @see toArray()           For single-object responses
 */
abstract class BaseData extends Data
{
    /** @var array<string, array<string, bool>> Runtime cache: class => property => isRawArray */
    protected static array $rawFieldsCache = [];

    /**
     * Recursion guard — prevents nested BaseData::toArray() from re-applying pruning.
     */
    protected static bool $isPruning = false;

    /**
     * Transform a list (array, Collection, paginator, or DataCollection) and apply field pruning.
     *
     * Typical controller usage after AutoFilterAndSortService:
     * ```php
     * $result['data'] = DecreeData::filterableCollect($result['data']);
     * ```
     */
    public static function filterableCollect(mixed $items): array
    {
        if (is_array($items) || $items instanceof Collection) {
            $collection = parent::collect($items, DataCollection::class);
        } else {
            $collection = parent::collect($items);
        }

        $request = request();
        $fields = static::parseQueryList($request->query('fields'));
        $except = static::parseQueryList($request->query('except'));

        if (is_object($collection) && method_exists($collection, 'only') && !empty($fields)) {
            $collection->only(...static::sanitizeFields($fields));
        }

        if (is_object($collection) && method_exists($collection, 'except') && !empty($except)) {
            $collection->except(...static::sanitizeFields($except));
        }

        if (static::$isPruning) {
            return $collection->toArray();
        }

        static::$isPruning = true;
        try {
            $array = $collection->toArray();
            $prunedArray = static::applyResponseFieldPruning($array, $fields, $except);
        } finally {
            static::$isPruning = false;
        }

        return $prunedArray;
    }

    /**
     * Serialize a single DTO and apply field pruning from the current request.
     */
    public function toArray(): array
    {
        if (static::$isPruning) {
            return parent::toArray();
        }

        static::$isPruning = true;
        try {
            $array = parent::toArray();
            $fields = static::parseQueryList(request()->query('fields'));
            $except = static::parseQueryList(request()->query('except'));
            $prunedArray = static::applyResponseFieldPruning($array, $fields, $except);
        } finally {
            static::$isPruning = false;
        }

        return $prunedArray;
    }

    /**
     * Parse a comma-separated query value into a trimmed list of field paths.
     *
     * @return string[]
     */
    protected static function parseQueryList(?string $value): array
    {
        if ($value === null || $value === '') {
            return [];
        }

        return array_values(array_filter(array_map('trim', explode(',', $value))));
    }

    /**
     * Apply whitelist (`fields`) and/or blacklist (`except`) pruning to a response array.
     *
     * Handles paginated payloads (`data` + `meta`/`links`), plain lists, and single objects.
     */
    protected static function applyResponseFieldPruning(array $array, array $fields, array $except): array
    {
        if (empty($fields) && empty($except)) {
            return $array;
        }

        // Paginated: { data: [...], meta: {...}, links: {...} }
        if (isset($array['data']) && is_array($array['data']) && (isset($array['meta']) || isset($array['links']))) {
            $array['data'] = static::pruneItemList($array['data'], $fields, $except);
            return $array;
        }

        // Plain list: [ {...}, {...} ]
        if (isset($array[0]) && is_array($array[0])) {
            return static::pruneItemList($array, $fields, $except);
        }

        return static::pruneSingleItem($array, $fields, $except);
    }

    /**
     * @param array<int, array<string, mixed>> $items
     * @return array<int, array<string, mixed>>
     */
    protected static function pruneItemList(array $items, array $fields, array $except): array
    {
        return array_map(
            fn(array $item) => static::pruneSingleItem($item, $fields, $except),
            $items
        );
    }

    /**
     * Whitelist then blacklist a single associative array item.
     */
    protected static function pruneSingleItem(array $item, array $fields, array $except): array
    {
        if (!empty($fields)) {
            $item = static::applyDeepArrayInclude($item, $fields);
        }

        if (!empty($except)) {
            $item = static::applyDeepArrayExclude($item, $except);
        }

        return $item;
    }

    /**
     * Include only the requested field paths (supports dot notation for nested keys).
     *
     * Examples:
     * - `fields=title`           → { title }
     * - `fields=category.name`   → { category: { name } }
     * - `fields=values.title`    → { values: [{ title }, ...] } for list relations
     */
    protected static function applyDeepArrayInclude(array $itemArray, array $requestedFields): array
    {
        $filtered = [];

        foreach ($requestedFields as $field) {
            if (!str_contains($field, '.')) {
                if (array_key_exists($field, $itemArray)) {
                    $filtered[$field] = $itemArray[$field];
                }
                continue;
            }

            $parts = explode('.', $field);
            $root = $parts[0];

            if (!array_key_exists($root, $itemArray)) {
                continue;
            }

            $internalPath = implode('.', array_slice($parts, 1));
            $targetArray = $itemArray[$root];

            if (!is_array($targetArray)) {
                continue;
            }

            if (array_is_list($targetArray)) {
                if (!isset($filtered[$root])) {
                    $filtered[$root] = array_fill(0, count($targetArray), []);
                }

                foreach ($targetArray as $index => $listItem) {
                    if (is_array($listItem) && Arr::has($listItem, $internalPath)) {
                        Arr::set($filtered[$root][$index], $internalPath, Arr::get($listItem, $internalPath));
                    }
                }
            } else {
                if (!isset($filtered[$root]) || !is_array($filtered[$root])) {
                    $filtered[$root] = [];
                }

                if (Arr::has($targetArray, $internalPath)) {
                    Arr::set($filtered[$root], $internalPath, Arr::get($targetArray, $internalPath));
                }
            }
        }

        return $filtered;
    }

    /**
     * Remove excluded field paths from an item (supports dot notation).
     *
     * Examples:
     * - `except=password`              → removes top-level key
     * - `except=category.translations`   → removes nested key inside category
     * - `except=values.content`        → removes content from each list item in values
     */
    protected static function applyDeepArrayExclude(array $itemArray, array $excludedFields): array
    {
        foreach ($excludedFields as $field) {
            if (!str_contains($field, '.')) {
                unset($itemArray[$field]);
                continue;
            }

            $parts = explode('.', $field);
            $root = array_shift($parts);
            $internalPath = implode('.', $parts);

            if (!array_key_exists($root, $itemArray) || !is_array($itemArray[$root])) {
                continue;
            }

            if (array_is_list($itemArray[$root])) {
                foreach ($itemArray[$root] as $index => $listItem) {
                    if (is_array($listItem)) {
                        Arr::forget($itemArray[$root][$index], $internalPath);
                    }
                }
            } else {
                Arr::forget($itemArray[$root], $internalPath);
            }
        }

        return $itemArray;
    }

    /**
     * Sanitize field names for Spatie DataCollection::only/except.
     *
     * Nested Data/DTO properties must be referenced by root key only;
     * raw array/scalar properties keep the full dot path.
     *
     * @param string[] $fields
     * @return string[]
     */
    protected static function sanitizeFields(array $fields): array
    {
        $safeFields = [];
        $className = static::class;

        foreach ($fields as $field) {
            $root = explode('.', $field)[0];
            if (static::isRawArrayField($className, $root)) {
                $safeFields[] = $root;
            } else {
                $safeFields[] = $field;
            }
        }

        return array_unique($safeFields);
    }

    /**
     * Detect whether a property is a plain array/scalar (true) or nested Data object (false).
     */
    protected static function isRawArrayField(string $className, string $propertyName): bool
    {
        if (isset(self::$rawFieldsCache[$className][$propertyName])) {
            return self::$rawFieldsCache[$className][$propertyName];
        }

        if (!property_exists($className, $propertyName)) {
            return self::$rawFieldsCache[$className][$propertyName] = true;
        }

        $property = new ReflectionProperty($className, $propertyName);
        $type = $property->getType();

        if (!$type) {
            return self::$rawFieldsCache[$className][$propertyName] = true;
        }

        $types = $type instanceof ReflectionUnionType ? $type->getTypes() : [$type];

        foreach ($types as $t) {
            if ($t instanceof ReflectionNamedType) {
                $typeName = $t->getName();
                if (is_subclass_of($typeName, Data::class) || $typeName === DataCollection::class) {
                    return self::$rawFieldsCache[$className][$propertyName] = false;
                }
            }
        }

        return self::$rawFieldsCache[$className][$propertyName] = true;
    }
}

<?php

namespace HMsoft\Tools\Features\DynamicFilters\Data;

use Spatie\LaravelData\Data;
use Spatie\LaravelData\DataCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Arr;
use ReflectionProperty;
use ReflectionUnionType;
use ReflectionNamedType;

abstract class BaseData extends Data
{
    protected static array $rawFieldsCache = [];

    // 💡 [الحل الجوهري]: قفل لمنع الفلترة المتداخلة (Recursion) في الكائنات الفرعية
    protected static bool $isPruning = false;

    /**
     * 1. فلترة المصفوفات (Collections / Pagination)
     */
    public static function filterableCollect(mixed $items): array
    {
        if (is_array($items) || $items instanceof Collection) {
            $collection = parent::collect($items, DataCollection::class);
        } else {
            $collection = parent::collect($items);
        }

        $request = request();

        if (is_object($collection) && method_exists($collection, 'only') && $request->filled('fields')) {
            $fields = array_map('trim', explode(',', $request->query('fields')));
            $collection->only(...static::sanitizeFields($fields));
        }

        if (is_object($collection) && method_exists($collection, 'except') && $request->filled('except')) {
            $except = array_map('trim', explode(',', $request->query('except')));
            $collection->except(...static::sanitizeFields($except));
        }

        // إذا كنا بالفعل داخل عملية فلترة، نعيد المصفوفة مباشرة لمنع تكرار العملية
        if (static::$isPruning) {
            return $collection->toArray();
        }

        static::$isPruning = true;
        try {
            $array = $collection->toArray();
            $prunedArray = static::applyDeepArrayPruningToCollection($array);
        } finally {
            // نضمن فك القفل دائماً حتى لو حدث خطأ (Exceptions)
            static::$isPruning = false;
        }

        return $prunedArray;
    }

    /**
     * 2. فلترة العناصر المفردة (Single Objects)
     */
    public function toArray(): array
    {
        // إذا كان الكائن الأب يقوم بالفلترة حالياً، فالكائن الابن يكتفي بالتحويل العادي
        if (static::$isPruning) {
            return parent::toArray();
        }

        static::$isPruning = true;
        try {
            $array = parent::toArray();
            $prunedArray = static::applyDeepArrayPruningToCollection($array);
        } finally {
            static::$isPruning = false;
        }

        return $prunedArray;
    }

    /**
     * 3. الموزع الذكي
     */
    protected static function applyDeepArrayPruningToCollection(array $array): array
    {
        $request = request();
        if (!$request->filled('fields')) return $array;

        $fields = array_map('trim', explode(',', $request->query('fields')));

        // أ. هل هي بيانات مجدولة (Paginated Data)؟
        if (isset($array['data']) && is_array($array['data']) && (isset($array['meta']) || isset($array['links']))) {
            $array['data'] = array_map(fn($item) => static::applyDeepArrayPruning($item, $fields), $array['data']);
            return $array;
        }

        // ب. هل هي مصفوفة عناصر (Normal Collection)؟
        if (isset($array[0]) && is_array($array[0])) {
            return array_map(fn($item) => static::applyDeepArrayPruning($item, $fields), $array);
        }

        // ج. هل هو عنصر مفرد (Single Item)
        return static::applyDeepArrayPruning($array, $fields);
    }

    /**
     * 4. محرك الفلترة الجراحية
     */
    protected static function applyDeepArrayPruning(array $itemArray, array $requestedFields): array
    {
        $filtered = [];

        foreach ($requestedFields as $field) {
            // الحالة الأولى: حقل رئيسي بالكامل (مثال: fields=vision)
            if (!str_contains($field, '.')) {
                if (array_key_exists($field, $itemArray)) {
                    $filtered[$field] = $itemArray[$field];
                }
                continue;
            }

            // الحالة الثانية: حقل فرعي عميق (مثال: fields=vision.id)
            $parts = explode('.', $field);
            $root = $parts[0];

            if (!array_key_exists($root, $itemArray)) continue;

            $internalPath = implode('.', array_slice($parts, 1));
            $targetArray = $itemArray[$root];

            if (!is_array($targetArray)) continue;

            // أ. إذا كانت البيانات عبارة عن قائمة (مثل values أو goals)
            if (array_is_list($targetArray)) {
                if (!isset($filtered[$root])) {
                    $filtered[$root] = array_fill(0, count($targetArray), []);
                }

                foreach ($targetArray as $index => $listItem) {
                    if (is_array($listItem) && Arr::has($listItem, $internalPath)) {
                        Arr::set($filtered[$root][$index], $internalPath, Arr::get($listItem, $internalPath));
                    }
                }
            }
            // ب. إذا كانت كائن مفرد (مثل vision أو mission)
            else {
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
     * 5. حماية Spatie (Sanitization)
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
     * 6. الاستنتاج الذكي لأنواع البيانات
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

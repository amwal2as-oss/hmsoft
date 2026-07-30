<?php

namespace HMsoft\Tools\Features\Attribute\Support;

use HMsoft\Tools\Features\Attribute\Models\Attribute;
use Illuminate\Support\Str;

class EavCodeGenerator
{
    /**
     * Resolve attribute code. Uses provided code or generates a generic unique one (e.g., attr_1).
     */
    public static function forAttribute(?string $code, string $entityType, array $locales, ?int $ignoreId = null): ?string
    {
        if ($code !== null && trim($code) !== '') {
            return self::normalize($code);
        }

        // Generate a unique code strictly using the fallback prefix
        return self::uniqueForEntity('attr', $entityType, $ignoreId);
    }

    /**
     * Resolve option code. Uses provided code or generates a generic unique one (e.g., opt_a1b2c).
     */
    public static function forOption(?string $code, array $locales): ?string
    {
        if ($code !== null && trim($code) !== '') {
            return self::normalize($code);
        }

        // Generate a random code strictly using the fallback prefix for options
        return 'opt_' . strtolower(Str::random(5));
    }

    public static function normalize(string $value): string
    {
        return Str::snake(Str::slug(trim($value), '_'));
    }

    protected static function uniqueForEntity(string $base, string $entityType, ?int $ignoreId = null): string
    {
        $candidate = $base;
        $suffix = 1;

        while (self::attributeCodeExists($candidate, $entityType, $ignoreId)) {
            $candidate = $base . '_' . $suffix;
            $suffix++;
        }

        return $candidate;
    }

    protected static function attributeCodeExists(string $code, string $entityType, ?int $ignoreId = null): bool
    {
        return Attribute::query()
            ->where('entity_type', $entityType)
            ->where('code', $code)
            ->when($ignoreId, fn($q) => $q->where('id', '!=', $ignoreId))
            ->exists();
    }
}

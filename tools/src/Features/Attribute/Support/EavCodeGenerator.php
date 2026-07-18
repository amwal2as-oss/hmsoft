<?php

namespace HMsoft\Tools\Features\Attribute\Support;

use HMsoft\Tools\Features\Attribute\Models\Attribute;
use Illuminate\Support\Str;

class EavCodeGenerator
{
    /**
     * Resolve attribute code. When omitted, generates a unique slug from the first locale title.
     */
    public static function forAttribute(?string $code, string $entityType, array $locales, ?int $ignoreId = null): ?string
    {
        if ($code !== null && trim($code) !== '') {
            return self::normalize($code);
        }

        $title = collect($locales)->first()['title'] ?? null;
        if (! $title) {
            return null;
        }

        return self::uniqueForEntity(self::normalize($title) ?: 'attribute', $entityType, $ignoreId);
    }

    /**
     * Resolve option code. Optional — returns null when omitted.
     */
    public static function forOption(?string $code, array $locales): ?string
    {
        if ($code !== null && trim($code) !== '') {
            return self::normalize($code);
        }

        $label = collect($locales)->first()['label']
            ?? collect($locales)->first()['title']
            ?? null;

        return $label ? (self::normalize($label) ?: null) : null;
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
            ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->exists();
    }
}

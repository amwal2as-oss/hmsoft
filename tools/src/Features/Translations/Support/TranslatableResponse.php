<?php

namespace HMsoft\Tools\Features\Translations\Support;

use HMsoft\Tools\Features\Translations\Contracts\TranslationResolver;
use Illuminate\Support\Collection;

/**
 * Static API for locale fallback. Delegates to the bound TranslationResolver
 * so apps can swap the engine without forking the package.
 */
class TranslatableResponse
{
    public static function formatTranslations(?Collection $translations): array
    {
        return self::map($translations);
    }

    /**
     * @param  list<string>|null  $fields
     * @return array<string, array<string, mixed>>
     */
    public static function map(mixed $source, ?array $fields = null): array
    {
        return self::resolver()->map($source, $fields);
    }

    /**
     * @param  list<string>|null  $fields
     * @return array<string, mixed>
     */
    public static function resolve(mixed $source, ?array $fields = null, ?string $locale = null): array
    {
        return self::resolver()->resolve($source, $fields, $locale);
    }

    public static function value(mixed $source, string $field, mixed $default = null, ?string $locale = null): mixed
    {
        return self::resolver()->value($source, $field, $default, $locale);
    }

    public static function pickScalar(array $localeToValue, mixed $default = null, ?string $locale = null): mixed
    {
        return self::resolver()->pickScalar($localeToValue, $default, $locale);
    }

    public static function process(array $data): array
    {
        return self::resolver()->process($data);
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public static function normalize(mixed $source): array
    {
        return self::resolver()->normalize($source);
    }

    public static function resolver(): TranslationResolver
    {
        if (function_exists('app') && app()->bound(TranslationResolver::class)) {
            return app(TranslationResolver::class);
        }

        return new DefaultTranslationResolver();
    }
}

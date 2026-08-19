<?php

namespace HMsoft\Tools\Features\Translations\Contracts;

interface TranslationResolver
{
    /**
     * Locale-keyed payload for API `translations`.
     *
     * @param  list<string>|null  $fields
     * @return array<string, array<string, mixed>>
     */
    public function map(mixed $source, ?array $fields = null): array;

    /**
     * Resolved translatable fields for the current (or given) locale, with fallback.
     *
     * @param  list<string>|null  $fields
     * @return array<string, mixed>
     */
    public function resolve(mixed $source, ?array $fields = null, ?string $locale = null): array;

    public function value(mixed $source, string $field, mixed $default = null, ?string $locale = null): mixed;

    /**
     * Pick a non-empty scalar from a locale => value map.
     */
    public function pickScalar(array $localeToValue, mixed $default = null, ?string $locale = null): mixed;

    public function process(array $data): array;

    /**
     * @return array<string, array<string, mixed>>
     */
    public function normalize(mixed $source): array;
}

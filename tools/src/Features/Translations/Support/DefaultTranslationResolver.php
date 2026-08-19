<?php

namespace HMsoft\Tools\Features\Translations\Support;

use HMsoft\Tools\Features\Translations\Contracts\TranslationResolver;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class DefaultTranslationResolver implements TranslationResolver
{
    public function map(mixed $source, ?array $fields = null): array
    {
        $normalized = $this->normalize($source);
        $fields ??= $this->detectFields($normalized);

        $mapped = [];
        foreach ($normalized as $locale => $row) {
            $mapped[$locale] = [];
            foreach ($fields as $field) {
                $mapped[$locale][$field] = $row[$field] ?? null;
            }
        }

        return $mapped;
    }

    public function resolve(mixed $source, ?array $fields = null, ?string $locale = null): array
    {
        $normalized = $this->normalize($source);
        $fields ??= $this->detectFields($normalized);
        $resolved = [];

        foreach ($fields as $field) {
            $resolved[$field] = $this->resolveField($normalized, $field, $locale);
        }

        return $resolved;
    }

    public function value(mixed $source, string $field, mixed $default = null, ?string $locale = null): mixed
    {
        $found = $this->resolveField($this->normalize($source), $field, $locale);

        return $this->isMissing($found) ? $default : $found;
    }

    public function pickScalar(array $localeToValue, mixed $default = null, ?string $locale = null): mixed
    {
        return $this->value($localeToValue, 'value', $default, $locale);
    }

    public function process(array $data): array
    {
        $normalized = $this->normalize($data['translations'] ?? null);

        if ($normalized === []) {
            return $data;
        }

        $data['translations'] = $this->map($normalized);
        $resolved = $this->resolve($normalized);

        foreach ($resolved as $field => $value) {
            if (! array_key_exists($field, $data) || $this->isMissing($data[$field])) {
                $data[$field] = $value;
            }
        }

        foreach ($resolved as $field => $value) {
            if (! array_key_exists($field, $data)) {
                $data[$field] = null;
            }
        }

        return $data;
    }

    public function normalize(mixed $source): array
    {
        $source = $this->unwrap($source);

        if ($source === null) {
            return [];
        }

        if ($source instanceof Model) {
            $locale = $source->getAttribute('locale');
            if ($locale === null || $locale === '') {
                return [];
            }

            return [(string) $locale => $this->attributesFrom($source)];
        }

        if ($source instanceof Collection) {
            $source = $source->all();
        }

        if ($source instanceof Arrayable) {
            $source = $source->toArray();
        }

        if (! is_array($source) || $source === []) {
            return [];
        }

        if ($this->isTranslationRow($source)) {
            $attrs = $this->attributesFrom($source);
            $locale = $attrs['locale'] ?? null;
            if ($locale === null || $locale === '') {
                return [];
            }

            return [(string) $locale => $attrs];
        }

        if ($this->isScalarMap($source)) {
            $out = [];
            foreach ($source as $locale => $value) {
                $out[(string) $locale] = ['value' => $value];
            }

            return $out;
        }

        if ($this->isLocaleKeyedMap($source)) {
            $out = [];
            foreach ($source as $locale => $row) {
                $out[(string) $locale] = $this->attributesFrom($row);
            }

            return $out;
        }

        $out = [];
        foreach ($source as $row) {
            $attrs = $this->attributesFrom($row);
            $locale = $attrs['locale'] ?? null;
            if ($locale === null || $locale === '') {
                continue;
            }
            $out[(string) $locale] = $attrs;
        }

        return $out;
    }

    private function unwrap(mixed $source): mixed
    {
        if (! $source instanceof Model) {
            return $source;
        }

        $many = (string) config('cms_translations.relations.many', 'translations');
        $one = (string) config('cms_translations.relations.one', 'translation');

        if ($source->relationLoaded($many)) {
            return $source->getRelation($many);
        }

        if ($source->relationLoaded($one) && $source->getRelation($one)) {
            return $source->getRelation($one);
        }

        if ($source->getAttribute('locale') !== null && $source->getAttribute('locale') !== '') {
            return $source;
        }

        return null;
    }

    /**
     * @param  array<string, array<string, mixed>>  $normalized
     */
    private function resolveField(array $normalized, string $field, ?string $locale = null): mixed
    {
        if ($normalized === []) {
            return null;
        }

        foreach ($this->localeOrder($normalized, $locale) as $candidate) {
            if (! array_key_exists($candidate, $normalized)) {
                continue;
            }

            $value = $normalized[$candidate][$field] ?? null;
            if (! $this->isMissing($value)) {
                return $value;
            }
        }

        return null;
    }

    /**
     * @param  array<string, array<string, mixed>>  $normalized
     * @return list<string>
     */
    private function localeOrder(array $normalized, ?string $locale = null): array
    {
        $requested = $locale ?: app()->getLocale();
        $fallback = (string) (
            config('cms_translations.fallback_locale')
            ?: config('cms_localization.fallback_locale')
            ?: config('app.fallback_locale')
            ?: 'en'
        );
        $supported = config('cms_translations.supported_locales')
            ?: config('cms_localization.supported_locales', []);
        $supported = is_array($supported) ? $supported : [];

        $priority = [];
        foreach (array_merge([$requested, $fallback], $supported, array_keys($normalized)) as $code) {
            $code = (string) $code;
            if ($code === '' || in_array($code, $priority, true)) {
                continue;
            }
            $priority[] = $code;
        }

        return $priority;
    }

    /**
     * @param  array<string, array<string, mixed>>  $normalized
     * @return list<string>
     */
    private function detectFields(array $normalized): array
    {
        $fields = [];
        foreach ($normalized as $row) {
            foreach (array_keys($row) as $field) {
                if ($this->isIgnored($field) || in_array($field, $fields, true)) {
                    continue;
                }
                $fields[] = $field;
            }
        }

        return $fields;
    }

    private function isIgnored(string $field): bool
    {
        $ignored = config('cms_translations.ignored_keys', []);
        $ignored = is_array($ignored) ? $ignored : [];

        if (in_array($field, $ignored, true)) {
            return true;
        }

        return (bool) config('cms_translations.ignore_foreign_keys', true)
            && str_ends_with($field, '_id');
    }

    private function isMissing(mixed $value): bool
    {
        if ($value === null) {
            return true;
        }

        if (! config('cms_translations.treat_blank_string_as_empty', true)) {
            return false;
        }

        return is_string($value) && trim($value) === '';
    }

    private function isTranslationRow(array $source): bool
    {
        return array_key_exists('locale', $source) && ! is_array($source['locale']);
    }

    /**
     * @param  array<int|string, mixed>  $translations
     */
    private function isScalarMap(array $translations): bool
    {
        foreach ($translations as $key => $value) {
            if (is_int($key) || (is_string($key) && ctype_digit($key))) {
                return false;
            }
            if ($value !== null && ! is_scalar($value)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param  array<int|string, mixed>  $translations
     */
    private function isLocaleKeyedMap(array $translations): bool
    {
        foreach ($translations as $key => $value) {
            if (is_int($key) || (is_string($key) && ctype_digit($key))) {
                return false;
            }
            if ($value === null) {
                continue;
            }
            if (! is_array($value) && ! is_object($value)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return array<string, mixed>
     */
    private function attributesFrom(mixed $row): array
    {
        if ($row instanceof Model) {
            return $row->getAttributes();
        }

        if ($row instanceof Arrayable) {
            return $row->toArray();
        }

        if (is_object($row)) {
            return get_object_vars($row);
        }

        return is_array($row) ? $row : [];
    }
}

<?php

namespace HMsoft\Tools\Features\Media\Traits;

use HMsoft\Tools\Features\Media\Rules\FileOrUrl;

trait InteractsWithMediaRules
{
    protected static function getMediaMetadataRules(string $prefix = 'media.*.'): array
    {
        return [
            $prefix . 'locales' => 'nullable|array',
            $prefix . 'locales.*.locale' => ['required_with:' . $prefix . 'locales', 'string'],
            $prefix . 'media_type' => 'nullable|string',
            $prefix . 'locales.*.title' => 'nullable|string|max:255',
            $prefix . 'locales.*.alt' => 'nullable|string|max:500',
            $prefix . 'locales.*.short_description' => 'nullable|string',
        ];
    }

    protected static function getSingleMediaRules(string $field = 'image', int|null $maxSize = null): array
    {
        return [
            $field           => is_null($maxSize) ? ['sometimes', new FileOrUrl, 'nullable'] : ['sometimes', 'nullable', new FileOrUrl, "max:$maxSize"],
            "delete_$field"  => ['sometimes', 'boolean'],
        ];
    }

    protected static function getGalleryRules(string $field = 'gallery'): array
    {
        return [
            $field              => ['sometimes', 'array'],
            "$field.*.file"     => ['required', new FileOrUrl],
            "$field.*.sort"     => ['sometimes', 'integer', 'min:0'],
            "deleted_{$field}_ids" => ['sometimes', 'array'],
        ];
    }
}

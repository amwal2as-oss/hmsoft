<?php

namespace HMsoft\Tools\Features\Media\Data;

use HMsoft\Tools\Features\Media\Rules\FileOrUrl;
use HMsoft\Tools\Features\Media\Support\MediaStorePayload;
use HMsoft\Tools\Features\Media\Traits\ExtractsOwnerFromRoute;
use Illuminate\Http\UploadedFile;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Optional;

class StoreMediaData extends Data
{
    use ExtractsOwnerFromRoute;

    public function __construct(
        public readonly UploadedFile|string|Optional $file,
        public readonly Optional|bool $is_default,
        public readonly Optional|string $media_type,
        public readonly Optional|array $locales,
        public readonly Optional|string|null $owner_id = null,
        public readonly Optional|string|null $owner_type = null,
        public readonly Optional|string|null $folder = null,
    ) {}

    public static function prepareForPipeline(array $properties): array
    {
        $properties = self::unwrapNestedMediaItem($properties);

        $ownerData = self::getOwnerFromRoute();

        if (!isset($properties['owner_id']) && !empty($ownerData['owner_id'])) {
            $properties['owner_id'] = $ownerData['owner_id'];
        }

        if (!isset($properties['owner_type']) && !empty($ownerData['owner_type'])) {
            $properties['owner_type'] = $ownerData['owner_type'];
        }

        if (array_key_exists('is_default', $properties)) {
            $properties['is_default'] = filter_var($properties['is_default'], FILTER_VALIDATE_BOOLEAN);
        }

        return $properties;
    }

    public static function rules(): array
    {
        return [
            'owner_id'    => ['nullable', 'string'],
            'owner_type'  => ['nullable', 'string'],
            'file'        => ['required', new FileOrUrl()],
            'is_default'  => ['nullable', 'boolean'],
            'media_type'  => ['nullable', 'string'],
            'folder'      => ['nullable', 'string'],
            'locales'     => ['nullable', 'array'],
        ];
    }

    /**
     * Accept either root `file` or the first bulk item `media[0][file]`.
     *
     * @param  array<string, mixed>  $properties
     * @return array<string, mixed>
     */
    private static function unwrapNestedMediaItem(array $properties): array
    {
        if (! MediaStorePayload::isMissingFile($properties['file'] ?? null)) {
            return $properties;
        }

        $media = $properties['media'] ?? null;
        if (! is_array($media) || $media === []) {
            return $properties;
        }

        $first = $media[0] ?? reset($media);

        if ($first instanceof UploadedFile) {
            $properties['file'] = $first;

            return $properties;
        }

        if (! is_array($first)) {
            return $properties;
        }

        foreach (['file', 'media_type', 'is_default', 'locales', 'folder'] as $key) {
            if (MediaStorePayload::isMissingFile($properties[$key] ?? null) && array_key_exists($key, $first)) {
                $properties[$key] = $first[$key];
            }
        }

        return $properties;
    }
}
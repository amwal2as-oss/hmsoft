<?php

namespace HMsoft\Tools\Features\Media\Data;

use HMsoft\Tools\Features\Media\Rules\FileOrUrl;
use HMsoft\Tools\Features\Media\Support\MediaStorePayload;
use HMsoft\Tools\Features\Media\Traits\ExtractsOwnerFromRoute;
use Illuminate\Http\UploadedFile;
use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Optional;

class StoreBulkMediaData extends Data
{
    use ExtractsOwnerFromRoute;

    public function __construct(
        #[DataCollectionOf(StoreMediaData::class)]
        public readonly array $media,
        public readonly Optional|string|null $owner_id = null,
        public readonly Optional|string|null $owner_type = null,
        public readonly Optional|string|null $folder = null,
    ) {}

    public static function prepareForPipeline(array $properties): array
    {
        if (array_is_list($properties) && !isset($properties['media'])) {
            $properties = ['media' => $properties];
        }

        $properties = self::hydrateMediaFiles($properties);

        $ownerData = self::getOwnerFromRoute();

        if (!isset($properties['owner_id']) && !empty($ownerData['owner_id'])) {
            $properties['owner_id'] = $ownerData['owner_id'];
        }

        if (!isset($properties['owner_type']) && !empty($ownerData['owner_type'])) {
            $properties['owner_type'] = $ownerData['owner_type'];
        }

        $folderValue = null;
        if (isset($properties['folder'])) {
            $folderValue = $properties['folder'] instanceof Optional ? null : $properties['folder'];
        }

        if (isset($properties['media']) && is_array($properties['media'])) {
            foreach ($properties['media'] as $index => $item) {
                if ($item instanceof UploadedFile) {
                    $item = ['file' => $item];
                }

                if (! is_array($item)) {
                    continue;
                }

                if (isset($properties['owner_id'])) {
                    $item['owner_id'] = $properties['owner_id'];
                }
                if (isset($properties['owner_type'])) {
                    $item['owner_type'] = $properties['owner_type'];
                }
                $item['folder'] = $folderValue;

                if (array_key_exists('is_default', $item)) {
                    $item['is_default'] = filter_var($item['is_default'], FILTER_VALIDATE_BOOLEAN);
                }

                $properties['media'][$index] = $item;
            }
        }

        return $properties;
    }

    public static function rules(): array
    {
        return [
            'owner_id' => ['nullable', 'string'],
            'owner_type' => ['nullable', 'string'],
            'folder' => ['nullable', 'string'],
            'media' => ['required', 'array', 'min:1'],
            'media.*.file' => ['required', new FileOrUrl()],
            'media.*.is_default' => ['nullable', 'boolean'],
            'media.*.media_type' => ['nullable', 'string'],
            'media.*.locales' => ['nullable', 'array'],
            'media.*.folder' => ['nullable', 'string'],
        ];
    }

    /**
     * @param  array<string, mixed>  $properties
     * @return array<string, mixed>
     */
    private static function hydrateMediaFiles(array $properties): array
    {
        $request = request();
        if (! $request) {
            return $properties;
        }

        $media = $properties['media'] ?? null;
        if (! is_array($media) || $media === []) {
            $media = [];
        }

        $files = $request->file('media');
        if (is_array($files)) {
            foreach ($files as $index => $file) {
                if ($file instanceof UploadedFile) {
                    $media[$index] = array_merge(
                        is_array($media[$index] ?? null) ? $media[$index] : [],
                        ['file' => $file]
                    );
                    continue;
                }

                if (is_array($file) && isset($file['file']) && $file['file'] instanceof UploadedFile) {
                    $media[$index] = array_merge(
                        is_array($media[$index] ?? null) ? $media[$index] : [],
                        $file
                    );
                }
            }
        }

        foreach (array_keys($media) as $index) {
            $item = is_array($media[$index]) ? $media[$index] : [];
            if (MediaStorePayload::isMissingFile($item['file'] ?? null) && $request->hasFile("media.{$index}.file")) {
                $item['file'] = $request->file("media.{$index}.file");
            }
            $media[$index] = $item;
        }

        if ($media !== []) {
            $properties['media'] = $media;
        }

        return $properties;
    }
}

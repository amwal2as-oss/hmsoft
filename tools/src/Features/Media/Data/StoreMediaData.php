<?php

namespace HMsoft\Tools\Features\Media\Data;

use HMsoft\Tools\Features\Media\Rules\FileOrUrl;
use Illuminate\Http\UploadedFile;
use HMsoft\Tools\Features\Media\Traits\ExtractsOwnerFromRoute;
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
        public readonly Optional|string $owner_id,
        public readonly Optional|string $owner_type,
        public readonly Optional|string|null $folder,
    ) {}

    public static function prepareForPipeline(array $properties): array
    {
        $ownerData = self::getOwnerFromRoute();
        $properties['owner_id']   = $properties['owner_id'] ?? $ownerData['owner_id'];
        $properties['owner_type'] = $properties['owner_type'] ?? $ownerData['owner_type'];

        if (array_key_exists('is_default', $properties)) {
            $properties['is_default'] = filter_var($properties['is_default'], FILTER_VALIDATE_BOOLEAN);
        }

        return $properties;
    }

    public static function rules(): array
    {
        return [
            'owner_id'    => ['required', 'string'],
            'owner_type'  => ['required', 'string'],
            'file'        => ['required', new FileOrUrl()],
            'is_default'  => ['nullable', 'boolean'],
            'media_type'  => ['nullable', 'string'],
            'folder'      => ['nullable', 'string'],
            'locales'     => ['nullable', 'array'],
        ];
    }
}

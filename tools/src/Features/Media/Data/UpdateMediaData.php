<?php

namespace HMsoft\Tools\Features\Media\Data;

use HMsoft\Tools\Features\Media\Rules\FileOrUrl;
use Illuminate\Http\UploadedFile;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Optional;

class UpdateMediaData extends Data
{
    public function __construct(
        public readonly Optional|int $id,
        public readonly UploadedFile|string|Optional $file,
        public readonly bool|Optional $is_default,
        public readonly int|Optional $sort_number,
        public readonly string|Optional $media_type,
        public readonly array|Optional $locales,
    ) {}

    public static function rules(): array
    {
        return [
            'id'                => ['sometimes', 'required', 'integer', 'exists:media,id'],
            'file'              => ['nullable', new FileOrUrl()], // السماح بتحديث الملف أو الرابط
            'is_default'        => ['nullable', 'boolean'],
            'sort_number'       => ['nullable', 'integer'],
            'media_type'        => ['nullable', 'string'],
            'locales'           => ['nullable', 'array'],
        ];
    }

    public static function prepareForPipeline(array $properties): array
    {
        if (array_key_exists('is_default', $properties)) {
            $properties['is_default'] = filter_var($properties['is_default'], FILTER_VALIDATE_BOOLEAN);
        }
        return $properties;
    }
}

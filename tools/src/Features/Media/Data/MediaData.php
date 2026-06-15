<?php

namespace HMsoft\Tools\Features\Media\Data;

use App\Data\BaseData;
use HMsoft\Tools\Features\Media\Models\Medium;
use Spatie\LaravelData\Optional;

class MediaData extends BaseData
{
    public function __construct(
        public readonly ?int $id,
        public readonly ?string $owner_id,
        public readonly ?string $owner_type,
        public readonly ?string $file_path,
        public readonly ?string $file_name,
        public readonly ?string $file_url,
        public readonly ?string $mime_type,
        public readonly ?string $media_type,
        public readonly ?bool $is_default,
        public readonly ?int $sort_number,
        public readonly array|Optional $translations,
        public readonly ?\DateTime $created_at,
    ) {}

    public static function fromModel(Medium $medium): self
    {
        return new self(
            id: $medium->id,
            owner_id: $medium->owner_id,
            owner_type: $medium->owner_type,
            file_path: $medium->file_path,
            file_name: $medium->file_name,
            file_url: $medium->file_url,
            mime_type: $medium->mime_type,
            media_type: $medium->media_type,
            is_default: $medium->is_default,
            sort_number: $medium->sort_number,
            translations: $medium->relationLoaded('translations')
                ? $medium->translations->mapWithKeys(function ($translation) {
                    return [
                        $translation->locale => [
                            'title' => $translation->title,
                            'alt' => $translation->alt,
                            'short_description' => $translation->short_description,
                        ]
                    ];
                })->toArray()
                : Optional::create(),
            created_at: $medium->created_at,
        );
    }
}

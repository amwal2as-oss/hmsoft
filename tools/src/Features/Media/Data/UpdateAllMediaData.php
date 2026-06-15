<?php

namespace HMsoft\Tools\Features\Media\Data;

use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Data;

class UpdateAllMediaData extends Data
{
    public function __construct(
        #[DataCollectionOf(UpdateMediaData::class)]
        public readonly array $media
    ) {}

    public static function prepareForPipeline(array $properties): array
    {
        return array_is_list($properties) ? ['media' => $properties] : $properties;
    }
}

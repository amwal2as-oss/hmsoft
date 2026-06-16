<?php

namespace HMsoft\Tools\Features\Attribute\Data;

use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Data;

class UpdateAllAttributesData extends Data
{
    public function __construct(
        #[DataCollectionOf(UpdateAttributeData::class)]
        public readonly array $attributes,
    ) {}

    public static function prepareForPipeline(array $properties): array
    {
        if (array_is_list($properties)) {
            $properties = ['attributes' => $properties];
        }
        return $properties;
    }
}

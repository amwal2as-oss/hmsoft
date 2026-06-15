<?php

namespace HMsoft\Tools\Features\Media\Data;

use HMsoft\Tools\Features\Media\Traits\ExtractsOwnerFromRoute;
use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Optional;

class StoreBulkMediaData extends Data
{
    use ExtractsOwnerFromRoute;

    public function __construct(
        #[DataCollectionOf(StoreMediaData::class)]
        public readonly array $media,
        public readonly ?string $owner_id = null,
        public readonly ?string $owner_type = null,
        public readonly Optional|string|null $folder,
    ) {}

    public static function prepareForPipeline(array $properties): array
    {
        $ownerData = self::getOwnerFromRoute();
        $properties['owner_id']   = $properties['owner_id'] ?? $ownerData['owner_id'];
        $properties['owner_type'] = $properties['owner_type'] ?? $ownerData['owner_type'];

        if (isset($properties['media']) && is_array($properties['media'])) {
            foreach ($properties['media'] as &$item) {
                $item['owner_id'] = $properties['owner_id'];
                $item['owner_type'] = $properties['owner_type'];
                $item['folder'] = $properties['folder'] ?? null;

                if (array_key_exists('is_default', $item)) {
                    $item['is_default'] = filter_var($item['is_default'], FILTER_VALIDATE_BOOLEAN);
                }
            }
        }

        return $properties;
    }
}

<?php

namespace HMsoft\Tools\Features\Attribute\Actions;

use HMsoft\Tools\Features\Attribute\Data\SyncAttributeImageData;
use HMsoft\Tools\Features\Attribute\Models\Attribute;
use HMsoft\Tools\Features\Media\Traits\HandlesSingleMedia;

class SyncImageAction
{
    use HandlesSingleMedia;

    public function execute(Attribute $attribute, SyncAttributeImageData $data): array
    {
        $mediaStatus = $this->syncSingleImage(
            model: $attribute,
            file: $data->image ?? null,
            field: 'image',
            deleteImage: (bool)($data->delete_image ?? false),
            folder: Attribute::MEDIA_FOLDER
        );

        return [
            'model' => $attribute->refresh(),
            'media_status' => $mediaStatus
        ];
    }
}

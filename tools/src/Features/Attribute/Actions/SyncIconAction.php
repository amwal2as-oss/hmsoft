<?php

namespace HMsoft\Tools\Features\Attribute\Actions;

use HMsoft\Tools\Features\Attribute\Data\SyncAttributeIconData;
use HMsoft\Tools\Features\Attribute\Models\Attribute;
use HMsoft\Tools\Features\Media\Traits\HandlesSingleMedia;

class SyncIconAction
{
    use HandlesSingleMedia;

    public function execute(Attribute $attribute, SyncAttributeIconData $data): array
    {
        $mediaStatus = $this->syncSingleImage(
            model: $attribute,
            file: $data->icon ?? null,
            field: 'icon',
            deleteImage: (bool)($data->delete_icon ?? false),
            folder: Attribute::MEDIA_FOLDER
        );

        return [
            'model' => $attribute->refresh(),
            'media_status' => $mediaStatus
        ];
    }
}

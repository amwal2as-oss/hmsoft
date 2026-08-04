<?php

namespace HMsoft\Tools\Features\Media\Traits;

use HMsoft\Tools\Features\Media\Data\StoreBulkMediaData;
use HMsoft\Tools\Features\Media\Service\MediaService;
use Illuminate\Database\Eloquent\Model;

trait HandlesMultipleMedia
{
    protected function syncMultipleMedia(
        Model $model,
        array $files = [],
        string $field = 'image',
        array $deletedIds = [],
        ?string $folder = null
    ): void {
        $mediaService = app(MediaService::class);
        $folder = $folder ?? $model->getTable();

        if (!empty($deletedIds)) {
            foreach ($deletedIds as $id) {
                $medium = $model->mediaList()->find($id);
                if ($medium) {
                    $mediaService->delete($model, $medium);
                }
            }
        }

        if (!empty($files)) {
            $ownerId = (string) $model->getKey();
            $ownerType = $model->getMorphClass();

            $mediaDataArray = [];
            foreach ($files as $file) {
                $mediaDataArray[] = [
                    'file'       => $file,
                    'media_type' => $field,
                    'is_default' => false,
                    'owner_id'   => $ownerId,
                    'owner_type' => $ownerType,
                ];
            }

            $data = \HMsoft\Tools\Features\Media\Data\StoreBulkMediaData::from([
                'media'      => $mediaDataArray,
                'folder'     => $folder,
                'owner_id'   => $ownerId,
                'owner_type' => $ownerType,
            ]);

            $mediaService->storeBulk($model, $data);
        }
    }
}

<?php

namespace HMsoft\Tools\Features\Media\Traits;

use HMsoft\Tools\Features\Media\Data\StoreMediaData;
use HMsoft\Tools\Features\Media\Service\MediaService;
use Illuminate\Database\Eloquent\Model;

trait HandlesMultipleMedia
{
    protected function syncMultipleMedia(
        Model $model,
        array $files = [],
        string $field = 'gallery',
        array $deletedIds = [],
        ?string $folder = null
    ): void {
        $mediaService = app(MediaService::class);
        $folder = $folder ?? $model->getTable();

        if (!empty($deletedIds)) {
            foreach ($deletedIds as $id) {
                $medium = $model->cmsMedia()->find($id);
                if ($medium) $mediaService->delete($model, $medium);
            }
        }

        if (!empty($files)) {
            $mediaDataArray = [];
            foreach ($files as $file) {
                $mediaDataArray[] = [
                    'file' => $file,
                    'media_type' => $field,
                    'is_default' => false
                ];
            }

            $data = StoreMediaData::from([
                'media' => $mediaDataArray,
                'folder' => $folder
            ]);

            $mediaService->store($model, $data);
        }
    }
}

<?php

namespace HMsoft\Tools\Features\Media\Traits;

use HMsoft\Tools\Features\Media\Facades\MediaUploader;
use HMsoft\Tools\Features\Media\Service\MediaService;
use HMsoft\Tools\Features\Media\Data\StoreMediaData;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

trait HandlesSingleMedia
{

    protected function syncSingleImage(
        Model $model,
        $file = null,
        string $field = 'image',
        bool $deleteImage = false,
        ?string $folder = null,
        ?string $sizeSet = null,
        ?string $disk = null
    ): string {

        if ($file) {
            $deleteImage = false;
        }

        if ($deleteImage) {
            return $this->deleteSingleImage($model, $field, $disk);
        }

        if ($file) {
            return $this->uploadSingleImage($model, $file, $field, $folder, $sizeSet, $disk);
        }

        return 'unchanged';
    }

    /**
     * دالة الرفع (تقوم بحذف الصورة القديمة دائماً قبل الرفع)
     */
    protected function uploadSingleImage(Model $model, $file, string $field = 'image', ?string $folder = null, ?string $sizeSet = null, ?string $disk = null): string
    {
        $folder = $folder ?? $model->getTable();
        $disk = $disk ?? (method_exists($model, 'getMediaDisk') ? $model->getMediaDisk() : config('cms_media.default_disk', 'public'));

        // 1. حذف الصورة القديمة أولاً
        $this->deleteSingleImage($model, $field, $disk);

        // 2. رفع الصورة الجديدة
        if (Schema::hasColumn($model->getTable(), $field)) {
            $path = is_string($file) ? $file : MediaUploader::upload($file, $folder, $disk, $sizeSet);
            $model->update([$field => $path]);
        } else {
            $mediaService = app(MediaService::class);
            $data = StoreMediaData::from([
                'media' => [['file' => $file, 'is_default' => true, 'media_type' => $field]],
                'folder' => $folder
            ]);
            $mediaService->store($model, $data);
        }

        return 'uploaded';
    }

    /**
     * دالة الحذف النهائي للبيانات والملف
     */
    protected function deleteSingleImage(Model $model, string $field = 'image', ?string $disk = null): string
    {
        $disk = $disk ?? (method_exists($model, 'getMediaDisk') ? $model->getMediaDisk() : config('cms_media.default_disk', 'public'));
        $isDeleted = false;

        if (Schema::hasColumn($model->getTable(), $field)) {
            if ($model->{$field}) {
                MediaUploader::deleteFile($model->{$field}, $disk);
                $model->update([$field => null]);
                $isDeleted = true;
            }
        } else {
            $media = $model->mediaList()->where('media_type', $field)->first();
            if ($media) {
                app(MediaService::class)->delete($model, $media);
                $isDeleted = true;
            }
        }

        return $isDeleted ? 'deleted' : 'unchanged';
    }
}

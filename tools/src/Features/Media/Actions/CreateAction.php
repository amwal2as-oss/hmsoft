<?php

namespace HMsoft\Tools\Features\Media\Actions;

use HMsoft\Tools\Features\Media\Data\StoreMediaData;
use HMsoft\Tools\Features\Media\Models\Medium;
use HMsoft\Tools\Features\Media\Facades\MediaUploader;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Spatie\LaravelData\Optional; // 👈 استدعاء كلاس Optional ضروري هنا

class CreateAction
{
    public function execute(StoreMediaData $data, Model $owner): Medium
    {
        return DB::transaction(function () use ($owner, $data) {

            // 1. فك تغليف الحقول المنطقية
            $isDefault = $data->is_default instanceof Optional ? false : (bool) $data->is_default;

            if ($isDefault) {
                $owner->mediaList()->update(['is_default' => false]);
            } else {
                $hasExistingDefault = $owner->mediaList()->where('is_default', true)->exists();
                if (!$hasExistingDefault) {
                    $isDefault = true;
                }
            }

            $sortNumber = ($owner->mediaList()->max('sort_number') ?? 0) + 1;
            $fileDetails = [];
            $autoMediaType = 'gallery';

            $folder = $data->folder instanceof Optional
                ? "{$owner->getMorphClass()}/{$owner->id}/media"
                : $data->folder;

            // فك تغليف الملف نفسه للحماية
            $fileOrUrl = $data->file instanceof Optional ? null : $data->file;

            if ($fileOrUrl instanceof UploadedFile) {
                // الآن $folder عبارة عن نص 100%
                $path = MediaUploader::upload(file: $fileOrUrl, directory: $folder);
                $mimeType = $fileOrUrl->getMimeType();

                $fileDetails = [
                    'file_path' => $path,
                    'file_name' => $fileOrUrl->getClientOriginalName(),
                    'mime_type' => $mimeType,
                ];
                $autoMediaType = str_starts_with($mimeType, 'image/') ? 'image' : (str_starts_with($mimeType, 'video/') ? 'video' : 'file');
            } elseif (is_string($fileOrUrl)) {
                $fileDetails = ['file_path' => $fileOrUrl, 'mime_type' => 'link', 'file_name' => basename($fileOrUrl)];
                $autoMediaType = 'video';
            }

            // فك تغليف نوع الميديا
            $customMediaType = $data->media_type instanceof Optional ? null : $data->media_type;

            $newMedia = $owner->mediaList()->create(array_merge($fileDetails, [
                'is_default'  => $isDefault,
                'sort_number' => $sortNumber,
                'media_type'  => $customMediaType ?? $autoMediaType,
            ]));

            // فك تغليف الترجمات
            $locales = $data->locales instanceof Optional ? null : $data->locales;
            if ($locales !== null && method_exists($newMedia, 'syncTranslations')) {
                $newMedia->syncTranslations($newMedia, $locales);
            }

            return $newMedia->load(Medium::DEFAULT_INCLUDES);
        });
    }
}

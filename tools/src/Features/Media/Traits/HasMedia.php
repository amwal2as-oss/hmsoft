<?php

namespace HMsoft\Tools\Features\Media\Traits;

use HMsoft\Tools\Features\Media\Facades\MediaUploader;
use HMsoft\Tools\Features\Media\Models\Medium;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

trait HasMedia
{

    public static function bootHasMedia(): void
    {
        static::deleting(function ($model) {
            $model->purgeAssociatedMedia();
        });

        if (method_exists(static::class, 'forceDeleting')) {
            static::forceDeleting(function ($model) {
                $model->purgeAssociatedMedia();
            });
        }
    }

    public function mediaList(): MorphMany
    {
        return $this->morphMany(Medium::class, 'owner')->orderBy('sort_number');
    }

    protected function purgeAssociatedMedia(): void
    {
        $fields = $this->cmsMediaFields ?? $this->mediaFields ?? [];

        foreach ($fields as $field) {
            $filePath = $this->getAttribute($field);

            if (!empty($filePath)) {
                MediaUploader::deleteFile($filePath);
            }
        }

        if (\Illuminate\Support\Facades\Schema::hasTable('media')) {
            $mediaItems = $this->mediaList()->get();

            if ($mediaItems->isNotEmpty()) {
                $paths = $mediaItems->pluck('file_path')->toArray();
                MediaUploader::deleteFiles($paths);
                $this->mediaList()->delete();
            }
        }
    }

    public function cleanupMediaFiles()
    {
        if (property_exists($this, 'mediaFields')) {
            foreach ($this->mediaFields as $field) {
                if (!empty($this->{$field})) MediaUploader::deleteFile($this->{$field});
            }
        }
        if (Schema::hasTable('media')) {
            $this->mediaList->each->delete();
        }
    }


    public function getMediaDisk(): string
    {
        return $this->cmsMediaDisk ?? config('cms_media.default_disk', 'public');
    }

    protected function isCmsMediaField(string $field): bool
    {
        $fields = $this->cmsMediaFields ?? $this->mediaFields ?? [];
        return in_array($field, $fields);
    }

    public function getAttribute($key)
    {
        // 1. توليد كائن الميديا الكامل إذا تم طلب الحقل بلاحقة _object (مثال: image_object)
        if (preg_match('/^(.+)_object$/', $key, $matches)) {
            $field = $matches[1];
            if ($this->isCmsMediaField($field)) {
                return $this->getMediaObject($field);
            }
        }

        // 2. توليد الرابط المباشر أو المصغر إذا تم طلب الحقل بلاحقة _url أو _url_suffix
        if (preg_match('/^(.+)_url(?:_(.+))?$/', $key, $matches)) {
            $field = $matches[1];
            $suffix = $matches[2] ?? null;

            if ($this->isCmsMediaField($field)) {
                return $this->generateMediaUrl($field, $suffix);
            }
        }

        return parent::getAttribute($key);
    }

    public function getMediaObject(string $field): ?array
    {
        if (empty($this->attributes[$field])) {

            $placeholderUrl = $this->cmsPlaceholder ?? config('cms_media.default_placeholder');
            if ($this->cmsNoPlaceholder ?? false || $placeholderUrl == null) {
                return null;
            }

            return [
                'url'    => $placeholderUrl,
                'thumb'  => $placeholderUrl,
                'medium' => $placeholderUrl,
                'srcset' => null,
            ];
        }

        $mainUrl = $this->getAttribute($field . '_url'); // يستدعي الـ Magic Accessor
        $data = ['url' => $mainUrl];
        $setName = $this->cmsMediaSet ?? null;
        $sets = config('cms_media.image_sets', []);
        $srcset = [];

        if ($setName && isset($sets[$setName])) {
            foreach ($sets[$setName] as $suffix => $dimensions) {
                // يستدعي الـ Magic Accessor تلقائياً (مثال: image_url_thumb)
                $suffixUrl = $this->getAttribute($field . '_url_' . $suffix);
                $data[$suffix] = $suffixUrl;

                if ($suffixUrl && isset($dimensions['width'])) {
                    $srcset[] = "{$suffixUrl} {$dimensions['width']}w";
                }
            }
        }

        if (!empty($srcset)) {
            $data['srcset'] = implode(', ', $srcset);
        }

        return $data;
    }

    protected function generateMediaUrl(string $field, ?string $suffix = null): ?string
    {
        $path = $this->attributes[$field] ?? null;

        if (!$path) return null;

        // إذا كان الرابط خارجياً (مرفوع عبر رابط خارجي وليس ملف محلي) لا نطبق عليه الـ Suffix
        if (filter_var($path, FILTER_VALIDATE_URL)) {
            return $path;
        }

        // التعديل الآمن: تطبيق الـ Suffix على مسار الملف في قاعدة البيانات (قبل تحويله لرابط)
        if ($suffix) {
            $pathInfo = pathinfo($path);
            $ext = isset($pathInfo['extension']) ? "." . $pathInfo['extension'] : "";
            $path = $pathInfo['dirname'] . '/' . $pathInfo['filename'] . "_{$suffix}" . $ext;
        }

        // الآن نقوم بتوليد الرابط الآمن بناءً على المسار الجديد والـ Disk الخاص بالموديل
        return Storage::disk($this->getMediaDisk())->url($path);
    }
}

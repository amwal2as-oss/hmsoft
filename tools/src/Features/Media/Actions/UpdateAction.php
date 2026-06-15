<?php

namespace HMsoft\Tools\Features\Media\Actions;

use HMsoft\Tools\Features\Media\Data\UpdateMediaData;
use HMsoft\Tools\Features\Media\Facades\MediaUploader;
use HMsoft\Tools\Features\Media\Models\Medium;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Spatie\LaravelData\Optional; // 👈 لا تنس استدعاء هذا الكلاس

class UpdateAction
{
    public function __construct() {}

    public function execute(Model $owner, Medium $medium, UpdateMediaData $data): Medium
    {
        return DB::transaction(function () use ($owner, $medium, $data) {

            // 1. استخراج القيمة بأمان (إذا لم يرسلها المستخدم، نأخذ القيمة القديمة من الداتا بيز)
            $isDefault = $data->is_default instanceof Optional ? $medium->is_default : (bool) $data->is_default;

            // معالجة تغيير الحالة الافتراضية
            if ($isDefault && !$medium->is_default) {
                $owner->mediaList()->where('id', '!=', $medium->id)->update(['is_default' => false]);
            }

            // 2. تنظيف مصفوفة التحديث من أي كائن Optional قد يتسرب ويتسبب بخطأ PDO
            $updateData = collect($data->toArray())
                ->except(['locales', 'id', 'file'])
                ->reject(fn($value) => $value instanceof Optional) // 👈 هذا هو فلتر الحماية!
                ->toArray();

            // تحديث الحقل الافتراضي في المصفوفة فقط إذا أرسله المستخدم
            if (!($data->is_default instanceof Optional)) {
                $updateData['is_default'] = $isDefault;
            }

            // === [ معالجة استبدال الملف ] ===
            // 3. استخراج الملف الفعلي (نجعله null إذا كان Optional)
            $fileOrUrl = $data->file instanceof Optional ? null : $data->file;

            if ($fileOrUrl) {


                // حذف الملف القديم
                if ($medium->mime_type !== 'link' && $medium->file_path) {
                    // MediaUploader::deleteFile("{$owner->getMorphClass()}/{$owner->id}/media/{$medium->file_path}");
                    MediaUploader::deleteFile($medium->file_path);
                }

                if ($fileOrUrl instanceof UploadedFile) {
                    $folder = "{$owner->getMorphClass()}/{$owner->id}/media";

                    $path = MediaUploader::upload(file: $fileOrUrl, directory: $folder);
                    $mimeType = $fileOrUrl->getMimeType();

                    $updateData['file_path'] = $path;
                    $updateData['file_name'] = $fileOrUrl->getClientOriginalName();
                    $updateData['mime_type'] = $mimeType;

                    if (str_starts_with($mimeType, 'image/')) $updateData['media_type'] = 'image';
                    elseif (str_starts_with($mimeType, 'video/')) $updateData['media_type'] = 'video';
                    elseif (str_starts_with($mimeType, 'audio/')) $updateData['media_type'] = 'audio';
                    else $updateData['media_type'] = 'file';
                } elseif (is_string($fileOrUrl)) {
                    $updateData['file_path'] = $fileOrUrl;
                    $updateData['file_name'] = basename($fileOrUrl);
                    $updateData['mime_type'] = 'link';

                    $mediaType = $data->media_type instanceof Optional ? 'video' : ($data->media_type ?? 'video');
                    $updateData['media_type'] = $mediaType;
                }
            }
            // ===================================

            // التحديث النهائي للميديا (ببيانات نظيفة 100%)
            if (!empty($updateData)) {
                $medium->update($updateData);
            }

            // 4. تحديث الترجمات
            $locales = $data->locales instanceof Optional ? null : $data->locales;
            if ($locales !== null && method_exists($medium, 'syncTranslations')) {
                $medium->syncTranslations($medium, $locales);
            }

            return $medium->refresh()->load(Medium::DEFAULT_INCLUDES);
        });
    }
}

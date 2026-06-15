<?php

namespace HMsoft\Tools\Features\Media\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static string upload(\Illuminate\Http\UploadedFile $file, string $directory, ?string $disk = null, ?string $sizeSet = null)
 * @method static string getUrl(\Illuminate\Database\Eloquent\Model $model, string $field)
 * @method static bool deleteFile(?string $path, ?string $disk = null)
 * @method static void deleteFiles(array $paths, ?string $disk = null)
 * @method static string|null resolveActualUrl(\Illuminate\Database\Eloquent\Model $model, string $field)
 * @method static string getPlaceholder(\Illuminate\Database\Eloquent\Model $model, string $field)
 *
 * @see \HMsoft\Tools\Features\Media\Services\MediaUploadService
 */
class MediaUploader extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'media-uploader';
    }
}

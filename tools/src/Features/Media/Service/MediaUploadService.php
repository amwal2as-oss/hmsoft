<?php

namespace HMsoft\Tools\Features\Media\Service;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

class MediaUploadService
{
    public function upload(UploadedFile $file, string $directory, ?string $disk = null, ?string $sizeSet = null): string
    {
        $disk = $disk ?? config('filesystems.default', 'public');
        $directory = trim($directory, '/');
        $fileExtension = strtolower($file->getClientOriginalExtension() ?: $file->extension());

        if (!Storage::disk($disk)->exists($directory)) {
            Storage::disk($disk)->makeDirectory($directory);
        }

        $fileName = Carbon::now()->toDateString() . "-" . uniqid();

        if (!in_array($fileExtension, ['jpg', 'jpeg', 'bmp', 'png', 'webp']) || $fileExtension === 'svg') {
            $fullName = $fileName . '.' . $fileExtension;
            Storage::disk($disk)->put("{$directory}/{$fullName}", file_get_contents($file));
            return "{$directory}/{$fullName}";
        }

        try {
            $manager = new ImageManager(new Driver());
            $image = $manager->read($file);
            $extension = 'webp';
            $mainPath = "{$directory}/{$fileName}.{$extension}";

            Storage::disk($disk)->put($mainPath, (string) $image->toWebp(quality: 80));

            $sets = config('cms_media.image_sets', []);
            if ($sizeSet && isset($sets[$sizeSet])) {
                foreach ($sets[$sizeSet] as $suffix => $dimensions) {
                    $resizedImage = clone $image;
                    $resizedImage->scale(width: $dimensions['width'] ?? null, height: $dimensions['height'] ?? null);
                    Storage::disk($disk)->put("{$directory}/{$fileName}_{$suffix}.{$extension}", (string) $resizedImage->toWebp(quality: 80));
                }
            }
            return $mainPath;
        } catch (\Throwable $th) {
            info('MediaService Upload Error', ['error' => $th->getMessage()]);
            $fullName = $fileName . '.' . $fileExtension;
            Storage::disk($disk)->put("{$directory}/{$fullName}", file_get_contents($file));
            return "{$directory}/{$fullName}";
        }
    }

    public function getUrl(Model $model, string $field): string
    {
        $actualUrl = $this->resolveActualUrl($model, $field);
        return $actualUrl ?? $this->getPlaceholder($model, $field);
    }

    public function deleteFile(?string $path, ?string $disk = null): bool
    {
        if (!$path) return false;
        $disk = $disk ?? config('filesystems.default', 'public');
        if (Storage::disk($disk)->exists($path)) return Storage::disk($disk)->delete($path);
        return false;
    }

    public function deleteFiles(array $paths, ?string $disk = null): void
    {
        if (empty($paths)) return;
        $disk = $disk ?? config('filesystems.default', 'public');
        $storage = Storage::disk($disk);
        foreach (array_unique(array_filter($paths)) as $path) {
            if ($storage->exists($path)) $storage->delete($path);
        }
    }

    public function resolveActualUrl(Model $model, string $field): ?string
    {
        $attributes = $model->getAttributes();
        if (isset($attributes[$field]) && !empty($attributes[$field])) {
            $value = $attributes[$field];
            if (filter_var($value, FILTER_VALIDATE_URL)) return $value;
            return Storage::disk(config('filesystems.default', 'public'))->url($value);
        }
        return null;
    }

    public function getPlaceholder(Model $model, string $field): string
    {
        $config = config('cms_media.placeholders', []);
        $modelKey = strtolower(class_basename($model));

        if (isset($config['fields'][$field])) return asset($config['fields'][$field]);
        if (isset($config['models'][$modelKey])) return asset($config['models'][$modelKey]);
        return asset($config['default'] ?? 'assets/images/placeholder.png');
    }
}

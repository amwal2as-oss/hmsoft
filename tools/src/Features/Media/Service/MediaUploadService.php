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
    private const IMAGE_EXTENSIONS = ['jpg', 'jpeg', 'bmp', 'png', 'webp'];

    public function upload(UploadedFile $file, string $directory, ?string $disk = null, ?string $sizeSet = null): string
    {
        $disk = $disk ?? config('filesystems.default', 'public');
        $directory = trim($directory, '/');
        $fileExtension = strtolower($file->getClientOriginalExtension() ?: $file->extension());

        $this->ensureDirectoryExists($directory, $disk);

        $fileName = Carbon::now()->toDateString() . '-' . uniqid();

        if (!$this->isProcessableImage($fileExtension)) {
            return $this->storeRawFile($file, $directory, $disk, $fileName);
        }

        try {
            return $this->processAndStoreImage($file, $directory, $disk, $fileName, $fileExtension, $sizeSet);
        } catch (\Throwable $th) {
            info('MediaService Upload Error', ['error' => $th->getMessage()]);

            return $this->storeRawFile($file, $directory, $disk, $fileName);
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

    private function isProcessableImage(string $extension): bool
    {
        return in_array($extension, self::IMAGE_EXTENSIONS, true) && $extension !== 'svg';
    }

    private function ensureDirectoryExists(string $directory, string $disk): void
    {
        if (!Storage::disk($disk)->exists($directory)) {
            Storage::disk($disk)->makeDirectory($directory);
        }
    }

    /**
     * Stream non-image files directly to storage without loading into memory.
     */
    private function storeRawFile(UploadedFile $file, string $directory, string $disk, string $fileName): string
    {
        $extension = strtolower($file->getClientOriginalExtension() ?: $file->extension());
        $fullName = $extension ? "{$fileName}.{$extension}" : $fileName;

        $path = Storage::disk($disk)->putFileAs($directory, $file, $fullName);

        return $path ?: "{$directory}/{$fullName}";
    }

    /**
     * Decode, optionally resize, convert to WebP, and generate thumbnail variants.
     */
    private function processAndStoreImage(
        UploadedFile $file,
        string $directory,
        string $disk,
        string $fileName,
        string $fileExtension,
        ?string $sizeSet
    ): string {
        $manager = new ImageManager(new Driver());
        $image = $manager->read($file);

        $maxDimension = (int) config('cms_media.max_image_dimension', 4096);
        if ($maxDimension > 0 && ($image->width() > $maxDimension || $image->height() > $maxDimension)) {
            $image->scale(width: $maxDimension, height: $maxDimension);
        }

        $extension = 'webp';
        $mainPath = "{$directory}/{$fileName}.{$extension}";

        Storage::disk($disk)->put($mainPath, (string) $image->toWebp(quality: 80));

        $sets = config('cms_media.image_sets', []);
        if ($sizeSet && isset($sets[$sizeSet])) {
            foreach ($sets[$sizeSet] as $suffix => $dimensions) {
                $resizedImage = clone $image;
                $resizedImage->scale(width: $dimensions['width'] ?? null, height: $dimensions['height'] ?? null);
                Storage::disk($disk)->put(
                    "{$directory}/{$fileName}_{$suffix}.{$extension}",
                    (string) $resizedImage->toWebp(quality: 80)
                );
                unset($resizedImage);
            }
        }

        unset($image, $manager);

        return $mainPath;
    }
}

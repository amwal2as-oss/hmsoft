<?php

namespace HMsoft\Tools\Features\Media\Support;

use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;

final class MediaStorePayload
{
    /**
     * True when the request uses the bulk shape (media[0][file]) rather than a root file.
     */
    public static function isBulk(?Request $request = null): bool
    {
        $request ??= request();

        if (! $request) {
            return false;
        }

        if (self::hasRootFile($request)) {
            return false;
        }

        if ($request->hasFile('media.0.file') || $request->hasFile('media.0')) {
            return true;
        }

        $media = $request->input('media');

        return is_array($media) && $media !== [];
    }

    public static function hasRootFile(?Request $request = null): bool
    {
        $request ??= request();

        if (! $request) {
            return false;
        }

        if ($request->hasFile('file')) {
            return true;
        }

        $file = $request->input('file');

        return is_string($file) && $file !== '' && filter_var($file, FILTER_VALIDATE_URL) !== false;
    }

    public static function isMissingFile(mixed $file): bool
    {
        if ($file === null || $file === '') {
            return true;
        }

        return $file instanceof UploadedFile && ! $file->isValid() && $file->getPath() === '';
    }
}

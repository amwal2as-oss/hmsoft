<?php

namespace HMsoft\Tools\Features\Media\Actions;

use HMsoft\Tools\Features\Media\Data\BulkDeleteMediaData;
use HMsoft\Tools\Features\Media\Facades\MediaUploader;
use HMsoft\Tools\Features\Media\Models\Medium;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class DeleteAction
{
    public function __construct() {}

    public function executeSingle(Model $owner, Medium $medium): bool
    {
        return DB::transaction(function () use ($owner, $medium) {
            $this->processDeletion($owner, $medium);
            return true;
        });
    }

    public function executeBulk(Model $owner, BulkDeleteMediaData $data): bool
    {
        return DB::transaction(function () use ($owner, $data) {
            $mediaList = $owner->mediaList()->whereIn('id', $data->ids)->get();
            foreach ($mediaList as $medium) {
                $this->processDeletion($owner, $medium);
            }
            return true;
        });
    }

    private function processDeletion(Model $owner, Medium $medium)
    {
        if ($medium->is_default) {
            $nextCandidate = $owner->mediaList()->where('id', '!=', $medium->id)->orderBy('sort_number')->first();
            if ($nextCandidate) $nextCandidate->update(['is_default' => true]);
        }

        if ($medium->mime_type !== 'link' && $medium->file_path) {
            MediaUploader::deleteFile($medium->file_path);
        }

        $medium->delete();
    }
}

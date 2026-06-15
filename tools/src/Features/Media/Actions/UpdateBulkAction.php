<?php

namespace HMsoft\Tools\Features\Media\Actions;

use HMsoft\Tools\Features\Media\Data\UpdateAllMediaData;
use HMsoft\Tools\Features\Media\Models\Medium;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class UpdateBulkAction
{
    public function __construct(private readonly UpdateAction $update_action) {}

    public function execute(Model $owner, UpdateAllMediaData $data): Collection
    {
        return DB::transaction(function () use ($owner, $data) {
            $updated = collect();

            $hasDefault = collect($data->media)->contains(function ($mediaData) {
                return !($mediaData->is_default instanceof \Spatie\LaravelData\Optional)
                    && $mediaData->is_default === true;
            });
            if ($hasDefault) {
                $owner->mediaList()->update(['is_default' => false]);
            }

            foreach ($data->media as $mediaData) {
                if (isset($mediaData->id)) {
                    $medium = $owner->mediaList()->findOrFail($mediaData->id);
                    $updated->push($this->update_action->execute($owner, $medium, $mediaData));
                }
            }

            return $owner->mediaList()->with(Medium::DEFAULT_INCLUDES)->get();
        });
    }
}

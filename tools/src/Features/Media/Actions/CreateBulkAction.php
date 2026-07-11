<?php

namespace HMsoft\Tools\Features\Media\Actions;

use HMsoft\Tools\Features\Media\Data\StoreBulkMediaData;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class CreateBulkAction
{
    public function __construct(private readonly CreateAction $createAction) {}

    public function execute(StoreBulkMediaData $data, Model $owner): Collection
    {
        return DB::transaction(function () use ($owner, $data) {
            $mediaCollection = collect();

            foreach ($data->media as $mediaItemData) {
                $newMedia = $this->createAction->execute($mediaItemData, $owner);
                $mediaCollection->push($newMedia);
                unset($newMedia);
                gc_collect_cycles();
            }

            return $mediaCollection;
        });
    }
}

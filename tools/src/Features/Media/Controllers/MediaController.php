<?php

namespace HMsoft\Tools\Features\Media\Controllers;

use HMsoft\Tools\Features\Media\Data\{BulkDeleteMediaData, MediaData, StoreBulkMediaData, StoreMediaData, UpdateAllMediaData, UpdateMediaData};
use HMsoft\Tools\Features\Media\Models\Medium;
use HMsoft\Tools\Features\Media\Service\MediaService;
use HMsoft\Tools\Features\Media\Traits\ExtractsOwnerFromRoute;
use HMsoft\Tools\Features\Response\Facades\CmsResponse;
use Illuminate\Http\Request;

class MediaController
{
    use ExtractsOwnerFromRoute;

    public function __construct(private readonly MediaService $mediaService) {}

    public function index(Request $request)
    {
        $ownerData = self::getOwnerFromRoute();
        abort_if(!$ownerData['owner_id'], 404, 'Owner is required for media.');

        $result = $this->mediaService->list($ownerData['owner_id'], $ownerData['owner_type']);
        $result['data'] = MediaData::filterableCollect($result['data']);

        return CmsResponse::success(data: $result['data'], pagination: $result['pagination']);
    }

    public function show(string $ownerType, string $ownerId, Medium $medium)
    {
        $this->verifyOwner($medium);
        return CmsResponse::success(data: MediaData::fromModel($this->mediaService->show($medium)));
    }

    public function store(StoreMediaData $data)
    {
        $owner = self::resolveOwnerModel();
        $media = $this->mediaService->store($owner, $data);
        return CmsResponse::success(message: __('media::messages.added_successfully'), data: MediaData::fromModel($media));
    }

    public function storeBulk(StoreBulkMediaData $data)
    {
        $owner = self::resolveOwnerModel();
        $mediaCollection = $this->mediaService->storeBulk($owner, $data);
        return CmsResponse::success(message: __('media::messages.added_successfully'), data: MediaData::filterableCollect($mediaCollection));
    }

    public function update(UpdateMediaData $data, string $ownerType, string $ownerId, Medium $medium)
    {
        $owner = self::resolveOwnerModel();
        $this->verifyOwner($medium, $owner);

        $updatedMedia = $this->mediaService->update($owner, $medium, $data);
        return CmsResponse::success(message: __('media::messages.updated_successfully'), data: MediaData::fromModel($updatedMedia));
    }

    public function updateAll(UpdateAllMediaData $data)
    {
        $owner = self::resolveOwnerModel();
        $updated = $this->mediaService->updateAll($owner, $data);
        return CmsResponse::success(message: __('media::messages.updated_successfully'), data: MediaData::filterableCollect($updated));
    }

    public function destroy(string $ownerType, string $ownerId, Medium $medium)
    {
        $owner = self::resolveOwnerModel();
        $this->verifyOwner($medium, $owner);

        $this->mediaService->delete($owner, $medium);
        return CmsResponse::success(message: __('media::messages.deleted_successfully'));
    }

    public function deleteBulk(BulkDeleteMediaData $data)
    {
        $owner = self::resolveOwnerModel();
        $this->mediaService->deleteBulk($owner, $data);
        return CmsResponse::success(message: __('media::messages.deleted_successfully'));
    }

    private function verifyOwner(Medium $medium, $owner = null)
    {
        $ownerData = $owner ? ['owner_id' => $owner->id, 'owner_type' => $owner->getMorphClass()] : self::getOwnerFromRoute();
        if ($medium->owner_type != $ownerData['owner_type'] || $medium->owner_id != $ownerData['owner_id']) {
            abort(404, 'Media does not belong to the specified owner.');
        }
    }
}

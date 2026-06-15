<?php

namespace HMsoft\Tools\Features\Media\Service;

use HMsoft\Tools\Features\Media\Actions\{CreateAction, CreateBulkAction, DeleteAction, GetListAction, UpdateAction, UpdateBulkAction};
use HMsoft\Tools\Features\Media\Data\{BulkDeleteMediaData, StoreBulkMediaData, StoreMediaData, UpdateAllMediaData, UpdateMediaData};
use HMsoft\Tools\Features\Media\Models\Medium;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class MediaService
{
    public function __construct(
        private readonly CreateAction $create_action,
        private readonly UpdateAction $update_action,
        private readonly CreateBulkAction $create_bulk_action,
        private readonly DeleteAction $delete_action,
        private readonly UpdateBulkAction $update_bulk_action,
        private readonly GetListAction $get_list_action,
    ) {}

    public function list(string $ownerId, string $ownerType): array
    {
        return $this->get_list_action->execute($ownerId, $ownerType);
    }

    public function store(Model $owner, StoreMediaData $data): Medium
    {
        return $this->create_action->execute($data, $owner);
    }

    public function storeBulk(Model $owner, StoreBulkMediaData $data): Collection
    {
        return $this->create_bulk_action->execute($data, $owner);
    }

    public function update(Model $owner, Medium $model, UpdateMediaData $data): Medium
    {
        return $this->update_action->execute($owner, $model, $data);
    }

    public function updateAll(Model $owner, UpdateAllMediaData $data): Collection
    {
        return $this->update_bulk_action->execute($owner, $data);
    }

    public function show(Medium $model): Medium
    {
        return $model->loadMissing(Medium::DEFAULT_INCLUDES);
    }

    public function delete(Model $owner, Medium $model): bool
    {
        return $this->delete_action->executeSingle($owner, $model);
    }

    public function deleteBulk(Model $owner, BulkDeleteMediaData $data): bool
    {
        return $this->delete_action->executeBulk($owner, $data);
    }
}

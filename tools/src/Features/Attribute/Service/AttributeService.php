<?php

namespace HMsoft\Tools\Features\Attribute\Service;

use HMsoft\Tools\Features\Attribute\Actions\{CreateAction, DeleteAction, GetListAction, SyncImageAction, UpdateAction, UpdateBulkAction};
use HMsoft\Tools\Features\Attribute\Data\{StoreAttributeData, SyncAttributeImageData, UpdateAllAttributesData, UpdateAttributeData};
use HMsoft\Tools\Features\Attribute\Models\Attribute;

class AttributeService
{
    public function __construct(
        private readonly CreateAction $create_action,
        private readonly UpdateAction $update_action,
        private readonly UpdateBulkAction $update_bulk_action,
        private readonly DeleteAction $delete_action,
        private readonly GetListAction $get_list_action,
        private readonly SyncImageAction $sync_image_action
    ) {}

    public function list(string $scope): array
    {
        return $this->get_list_action->execute($scope);
    }

    public function store(StoreAttributeData $data): Attribute
    {
        return $this->create_action->execute($data);
    }

    public function update(Attribute $model, UpdateAttributeData $data): Attribute
    {
        return $this->update_action->execute($model, $data);
    }

    public function updateAll(UpdateAllAttributesData $data): \Illuminate\Support\Collection
    {
        return $this->update_bulk_action->execute($data);
    }

    public function syncImage(Attribute $model, SyncAttributeImageData $data): array
    {
        return $this->sync_image_action->execute($model, $data);
    }

    public function show(Attribute $model): Attribute
    {
        return $model->loadMissing(Attribute::DEFAULT_INCLUDES);
    }

    public function delete(Attribute $model): bool
    {
        return $this->delete_action->executeSingle($model);
    }

    public function deleteBulk(array $ids): bool
    {
        return $this->delete_action->executeBulk($ids);
    }
}

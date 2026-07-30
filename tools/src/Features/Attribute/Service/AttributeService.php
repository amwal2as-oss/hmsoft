<?php

namespace HMsoft\Tools\Features\Attribute\Service;

use HMsoft\Tools\Features\Attribute\Actions\{CreateAction, DeleteAction, GetListAction, GetObjectAttributesAction, SyncIconAction, UpdateAction, UpdateBulkAction};
use HMsoft\Tools\Features\Attribute\Data\{AttributeData, StoreAttributeData, SyncAttributeIconData, UpdateAllAttributesData, UpdateAttributeData};
use HMsoft\Tools\Features\Attribute\Models\Attribute;

class AttributeService
{
    public function __construct(
        private readonly CreateAction $create_action,
        private readonly UpdateAction $update_action,
        private readonly UpdateBulkAction $update_bulk_action,
        private readonly DeleteAction $delete_action,
        private readonly GetListAction $get_list_action,
        private readonly GetObjectAttributesAction $get_object_attributes_action,
        private readonly SyncIconAction $sync_icon_action
    ) {}

    public function list(string $scope): array
    {
        return $this->get_list_action->execute($scope);
    }

    public function forObject(
        string $entityType,
        int|string $valuableId,
        ?string $categoryType = null,
        ?int $categoryId = null,
    ): array {
        $rows = $this->get_object_attributes_action->execute(
            entityType: $entityType,
            valuableId: $valuableId,
            categoryType: $categoryType,
            categoryId: $categoryId,
        );

        return AttributeData::collectWithValues($rows);
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

    public function syncIcon(Attribute $model, SyncAttributeIconData $data): array
    {
        return $this->sync_icon_action->execute($model, $data);
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

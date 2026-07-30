<?php

namespace HMsoft\Tools\Features\Attribute\Controllers;

use HMsoft\Tools\Features\Attribute\Data\{AttributeData, StoreAttributeData, SyncAttributeIconData, UpdateAllAttributesData, UpdateAttributeData};
use HMsoft\Tools\Features\Attribute\Models\Attribute;
use HMsoft\Tools\Features\Attribute\Service\AttributeService;
use HMsoft\Tools\Features\Response\Facades\CmsResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AttributeController
{
    public function __construct(private readonly AttributeService $attributeService) {}

    public function index(Request $request, string $scope)
    {
        $cleanScope = Str::singular($scope);
        $result = $this->attributeService->list($cleanScope);

        $result['data'] = AttributeData::filterableCollect($result['data']);

        return CmsResponse::success(data: $result['data'], pagination: $result['pagination']);
    }

    /**
     * Get attribute definitions with resolved values for a specific object.
     *
     * GET /api/{scope}/{valuable}/attributes
     */
    public function forObject(Request $request, string $scope, int|string $valuable)
    {
        $entityType = Str::singular($scope);

        $data = $this->attributeService->forObject(
            entityType: $entityType,
            valuableId: $valuable,
            categoryType: $request->query('category_type'),
            categoryId: $request->filled('category_id') ? (int) $request->query('category_id') : null,
        );

        return CmsResponse::success(data: AttributeData::filterableCollect($data));
    }

    public function show(string $scope, Attribute $attribute)
    {
        $this->verifyScope($scope, $attribute);
        return CmsResponse::success(data: AttributeData::fromModel($this->attributeService->show($attribute)));
    }

    public function store(string $scope, StoreAttributeData $data)
    {
        $attribute = $this->attributeService->store($data);
        return CmsResponse::success(message: __('cms_attribute::messages.added_successfully'), data: AttributeData::fromModel($attribute));
    }

    public function update(UpdateAttributeData $data, string $scope, Attribute $attribute)
    {
        $this->verifyScope($scope, $attribute);
        $updated = $this->attributeService->update($attribute, $data);
        return CmsResponse::success(message: __('cms_attribute::messages.updated_successfully'), data: AttributeData::fromModel($updated));
    }

    public function updateAll(UpdateAllAttributesData $data, string $scope)
    {
        $updated = $this->attributeService->updateAll($data);
        return CmsResponse::success(
            message: __('cms_attribute::messages.updated_successfully'),
            data: AttributeData::filterableCollect($updated)
        );
    }

    public function updateIcon(SyncAttributeIconData $data, string $scope, Attribute $attribute)
    {
        $this->verifyScope($scope, $attribute);

        $result = $this->attributeService->syncIcon($attribute, $data);
        $mediaStatus = $result['media_status'];

        if ($mediaStatus === 'deleted') {
            $message = __('cms_attribute::messages.icon_deleted_successfully');
        } elseif ($mediaStatus === 'uploaded') {
            $message = __('cms_attribute::messages.icon_uploaded_successfully');
        } else {
            $message = __('cms_attribute::messages.icon_unchanged');
        }

        return CmsResponse::success(
            message: $message,
            data: AttributeData::fromModel($result['model'])
        );
    }

    public function destroy(string $scope, Attribute $attribute)
    {
        $this->verifyScope($scope, $attribute);
        $this->attributeService->delete($attribute);
        return CmsResponse::success(message: __('cms_attribute::messages.deleted_successfully'));
    }

    public function bulkDelete(Request $request, string $scope)
    {
        $request->validate(['ids' => 'required|array', 'ids.*' => 'integer|exists:eav_attributes,id']);
        $this->attributeService->deleteBulk($request->ids);
        return CmsResponse::success(message: __('cms_attribute::messages.deleted_successfully'));
    }

    private function verifyScope(string $scope, Attribute $attribute)
    {
        $cleanScope = Str::singular($scope);
        if ($attribute->entity_type !== $cleanScope) {
            abort(404, 'Attribute does not belong to this entity type.');
        }
    }
}

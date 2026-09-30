<?php

namespace HMsoft\Tools\Features\Attribute\Http;

use HMsoft\Tools\Features\Attribute\Controllers\AttributeController;
use HMsoft\Tools\Features\Attribute\Data\StoreAttributeData;
use HMsoft\Tools\Features\Attribute\Data\SyncAttributeIconData;
use HMsoft\Tools\Features\Attribute\Data\UpdateAllAttributesData;
use HMsoft\Tools\Features\Attribute\Data\UpdateAttributeData;
use HMsoft\Tools\Features\Attribute\Models\Attribute;
use HMsoft\Tools\Features\Attribute\Support\EavConfig;
use HMsoft\Tools\Features\BulkDelete\Support\BulkDeleteIds;
use HMsoft\Tools\Features\Response\Facades\CmsResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

trait ForwardsScopedAttributeActions
{
    abstract protected function attributeRouteScope(): string;

    protected function attributeController(): AttributeController
    {
        return app(AttributeController::class);
    }

    public function index(Request $request)
    {
        return $this->attributeController()->index($request, $this->attributeRouteScope());
    }

    public function forObject(Request $request, int|string $valuable)
    {
        return $this->attributeController()->forObject($request, $this->attributeRouteScope(), $valuable);
    }

    public function show(Attribute $attribute)
    {
        return $this->attributeController()->show($this->attributeRouteScope(), $attribute);
    }

    public function store(StoreAttributeData $data)
    {
        return $this->attributeController()->store($this->attributeRouteScope(), $data);
    }

    public function update(UpdateAttributeData $data, Attribute $attribute)
    {
        return $this->attributeController()->update($data, $this->attributeRouteScope(), $attribute);
    }

    public function updateAll(UpdateAllAttributesData $data)
    {
        return $this->attributeController()->updateAll($data, $this->attributeRouteScope());
    }

    public function updateIcon(SyncAttributeIconData $data, Attribute $attribute)
    {
        return $this->attributeController()->updateIcon($data, $this->attributeRouteScope(), $attribute);
    }

    public function destroy(Attribute $attribute)
    {
        return $this->attributeController()->destroy($this->attributeRouteScope(), $attribute);
    }

    public function bulkDelete(Request $request)
    {
        $table = EavConfig::table('attributes') ?: 'eav_attributes';
        $request->validate(BulkDeleteIds::rules($table));

        if (BulkDeleteIds::isAll($request->input('ids'))) {
            $ids = Attribute::query()
                ->where('entity_type', Str::singular($this->attributeRouteScope()))
                ->pluck('id')
                ->all();

            if ($ids === []) {
                return CmsResponse::success(message: __('cms_attribute::messages.deleted_successfully'));
            }

            $request->merge(['ids' => $ids]);
        }

        return $this->attributeController()->bulkDelete($request, $this->attributeRouteScope());
    }
}

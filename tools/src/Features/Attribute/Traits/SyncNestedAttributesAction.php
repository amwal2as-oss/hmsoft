<?php

namespace HMsoft\Tools\Features\Attribute\Traits;

use HMsoft\Tools\Features\Attribute\Models\Attribute;
use Illuminate\Database\Eloquent\Model;

class SyncNestedAttributesAction
{
    public function execute(Model $owner, array $attributesData): void
    {
        $owner->attributeValues()->delete();

        if (empty($attributesData)) return;

        $attributeIds = collect($attributesData)->pluck('attribute_id')->unique();
        $attributesMap = Attribute::whereIn('id', $attributeIds)->get()->keyBy('id');

        $valuesToInsert = [];
        $ownerId = $owner->getKey();
        $ownerType = $owner->getMorphClass();

        foreach ($attributesData as $data) {
            $attribute = $attributesMap->get($data['attribute_id'] ?? null);
            if (!$attribute || !isset($data['value'])) continue;

            $value = $data['value'];

            if ($attribute->type === 'checkbox') {
                $valueToSave = is_array($value) ? json_encode(array_values(array_filter($value))) : null;
                if ($valueToSave) {
                    $valuesToInsert[] = [
                        'owner_id'     => $ownerId,
                        'owner_type'   => $ownerType,
                        'attribute_id' => $attribute->id,
                        'locale'       => null,
                        'value'        => $valueToSave,
                    ];
                }
            } elseif (in_array($attribute->type, ['text', 'textarea', 'wysiwyg'])) {
                $valuesToInsert[] = [
                    'owner_id'     => $ownerId,
                    'owner_type'   => $ownerType,
                    'attribute_id' => $attribute->id,
                    'locale'       => $data['locale'] ?? app()->getLocale(),
                    'value'        => (string) $value,
                ];
            } else {
                $valuesToInsert[] = [
                    'owner_id'     => $ownerId,
                    'owner_type'   => $ownerType,
                    'attribute_id' => $attribute->id,
                    'locale'       => null,
                    'value'        => (string) $value,
                ];
            }
        }

        if (!empty($valuesToInsert)) {
            $owner->attributeValues()->insert($valuesToInsert);
        }
    }
}

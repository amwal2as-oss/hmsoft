<?php

namespace HMsoft\Tools\Features\Attribute\Actions;

use HMsoft\Tools\Features\Attribute\Data\SyncAttributeIconData;
use HMsoft\Tools\Features\Attribute\Data\UpdateAttributeData;
use HMsoft\Tools\Features\Attribute\Enums\InputTypeEnum;
use HMsoft\Tools\Features\Attribute\Models\Attribute;
use HMsoft\Tools\Features\Attribute\Services\EavFilterRegistrar;
use Illuminate\Support\Facades\DB;
use Spatie\LaravelData\Optional;

class UpdateAction
{
    public function __construct(private readonly SyncIconAction $SyncIconAction) {}

    public function execute(Attribute $attribute, UpdateAttributeData $data): Attribute
    {
        return DB::transaction(function () use ($attribute, $data) {
            $updateData = collect($data->toArray())
                ->except(['locales', 'options', 'categories', 'id', 'icon', 'delete_icon', 'scope'])
                ->reject(fn($value) => $value instanceof Optional)
                ->toArray();

            // Set the correct value_type based on input_type
            if (isset($updateData['input_type'])) {
                $inputTypeEnum = InputTypeEnum::tryFrom($updateData['input_type']);
                if ($inputTypeEnum) {
                    $updateData['value_type'] = $inputTypeEnum->valueType();
                }
            }

            if (!empty($updateData)) {
                $attribute->update($updateData);
            }

            // Sync Translations
            $locales = $data->locales instanceof Optional ? null : $data->locales;
            if ($locales !== null && method_exists($attribute, 'syncTranslations')) {
                $attribute->syncTranslations($attribute, $locales);
            }

            // Sync Categories
            $categoriesData = $data->categories instanceof Optional ? null : $data->categories;
            if ($categoriesData !== null) {
                $attribute->categories()->delete();
                foreach ($categoriesData as $category) {
                    $attribute->categories()->create([
                        'category_type' => $category['category_type'],
                        'category_id'   => $category['category_id'],
                    ]);
                }
            }

            // Sync Options
            $optionsData = $data->options instanceof Optional ? null : $data->options;
            $currentInputType = isset($updateData['input_type'])
                ? $updateData['input_type']
                : ($attribute->input_type->value ?? $attribute->input_type);
            $inputTypeEnum = $currentInputType instanceof InputTypeEnum
                ? $currentInputType
                : InputTypeEnum::tryFrom((string) $currentInputType);

            if ($optionsData !== null && $inputTypeEnum?->hasOptions()) {
                $keepOptionIds = [];

                foreach ($optionsData as $optData) {
                    $optArray = is_array($optData) ? $optData : $optData->toArray();

                    if (isset($optArray['id']) && $optArray['id']) {
                        $option = $attribute->options()->findOrFail($optArray['id']);
                        $option->update(collect($optArray)->except(['locales', 'id'])->reject(fn($v) => $v instanceof Optional)->toArray());
                    } else {
                        $option = $attribute->options()->create(collect($optArray)->except(['locales', 'id'])->reject(fn($v) => $v instanceof Optional)->toArray());
                    }

                    $keepOptionIds[] = $option->id;

                    if (isset($optArray['locales']) && method_exists($option, 'syncTranslations')) {
                        $option->syncTranslations($option, $optArray['locales']);
                    }
                }

                $attribute->options()->whereNotIn('id', $keepOptionIds)->delete();
            } elseif (isset($updateData['input_type']) && ! $inputTypeEnum?->hasOptions()) {
                $attribute->options()->delete();
            }

            // Sync Icon (only when icon fields were sent on this request)
            $iconFile = property_exists($data, 'icon') && ! ($data->icon instanceof Optional)
                ? $data->icon
                : null;
            $shouldDelete = property_exists($data, 'delete_icon') && ! ($data->delete_icon instanceof Optional)
                ? (bool) $data->delete_icon
                : false;

            if ($iconFile !== null || $shouldDelete) {
                $syncIconData = SyncAttributeIconData::from([
                    'icon' => $iconFile,
                    'delete_icon' => $shouldDelete,
                ]);
                $this->SyncIconAction->execute($attribute, $syncIconData);
            }

            EavFilterRegistrar::flushEntityCache($attribute->entity_type);

            return $attribute->refresh()->load(Attribute::DEFAULT_INCLUDES);
        });
    }
}
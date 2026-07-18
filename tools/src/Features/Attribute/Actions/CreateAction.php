<?php

namespace HMsoft\Tools\Features\Attribute\Actions;

use HMsoft\Tools\Features\Attribute\Data\StoreAttributeData;
use HMsoft\Tools\Features\Attribute\Enums\InputTypeEnum;
use HMsoft\Tools\Features\Attribute\Models\Attribute;
use HMsoft\Tools\Features\Attribute\Models\AttributeCategory;
use HMsoft\Tools\Features\Attribute\Models\AttributeOptionTranslation;
use HMsoft\Tools\Features\Attribute\Models\AttributeTranslation;
use HMsoft\Tools\Features\Attribute\Services\EavFilterRegistrar;
use HMsoft\Tools\Features\Attribute\Support\EavCodeGenerator;
use Illuminate\Support\Facades\DB;
use Spatie\LaravelData\Optional;

class CreateAction
{
    public function execute(StoreAttributeData $data): Attribute
    {
        return DB::transaction(function () use ($data) {
            $inputType = InputTypeEnum::from($data->input_type);

            $code = EavCodeGenerator::forAttribute($data->code, $data->entity_type, $data->locales);

            $attribute = Attribute::create([
                'entity_type'       => $data->entity_type,
                'code'              => $code,
                'input_type'        => $inputType,
                'value_type'        => $inputType->valueType(),
                'default_value'     => $data->default_value,
                'validation_rules'  => $data->validation_rules,
                'icon'              => $data->icon,
                'sort_number'       => $data->sort_number ?? 0,
                'is_active'         => $data->is_active ?? true,
                'is_filterable'     => $data->is_filterable ?? true,
                'is_sortable'       => $data->is_sortable ?? false,
                'is_searchable'     => $data->is_searchable ?? false,
                'is_required'       => $data->is_required ?? false,
            ]);

            foreach ($data->locales as $localeRow) {
                AttributeTranslation::create([
                    'attribute_id' => $attribute->id,
                    'locale'       => $localeRow['locale'],
                    'title'        => $localeRow['title'],
                    'placeholder'  => $localeRow['placeholder'] ?? null,
                    'help_text'    => $localeRow['help_text'] ?? null,
                ]);
            }

            if ($inputType->hasOptions() && ! empty($data->options)) {
                foreach ($data->options as $optionData) {
                    $option = $attribute->options()->create([
                        'code'        => EavCodeGenerator::forOption($optionData->code ?? null, $optionData->locales),
                        'color'       => $optionData->color ?? null,
                        'icon'        => $optionData->icon ?? null,
                        'is_default'  => $optionData->is_default ?? false,
                        'is_active'   => $optionData->is_active ?? true,
                        'sort_number' => $optionData->sort_number ?? 0,
                    ]);

                    foreach ($optionData->locales as $localeRow) {
                        AttributeOptionTranslation::create([
                            'attribute_option_id' => $option->id,
                            'locale'                => $localeRow['locale'],
                            'label'                 => $localeRow['label'] ?? $localeRow['title'] ?? '',
                        ]);
                    }
                }
            }

            if (! empty($data->categories)) {
                foreach ($data->categories as $category) {
                    AttributeCategory::create([
                        'attribute_id'  => $attribute->id,
                        'category_type' => $category['category_type'],
                        'category_id'   => $category['category_id'],
                    ]);
                }
            }

            EavFilterRegistrar::flushEntityCache($attribute->entity_type);

            return $attribute->load(Attribute::DEFAULT_INCLUDES);
        });
    }
}

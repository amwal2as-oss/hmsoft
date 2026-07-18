<?php

namespace HMsoft\Tools\Features\Attribute\Services;

use HMsoft\Tools\Features\Attribute\Enums\InputTypeEnum;
use HMsoft\Tools\Features\Attribute\Enums\ValueTypeEnum;
use HMsoft\Tools\Features\Attribute\Models\Attribute;
use HMsoft\Tools\Features\Attribute\Models\EavValue;
use HMsoft\Tools\Features\Attribute\Models\EavValueOption;
use HMsoft\Tools\Features\Attribute\Models\EavValueTranslation;
use HMsoft\Tools\Features\Attribute\Support\EavConfig;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class EavValueSyncService
{
    /**
     * Sync EAV values for any morph-capable model.
     *
     * Payload shapes:
     * - ['code' => 'weight', 'value' => 12.5]
     * - ['attribute_id' => 1, 'value' => 'red']
     * - ['code' => 'description', 'value' => ['ar' => '...', 'en' => '...']]
     * - ['code' => 'materials', 'value' => [1, 3, 5]]  // option ids
     */
    public function sync(Model $owner, array $payload, ?string $entityType = null): void
    {
        if (! EavConfig::isEnabled()) {
            return;
        }

        $entityType ??= method_exists($owner, 'getMorphClass')
            ? $owner->getMorphClass()
            : $owner->getTable();

        DB::transaction(function () use ($owner, $payload, $entityType) {
            $this->deleteExistingValues($owner);

            if ($payload === []) {
                return;
            }

            $attributes = $this->resolveAttributes($payload, $entityType);

            foreach ($payload as $item) {
                if (! is_array($item)) {
                    continue;
                }

                $attribute = $this->matchAttribute($attributes, $item);
                if (! $attribute || ! array_key_exists('value', $item)) {
                    continue;
                }

                $this->persistValue($owner, $attribute, $item['value']);
            }
        });
    }

    protected function deleteExistingValues(Model $owner): void
    {
        $owner->eavValues()->each(function (EavValue $value) {
            $value->translations()->delete();
            $value->selectedOptions()->delete();
            $value->delete();
        });
    }

    protected function resolveAttributes(array $payload, string $entityType)
    {
        $codes = collect($payload)->pluck('code')->filter()->unique()->values();
        $ids = collect($payload)->pluck('attribute_id')->filter()->unique()->values();

        return Attribute::query()
            ->where('entity_type', $entityType)
            ->where(function ($q) use ($codes, $ids) {
                if ($codes->isNotEmpty()) {
                    $q->whereIn('code', $codes);
                }
                if ($ids->isNotEmpty()) {
                    $q->orWhereIn('id', $ids);
                }
            })
            ->get()
            ->keyBy('id');
    }

    protected function matchAttribute($attributes, array $item): ?Attribute
    {
        if (isset($item['attribute_id'])) {
            return $attributes->get($item['attribute_id']);
        }

        if (isset($item['code'])) {
            return $attributes->firstWhere('code', $item['code']);
        }

        return null;
    }

    protected function persistValue(Model $owner, Attribute $attribute, mixed $rawValue): void
    {
        $inputType = $attribute->input_type instanceof InputTypeEnum
            ? $attribute->input_type
            : InputTypeEnum::from($attribute->input_type);

        if ($inputType->isTranslatable() && is_array($rawValue)) {
            $this->persistTranslatableValue($owner, $attribute, $rawValue);

            return;
        }

        $value = $owner->eavValues()->create(
            $this->mapScalarColumns($attribute->value_type, $rawValue, $inputType)
            + ['attribute_id' => $attribute->id]
        );

        if ($attribute->value_type === ValueTypeEnum::Options) {
            $this->syncSelectedOptions($value, (array) $rawValue);
        }
    }

    protected function persistTranslatableValue(Model $owner, Attribute $attribute, array $localeValues): void
    {
        $value = $owner->eavValues()->create([
            'attribute_id' => $attribute->id,
        ]);

        foreach ($localeValues as $locale => $text) {
            if ($text === null || $text === '') {
                continue;
            }

            $isLong = $attribute->input_type === InputTypeEnum::Textarea;

            EavValueTranslation::create([
                'value_id' => $value->id,
                'locale' => $locale,
                'value_text' => $isLong ? null : (string) $text,
                'value_long_text' => $isLong ? (string) $text : null,
            ]);
        }
    }

    protected function mapScalarColumns(ValueTypeEnum $valueType, mixed $rawValue, InputTypeEnum $inputType): array
    {
        return match ($valueType) {
            ValueTypeEnum::String => [
                'value_text' => (string) $rawValue,
            ],
            ValueTypeEnum::Text => [
                'value_long_text' => (string) $rawValue,
            ],
            ValueTypeEnum::Number => [
                'value_number' => is_numeric($rawValue) ? $rawValue : null,
            ],
            ValueTypeEnum::Date => [
                'value_date' => $rawValue,
            ],
            ValueTypeEnum::Boolean => [
                'value_boolean' => filter_var($rawValue, FILTER_VALIDATE_BOOLEAN),
            ],
            ValueTypeEnum::Option => [
                'attribute_option_id' => (int) $rawValue,
            ],
            ValueTypeEnum::Options => [],
        };
    }

    protected function syncSelectedOptions(EavValue $value, array $optionIds): void
    {
        foreach (array_values(array_filter($optionIds)) as $optionId) {
            EavValueOption::create([
                'value_id' => $value->id,
                'attribute_option_id' => (int) $optionId,
            ]);
        }
    }
}

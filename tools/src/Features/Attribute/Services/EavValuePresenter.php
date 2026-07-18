<?php

namespace HMsoft\Tools\Features\Attribute\Services;

use HMsoft\Tools\Features\Attribute\Enums\InputTypeEnum;
use HMsoft\Tools\Features\Attribute\Enums\ValueTypeEnum;
use HMsoft\Tools\Features\Attribute\Models\Attribute;
use HMsoft\Tools\Features\Attribute\Models\EavValue;

class EavValuePresenter
{
    /**
     * Present stored EAV value in frontend/sync-friendly shape.
     */
    public static function present(?EavValue $value, Attribute $attribute): mixed
    {
        if ($value === null) {
            return self::presentDefault($attribute);
        }

        $inputType = $attribute->input_type instanceof InputTypeEnum
            ? $attribute->input_type
            : InputTypeEnum::tryFrom((string) $attribute->input_type);

        if ($inputType?->isTranslatable()) {
            return self::presentTranslations($value);
        }

        $valueType = $attribute->value_type instanceof ValueTypeEnum
            ? $attribute->value_type
            : ValueTypeEnum::from((string) $attribute->value_type);

        return match ($valueType) {
            ValueTypeEnum::String => $value->value_text,
            ValueTypeEnum::Text => $value->value_long_text ?? $value->value_text,
            ValueTypeEnum::Number => $value->value_number !== null ? (float) $value->value_number : null,
            ValueTypeEnum::Date => $value->value_date?->format('Y-m-d'),
            ValueTypeEnum::Boolean => $value->value_boolean,
            ValueTypeEnum::Option => $value->attribute_option_id,
            ValueTypeEnum::Options => $value->relationLoaded('selectedOptions')
                ? $value->selectedOptions->pluck('attribute_option_id')->values()->all()
                : [],
        };
    }

    protected static function presentTranslations(EavValue $value): ?array
    {
        if (! $value->relationLoaded('translations') || $value->translations->isEmpty()) {
            return null;
        }

        return $value->translations
            ->mapWithKeys(fn ($row) => [
                $row->locale => $row->value_long_text ?? $row->value_text,
            ])
            ->all();
    }

    protected static function presentDefault(Attribute $attribute): mixed
    {
        $default = $attribute->default_value;
        if (! is_array($default)) {
            return null;
        }

        if (array_key_exists('value', $default)) {
            return $default['value'];
        }

        if (array_key_exists('option_id', $default)) {
            return $default['option_id'];
        }

        if (array_key_exists('option_ids', $default)) {
            return $default['option_ids'];
        }

        // Translatable default: { ar: ..., en: ... }
        if (self::looksLikeLocaleMap($default)) {
            return $default;
        }

        return null;
    }

    protected static function looksLikeLocaleMap(array $default): bool
    {
        if ($default === []) {
            return false;
        }

        foreach (array_keys($default) as $key) {
            if (! is_string($key) || strlen($key) > 5) {
                return false;
            }
        }

        return true;
    }
}

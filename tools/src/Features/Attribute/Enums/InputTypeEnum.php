<?php

namespace HMsoft\Tools\Features\Attribute\Enums;

enum InputTypeEnum: string
{
    case Text = 'text';
    case Textarea = 'textarea';
    case Select = 'select';
    case MultiSelect = 'multi_select';
    case Radio = 'radio';
    case Checkbox = 'checkbox';
    case Color = 'color';
    case Number = 'number';
    case Date = 'date';
    case Boolean = 'boolean';

    public function valueType(): ValueTypeEnum
    {
        return match ($this) {
            self::Text, self::Color => ValueTypeEnum::String,
            self::Textarea => ValueTypeEnum::Text,
            self::Select, self::Radio => ValueTypeEnum::Option,
            self::MultiSelect, self::Checkbox => ValueTypeEnum::Options,
            self::Number => ValueTypeEnum::Number,
            self::Date => ValueTypeEnum::Date,
            self::Boolean => ValueTypeEnum::Boolean,
        };
    }

    public function isTranslatable(): bool
    {
        return match ($this) {
            self::Text, self::Textarea => true,
            default => false,
        };
    }

    public function hasOptions(): bool
    {
        return in_array($this, [
            self::Select,
            self::MultiSelect,
            self::Radio,
            self::Checkbox,
        ], true);
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}

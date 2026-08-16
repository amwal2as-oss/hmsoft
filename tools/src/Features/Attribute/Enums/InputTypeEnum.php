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
            self::Text => ValueTypeEnum::String,
            self::Textarea => ValueTypeEnum::Text,
            self::Select, self::Radio, self::Color => ValueTypeEnum::Option,
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
            self::Color,
        ], true);
    }

    /**
     * @return list<string>
     */
    public static function optionValues(): array
    {
        return array_values(array_map(
            fn (self $case) => $case->value,
            array_filter(self::cases(), fn (self $case) => $case->hasOptions())
        ));
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}

<?php

namespace HMsoft\Tools\Features\Attribute\Enums;

enum ValueTypeEnum: string
{
    case String = 'string';
    case Text = 'text';
    case Number = 'number';
    case Date = 'date';
    case Boolean = 'boolean';
    case Option = 'option';
    case Options = 'options';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}

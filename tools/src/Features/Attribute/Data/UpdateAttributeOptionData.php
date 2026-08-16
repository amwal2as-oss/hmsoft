<?php

namespace HMsoft\Tools\Features\Attribute\Data;

use HMsoft\Tools\Features\Attribute\Support\PrunesEmptyLocales;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Optional;

class UpdateAttributeOptionData extends Data
{
    public function __construct(
        public readonly array $locales,
        public readonly Optional|int|null $id,
        public readonly Optional|string|null $code,
        public readonly Optional|string|null $color,
        public readonly Optional|string|null $icon,
        public readonly Optional|bool $is_default,
        public readonly Optional|bool $is_active,
        public readonly Optional|int $sort_number,
    ) {}

    public static function rules(): array
    {
        return [
            'id'                 => ['sometimes', 'nullable', 'integer', 'exists:eav_attribute_options,id'],
            'code'               => ['sometimes', 'nullable', 'string', 'max:100', 'regex:/^[a-z0-9_]+$/'],
            'color'              => ['sometimes', 'nullable', 'string', 'max:20'],
            'icon'               => ['sometimes', 'nullable', 'string', 'max:255'],
            'is_default'         => ['sometimes', 'boolean'],
            'is_active'          => ['sometimes', 'boolean'],
            'sort_number'        => ['sometimes', 'integer'],
            'locales'            => ['required', 'array', 'min:1'],
            'locales.*.locale'   => ['required', 'string'],
            'locales.*.label'    => ['required', 'string', 'max:255'],
        ];
    }

    public static function prepareForPipeline(array $properties): array
    {
        foreach (['is_active', 'is_default'] as $field) {
            if (array_key_exists($field, $properties)) {
                $properties[$field] = filter_var($properties[$field], FILTER_VALIDATE_BOOLEAN);
            }
        }

        if (isset($properties['locales']) && is_array($properties['locales'])) {
            $properties['locales'] = PrunesEmptyLocales::prune($properties['locales'], 'label');
        }

        return $properties;
    }
}

<?php

namespace HMsoft\Tools\Features\Attribute\Data;

use Spatie\LaravelData\Data;

class StoreAttributeOptionData extends Data
{
    public function __construct(
        public readonly array $locales,
        public readonly ?string $code = null,
        public readonly ?string $color = null,
        public readonly ?string $icon = null,
        public readonly ?bool $is_default = false,
        public readonly ?bool $is_active = true,
        public readonly ?int $sort_number = 0,
    ) {}

    public static function rules(): array
    {
        return [
            'code'               => ['nullable', 'string', 'max:100', 'regex:/^[a-z0-9_]+$/'],
            'color'              => ['nullable', 'string', 'max:20'],
            'icon'               => ['nullable', 'string', 'max:255'],
            'is_default'         => ['sometimes', 'boolean'],
            'is_active'          => ['sometimes', 'boolean'],
            'sort_number'        => ['sometimes', 'integer'],
            'locales'            => ['required', 'array', 'min:1'],
            'locales.*.locale'   => ['required', 'string'],
            'locales.*.label'    => ['required_without:locales.*.title', 'string', 'max:255'],
            'locales.*.title'    => ['required_without:locales.*.label', 'string', 'max:255'],
        ];
    }

    public static function prepareForPipeline(array $properties): array
    {
        foreach (['is_active', 'is_default'] as $field) {
            if (array_key_exists($field, $properties)) {
                $properties[$field] = filter_var($properties[$field], FILTER_VALIDATE_BOOLEAN);
            }
        }

        return $properties;
    }
}

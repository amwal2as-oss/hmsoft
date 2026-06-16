<?php

namespace HMsoft\Tools\Features\Attribute\Data;

use Spatie\LaravelData\Data;

class StoreAttributeOptionData extends Data
{
    public function __construct(
        public readonly array $locales,
        public readonly ?bool $is_active = true,
        public readonly ?int $sort_number = 0,
    ) {}

    public static function rules(): array
    {
        return [
            'is_active'          => ['sometimes', 'boolean'],
            'sort_number'        => ['sometimes', 'integer'],
            'locales'            => ['required', 'array', 'min:1'],
            'locales.*.locale'   => ['required', 'string'],
            'locales.*.title'    => ['required', 'string', 'max:255'],
        ];
    }

    public static function prepareForPipeline(array $properties): array
    {
        if (array_key_exists('is_active', $properties)) {
            $properties['is_active'] = filter_var($properties['is_active'], FILTER_VALIDATE_BOOLEAN);
        }
        return $properties;
    }
}

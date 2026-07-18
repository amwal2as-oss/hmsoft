<?php

namespace HMsoft\Tools\Features\Attribute\Data;

use HMsoft\Tools\Features\Attribute\Enums\InputTypeEnum;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Data;

class StoreAttributeData extends Data
{
    public function __construct(
        public readonly array $locales,
        public readonly string $input_type,
        public readonly ?string $code = null,

        #[DataCollectionOf(StoreAttributeOptionData::class)]
        public readonly ?array $options = null,

        public readonly ?array $categories = null,
        public readonly ?string $entity_type = null,
        public readonly ?bool $is_active = true,
        public readonly ?bool $is_filterable = true,
        public readonly ?bool $is_sortable = false,
        public readonly ?bool $is_searchable = false,
        public readonly ?bool $is_required = false,
        public readonly ?int $sort_number = 0,
        public readonly ?string $icon = null,
        public readonly ?array $default_value = null,
        public readonly ?array $validation_rules = null,

        /** @deprecated use input_type */
        public readonly ?string $type = null,
        /** @deprecated use entity_type */
        public readonly ?string $scope = null,
    ) {}

    public static function prepareForPipeline(array $properties): array
    {
        $route = request()->route();
        if ($route && $route->hasParameter('scope') && empty($properties['entity_type'])) {
            $properties['entity_type'] = Str::singular($route->parameter('scope'));
        }

        if (! empty($properties['type']) && empty($properties['input_type'])) {
            $properties['input_type'] = $properties['type'] === 'checkbox'
                ? 'multi_select'
                : $properties['type'];
        }

        if (! empty($properties['scope']) && empty($properties['entity_type'])) {
            $properties['entity_type'] = $properties['scope'];
        }

        foreach (['is_active', 'is_filterable', 'is_sortable', 'is_searchable', 'is_required'] as $field) {
            if (array_key_exists($field, $properties)) {
                $properties[$field] = filter_var($properties[$field], FILTER_VALIDATE_BOOLEAN);
            }
        }

        return $properties;
    }

    public static function rules(): array
    {
        return [
            'entity_type'        => ['required', 'string', 'max:100'],
            'code'               => ['nullable', 'string', 'max:100', 'regex:/^[a-z0-9_]+$/'],
            'input_type'         => ['required', 'string', Rule::in(InputTypeEnum::values())],
            'categories'         => ['nullable', 'array'],
            'categories.*.category_type' => ['required_with:categories', 'string', 'max:100'],
            'categories.*.category_id'   => ['required_with:categories', 'integer'],
            'is_active'          => ['nullable', 'boolean'],
            'is_filterable'      => ['nullable', 'boolean'],
            'is_sortable'        => ['nullable', 'boolean'],
            'is_searchable'      => ['nullable', 'boolean'],
            'is_required'        => ['nullable', 'boolean'],
            'sort_number'        => ['nullable', 'integer'],
            'icon'               => ['nullable', 'string', 'max:255'],
            'default_value'      => ['nullable', 'array'],
            'validation_rules'   => ['nullable', 'array'],
            'locales'            => ['required', 'array', 'min:1'],
            'locales.*.locale'   => ['required', 'string'],
            'locales.*.title'    => ['required', 'string', 'max:255'],
            'locales.*.placeholder' => ['nullable', 'string', 'max:255'],
            'locales.*.help_text'   => ['nullable', 'string'],
            'options'            => ['required_if:input_type,select,radio,multi_select,checkbox', 'array'],
        ];
    }
}

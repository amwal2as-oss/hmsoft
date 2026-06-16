<?php

namespace HMsoft\Tools\Features\Attribute\Data;

use HMsoft\Tools\Features\Media\Rules\FileOrUrl;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Data;

class StoreAttributeData extends Data
{
    public function __construct(
        public readonly array $locales,
        public readonly string $type,
        public readonly ?array $category_ids = null,

        #[DataCollectionOf(StoreAttributeOptionData::class)]
        public readonly ?array $options = null,

        public readonly ?string $scope = null,
        public readonly ?bool $is_active = true,
        public readonly ?bool $is_filterable = true,
        public readonly ?bool $is_required = false,
        public readonly ?int $sort_number = 0,

        public readonly mixed $image = null,
        public readonly ?bool $delete_image = false,
    ) {}

    public static function prepareForPipeline(array $properties): array
    {
        $route = request()->route();
        if ($route && $route->hasParameter('scope')) {
            $properties['scope'] = Str::singular($route->parameter('scope'));
        }

        foreach (['is_active', 'is_filterable', 'is_required'] as $field) {
            if (array_key_exists($field, $properties)) {
                $properties[$field] = filter_var($properties[$field], FILTER_VALIDATE_BOOLEAN);
            }
        }
        return $properties;
    }

    public static function rules(): array
    {
        return [
            'scope'              => ['nullable', 'string'],
            'type'               => ['required', 'string', Rule::in(['text', 'textarea', 'select', 'radio', 'checkbox', 'number', 'date', 'boolean'])],
            'category_ids'       => ['nullable', 'array'],
            'category_ids.*'     => ['integer'],
            'is_active'          => ['nullable', 'boolean'],
            'is_filterable'      => ['nullable', 'boolean'],
            'is_required'        => ['nullable', 'boolean'],
            'sort_number'        => ['nullable', 'integer'],

            'locales'            => ['required', 'array', 'min:1'],
            'locales.*.locale'   => ['required', 'string'],
            'locales.*.title'    => ['required', 'string', 'max:255'],

            'options'            => ['required_if:type,select,radio,checkbox', 'array'],

            'image'              => ['sometimes', 'nullable', new FileOrUrl()],
            'delete_image'       => ['sometimes', 'boolean'],
        ];
    }
}

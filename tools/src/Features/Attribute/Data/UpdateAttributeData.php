<?php

namespace HMsoft\Tools\Features\Attribute\Data;

use HMsoft\Tools\Features\Media\Rules\FileOrUrl;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Optional;
use Spatie\LaravelData\Support\Validation\ValidationContext;

class UpdateAttributeData extends Data
{
    public function __construct(
        public readonly Optional|int|null $id,
        public readonly Optional|string|null $scope,
        public readonly Optional|array $locales,
        public readonly Optional|string $type,
        public readonly Optional|array|null $category_ids,

        #[DataCollectionOf(UpdateAttributeOptionData::class)]
        public readonly Optional|array|null $options,

        public readonly Optional|bool $is_active,
        public readonly Optional|bool $is_filterable,
        public readonly Optional|bool $is_required,
        public readonly Optional|int $sort_number,

        public readonly mixed $image = null,
        public readonly ?bool $delete_image = false,
    ) {}

    public static function prepareForPipeline(array $properties): array
    {
        $route = Request::route();
        if ($route && $route->hasParameter('scope')) {
            $properties['scope'] = Str::singular($route->parameter('scope'));
        }

        foreach (['is_active', 'is_filterable', 'is_required', 'delete_image'] as $field) {
            if (array_key_exists($field, $properties)) {
                $properties[$field] = filter_var($properties[$field], FILTER_VALIDATE_BOOLEAN);
            }
        }
        return $properties;
    }

    public static function rules(ValidationContext $context): array
    {
        $fullPayload = $context->fullPayload;
        $scope = $fullPayload['scope'] ?? null;

        return [
            'id'                 => ['sometimes', 'required', 'integer', 'exists:attributes,id'],
            'scope'              => ['sometimes', 'nullable', 'string'],
            'type'               => ['sometimes', 'string', Rule::in(['text', 'textarea', 'select', 'radio', 'checkbox', 'number', 'date', 'boolean'])],
            'category_ids'       => ['sometimes', 'nullable', 'array'],
            'category_ids.*'     => ['integer'],
            'is_active'          => ['sometimes', 'boolean'],
            'is_filterable'      => ['sometimes', 'boolean'],
            'is_required'        => ['sometimes', 'boolean'],
            'sort_number'        => ['sometimes', 'integer'],
            'locales'            => ['sometimes', 'array', 'min:1'],
            'locales.*.locale'   => ['required_with:locales', 'string'],

            'locales.*.title'    => [
                'required_with:locales',
                'string',
                'max:255',
                function ($attribute, $value, $fail) use ($fullPayload, $scope) {
                    $pathParts = explode('.', $attribute);
                    $isStandalone = $pathParts[0] === 'locales';

                    if ($isStandalone) {
                        $localeIndex = $pathParts[1];
                        $currentLocale = Arr::get($fullPayload, "locales.{$localeIndex}.locale");

                        $routeParam = Request::route('attribute');
                        $attributeId = $fullPayload['id'] ?? (is_object($routeParam) ? $routeParam->id : $routeParam);

                        if ($currentLocale) {
                            $query = DB::table('attribute_translations')
                                ->join('attributes', 'attributes.id', '=', 'attribute_translations.attribute_id')
                                ->where('attribute_translations.locale', $currentLocale)
                                ->where('attribute_translations.title', $value);

                            if ($scope) {
                                $query->where('attributes.scope', $scope);
                            }

                            if ($attributeId) {
                                $query->where('attribute_translations.attribute_id', '!=', $attributeId);
                            }

                            if ($query->exists()) {
                                $fail(trans('validation.unique', ['attribute' => 'title']));
                            }
                        }
                    } else {
                        array_pop($pathParts);
                        $localeIndex = array_pop($pathParts);
                        array_pop($pathParts);
                        array_pop($pathParts);

                        $parentArrayPath = implode('.', $pathParts);
                        $parentArray = Arr::get($fullPayload, $parentArrayPath, []);

                        $exactCurrentLocalePath = str_replace('.title', '.locale', $attribute);
                        $currentLocale = Arr::get($fullPayload, $exactCurrentLocalePath);

                        if (!$currentLocale) return;

                        $duplicateCount = 0;
                        foreach ($parentArray as $attrItem) {
                            $locales = $attrItem['locales'] ?? [];
                            foreach ($locales as $localeData) {
                                if (
                                    ($localeData['locale'] ?? '') === $currentLocale &&
                                    ($localeData['title'] ?? '') === $value
                                ) {
                                    $duplicateCount++;
                                }
                            }
                        }

                        if ($duplicateCount > 1) {
                            $fail(__('validation.distinct', ['attribute' => 'title']));
                        }
                    }
                }
            ],
            'options'            => ['sometimes', 'nullable', 'array'],
            'image'              => ['sometimes', 'nullable', new FileOrUrl()],
            'delete_image'       => ['sometimes', 'boolean'],
        ];
    }
}

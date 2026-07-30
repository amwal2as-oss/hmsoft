<?php

namespace HMsoft\Tools\Features\Attribute\Data;

use HMsoft\Tools\Features\Attribute\Enums\InputTypeEnum;
use HMsoft\Tools\Features\Media\Rules\FileOrUrl;
use HMsoft\Tools\Features\Attribute\Rules\ValidMorphCategory;
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
        public readonly Optional|string $input_type,
        public readonly Optional|array|null $categories,
        #[DataCollectionOf(UpdateAttributeOptionData::class)]
        public readonly Optional|array|null $options,
        public readonly Optional|bool $is_active,
        public readonly Optional|bool $is_filterable,
        public readonly Optional|bool $is_required,
        public readonly Optional|bool $is_sortable,
        public readonly Optional|bool $is_searchable,
        public readonly Optional|int $sort_number,
    ) {}

    public static function prepareForPipeline(array $properties): array
    {
        $route = Request::route();

        // 1. استخراج الـ Scope من الرابط إذا لم يكن موجوداً
        if ($route && $route->hasParameter('scope')) {
            $properties['scope'] = Str::singular($route->parameter('scope'));
        }

        // 2. التحويل الذكي لمصفوفة الفئات (Categories)
        if (isset($properties['categories']) && is_array($properties['categories']) && count($properties['categories']) > 0) {
            // إذا كان الـ Frontend قد أرسل مصفوفة أرقام مسطحة مثل [1, 2]
            if (is_numeric($properties['categories'][0])) {
                $entityType = $properties['scope'] ?? 'item';

                // قراءة الـ Morph الخاص بالفئة من ملف الإعدادات
                $categoryType = config("cms_eav.category_map.{$entityType}", "{$entityType}_category");

                $formattedCategories = [];
                foreach ($properties['categories'] as $catId) {
                    $formattedCategories[] = [
                        'category_type' => $categoryType,
                        'category_id'   => (int) $catId
                    ];
                }

                $properties['categories'] = $formattedCategories;
            }
        }

        // 3. فلترة القيم المنطقية (Booleans)
        foreach (['is_active', 'is_filterable', 'is_required', 'is_sortable', 'is_searchable'] as $field) {
            if (array_key_exists($field, $properties)) {
                $properties[$field] = filter_var($properties[$field], FILTER_VALIDATE_BOOLEAN);
            }
        }

        return $properties;
    }

    public static function rules(ValidationContext $context): array
    {
        $fullPayload = $context->fullPayload;

        return [
            'id'                 => ['sometimes', 'required', 'integer', 'exists:eav_attributes,id'],
            'scope'              => ['sometimes', 'nullable', 'string'],
            'input_type'         => ['sometimes', 'string', Rule::in(InputTypeEnum::values())],

            // --- قواعد التحقق الخاصة بالفئات ---
            'categories'         => ['sometimes', 'nullable', 'array'],
            'categories.*.category_type' => ['required_with:categories', 'string', 'max:100'],
            'categories.*.category_id'   => [
                'required_with:categories',
                'integer',
                new ValidMorphCategory($fullPayload) // استخدام قاعدة التحقق المخصصة والنظيفة
            ],
            // -----------------------------------

            'is_active'          => ['sometimes', 'boolean'],
            'is_filterable'      => ['sometimes', 'boolean'],
            'is_sortable'        => ['sometimes', 'boolean'],
            'is_searchable'      => ['sometimes', 'boolean'],
            'is_required'        => ['sometimes', 'boolean'],
            'sort_number'        => ['sometimes', 'integer'],

            'options'            => ['sometimes', 'nullable', 'array'],

            'locales'            => ['sometimes', 'array', 'min:1'],
            'locales.*.locale'   => ['required_with:locales', 'string'],
            'locales.*.title'    => ['required_with:locales', 'string', 'max:255'],
        ];
    }
}

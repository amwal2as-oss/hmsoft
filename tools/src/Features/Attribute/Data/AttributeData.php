<?php

namespace HMsoft\Tools\Features\Attribute\Data;

use HMsoft\Tools\Features\Attribute\Models\Attribute;
use HMsoft\Tools\Features\Attribute\Models\EavValue;
use HMsoft\Tools\Features\Attribute\Services\EavValuePresenter;
use HMsoft\Tools\Features\DynamicFilters\Data\BaseData;
use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\DataCollection;
use Spatie\LaravelData\Optional;

class AttributeData extends BaseData
{
    public function __construct(
        public readonly ?int $id,
        public readonly ?string $entity_type,
        public readonly ?string $code,
        public readonly ?string $input_type,
        public readonly ?string $value_type,
        public readonly ?array $default_value,
        public readonly ?array $validation_rules,
        public readonly Lazy|array|null $icon,
        public readonly ?string $icon_url,
        public readonly ?bool $is_active,
        public readonly ?bool $is_filterable,
        public readonly ?bool $is_sortable,
        public readonly ?bool $is_searchable,
        public readonly ?bool $is_required,
        public readonly ?int $sort_number,
        public readonly ?string $title,
        public readonly array|Optional $translations,
        public readonly array|Optional $categories,
        #[DataCollectionOf(AttributeOptionData::class)]
        public readonly DataCollection|array|Optional $options,
        public readonly ?string $scope = null,
        public readonly ?string $type = null,
        public readonly ?\DateTime $created_at = null,
        public readonly ?\DateTime $updated_at = null,
        public readonly mixed $value = null,
        public readonly ?int $value_id = null,
    ) {}

    public static function fromModel(Attribute $attribute): self
    {
        $defaultTranslation = null;
        if ($attribute->relationLoaded('translations')) {
            $defaultTranslation = $attribute->translations->firstWhere('locale', app()->getLocale())
                ?? $attribute->translations->first();
        }

        $inputType = $attribute->input_type?->value ?? $attribute->input_type;
        $valueType = $attribute->value_type?->value ?? $attribute->value_type;

        return new self(
            id: $attribute->id,
            entity_type: $attribute->entity_type,
            code: $attribute->code,
            input_type: $inputType,
            value_type: $valueType,
            default_value: $attribute->default_value,
            validation_rules: $attribute->validation_rules,
            icon: $attribute->getMediaObject('icon'),
            icon_url: $attribute->icon_url,
            is_active: $attribute->is_active,
            is_filterable: $attribute->is_filterable,
            is_sortable: $attribute->is_sortable,
            is_searchable: $attribute->is_searchable,
            is_required: $attribute->is_required,
            sort_number: $attribute->sort_number,
            title: $defaultTranslation?->title,
            translations: $attribute->relationLoaded('translations')
                ? $attribute->translations->mapWithKeys(fn($t) => [
                    $t->locale => [
                        'title' => $t->title,
                        'placeholder' => $t->placeholder,
                        'help_text' => $t->help_text,
                    ],
                ])->toArray()
                : Optional::create(),

            // --- الاستدعاء الذكي للفئات (Polymorphism) ---
            categories: $attribute->relationLoaded('categories')
                ? $attribute->categories->map(function ($c) {

                    // 1. فرض تحميل العلاقة إذا فُقدت بسبب الـ Refresh
                    if (! $c->relationLoaded('category')) {
                        $c->load('category');
                    }

                    // 2. إذا تم العثور على الفئة في قاعدة البيانات
                    if ($c->category) {
                        if (method_exists($c->category, 'toEavResourceArray')) {
                            return $c->category->toEavResourceArray();
                        }
                        return $c->category->toArray();
                    }

                    // 3. مسار الحماية (يُنفذ فقط إذا كان المودل null)
                    return [
                        'id' => $c->category_id,
                        'category_type' => $c->category_type,
                        '_error' => 'Category not found in DB, or hidden by Global Scope (is_active=false), or MorphMap missing.'
                    ];
                })->values()->all()
                : Optional::create(),
            // ----------------------------------------

            options: $attribute->relationLoaded('options')
                ? AttributeOptionData::collect($attribute->options, DataCollection::class)
                : Optional::create(),
            scope: $attribute->entity_type,
            type: $inputType,
            created_at: $attribute->created_at,
            updated_at: $attribute->updated_at,
        );
    }

    public static function fromModelWithValue(Attribute $attribute, ?EavValue $value = null, mixed $presentedValue = null): self
    {
        $base = self::fromModel($attribute);

        return new self(
            id: $base->id,
            entity_type: $base->entity_type,
            code: $base->code,
            input_type: $base->input_type,
            value_type: $base->value_type,
            default_value: $base->default_value,
            validation_rules: $base->validation_rules,
            icon: $attribute->getMediaObject('icon'),
            icon_url: $attribute->icon_url,
            is_active: $base->is_active,
            is_filterable: $base->is_filterable,
            is_sortable: $base->is_sortable,
            is_searchable: $base->is_searchable,
            is_required: $base->is_required,
            sort_number: $base->sort_number,
            title: $base->title,
            translations: $base->translations instanceof Optional ? Optional::create() : $base->translations,
            categories: $base->categories instanceof Optional ? Optional::create() : $base->categories,
            options: $base->options instanceof Optional ? Optional::create() : $base->options,
            scope: $base->scope,
            type: $base->type,
            created_at: $base->created_at,
            updated_at: $base->updated_at,
            value: $presentedValue ?? EavValuePresenter::present($value, $attribute),
            value_id: $value?->id,
        );
    }

    public static function collectWithValues(iterable $rows): array
    {
        return collect($rows)
            ->map(fn(array $row) => self::fromModelWithValue(
                $row['attribute'],
                $row['value'],
                $row['presented_value'] ?? null,
            ))
            ->values()
            ->all();
    }
}

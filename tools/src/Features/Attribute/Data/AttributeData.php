<?php

namespace HMsoft\Tools\Features\Attribute\Data;

use App\Data\BaseData;
use HMsoft\Tools\Features\Attribute\Models\Attribute;
use Spatie\LaravelData\Optional;
use Spatie\LaravelData\DataCollection;
use Spatie\LaravelData\Attributes\DataCollectionOf;

class AttributeData extends BaseData
{
    public function __construct(
        public readonly ?int $id,
        public readonly ?string $scope,
        public readonly ?string $type,
        public readonly ?string $cast_type,
        public readonly ?array $category_ids,
        public readonly ?bool $is_active,
        public readonly ?bool $is_filterable,
        public readonly ?bool $is_required,
        public readonly ?int $sort_number,
        public readonly ?string $title,
        public readonly array|Optional $translations,

        #[DataCollectionOf(AttributeOptionData::class)]
        public readonly DataCollection|array|Optional $options,
        public readonly ?string $image_url,
        public readonly ?\DateTime $created_at,
    ) {}

    public static function fromModel(Attribute $attribute): self
    {
        $defaultTranslation = null;
        if ($attribute->relationLoaded('translations')) {
            $defaultTranslation = $attribute->translations->firstWhere('locale', app()->getLocale())
                ?? $attribute->translations->first();
        }

        return new self(
            id: $attribute->id,
            scope: $attribute->scope,
            type: $attribute->type,
            cast_type: $attribute->cast_type,
            category_ids: $attribute->category_ids,
            is_active: $attribute->is_active,
            is_filterable: $attribute->is_filterable,
            is_required: $attribute->is_required,
            sort_number: $attribute->sort_number,
            title: $defaultTranslation?->title,
            translations: $attribute->relationLoaded('translations')
                ? $attribute->translations->mapWithKeys(fn($t) => [$t->locale => ['title' => $t->title]])->toArray()
                : Optional::create(),
            options: $attribute->relationLoaded('options')
                ? AttributeOptionData::collect($attribute->options, \Spatie\LaravelData\DataCollection::class)
                : Optional::create(),
            image_url: $attribute->image_url ?? null,
            created_at: $attribute->created_at,
        );
    }
}

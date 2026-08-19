<?php

namespace HMsoft\Tools\Features\Attribute\Data;

use HMsoft\Tools\Features\Attribute\Models\AttributeOption;
use HMsoft\Tools\Features\DynamicFilters\Data\BaseData;
use HMsoft\Tools\Features\Translations\Support\TranslatableResponse;
use Spatie\LaravelData\Optional;

class AttributeOptionData extends BaseData
{
    public function __construct(
        public readonly ?int $id,
        public readonly ?string $code,
        public readonly ?string $color,
        public readonly ?string $icon,
        public readonly ?bool $is_default,
        public readonly ?bool $is_active,
        public readonly ?int $sort_number,
        public readonly ?string $label,
        public readonly array|Optional $translations,
    ) {}

    public static function fromModel(AttributeOption $option): self
    {
        $resolved = TranslatableResponse::resolve($option);

        return new self(
            id: $option->id,
            code: $option->code,
            color: $option->color,
            icon: $option->icon,
            is_default: $option->is_default,
            is_active: $option->is_active,
            sort_number: $option->sort_number,
            label: $resolved['label'] ?? null,
            translations: $option->relationLoaded('translations')
                ? TranslatableResponse::map($option, ['label'])
                : Optional::create(),
        );
    }
}

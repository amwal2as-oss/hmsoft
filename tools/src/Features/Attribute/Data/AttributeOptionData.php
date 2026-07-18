<?php

namespace HMsoft\Tools\Features\Attribute\Data;

use HMsoft\Tools\Features\Attribute\Models\AttributeOption;
use HMsoft\Tools\Features\DynamicFilters\Data\BaseData;
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
        $defaultTranslation = null;
        if ($option->relationLoaded('translations')) {
            $defaultTranslation = $option->translations->firstWhere('locale', app()->getLocale())
                ?? $option->translations->first();
        }

        return new self(
            id: $option->id,
            code: $option->code,
            color: $option->color,
            icon: $option->icon,
            is_default: $option->is_default,
            is_active: $option->is_active,
            sort_number: $option->sort_number,
            label: $defaultTranslation?->label,
            translations: $option->relationLoaded('translations')
                ? $option->translations->mapWithKeys(fn ($t) => [
                    $t->locale => ['label' => $t->label],
                ])->toArray()
                : Optional::create(),
        );
    }
}

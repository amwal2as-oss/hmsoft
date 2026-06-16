<?php

namespace HMsoft\Tools\Features\Attribute\Data;

use App\Data\BaseData;
use HMsoft\Tools\Features\Attribute\Models\AttributeOption;
use Spatie\LaravelData\Optional;

class AttributeOptionData extends BaseData
{
    public function __construct(
        public readonly ?int $id,
        public readonly ?bool $is_active,
        public readonly ?int $sort_number,
        public readonly ?string $title,
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
            is_active: $option->is_active,
            sort_number: $option->sort_number,
            title: $defaultTranslation?->title,
            translations: $option->relationLoaded('translations')
                ? $option->translations->mapWithKeys(fn($t) => [$t->locale => ['title' => $t->title]])->toArray()
                : Optional::create(),
        );
    }
}

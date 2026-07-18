<?php

namespace HMsoft\Tools\Features\Attribute\Actions;

use HMsoft\Tools\Features\Attribute\Models\Attribute;
use HMsoft\Tools\Features\Attribute\Models\EavValue;
use HMsoft\Tools\Features\Attribute\Services\EavValuePresenter;
use Illuminate\Support\Collection;

class GetObjectAttributesAction
{
    /**
     * Load attribute definitions for an entity type with values for a specific object.
     *
     * @return Collection<int, array{attribute: Attribute, value: ?EavValue, presented_value: mixed}>
     */
    public function execute(
        string $entityType,
        int|string $valuableId,
        ?string $categoryType = null,
        ?int $categoryId = null,
        bool $activeOnly = true,
    ): Collection {
        $query = Attribute::query()
            ->forEntity($entityType)
            ->with(Attribute::DEFAULT_INCLUDES)
            ->orderBy('sort_number');

        if ($activeOnly) {
            $query->where('is_active', true);
        }

        if ($categoryType !== null && $categoryId !== null) {
            $query->where(function ($q) use ($categoryType, $categoryId) {
                $q->whereDoesntHave('categories')
                    ->orWhereHas('categories', function ($categoryQuery) use ($categoryType, $categoryId) {
                        $categoryQuery
                            ->where('category_type', $categoryType)
                            ->where('category_id', $categoryId);
                    });
            });
        }

        $attributes = $query->get();

        if ($attributes->isEmpty()) {
            return collect();
        }

        $values = EavValue::query()
            ->where('valuable_type', $entityType)
            ->where('valuable_id', $valuableId)
            ->whereIn('attribute_id', $attributes->pluck('id'))
            ->with(['translations', 'selectedOptions'])
            ->get()
            ->keyBy('attribute_id');

        return $attributes->map(function (Attribute $attribute) use ($values) {
            $value = $values->get($attribute->id);

            return [
                'attribute' => $attribute,
                'value' => $value,
                'presented_value' => EavValuePresenter::present($value, $attribute),
            ];
        });
    }
}

<?php

namespace HMsoft\Tools\Features\Attribute\Actions;

use HMsoft\Tools\Features\Attribute\Models\Attribute;
use HMsoft\Tools\Features\Attribute\Models\EavValue;
use HMsoft\Tools\Features\Attribute\Services\EavValuePresenter;
use HMsoft\Tools\Features\DynamicFilters\Services\AutoFilterAndSortService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class GetObjectAttributesAction
{
    private const CATEGORY_FILTER_KEYS = [
        'categories.category_id',
        'category_id',
        'category_ids',
    ];

    /**
     * Load attribute definitions via DynamicFilters (columnFilters from request)
     * and attach stored values for one entity record.
     *
     * Unassigned attributes (no category rows) are excluded unless the client
     * explicitly filters for them. Item forms should filter by category id(s).
     *
     * @return Collection<int, array{attribute: Attribute, value: ?EavValue, presented_value: mixed}>
     */
    public function execute(
        string $entityType,
        int|string $valuableId,
        ?Request $request = null,
    ): Collection {
        $request ??= request();

        if (! $this->requestHasCategoryFilter($request)) {
            return collect();
        }

        $result = AutoFilterAndSortService::dynamicSearchFromRequest(
            model: Attribute::class,
            extraOperation: function (Builder &$query) use ($entityType) {
                $query
                    ->where('entity_type', $entityType)
                    ->with(Attribute::DEFAULT_INCLUDES);
            },
        );

        $attributes = collect($result['data'] ?? []);

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

    private function requestHasCategoryFilter(Request $request): bool
    {
        $filters = AutoFilterAndSortService::getFiltersValuesFromRequest($request);

        return $filters->contains(
            fn ($filter) => in_array($filter->id, self::CATEGORY_FILTER_KEYS, true),
        );
    }
}

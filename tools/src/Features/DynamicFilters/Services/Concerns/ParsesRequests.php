<?php

namespace HMsoft\Tools\Features\DynamicFilters\Services\Concerns;

use HMsoft\Tools\Features\DynamicFilters\Data\DynamicFilterData;
use HMsoft\Tools\Features\DynamicFilters\Data\ColumnFilterData;
use HMsoft\Tools\Features\DynamicFilters\Data\ColumnSortData;
use HMsoft\Tools\Features\DynamicFilters\Enums\FilterFnsEnum;
use HMsoft\Tools\Features\DynamicFilters\Enums\PaginationFormateEnum;
use HMsoft\Tools\Interfaces\AutoFilterable;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

trait ParsesRequests
{
    public function initializeDynamicFilterData(
        ?DynamicFilterData $dynamicFilterData = null,
        ?Request $request = null,
        $page = null,
        $perPage = null,
        $paginationFormate = null,
        $filters = null,
        $sorting = null,
        $globalFilter = null,
        $advanceFilter = null,
        $globaleFilterExtraOperation = null,
        $extraOperation = null,
        $beforeOperation = null,
        array $filterKeyMap = [],
        array $sortKeyMap = [],
        $columns = null,
        $count_only = null
    ): DynamicFilterData {
        if ($dynamicFilterData) {
            return $dynamicFilterData;
        }

        $request = $request ?? request();

        $finalPage = $page ?? $request->input('page');
        $finalPerPage = $perPage ?? $request->input('perPage', $request->input('per_page', $request->input('limit')));
        $finalCountOnly = $count_only !== null ? (bool) $count_only : (bool) $request->input('count_only', false);

        $finalPaginationFormate = is_null($paginationFormate)
            ? PaginationFormateEnum::from($request->input('paginationFormate', PaginationFormateEnum::separated->value))
            : $paginationFormate;

        if (is_null($finalPage) || is_null($finalPerPage) || $finalPerPage === 'all' || $request->header('pdt') === '0' || $finalCountOnly) {
            $finalPaginationFormate = PaginationFormateEnum::none;
            $finalPage = 'all';
            $finalPerPage = 'all';
        }

        $finalFilters = $filters ?? self::getFiltersValuesFromRequest($request);
        $finalSorting = $sorting ?? self::getSortingValuesFromRequest($request);
        $finalAdvanceFilter = $advanceFilter ?? self::getAdvanceFilterFromRequest($request);
        $finalGlobalFilter = $globalFilter ?? $request->input('globalFilter');

        if ($finalFilters->isEmpty() && $this->model instanceof AutoFilterable) {
            $finalFilters = collect();
            foreach ($this->model->cmsDefaultFilters() as $fId => $fVal) {
                if (!is_numeric($fId)) {
                    $finalFilters->push(new ColumnFilterData($fId, $fVal->value, FilterFnsEnum::from($fVal->filterFn)));
                }
            }
        }

        if ($finalSorting->isEmpty() && $this->model instanceof AutoFilterable) {
            $finalSorting = collect();
            foreach ($this->model->cmsDefaultSorts() as $s) {
                if (isset($s->id)) {
                    $finalSorting->push(new ColumnSortData($s->id, $s->desc ?? false));
                }
            }
        }

        $finalColumns = $columns ?? $request->input('fields');

        if (!empty($filterKeyMap)) {
            $finalFilters = $finalFilters->map(function ($filter) use ($filterKeyMap) {
                if (isset($filterKeyMap[$filter->id])) {
                    $filter->id = $filterKeyMap[$filter->id];
                }
                return $filter;
            });
        }

        if (!empty($sortKeyMap)) {
            $finalSorting = $finalSorting->map(function ($sort) use ($sortKeyMap) {
                if (isset($sortKeyMap[$sort->id])) {
                    $sort->id = $sortKeyMap[$sort->id];
                }
                return $sort;
            });
        }

        return new DynamicFilterData(
            page: $finalPage,
            perPage: $finalPerPage ,
            paginationFormate: $finalPaginationFormate,
            filters: $finalFilters,
            advanceFilter: $finalAdvanceFilter,
            sorting: $finalSorting,
            globalFilter: $finalGlobalFilter,
            globaleFilterExtraOperation: $globaleFilterExtraOperation,
            extraOperation: $extraOperation,
            beforeOperation: $beforeOperation,
            columns: $finalColumns,
            count_only: $finalCountOnly
        );
    }

    public static function getFiltersValuesFromRequest($request): Collection
    {
        $filters = collect([]);
        $encodedFilters = $request->input('filters');
        if (empty($encodedFilters)) return $filters;
        $decodedFilters = self::smartDecode($encodedFilters, 'Filters');
        if ($decodedFilters === null) return $filters;
        foreach ($decodedFilters as $filter) {
            if (is_array($filter) && isset($filter['id'], $filter['filterFns']) && array_key_exists('value', $filter)) {
                $filterFnEnum = FilterFnsEnum::tryFrom($filter['filterFns']);
                if ($filterFnEnum) $filters->push(new ColumnFilterData(id: $filter['id'], value: $filter['value'], filterFns: $filterFnEnum));
            }
        }
        return $filters;
    }

    public static function getSortingValuesFromRequest($request): Collection
    {
        $sorting = collect([]);
        $encodedSorting = $request->input('sorting');
        if (empty($encodedSorting)) return $sorting;
        $decodedSorting = self::smartDecode($encodedSorting, 'Sorting');
        if ($decodedSorting === null) return $sorting;
        foreach ($decodedSorting as $sort) {
            if (is_array($sort) && isset($sort['id'], $sort['desc'])) $sorting->push(new ColumnSortData(id: $sort['id'], desc: (bool)$sort['desc']));
        }
        return $sorting;
    }

    public static function getAdvanceFilterFromRequest($request): ?object
    {
        $encodedAdvanceFilter = $request->input('advanceFilter');
        if (empty($encodedAdvanceFilter)) return null;
        $advanceFilter = self::smartDecode($encodedAdvanceFilter, 'AdvanceFilter');
        if ($advanceFilter === null) return null;
        return json_decode(json_encode($advanceFilter), false);
    }

    private static function smartDecode(string $encodedData, string $paramName = 'data'): ?array
    {
        $b64Standard = str_replace(['-', '_'], ['+', '/'], $encodedData);
        $step1 = base64_decode($b64Standard, true);
        if ($step1 === false) return null;
        $jsonAttempt = json_decode($step1, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($jsonAttempt)) return $jsonAttempt;
        $inflateAttempt = @gzinflate($step1);
        if ($inflateAttempt !== false) {
            $jsonAttempt = json_decode($inflateAttempt, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($jsonAttempt)) return $jsonAttempt;
        }
        $gzipAttempt = @gzdecode($step1);
        if ($gzipAttempt !== false) {
            $jsonAttempt = json_decode($gzipAttempt, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($jsonAttempt)) return $jsonAttempt;
        }
        return null;
    }
}

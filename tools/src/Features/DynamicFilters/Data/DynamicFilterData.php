<?php

namespace HMsoft\Tools\Features\DynamicFilters\Data;

use HMsoft\Tools\Features\DynamicFilters\Enums\PaginationFormateEnum;
use Illuminate\Support\Collection;
use Spatie\LaravelData\Data;

/**
 * Normalized input for {@see \HMsoft\Tools\Features\DynamicFilters\Services\AutoFilterAndSortService}.
 *
 * Populated automatically from the HTTP request by ParsesRequests::initializeDynamicFilterData().
 */
class DynamicFilterData extends Data
{
    /**
     * @param string|null $globalFilter Free-text search applied across model search columns
     * @param array<ColumnFilterData>|Collection $filters Column filters (AND between different columns)
     * @param array<ColumnFilterData>|Collection $orFilters Reserved for future OR-filter groups
     * @param object|array|null $advanceFilter Nested filter tree (React Query Builder format)
     * @param array<ColumnSortData>|Collection $sorting Sort directives
     * @param string|null $page Page number or `all`
     * @param string|null $perPage Page size, `all`, or header-driven disable via `pdt: 0`
     * @param callable|null $extraOperation Hook after filters/global search: fn(Builder $q, array $ctx)
     * @param callable|null $beforeOperation Hook before filters: fn(Builder $q, array $ctx)
     * @param callable|null $globaleFilterExtraOperation Hook inside global search OR group
     * @param string|null $columns DB column selection (maps from `?fields=` query param)
     * @param PaginationFormateEnum $paginationFormate Response shape for pagination
     * @param bool $count_only Return `{ data: totalCount }` without rows
     */
    public function __construct(
        public ?string $globalFilter = null,
        public array|Collection $filters = [],
        public array|Collection $orFilters = [],
        public object|array|null $advanceFilter = null,
        public array|Collection $sorting = [],
        public string|null $page = null,
        public string|null $perPage = null,
        public  $extraOperation = null,
        public  $beforeOperation = null,
        public  $globaleFilterExtraOperation = null,
        public ?string $columns = null, // تم تغيير الاسم من fields إلى columns
        public PaginationFormateEnum $paginationFormate = PaginationFormateEnum::normal,
        public bool $count_only = false
    ) {
    }
}

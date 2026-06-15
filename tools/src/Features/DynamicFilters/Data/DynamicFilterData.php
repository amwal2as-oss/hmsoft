<?php

namespace HMsoft\Tools\Features\DynamicFilters\Data;

use HMsoft\Tools\Features\DynamicFilters\Enums\PaginationFormateEnum;
use Illuminate\Support\Collection;
use Spatie\LaravelData\Data;

class DynamicFilterData extends Data
{
    /**
     * dynamic filter data model.
     *
     * @param ?string $globalFilter global filter string
     * @param array<ColumnFilterData> $filters dynamic filtering array
     * @param array<ColumnFilterData> $orFilters
     * @param object|array|null $advanceFilter
     * @param array<ColumnSortData> $sorting
     * @param string $page
     * @param string|null $perPage
     * @param callable $extraOperation
     * @param callable $globaleFilterExtraOperation
     * @param callable $beforeOperation
     * @param PaginationFormateEnum $paginationFormate
     * @param ?string $columns [RENAMED] columns to select from DB (e.g. "id,name,category.slug")
     * @param bool $count_only
     **/
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

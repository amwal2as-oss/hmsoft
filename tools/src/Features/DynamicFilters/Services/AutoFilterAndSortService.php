<?php

namespace HMsoft\Tools\Features\DynamicFilters\Services;

use HMsoft\Tools\Features\DynamicFilters\Services\JoinManager;
use HMsoft\Tools\Features\DynamicFilters\Contracts\AutoFilterable;
use HMsoft\Tools\Features\DynamicFilters\Data\DynamicFilterData;
use HMsoft\Tools\Features\DynamicFilters\Data\ColumnFilterData;
use HMsoft\Tools\Features\DynamicFilters\Data\ColumnSortData;
use HMsoft\Tools\Features\DynamicFilters\Enums\PaginationFormateEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;

class AutoFilterAndSortService
{
    // استدعاء جميع المكونات (Concerns)
    use Concerns\ParsesRequests;
    use Concerns\BuildsSurgicalSelects;
    use Concerns\AppliesGlobalSearch;
    use Concerns\AppliesFilters;
    use Concerns\AppliesSorting;
    use Concerns\PaginatesResults;

    /**
     * @var JoinManager
     */
    private JoinManager $joinManager;

    /**
     * @var array
     */
    private array $customFilterHandlers = [];

    /**
     * @var Model
     */
    public Model $model;

    public function __construct(string|Model $model)
    {
        if (is_string($model)) {
            if (!class_exists($model)) {
                throw new \Exception("Class {$model} does not exist.");
            }

            $modelInstance = new $model();
            if (!$modelInstance instanceof Model) {
                throw new \Exception("Class {$model} must be an instance of Illuminate\Database\Eloquent\Model.");
            }

            $this->model = $modelInstance;
        } elseif (!$model instanceof Model) {
            throw new \Exception("Model must be an instance of Illuminate\Database\Eloquent\Model or a class name.");
        }
    }

    public function setCustomFilterHandlers(array $handlers): self
    {
        $this->customFilterHandlers = $handlers;
        return $this;
    }

    /**
     * الدالة الرئيسية التي تبني الاستعلام.
     */
    public function buildQuery(?DynamicFilterData $dynamicFilterData = null, bool $applySorting = true): Builder
    {
        if (!($this->model instanceof AutoFilterable)) {
            throw new \Exception('Model ' . get_class($this->model) . ' must implement the AutoFilterable interface.');
        }

        if (!$dynamicFilterData) {
            $dynamicFilterData = $this->initializeDynamicFilterData();
        }

        $query = $this->model->query();
        $tableName = $this->model->getTable();
        $mainTableAlias = $tableName;
        $query->from($tableName, $mainTableAlias);

        $this->joinManager = new JoinManager($query, $mainTableAlias);

        // 1. Surgical Select & Eager Load
        if (!$dynamicFilterData->count_only) {
            $this->buildSurgicalSelectAndEagerLoad($query, $dynamicFilterData->columns);
        }

        $extraOperation = $dynamicFilterData->extraOperation;
        $beforeOperation = $dynamicFilterData->beforeOperation;
        $allowedFilters = $this->model->defineFilterableAttributes();
        $allowedSorts = $this->model->defineSortableAttributes();
        $fieldMap = $this->model->defineFieldSelectionMap();

        // 2. تنقية الفلاتر والترتيب المسموح بها
        $dynamicFilterData->filters = collect($dynamicFilterData->filters)
            ->filter(function (ColumnFilterData $filter) use ($allowedFilters, $fieldMap) {
                $mappedId = $fieldMap[$filter->id] ?? $filter->id;
                return in_array($filter->id, $allowedFilters)
                    || in_array($mappedId, $allowedFilters)
                    || isset($this->customFilterHandlers[$filter->id]);
            })
            ->values();

        $dynamicFilterData->sorting = collect($dynamicFilterData->sorting)
            ->filter(function (ColumnSortData $sort) use ($allowedSorts, $fieldMap) {
                $mappedId = $fieldMap[$sort->id] ?? $sort->id;
                return in_array($sort->id, $allowedSorts) || in_array($mappedId, $allowedSorts);
            })
            ->values();

        $pFilterKeys = collect($dynamicFilterData->filters)->groupBy('id');
        $sortingKeys = collect($dynamicFilterData->sorting)->groupBy('id');

        if (isset($beforeOperation)) {
            $beforeOperation($query, [
                'filterKeys' => $pFilterKeys,
                'sortingKeys' => $sortingKeys,
                'mainTableAlias' => $mainTableAlias
            ]);
        }

        // 3. تطبيق Advanced Filters (إن وجدت)
        if (!empty($dynamicFilterData->advanceFilter)) {
            $query->where(function (Builder $builder) use ($dynamicFilterData, $allowedFilters) {
                self::applyAdvancedFilterGroup($builder, $dynamicFilterData->advanceFilter, $allowedFilters);
            });
        }

        // 4. تطبيق الفلاتر العادية
        if ($dynamicFilterData->filters->isNotEmpty()) {
            foreach ($dynamicFilterData->filters as $filter) {
                if (isset($this->customFilterHandlers[$filter->id])) {
                    $handler = $this->customFilterHandlers[$filter->id];
                    $handler($query, $filter);
                    continue;
                }

                if (isset($pFilterKeys[$filter->id])) {
                    self::handelFilterOne($query, collect($pFilterKeys[$filter->id])->toArray(), $filter->id);
                }
            }
        }

        // 5. تطبيق البحث العام (Global Search)
        if (isset($dynamicFilterData->globalFilter) && !empty($dynamicFilterData->globalFilter)) {
            $this->applyGlobalFilter($query, $dynamicFilterData->globalFilter);
        }

        // 6. العمليات الإضافية المحقونة
        if (isset($extraOperation)) {
            $extraOperation($query, [
                'filterKeys' => $pFilterKeys,
                'sortingKeys' => $sortingKeys,
                'globalFilter' => $dynamicFilterData->globalFilter,
                'mainTableAlias'    => $mainTableAlias
            ]);
        }

        // 7. تطبيق الترتيب
        if ($applySorting && !$dynamicFilterData->count_only) {
            self::handelSorting($query, $sortingKeys, $this->joinManager);
        }

        info($query->toRawSql());

        return $query;
    }

    /**
     * معالجة الفلترة وتنفيذ الاستعلام النهائي للإرجاع كبيانات مهيأة.
     */
    public function dynamicFilter(DynamicFilterData $dynamicFilterData): array
    {
        $query = $this->buildQuery($dynamicFilterData, false);
        $sortingKeys = collect($dynamicFilterData->sorting)->groupBy('id');

        if ($dynamicFilterData->count_only) {
            return $this->handleCountOnly($query);
        }

        if (in_array($dynamicFilterData->paginationFormate, [
            PaginationFormateEnum::normal_simple,
            PaginationFormateEnum::separated_simple
        ])) {
            self::handelSorting($query, $sortingKeys, $this->joinManager);
            return $this->handelResultFormate($dynamicFilterData->paginationFormate, $dynamicFilterData->page, $dynamicFilterData->perPage, $query);
        }

        $totalRecords = (clone $query)->toBase()->getCountForPagination();

        self::handelSorting($query, $sortingKeys, $this->joinManager);
        $paginationData = self::handelPageAndPerPage($dynamicFilterData->page, $dynamicFilterData->perPage, $totalRecords);

        return $this->handelResultFormate($dynamicFilterData->paginationFormate, $paginationData['page'], $paginationData['perPage'], $query);
    }

    /**
     * نقطة الدخول الثابتة (Static Entry Point).
     */
    public static function dynamicSearchFromRequest(
        $model,
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
        $count_only = null,
        int $cacheDuration = 0,
        array $customFilterHandlers = []
    ) {
        $request = request();
        $modelInstance = is_string($model) ? new $model : $model;
        $service = new self($model);
        $service->setCustomFilterHandlers($customFilterHandlers);

        $dynamicFilterData = $service->initializeDynamicFilterData(
            request: $request,
            page: $page,
            perPage: $perPage,
            paginationFormate: $paginationFormate,
            filters: $filters,
            sorting: $sorting,
            globalFilter: $globalFilter,
            advanceFilter: $advanceFilter,
            globaleFilterExtraOperation: $globaleFilterExtraOperation,
            extraOperation: $extraOperation,
            beforeOperation: $beforeOperation,
            filterKeyMap: $filterKeyMap,
            sortKeyMap: $sortKeyMap,
            columns: $columns,
            count_only: $count_only
        );

        $executionLogic = function () use ($service, $dynamicFilterData) {
            return $service->dynamicFilter($dynamicFilterData);
        };

        if ($cacheDuration <= 0) return $executionLogic();

        $queryDraft = $service->buildQuery($dynamicFilterData, true);
        $cacheKey = "search_results_" . $modelInstance->getTable() . "_" . md5($queryDraft->toSql() . serialize($queryDraft->getBindings()) . json_encode(['p' => $dynamicFilterData->page, 'pp' => $dynamicFilterData->perPage]));

        return Cache::remember($cacheKey, now()->addMinutes($cacheDuration), $executionLogic);
    }

    public function count(): int
    {
        return $this->model->query()->select([$this->model->getKeyName()])->count();
    }
}

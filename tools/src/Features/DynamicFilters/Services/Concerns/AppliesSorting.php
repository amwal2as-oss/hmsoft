<?php

namespace HMsoft\Tools\Features\DynamicFilters\Services\Concerns;

use HMsoft\Tools\Features\DynamicFilters\Services\JoinManager;
use HMsoft\Tools\Features\DynamicFilters\Contracts\AutoFilterable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

trait AppliesSorting
{
    public static function handelSorting(Builder $query, $sortingKeys, JoinManager $joinManager): void
    {
        $model = $query->getModel();
        $mainTable = $model->getTable();

        static $physicalColumnsCache = [];
        if (!isset($physicalColumnsCache[$mainTable])) {
            $physicalColumnsCache[$mainTable] = \Illuminate\Support\Facades\Schema::getColumnListing($mainTable);
        }
        $physicalColumns = $physicalColumnsCache[$mainTable];

        foreach ($sortingKeys as $columnId => $columnCollection) {
            $sortingValue = $columnCollection[0];
            $sortDirection = $sortingValue->desc ? 'desc' : 'asc';

            if ($model instanceof AutoFilterable) {
                $map = $model->defineFieldSelectionMap();
                $columnId = self::resolveAliasPath($columnId, $map, $model);
            }

            $studlyColumn = Str::studly(str_replace('.', '_', $columnId));
            $scopeName = 'scopeSort' . $studlyColumn;
            $methodName = 'sort' . $studlyColumn;

            if (method_exists($model, $scopeName)) {
                $query->{$methodName}($sortDirection);
                continue;
            }

            if (str_contains($columnId, '.')) {
                $sortExpression = self::resolveAndJoinForSort($columnId, $joinManager, $model);

                if (!empty($sortExpression)) {
                    if (empty($query->getQuery()->columns)) {
                        $query->select("{$mainTable}.*");
                    }
                    $safeSortExpression = collect(explode('.', $sortExpression))
                        ->map(fn($part) => "`" . str_replace('`', '', $part) . "`")
                        ->implode('.');

                    $query->orderByRaw("{$safeSortExpression} IS NULL ASC, {$safeSortExpression} {$sortDirection}");
                }
            } else {
                if (in_array($columnId, $physicalColumns)) {
                    $query->orderBy("{$mainTable}.{$columnId}", $sortDirection);
                } else {
                    $query->orderBy($columnId, $sortDirection);
                }
            }
        }
    }

    private static function resolveAndJoinForSort(string $columnId, JoinManager $joinManager, Model $model): ?string
    {
        if (!str_contains($columnId, '.')) return $joinManager->getMainTableAlias() . '.' . $columnId;

        $parts = explode('.', $columnId);
        $columnName = array_pop($parts);
        $relationPath = implode('.', $parts);

        $relations = method_exists($model, 'defineRelationships') ? $model->defineRelationships() : [];
        if (!isset($relations[$parts[0]])) return null;

        try {
            $currentModel = $model;

            foreach ($parts as $relationName) {
                $eloquentMethod = Str::camel($relationName);

                if (!method_exists($currentModel, $eloquentMethod)) {
                    return null;
                }

                $relationInstance = $currentModel->{$eloquentMethod}();

                if (
                    $relationInstance instanceof \Illuminate\Database\Eloquent\Relations\HasMany ||
                    $relationInstance instanceof \Illuminate\Database\Eloquent\Relations\BelongsToMany ||
                    $relationInstance instanceof \Illuminate\Database\Eloquent\Relations\MorphMany
                ) {
                    return null;
                }
                $currentModel = $relationInstance->getRelated();
            }

            return "{$joinManager->ensureJoin($relationPath)}.{$columnName}";
        } catch (\Exception $e) {
            return null;
        }
    }
}

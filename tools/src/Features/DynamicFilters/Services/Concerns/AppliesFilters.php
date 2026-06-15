<?php

namespace HMsoft\Tools\Features\DynamicFilters\Services\Concerns;

use HMsoft\Tools\Features\DynamicFilters\Services\JoinManager;
use HMsoft\Tools\Features\DynamicFilters\Data\ColumnFilterData;
use HMsoft\Tools\Features\DynamicFilters\Enums\FilterFnsEnum;
use HMsoft\Tools\Interfaces\AutoFilterable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

trait AppliesFilters
{
    private static function applyAdvancedFilterGroup(Builder $query, object $filterGroup, array $allowedFilters): void
    {
        $condition = strtoupper($filterGroup->condition ?? 'AND') === 'OR' ? 'orWhere' : 'where';
        foreach ($filterGroup->rules ?? [] as $rule) {
            if (isset($rule->condition)) {
                $query->{$condition}(function (Builder $subQuery) use ($rule, $allowedFilters) {
                    self::applyAdvancedFilterGroup($subQuery, $rule, $allowedFilters);
                });
            } elseif (isset($rule->id)) {
                if (!in_array($rule->id, $allowedFilters)) continue;
                $filterData = new ColumnFilterData(id: $rule->id, value: $rule->value, filterFns: FilterFnsEnum::from($rule->filterFns));
                $query->{$condition}(function ($q) use ($rule, $filterData) {
                    self::handelFilterOne($q, [$filterData], $rule->id);
                });
            }
        }
    }

    public static function handelFilter(&$query, $filterKeys, $columnPrefix = null)
    {
        $filterKeys->map(function ($filterValueObject, $columnId) use (&$query) {
            self::handelFilterOne($query, $filterValueObject, $columnId);
        });
    }

    public static function handelFilterOne(Builder $query, array $filterObjects, string $columnId, ?Model $model = null, ?JoinManager $joinManager = null): void
    {
        $model = $model ?? $query->getModel();
        $allowedRelations = [];

        static $physicalColumnsCache = [];
        $tableName = $model->getTable();
        if (!isset($physicalColumnsCache[$tableName])) {
            $physicalColumnsCache[$tableName] = \Illuminate\Support\Facades\Schema::getColumnListing($tableName);
        }

        if ($model instanceof AutoFilterable) {
            $map = $model->defineFieldSelectionMap();
            $columnId = self::resolveAliasPath($columnId, $map, $model);
            $allowedRelations = method_exists($model, 'defineRelationships') ? $model->defineRelationships() : [];
        }

        foreach ($filterObjects as $filterData) {
            $value = is_array($filterData) ? $filterData['value'] : $filterData->value;
            $filterFns = is_array($filterData) ? $filterData['filterFns'] : $filterData->filterFns;
            $filterFnsEnum = is_string($filterFns) ? FilterFnsEnum::from($filterFns) : $filterFns;

            $tempFilter = new ColumnFilterData(id: $columnId, value: $value, filterFns: $filterFnsEnum);

            $studlyColumn = \Illuminate\Support\Str::studly(str_replace('.', '_', $columnId));
            $scopeName = 'scopeFilter' . $studlyColumn;
            $methodName = 'filter' . $studlyColumn;

            if (method_exists($model, $scopeName)) {
                $query->{$methodName}($tempFilter);
                continue;
            }

            if (str_contains($columnId, '.')) {
                $extracted = self::extractRelationAndColumn($columnId, $model);
                $relationPath = $extracted['relationPath'];
                $targetColumn = $extracted['targetColumn'];

                if (empty($relationPath)) {
                    $simpleFilter = new ColumnFilterData(id: $targetColumn, value: $value, filterFns: $filterFnsEnum);
                    $simpleFilter->buildQueryWhereStatment($query, $simpleFilter, null, true);
                    continue;
                }

                $rootRelation = explode('.', $relationPath)[0];

                if (isset($allowedRelations[$rootRelation])) {
                    $relationInstance = $model->{\Illuminate\Support\Str::camel($rootRelation)}();

                    if ($joinManager && (
                        $relationInstance instanceof \Illuminate\Database\Eloquent\Relations\BelongsTo ||
                        $relationInstance instanceof \Illuminate\Database\Eloquent\Relations\HasOne
                    ) && !($relationInstance instanceof \Illuminate\Database\Eloquent\Relations\MorphTo)) {
                        try {
                            $tableAlias = $joinManager->ensureJoin($relationPath);
                            $simpleFilter = new ColumnFilterData(id: "{$tableAlias}.{$targetColumn}", value: $value, filterFns: $filterFnsEnum);
                            $simpleFilter->buildQueryWhereStatment($query, $simpleFilter, null, false);
                            continue;
                        } catch (\Exception $e) {
                            // Fallback
                        }
                    }

                    if ($relationInstance instanceof \Illuminate\Database\Eloquent\Relations\MorphTo) {
                        if ($relationPath === $rootRelation) {
                            $query->whereHasMorph($relationPath, '*', function (Builder $q) use ($targetColumn, $value, $filterFnsEnum) {
                                self::handelFilterOne($q, [new ColumnFilterData(id: $targetColumn, value: $value, filterFns: $filterFnsEnum)], $targetColumn);
                            });
                        } else {
                            $remainingPath = substr($relationPath, strlen($rootRelation) + 1);
                            $query->whereHasMorph($rootRelation, '*', function (Builder $q) use ($remainingPath, $targetColumn, $value, $filterFnsEnum) {
                                $q->whereHas($remainingPath, function (Builder $q2) use ($targetColumn, $value, $filterFnsEnum) {
                                    self::handelFilterOne($q2, [new ColumnFilterData(id: $targetColumn, value: $value, filterFns: $filterFnsEnum)], $targetColumn);
                                });
                            });
                        }
                    } else {
                        $query->whereHas($relationPath, function (Builder $q) use ($targetColumn, $value, $filterFnsEnum) {
                            self::handelFilterOne($q, [new ColumnFilterData(id: $targetColumn, value: $value, filterFns: $filterFnsEnum)], $targetColumn);
                        });
                    }
                } else {
                    $rootColumn = explode('.', $columnId)[0];
                    if (in_array($rootColumn, $physicalColumnsCache[$tableName])) {
                        $jsonColumnId = str_replace('.', '->', $columnId);
                        $simpleFilter = new ColumnFilterData(id: $jsonColumnId, value: $value, filterFns: $filterFnsEnum);
                        $simpleFilter->buildQueryWhereStatment($query, $simpleFilter, null, true);
                    }
                }
            } else {
                $simpleFilter = new ColumnFilterData(id: $columnId, value: $value, filterFns: $filterFnsEnum);
                $simpleFilter->buildQueryWhereStatment($query, $simpleFilter, null, true);
            }
        }
    }

    public static function extractRelationAndColumn(string $columnId, Model $model): array
    {
        $parts = explode('.', $columnId);
        $relationPath = [];
        $currentModel = $model;

        foreach ($parts as $index => $part) {
            $eloquentMethod = \Illuminate\Support\Str::camel($part);
            if (method_exists($currentModel, $eloquentMethod)) {
                $relationPath[] = $part;
                $currentModel = $currentModel->{$eloquentMethod}()->getRelated();
            } else {
                $remainingParts = array_slice($parts, $index);
                $column = array_shift($remainingParts);
                if (!empty($remainingParts)) {
                    $column .= '->' . implode('->', $remainingParts);
                }
                return [
                    'relationPath' => implode('.', $relationPath),
                    'targetColumn' => $column
                ];
            }
        }
        $targetColumn = array_pop($relationPath);
        return [
            'relationPath' => implode('.', $relationPath),
            'targetColumn' => $targetColumn
        ];
    }
}

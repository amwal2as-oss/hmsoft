<?php

namespace HMsoft\Tools\Features\DynamicFilters\Services\Concerns;

use Illuminate\Database\Eloquent\Builder;

trait AppliesGlobalSearch
{
    private function applyGlobalFilter(Builder $query, string $globalFilterValue): void
    {
        $mainTableAlias = $this->joinManager->getMainTableAlias();
        $fullTextColumns = method_exists($this->model, 'defineFullTextSearchableAttributes') ? $this->model->defineFullTextSearchableAttributes() : [];
        $fieldMap = method_exists($this->model, 'defineFieldSelectionMap') ? $this->model->defineFieldSelectionMap() : [];

        $safeMatchValue = trim(preg_replace('/[+\-><\(\)~*\"@]+/', ' ', $globalFilterValue));
        $matchValue = empty($safeMatchValue) ? '' : $safeMatchValue . '*';
        $likeValue  = '%' . trim($globalFilterValue) . '%';

        $query->where(function (Builder $builder) use ($matchValue, $likeValue, $mainTableAlias, $fullTextColumns, $fieldMap, $query) {
            $baseAttributes = $this->model->defineGlobalSearchBaseAttributes();
            foreach ($baseAttributes as $col) {
                if ($matchValue !== '' && in_array($col, $fullTextColumns)) {
                    $builder->orWhereRaw("MATCH({$mainTableAlias}.{$col}) AGAINST(? IN BOOLEAN MODE)", [$matchValue]);
                } else {
                    $builder->orWhere($mainTableAlias . '.' . $col, 'LIKE', $likeValue);
                }
            }

            if (method_exists($this->model, 'defineGlobalSearchRelatedAttributes')) {
                $relatedSearchAttrs = $this->model->defineGlobalSearchRelatedAttributes();

                foreach ($relatedSearchAttrs as $relationPath => $columns) {
                    $rootRelation = explode('.', $relationPath)[0];
                    if (!method_exists($this->model, \Illuminate\Support\Str::camel($rootRelation))) continue;

                    $relationInstance = $this->model->{\Illuminate\Support\Str::camel($rootRelation)}();

                    if ($relationInstance instanceof \Illuminate\Database\Eloquent\Relations\MorphTo) {
                        $this->applyMorphGlobalSearch($builder, $relationPath, $rootRelation, $columns, $matchValue, $likeValue, $fullTextColumns);
                    } else {
                        $this->applyStandardWhereHas($builder, $relationPath, $columns, $matchValue, $likeValue, $fullTextColumns);
                    }
                }
            }
        });
    }

    private function applyMorphGlobalSearch(Builder $builder, $relationPath, $rootRelation, $columns, $matchValue, $likeValue, $fullTextColumns): void
    {
        if ($relationPath === $rootRelation) {
            $builder->orWhereHasMorph($relationPath, '*', function ($q) use ($columns, $matchValue, $likeValue, $fullTextColumns, $relationPath) {
                $q->where(function ($subQ) use ($columns, $matchValue, $likeValue, $relationPath, $fullTextColumns) {
                    foreach ($columns as $column) {
                        $subQ->orWhere($column, 'LIKE', $likeValue);
                    }
                });
            });
        } else {
            $remainingPath = substr($relationPath, strlen($rootRelation) + 1);
            $builder->orWhereHasMorph($rootRelation, '*', function ($q) use ($remainingPath, $columns, $matchValue, $likeValue, $fullTextColumns, $relationPath) {
                $q->whereHas($remainingPath, function ($subQ2) use ($columns, $matchValue, $likeValue, $relationPath, $fullTextColumns) {
                    $subQ2->where(function ($subQ) use ($columns, $matchValue, $likeValue, $relationPath, $fullTextColumns) {
                        foreach ($columns as $column) {
                            $subQ->orWhere($column, 'LIKE', $likeValue);
                        }
                    });
                });
            });
        }
    }

    private function applyStandardWhereHas(Builder $builder, string $relationPath, array $columns, string $matchValue, string $likeValue, array $fullTextColumns): void
    {
        $builder->orWhereHas($relationPath, function ($q) use ($columns, $matchValue, $likeValue, $fullTextColumns, $relationPath) {
            $q->where(function ($subQ) use ($columns, $matchValue, $likeValue, $relationPath, $fullTextColumns) {
                foreach ($columns as $column) {
                    $configKey = $relationPath . '.' . $column;
                    if ($matchValue !== '' && in_array($configKey, $fullTextColumns)) {
                        $subQ->orWhereRaw("MATCH({$column}) AGAINST(? IN BOOLEAN MODE)", [$matchValue]);
                    } else {
                        $subQ->orWhere($column, 'LIKE', $likeValue);
                    }
                }
            });
        });
    }
}

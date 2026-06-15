<?php

namespace HMsoft\Tools\Features\DynamicFilters\Services\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

trait BuildsSurgicalSelects
{
    private function buildSurgicalSelectAndEagerLoad(Builder $query, ?string $fields): void
    {
        $mainAlias = $this->joinManager->getMainTableAlias();
        $primaryKey = $this->model->getKeyName();
        $tableName = $this->model->getTable();

        static $physicalColumnsCache = [];
        if (!isset($physicalColumnsCache[$tableName])) {
            $physicalColumnsCache[$tableName] = \Illuminate\Support\Facades\Schema::getColumnListing($tableName);
        }
        $physicalColumns = $physicalColumnsCache[$tableName];

        if (empty($fields)) {
            $query->select("{$mainAlias}.*");
            return;
        }

        $requestedFields = array_filter(explode(',', $fields));
        $map = method_exists($this->model, 'defineFieldSelectionMap') ? $this->model->defineFieldSelectionMap() : [];
        $allowedRelations = method_exists($this->model, 'defineRelationships') ? $this->model->defineRelationships() : [];

        $selectColumns = ["{$mainAlias}.{$primaryKey}"];
        $relationsToLoad = [];

        foreach ($requestedFields as $field) {
            $trimmed = trim($field);
            $dbPath = self::resolveAliasPath($trimmed, $map, $this->model);

            if (method_exists($this->model, 'defineVirtualFieldsDependencies')) {
                $deps = $this->model->defineVirtualFieldsDependencies();
                if (isset($deps[$trimmed])) {
                    $virtualConfig = $deps[$trimmed];
                    if (is_array($virtualConfig) && (isset($virtualConfig['relations']) || isset($virtualConfig['columns']))) {
                        if (isset($virtualConfig['relations'])) {
                            $relationsToLoad = array_merge($relationsToLoad, (array)$virtualConfig['relations']);
                        }
                        if (isset($virtualConfig['columns'])) {
                            foreach ($virtualConfig['columns'] as $col) {
                                $selectColumns[] = "{$mainAlias}.{$col}";
                            }
                        }
                    } else {
                        foreach ((array)$virtualConfig as $depItem) {
                            if (in_array($depItem, $physicalColumns)) {
                                $selectColumns[] = "{$mainAlias}.{$depItem}";
                            } else {
                                $relationsToLoad[] = $depItem;
                            }
                        }
                    }
                }
            }

            if (str_contains($dbPath, '.')) {
                $extracted = $this->parseRelationAndColumn($dbPath);
                $relationPath = $extracted['relationPath'];
                $columnName = $extracted['columnName'];

                if (empty($relationPath)) {
                    $selectColumns[] = "{$mainAlias}.{$columnName} as {$trimmed}";
                    continue;
                }

                $rootRelation = explode('.', $relationPath)[0];

                if (isset($allowedRelations[$rootRelation])) {
                    $this->injectRelationForeignKeys($relationPath, $selectColumns, $mainAlias);
                    $relationInstance = method_exists($this->model, \Illuminate\Support\Str::camel($rootRelation))
                        ? $this->model->{\Illuminate\Support\Str::camel($rootRelation)}()
                        : null;

                    if ($relationInstance && (
                        $relationInstance instanceof \Illuminate\Database\Eloquent\Relations\HasMany ||
                        $relationInstance instanceof \Illuminate\Database\Eloquent\Relations\BelongsToMany ||
                        $relationInstance instanceof \Illuminate\Database\Eloquent\Relations\MorphMany
                    )) {
                        $relationsToLoad[] = $relationPath;
                    } else {
                        try {
                            $tableAlias = $this->joinManager->ensureJoin($relationPath);
                            $selectColumns[] = "{$tableAlias}.{$columnName} as {$trimmed}";
                        } catch (\Exception $e) {
                            $relationsToLoad[] = $relationPath;
                        }
                    }
                }
            } else {
                if (isset($allowedRelations[$dbPath])) {
                    $this->injectRelationForeignKeys($dbPath, $selectColumns, $mainAlias);
                    $relationsToLoad[] = $dbPath;
                } elseif (in_array($dbPath, $physicalColumns)) {
                    $selectColumns[] = "{$mainAlias}.{$dbPath}";
                }
            }
        }

        $query->select(array_unique($selectColumns));

        if (!empty($relationsToLoad)) {
            $query->with(array_unique($relationsToLoad));
        }
    }

    private function parseRelationAndColumn(string $dbPath): array
    {
        $parts = explode('.', $dbPath);
        $relationPath = [];
        $currentModel = $this->model;

        foreach ($parts as $index => $part) {
            $methodName = \Illuminate\Support\Str::camel($part);
            $isRelation = false;

            if (method_exists($currentModel, $methodName)) {
                try {
                    $ref = new \ReflectionMethod($currentModel, $methodName);
                    if ($ref->isPublic() && $ref->getNumberOfRequiredParameters() === 0) {
                        $result = $currentModel->$methodName();
                        if ($result instanceof \Illuminate\Database\Eloquent\Relations\Relation) {
                            $isRelation = true;
                        }
                    }
                } catch (\Exception $e) {
                    $isRelation = false;
                }
            }

            if ($isRelation) {
                $relationPath[] = $part;
                $currentModel = $currentModel->$methodName()->getRelated();
            } else {
                $remainingParts = array_slice($parts, $index);
                $column = array_shift($remainingParts);
                $jsonPath = !empty($remainingParts) ? '->' . implode('->', $remainingParts) : '';

                return [
                    'relationPath' => implode('.', $relationPath),
                    'columnName'   => $column . $jsonPath
                ];
            }
        }

        $columnName = array_pop($relationPath);
        return [
            'relationPath' => implode('.', $relationPath),
            'columnName'   => $columnName
        ];
    }

    private function injectRelationForeignKeys(string $relationPath, array &$selectColumns, string $mainTableAlias): void
    {
        $rootRelation = explode('.', $relationPath)[0];
        $methodName = \Illuminate\Support\Str::camel($rootRelation);

        if (method_exists($this->model, $methodName)) {
            $relationInstance = $this->model->{$methodName}();
            if ($relationInstance instanceof \Illuminate\Database\Eloquent\Relations\MorphTo) {
                $selectColumns[] = $mainTableAlias . '.' . $relationInstance->getForeignKeyName();
                $selectColumns[] = $mainTableAlias . '.' . $relationInstance->getMorphType();
            } elseif ($relationInstance instanceof \Illuminate\Database\Eloquent\Relations\BelongsTo) {
                $selectColumns[] = $mainTableAlias . '.' . $relationInstance->getForeignKeyName();
            }
        }
    }

    public static function resolveAliasPath(string $field, array $map, ?Model $model = null): string
    {
        if (isset($map[$field])) {
            return $map[$field];
        }

        if (str_contains($field, '.')) {
            $parts = explode('.', $field);
            $root = $parts[0];
            $mappedRoot = $root;

            if (isset($map[$root])) {
                $mappedRoot = str_replace('.*', '', $map[$root]);
            }

            if ($model) {
                $relationMethod = \Illuminate\Support\Str::camel($mappedRoot);

                if (method_exists($model, $relationMethod)) {
                    try {
                        $relationInstance = $model->{$relationMethod}();

                        if ($relationInstance instanceof \Illuminate\Database\Eloquent\Relations\Relation) {
                            $relatedModel = $relationInstance->getRelated();
                            array_shift($parts);
                            $remainingPath = implode('.', $parts);

                            if (method_exists($relatedModel, 'defineFieldSelectionMap')) {
                                $relatedMap = $relatedModel->defineFieldSelectionMap();
                                $resolvedRemaining = self::resolveAliasPath($remainingPath, $relatedMap, $relatedModel);
                                return $mappedRoot . '.' . $resolvedRemaining;
                            } else {
                                return $mappedRoot . '.' . $remainingPath;
                            }
                        }
                    } catch (\Exception $e) {
                        // Fallback
                    }
                }
            }

            if (isset($map[$root])) {
                array_shift($parts);
                return $mappedRoot . '.' . implode('.', $parts);
            }
        }

        return $field;
    }
}

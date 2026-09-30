<?php

namespace HMsoft\Tools\Features\SortNumber\Traits;

use HMsoft\Tools\Features\SortNumber\Contracts\Sortable;
use HMsoft\Tools\Features\SortNumber\Support\SortNumberShifter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

trait HasSortNumber
{
    /**
     * Boot the sorting trait logic. Hooks natively into Eloquent creating and saving events.
     */
    public static function bootHasSortNumber(): void
    {
        static::creating(function (Model $model) {
            /** @var Sortable|Model $model */
            $column = $model->getSortNumberColumnName();

            // Auto calculate the incremental sort index if the passed value is null or 0
            if (is_null($model->getAttribute($column)) || (int)$model->getAttribute($column) === 0) {
                $model->setAttribute($column, $model->calculateNextSortNumber($column));
            }
        });

        static::saving(function (Model $model) {
            SortNumberShifter::shiftCollisions($model);
        });
    }

    /**
     * Default Contextual Scope Resolver.
     * Uses SORT_NUMBER_CONTEXT / $sortNumberContext when set.
     */
    public function scopeSortByContext(Builder $query): Builder
    {
        foreach ($this->getSortNumberContextColumns() as $column) {
            $value = $this->getAttribute($column);
            $query = $value === null
                ? $query->whereNull($column)
                : $query->where($column, $value);
        }

        return $query;
    }

    /**
     * @return list<string>
     */
    public function getSortNumberContextColumns(): array
    {
        if (defined('static::SORT_NUMBER_CONTEXT')) {
            return (array) static::SORT_NUMBER_CONTEXT;
        }

        if (property_exists($this, 'sortNumberContext')) {
            return (array) $this->sortNumberContext;
        }

        return [];
    }

    /**
     * Calculate the next sequential sort order number based on the evaluated context scope.
     */
    protected function calculateNextSortNumber(string $column): int
    {
        $query = static::query();

        // Dynamically invoke the enforced contract scoping method
        $query = $this->scopeSortByContext($query);

        return ((int) $query->max($column)) + 1;
    }

    /**
     * Fallback lookup accessor for the sorting column name.
     */
    public function getSortNumberColumnName(): string
    {
        if (defined('static::SORT_COLUMN')) {
            return static::SORT_COLUMN;
        }

        return property_exists($this, 'sortNumberColumn') ? $this->sortNumberColumn : 'sort_number';
    }
}

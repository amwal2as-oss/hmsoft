<?php

namespace HMsoft\Tools\Features\SortNumber\Traits;

use HMsoft\Tools\Features\SortNumber\Contracts\Sortable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

trait HasSortNumber
{
    /**
     * Boot the sorting trait logic. Hooks natively into Eloquent creating event.
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
    }

    /**
     * Default Contextual Scope Resolver.
     * Automatically sniffs for common architectural partitioning fields like 'scope'.
     */
    public function scopeSortByContext(Builder $query): Builder
    {
        return $query;
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

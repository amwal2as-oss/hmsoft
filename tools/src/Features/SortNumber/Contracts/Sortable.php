<?php

namespace HMsoft\Tools\Features\SortNumber\Contracts;

use Illuminate\Database\Eloquent\Builder;

interface Sortable
{
    /**
     * Get the database column name responsible for storing the sorting values.
     *
     * @return string
     */
    public function getSortNumberColumnName(): string;

    /**
     * Apply a specific dynamic contextual query grouping/scoping before calculating the max index.
     *
     * @param Builder $query
     * @return Builder
     */
    public function scopeSortByContext(Builder $query): Builder;
}

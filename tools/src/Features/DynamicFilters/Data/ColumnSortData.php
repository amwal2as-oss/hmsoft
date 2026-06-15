<?php

namespace HMsoft\Tools\Features\DynamicFilters\Data;

use Spatie\LaravelData\Data;
use Illuminate\Database\Eloquent\Builder;

class ColumnSortData extends Data
{
    /**
     * @param string $id column name.
     * @param bool $desc is desc mode.
     */
    public function __construct(
        public string |null $id,
        public bool |null $desc,
    ) {}

    public function buildQuery(Builder &$queryBuilder): Builder
    {
        if (empty($this->id)) {
            return $queryBuilder;
        }

        $orderMode = $this->desc ? 'desc' : 'asc';

        // إذا كان العمود يحتوي على نقطة (جدول.عمود) أو كان عموداً عادياً
        return $queryBuilder->orderBy($this->id, $orderMode);
    }
}

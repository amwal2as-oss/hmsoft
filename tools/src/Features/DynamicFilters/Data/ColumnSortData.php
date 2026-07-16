<?php

namespace HMsoft\Tools\Features\DynamicFilters\Data;

use Spatie\LaravelData\Data;
use Illuminate\Database\Eloquent\Builder;

/**
 * Single sort directive. Relation sorts are resolved by AppliesSorting via JoinManager.
 */
class ColumnSortData extends Data
{
    /**
     * @param string|null $id Column or dot-path (e.g. `translation.title`)
     * @param bool|null $desc True for DESC, false for ASC
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

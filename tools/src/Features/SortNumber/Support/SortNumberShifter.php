<?php

namespace HMsoft\Tools\Features\SortNumber\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * When a model is saved onto an occupied sort_number, bump every later
 * row in the same sort context by +1 (query builder — no model events).
 */
final class SortNumberShifter
{
    public static function shiftCollisions(Model $model): void
    {
        if (! filter_var(config('sort_number.shift_on_collision', true), FILTER_VALIDATE_BOOLEAN)) {
            return;
        }

        if (! method_exists($model, 'getSortNumberColumnName')) {
            return;
        }

        $column = $model->getSortNumberColumnName();
        if (! $model->isDirty($column)) {
            return;
        }

        $value = $model->getAttribute($column);
        if ($value === null || (int) $value < 1) {
            return;
        }

        $value = (int) $value;

        if (! self::contextQuery($model)->where($column, $value)->exists()) {
            return;
        }

        self::contextQuery($model)->where($column, '>=', $value)->increment($column);
    }

    protected static function contextQuery(Model $model): Builder
    {
        $query = $model->newModelQuery();

        if (in_array(SoftDeletes::class, class_uses_recursive($model), true)) {
            $query->whereNull($model->getDeletedAtColumn());
        }

        if (method_exists($model, 'scopeSortByContext')) {
            $query = $model->scopeSortByContext($query);
        }

        if ($model->exists) {
            $query->whereKeyNot($model->getKey());
        }

        return $query;
    }
}

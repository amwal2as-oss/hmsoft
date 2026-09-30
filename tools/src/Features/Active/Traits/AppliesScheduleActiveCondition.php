<?php

namespace HMsoft\Tools\Features\Active\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

/**
 * Extends HasActiveScope with a schedule window via extraActiveCondition().
 */
trait AppliesScheduleActiveCondition
{
    use HasActiveScope {
        HasActiveScope::extraActiveCondition as private baseExtraActiveCondition;
    }

    protected function extraActiveCondition(Builder $builder): void
    {
        static::applyScheduleCondition($builder);
    }

    public function scopeActiveInPeriod(Builder $query): Builder
    {
        return $query->active();
    }

    public function isInActiveSchedulePeriod(?Carbon $now = null): bool
    {
        $now ??= now();
        $fromColumn = config('active.schedule_from_column', 'from_time');
        $toColumn = config('active.schedule_to_column', 'to_time');

        $from = $this->getAttribute($fromColumn);
        $to = $this->getAttribute($toColumn);

        if ($from && $now->lt($from)) {
            return false;
        }

        if ($to && $now->gt($to)) {
            return false;
        }

        return true;
    }

    public function isInActiveInPeriod(): bool
    {
        return $this->isInActiveSchedulePeriod();
    }

    public static function applyScheduleCondition(Builder $query, ?object $model = null): Builder
    {
        $model ??= $query->getModel();
        $now = now();
        $fromColumn = config('active.schedule_from_column', 'from_time');
        $toColumn = config('active.schedule_to_column', 'to_time');

        return $query->where(function ($outer) use ($model, $now, $fromColumn, $toColumn) {
            $outer->where(function ($fromGroup) use ($model, $now, $fromColumn) {
                $fromGroup->whereNull($model->qualifyColumn($fromColumn))
                    ->orWhere($model->qualifyColumn($fromColumn), '<=', $now);
            })->where(function ($toGroup) use ($model, $now, $toColumn) {
                $toGroup->whereNull($model->qualifyColumn($toColumn))
                    ->orWhere($model->qualifyColumn($toColumn), '>=', $now);
            });
        });
    }
}

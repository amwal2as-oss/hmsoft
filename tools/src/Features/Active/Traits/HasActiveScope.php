<?php

namespace HMsoft\Tools\Features\Active\Traits;

use HMsoft\Tools\Features\Active\Contracts\Activable;
use Illuminate\Database\Eloquent\Builder;

/**
 * Adds automatic filtering for active records (`is_active = 1`) plus optional extra rules.
 *
 * ## Quick start
 *
 * ```php
 * class Article extends Model implements Activable
 * {
 *     use HasActiveScope;
 * }
 * ```
 *
 * ## Execution order (global scope)
 *
 * 1. Check `shouldApplyActiveScope()` and optional `resolveActiveScopeCondition()` on the model.
 * 2. Apply `WHERE {active_column} = true`.
 * 3. Call `extraActiveCondition($builder)` — override in your model for custom rules.
 *
 * The same step 3 runs when using the local `active()` scope.
 *
 * @see \HMsoft\Tools\Features\Active\Contracts\Activable
 * @see ../README.md
 */
trait HasActiveScope
{
    /**
     * Global callable to control scope application for all models using this trait.
     *
     * @var callable(): bool|null
     */
    public static $applyScopeCondition = null;

    protected static function bootHasActiveScope()
    {
        static::addGlobalScope('active_scope', function (Builder $builder) {
            $model = $builder->getModel();

            $shouldApply = true;

            if (method_exists($model, 'resolveActiveScopeCondition')) {
                $shouldApply = $model->resolveActiveScopeCondition();
            }

            if (
                $model instanceof Activable &&
                $model->shouldApplyActiveScope() &&
                $shouldApply
            ) {
                $builder->where(
                    $model->qualifyColumn($model->getActiveColumnName()),
                    $model->getActiveColumnValue()
                );

                $model->extraActiveCondition($builder);
            }
        });
    }

    /**
     * Extra visibility rules applied together with the active column check.
     *
     * Override in any model. Keep logic generic (dates, relations, status, etc.).
     * The application layer can call shared helpers from here.
     *
     * ```php
     * protected function extraActiveCondition(Builder $builder): void
     * {
     *     $builder->where('published_at', '<=', now());
     *     // or: $builder->whereHas('category');
     * }
     * ```
     */
    protected function extraActiveCondition(Builder $builder): void
    {
    }

    /** @inheritdoc */
    public function getActiveColumnName(): string
    {
        return defined('static::ACTIVE_COLUMN') ? static::ACTIVE_COLUMN : 'is_active';
    }

    /**
     * Value used with getActiveColumnName() in the active scope.
     * Override when the active flag is not a boolean (e.g. status = published).
     */
    /** @inheritdoc */
    public function getActiveColumnValue(): mixed
    {
        return true;
    }

    /** @inheritdoc */
    public function shouldApplyActiveScope(): bool
    {
        if (is_callable(self::$applyScopeCondition)) {
            return call_user_func(self::$applyScopeCondition);
        }

        return true;
    }

    /**
     * Local scope: active column + extraActiveCondition().
     * Use with `withoutGlobalScope('active_scope')` when the global scope is disabled.
     */
    public function scopeActive(Builder $query)
    {
        $query->where($this->qualifyColumn($this->getActiveColumnName()), $this->getActiveColumnValue());
        $this->extraActiveCondition($query);

        return $query;
    }

    /** Local scope: opposite of getActiveColumnValue() for boolean columns only. */
    public function scopeInactive(Builder $query)
    {
        return $query->where($this->qualifyColumn($this->getActiveColumnName()), false);
    }
}

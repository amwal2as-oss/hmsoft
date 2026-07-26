<?php

namespace HMsoft\Tools\Features\Active\Contracts;

/**
 * Contract for models that support automatic active/inactive filtering.
 *
 * Implement this interface together with {@see \HMsoft\Tools\Features\Active\Traits\HasActiveScope}
 * to enable a global scope that hides inactive records from public queries.
 *
 * ## How it works
 *
 * 1. **Global scope** (`active_scope`) adds `WHERE is_active = 1` (or your custom column).
 * 2. **Optional extras** — override `extraActiveCondition()` in the model to append
 *    more rules (related records, dates, permissions, etc.).
 * 3. **Who sees everything** — disable the scope for admins via `resolveActiveScopeCondition()`
 *    or `shouldApplyActiveScope()` (see README).
 *
 * ## Required methods
 *
 * Both methods are implemented by default inside `HasActiveScope`.
 * You only override them when you need custom behaviour.
 *
 * @see \HMsoft\Tools\Features\Active\Traits\HasActiveScope
 * @see ../README.md
 */
interface Activable
{
    /**
     * Database column used for the active flag.
     *
     * Default in `HasActiveScope`: `is_active`.
     * Override or define `ACTIVE_COLUMN` constant on the model.
     *
     * @return string Column name without table prefix
     */
    public function getActiveColumnName(): string;

    /**
     * Value matched against {@see getActiveColumnName()} in the active scope.
     *
     * Default in `HasActiveScope`: `true` (boolean is_active).
     * Override for enum/string columns (e.g. News uses status = published).
     *
     * @return mixed
     */
    public function getActiveColumnValue(): mixed;

    /**
     * Whether the global active scope should run for the current request.
     *
     * Default in `HasActiveScope`: `true`.
     * Return `false` to skip filtering (e.g. admin panel sees all rows).
     *
     * You can also set `HasActiveScope::$applyScopeCondition` globally.
     *
     * @return bool
     */
    public function shouldApplyActiveScope(): bool;
}

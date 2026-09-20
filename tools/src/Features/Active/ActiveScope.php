<?php

namespace HMsoft\Tools\Features\Active;

/**
 * Shared holder for app-wide active scope application rules.
 *
 * Set once in your service provider so every model using {@see Traits\HasActiveScope}
 * picks up the same condition (including vendor models that do not set per-class
 * {@see Traits\HasActiveScope::$applyScopeCondition}).
 *
 * ```php
 * ActiveScope::$applyCondition = fn () => ! request()->isDashboardAndAdmin();
 * ```
 *
 * Per-model {@see Traits\HasActiveScope::$applyScopeCondition} and
 * {@see Contracts\Activable::shouldApplyActiveScope()} overrides take precedence.
 */
final class ActiveScope
{
    /**
     * @var (callable(): bool)|null
     */
    public static $applyCondition = null;
}

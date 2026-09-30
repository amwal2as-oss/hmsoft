# Active Feature

Automatic Eloquent filtering for active/inactive records (`is_active` by default), with an extension point for extra visibility rules.

## Directory structure

```text
HMsoft/Tools/Features/Active/
├── ActiveScope.php            # App-wide apply condition holder
├── Contracts/
│   └── Activable.php          # Model contract
├── Traits/
│   ├── HasActiveScope.php     # Global scope + local scopes
│   └── AppliesScheduleActiveCondition.php  # Optional from/to window
├── Providers/
│   └── ActiveServiceProvider.php
├── config/
│   └── active.php
└── README.md
```

## Installation & usage

Your model must implement `Activable` and use `HasActiveScope`:

```php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use HMsoft\Tools\Features\Active\Contracts\Activable;
use HMsoft\Tools\Features\Active\Traits\HasActiveScope;

class Article extends Model implements Activable
{
    use HasActiveScope;

    protected $fillable = ['title', 'is_active'];
}
```

By default the active column is `is_active`.

## How the global scope works

When the scope runs (typically for public/guest users):

```text
SELECT * FROM articles
WHERE articles.is_active = 1
  -- plus anything you add in extraActiveCondition()
```

Steps inside `HasActiveScope`:

| Step | What happens |
|------|----------------|
| 1 | `shouldApplyActiveScope()` → skip entirely if `false` |
| 2 | Optional `resolveActiveScopeCondition()` on the model (app-level, e.g. admin bypass) |
| 3 | `WHERE {active_column} = {active_value}` (default: `is_active = 1`) |
| 4 | `extraActiveCondition($builder)` — your custom rules |

## Customization

### Change the column name

Define a constant:

```php
class Article extends Model implements Activable
{
    use HasActiveScope;

    public const ACTIVE_COLUMN = 'status';
}
```

Or override `getActiveColumnName()`.

### Non-boolean active column (e.g. status enum)

```php
public function getActiveColumnName(): string
{
    return 'status';
}

public function getActiveColumnValue(): mixed
{
    return NewsStatusEnum::PUBLISHED->value;
}
```

### Disable scope for admins

**Per model** — override `shouldApplyActiveScope()`:

```php
public function shouldApplyActiveScope(): bool
{
    return ! auth()->user()?->isAdmin();
}
```

**App-wide** — set a callable on `ActiveScope` (applies to every model using the trait, including vendor EAV models):

```php
use HMsoft\Tools\Features\Active\ActiveScope;

ActiveScope::$applyCondition = fn () => ! auth()->user()?->isAdmin();
```

**Per model class** — `HasActiveScope::$applyScopeCondition` on that model still overrides the app-wide hook.

**Per request** — add `resolveActiveScopeCondition()` on the model (common pattern in this project via `ApplyActiveScopeForNotAdmin`).

### Extra active conditions (`extraActiveCondition`)

Add any query constraint on top of `is_active`. Runs in both the global scope and `active()` local scope.

```php
use Illuminate\Database\Eloquent\Builder;

protected function extraActiveCondition(Builder $builder): void
{
    // Example: must belong to an active category
    $builder->whereHas('category');

    // Example: only published
    // $builder->where('published_at', '<=', now());
}
```

Keep HMsoft generic — put reusable helpers (e.g. hierarchical categories) in your application layer and call them from `extraActiveCondition()`.

### Schedule window (`AppliesScheduleActiveCondition`)

Optional trait: `is_active` **plus** `from_time` / `to_time` (null = open-ended). Use it instead of `HasActiveScope` alone (it already includes that trait).

```php
use HMsoft\Tools\Features\Active\Contracts\Activable;
use HMsoft\Tools\Features\Active\Traits\AppliesScheduleActiveCondition;

class Offer extends Model implements Activable
{
    use AppliesScheduleActiveCondition;
}
```

Helpers: `isInActiveSchedulePeriod()`, alias `isInActiveInPeriod()`, `scopeActiveInPeriod()`.

Column names (publish `hmsoft-active-config`):

```
php artisan vendor:publish --tag=hmsoft-active-config
```

`active.schedule_from_column` / `active.schedule_to_column` (defaults `from_time` / `to_time`).

---

## Local scopes

| Scope | Behaviour |
|-------|-----------|
| `active()` | `is_active = 1` **+** `extraActiveCondition()` |
| `inactive()` | `is_active = 0` only (no extras) |

When the global scope is disabled:

```php
Article::withoutGlobalScope('active_scope')->active()->get();
Article::withoutGlobalScope('active_scope')->inactive()->get();
```

## Activable contract

| Method | Purpose | Default in trait |
|--------|---------|------------------|
| `getActiveColumnName(): string` | Active flag column | `'is_active'` |
| `shouldApplyActiveScope(): bool` | Run global scope? | `true` |

See `Contracts/Activable.php` for full PHPDoc.

## Related files in this project

- `App\Traits\ApplyActiveScopeForNotAdmin` — sets `resolveActiveScopeCondition()` so admins bypass the scope.
- `App\Support\ActiveScopeConstraints` — optional helpers for category trees (called from `extraActiveCondition()`).

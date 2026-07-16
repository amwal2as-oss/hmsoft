# DynamicFilters — Analysis, Bugs & Improvements

This document summarizes the pre-documentation code review performed on the DynamicFilters feature.

---

## Feature overview

DynamicFilters solves two problems:

1. **Query building (Auto Filter)** — The CMS/frontend sends structured filter/sort/search params in the URL. `AutoFilterAndSortService` converts them into a secure, whitelisted Eloquent query.

2. **Response shaping (BaseData)** — After data is loaded, `BaseData::filterableCollect()` trims the JSON using `?fields=` (whitelist) and `?except=` (blacklist), including nested relation keys.

---

## Architecture strengths

- **Security whitelist** — Only columns listed in `defineFilterableAttributes()` / `defineSortableAttributes()` are applied.
- **Relation-aware joins** — `JoinManager` handles BelongsTo/HasOne joins for filter/sort without N+1 on simple relations.
- **Custom scopes** — Models can define `scopeFilter{StudlyColumn}` / `scopeSort{StudlyColumn}` for complex logic.
- **Advanced filters** — Nested AND/OR groups via `advanceFilter` (query builder UI).
- **Surgical SELECT** — `?fields=` on the request reduces DB columns loaded (distinct from JSON pruning on the response DTO).
- **Encoding** — Filters/sorting are base64 (optionally gzip) JSON for clean URLs.

---

## Bugs found & fixed

### 1. Constructor did not accept Model instances (Critical)

**File:** `AutoFilterAndSortService.php`

When passing an Eloquent model instance instead of a class string, `$this->model` was never assigned.

**Fix:** Added `elseif ($model instanceof Model) { $this->model = $model; }`.

---

### 2. `notEquals` used `NOT LIKE` instead of `!=`

**File:** `ColumnFilterData.php`

`notEquals` behaved like a string wildcard mismatch, not equality.

**Fix:** Uses `!=` operator. `weakEquals` aliases to the same behavior.

---

### 3. Missing filter operator implementations

**File:** `ColumnFilterData.php`

Enum defined `isNull`, `notIsNull`, `equalsString`, `weakEquals` but switch had no cases.

**Fix:** Implemented all four operators.

---

### 4. SQL injection risk in `includesStringSensitive`

**File:** `ColumnFilterData.php`

Value was concatenated into raw SQL.

**Fix:** Uses parameterized `whereRaw("UPPER({$columnName}) LIKE ?", [...])`.

---

### 5. `except` query param incomplete in BaseData

**File:** `BaseData.php`

- Deep pruning only ran when `fields` was set; `except` alone had no effect on nested data.
- Single-object `toArray()` ignored `except` for nested structures.

**Fix:** Rewrote pruning pipeline:
- `fields` → whitelist via `applyDeepArrayInclude()`
- `except` → blacklist via `applyDeepArrayExclude()`
- Both supported on paginated lists, plain arrays, and single objects.

---

### 6. Debug SQL logged on every request

**File:** `AutoFilterAndSortService.php`

`info($query->toRawSql())` ran unconditionally.

**Fix:** Logs only when `config('hmsoft-tools.log_dynamic_filter_queries')` is true and `app.debug` is enabled.

---

### 7. `globaleFilterExtraOperation` never invoked

**File:** `AppliesGlobalSearch.php`

Callback was accepted in `DynamicFilterData` but never called.

**Fix:** Passed to `applyGlobalFilter()` and invoked inside the OR search group.

---

### 8. Advanced filters threw on invalid `filterFns`

**File:** `AppliesFilters.php`

Used `FilterFnsEnum::from()` which throws on unknown values.

**Fix:** Uses `tryFrom()` and skips invalid rules.

---

## Recommendations (not yet implemented)

These are design suggestions for future work:

| Item | Reason |
|------|--------|
| Add unit tests for BaseData `fields`/`except` edge cases | Prevent regressions on nested list pruning |
| Group BY on joined queries | Duplicate rows possible with HasMany joins used in filters |
| Wire `ToolsDefaultSorts` / `ToolsDefaultFilters` | Interface methods exist but ParsesRequests only uses `cmsDefault*` — **documented in 05-COMPLETE-API-REFERENCE** |
| Document `fields` dual meaning | Same query key used for DB SELECT (service) and JSON prune (BaseData) — see architecture doc |
| `orFilters` in DynamicFilterData | Defined but not processed by service — reserved — **documented in 05-COMPLETE-API-REFERENCE** |

---

## Verification checklist

After integration, verify these scenarios:

- [ ] Flat filter: `equals`, `contains`, `in`, `between`, date filters
- [ ] Relation filter: `translation.title`, `category.id`
- [ ] Advanced filter: nested AND/OR groups
- [ ] Global search on base + related columns
- [ ] Sort on base column and joinable relation column
- [ ] Pagination formats: `separated`, `normal`, `none`, `count_only`
- [ ] Response `?fields=id,title,category.name`
- [ ] Response `?except=translations,password`
- [ ] Combined `fields` + `except`

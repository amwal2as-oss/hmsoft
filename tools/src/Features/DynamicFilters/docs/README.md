# DynamicFilters — Documentation Index

> **GitHub main doc:** [../README.md](../README.md) — complete feature reference with setup, usage, and all use-case examples.

The **DynamicFilters** feature provides two complementary capabilities:

| Capability | Purpose | Main classes |
|------------|---------|--------------|
| **Auto Filter & Sort** | Build SQL from URL query params (filters, sort, search, pagination) | `AutoFilterAndSortService`, `IsAutoFilterable` |
| **Response Field Pruning** | Shrink JSON responses via `fields` / `except` query params | `BaseData` |

---

## Two parts — required setup

| | Auto Filter | fields / except |
|---|-------------|-----------------|
| Model + `IsAutoFilterable` | ✅ Required | Not needed |
| `GetListAction` | ✅ Required | Not needed |
| `{Feature}Data extends BaseData` | Not needed | ✅ **Required** |
| `filterableCollect()` in controller | Not needed | ✅ **Required** (list) |

See [04-SETUP-CHECKLIST.md](./04-SETUP-CHECKLIST.md) for the full printable checklist.

---

## Documentation files

| File | Audience | Description |
|------|----------|-------------|
| [../README.md](../README.md) | **Everyone** | **Main GitHub doc** — setup, usage, all use cases |
| [00-ANALYSIS-AND-FIXES.md](./00-ANALYSIS-AND-FIXES.md) | Backend devs | Architecture review, bugs found, improvements |
| [01-BACKEND-ARCHITECTURE.md](./01-BACKEND-ARCHITECTURE.md) | Backend devs | How the code works — workflow, classes, security |
| [02-BACKEND-INTEGRATION.md](./02-BACKEND-INTEGRATION.md) | Backend devs | Step-by-step backend integration |
| [03-FRONTEND-GUIDE.md](./03-FRONTEND-GUIDE.md) | Frontend devs | URL params, encoding, field selection |
| [04-SETUP-CHECKLIST.md](./04-SETUP-CHECKLIST.md) | Backend devs | Printable setup checklist |
| [05-COMPLETE-API-REFERENCE.md](./05-COMPLETE-API-REFERENCE.md) | Backend devs | Model hooks, relation matrix, edge cases, alternative APIs |

---

## Quick start

**Part A — Auto Filter (list query from URL):**

```php
return AutoFilterAndSortService::dynamicSearchFromRequest(
    model: Blog::class,
    extraOperation: fn ($q) => $q->with(Blog::DEFAULT_INCLUDES),
);
```

**Part B — fields / except (requires Data class):**

```php
// 1. BlogData must extend BaseData
// 2. Controller must call:
$result['data'] = BlogData::filterableCollect($result['data']);
```

**Frontend — typical list request:**

```http
GET /api/blogs?page=1&perPage=10&globalFilter=hello
    &filters=<base64-json>&sorting=<base64-json>
    &fields=id,title,category.name&except=translations
```

See [../README.md](../README.md) for complete examples.

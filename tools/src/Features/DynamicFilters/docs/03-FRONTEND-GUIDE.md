# DynamicFilters — Frontend Integration Guide

How to call API endpoints that use Auto Filter (DynamicFilters) from Vue/React CMS tables.

> **Full reference:** [../README.md](../README.md) | **Backend setup:** [02-BACKEND-INTEGRATION.md](./02-BACKEND-INTEGRATION.md)

---

## Backend requirement for `fields` / `except`

These params only work if the backend developer has:

1. Created a `{Feature}Data` class extending `BaseData`
2. Called `{Feature}Data::filterableCollect($result['data'])` in the list controller

If `fields` / `except` seem ignored, ask the backend team to verify Part B setup in [04-SETUP-CHECKLIST.md](./04-SETUP-CHECKLIST.md).

---

## Overview

The frontend sends list parameters as **query string** values. Filters and sorting are **JSON arrays encoded in base64** (gzip optional). Global search and pagination are plain strings.

---

## Base URL parameters

| Param | Required | Example | Description |
|-------|----------|---------|-------------|
| `page` | No | `1` | Page number (1-based) |
| `perPage` | No | `10` | Rows per page |
| `globalFilter` | No | `search term` | Search box value |
| `filters` | No | `(base64)` | Column filters array |
| `sorting` | No | `(base64)` | Sort state array |
| `advanceFilter` | No | `(base64)` | Query builder tree |
| `paginationFormate` | No | `separated` | Default in backend: `separated` |
| `fields` | No | `id,title,category.name` | Limit response + DB columns |
| `except` | No | `translations,pdf_path` | Exclude JSON keys |
| `count_only` | No | `1` | Get total count only |

### Disable pagination (fetch all)

Send header:

```
pdt: 0
```

Or set `perPage=all` — backend switches to `paginationFormate=none`.

---

## Encoding filters and sorting

### 1. Build JSON

**Filters:**

```json
[
  {
    "id": "is_active",
    "value": true,
    "filterFns": "equals"
  },
  {
    "id": "translation.title",
    "value": "decree",
    "filterFns": "contains"
  },
  {
    "id": "date",
    "value": ["2024-01-01", "2024-12-31"],
    "filterFns": "betweenInclusive"
  }
]
```

**Sorting:**

```json
[
  { "id": "date", "desc": true },
  { "id": "translation.title", "desc": false }
]
```

### 2. Encode to base64

```typescript
function encodeDynamicParam(data: unknown): string {
  const json = JSON.stringify(data);
  // Standard base64 — backend also accepts URL-safe variants on decode
  return btoa(unescape(encodeURIComponent(json)));
}
```

For large payloads, gzip first (backend auto-detects):

```typescript
import pako from 'pako';

function encodeDynamicParamGzip(data: unknown): string {
  const json = JSON.stringify(data);
  const compressed = pako.deflate(json);
  const binary = String.fromCharCode(...compressed);
  return btoa(binary);
}
```

### 3. Append to URL

```typescript
const params = new URLSearchParams({
  page: '1',
  perPage: '10',
  globalFilter: searchTerm,
  paginationFormate: 'separated',
});

if (filters.length) {
  params.set('filters', encodeDynamicParam(filters));
}
if (sorting.length) {
  params.set('sorting', encodeDynamicParam(sorting));
}
if (selectedFields.length) {
  params.set('fields', selectedFields.join(','));
}

const response = await fetch(`/api/decrees?${params}`);
```

---

## Filter operators (`filterFns`)

Use exact enum string values expected by the backend:

| UI label | `filterFns` value |
|----------|-------------------|
| Equals | `equals` |
| Not equals | `notEquals` |
| Contains | `contains` |
| Starts with | `startsWith` |
| Ends with | `endsWith` |
| Is empty | `empty` |
| Is not empty | `notEmpty` |
| Is null | `isNull` |
| Is not null | `notIsNull` |
| In list | `in` |
| Not in list | `notIn` |
| Between | `between` |
| Between (inclusive) | `betweenInclusive` |
| Greater than | `greaterThan` |
| Less than | `lessThan` |
| Same day | `dayEquals` |
| Same month | `monthEquals` |
| Same year | `yearEquals` |

---

## Advanced filter (query builder)

For Material React Table advanced filters or custom query builder UIs:

```json
{
  "condition": "AND",
  "rules": [
    {
      "id": "is_active",
      "value": 1,
      "filterFns": "equals"
    },
    {
      "condition": "OR",
      "rules": [
        {
          "id": "number",
          "value": "100",
          "filterFns": "startsWith"
        },
        {
          "id": "sector_id",
          "value": [1, 2],
          "filterFns": "in"
        }
      ]
    }
  ]
}
```

Encode as `advanceFilter` query param (same base64 process).

---

## Field selection (`fields` & `except`)

Reduce payload size by requesting only needed columns.

> Property names must match the backend Data class (e.g. if Data has `title`, use `fields=title` not `fields=translation.title` unless that is the property name).

### Use case 1 — Whitelist top-level fields

```
GET /api/decrees?fields=id,title,date,is_active
```

### Use case 2 — Whitelist nested relation field

```
GET /api/decrees?fields=id,title,category.name
```

Response item shape:

```json
{
  "id": 1,
  "title": "Decree title",
  "category": { "name": "Type A" }
}
```

### Use case 3 — Whitelist inside a list relation

```
?fields=id,values.title,values.sort_number
```

```json
{
  "id": 1,
  "values": [
    { "title": "Goal 1", "sort_number": 1 },
    { "title": "Goal 2", "sort_number": 2 }
  ]
}
```

### Use case 4 — Blacklist heavy fields

```
GET /api/decrees?except=translations,pdf_path,content
```

Keeps all fields **except** those listed (supports dot notation).

### Use case 5 — Blacklist nested key only

```
GET /api/decrees?except=translations.content,translations.meta_description
```

### Use case 6 — Combined whitelist + blacklist

```
?fields=id,title,category,translations&except=translations.content
```

Returns `id`, `title`, `category`, and translations **without** `content`.

### Use case 7 — fields/except on single item (show)

```
GET /api/decrees/5?fields=id,title,pdf_url
```

Works on show endpoints — no special frontend handling needed.

---

## Response shapes

### Separated pagination (default)

```json
{
  "success": true,
  "data": [ /* array of items */ ],
  "pagination": {
    "current_page": 1,
    "last_page": 5,
    "per_page": 10,
    "total": 48
  }
}
```

### Count only

```
GET /api/decrees?count_only=1&filters=...
```

```json
{
  "data": 48,
  "pagination": null
}
```

---

## Material React Table integration sketch

```typescript
import { useMemo, useState } from 'react';
import { MaterialReactTable } from 'material-react-table';

function DecreeListPage() {
  const [pagination, setPagination] = useState({ pageIndex: 0, pageSize: 10 });
  const [globalFilter, setGlobalFilter] = useState('');
  const [columnFilters, setColumnFilters] = useState([]);
  const [sorting, setSorting] = useState([]);

  const queryParams = useMemo(() => {
    const filters = columnFilters.map((f) => ({
      id: f.id,
      value: f.value,
      filterFns: f.filterFn ?? 'equals',
    }));

    const sortPayload = sorting.map((s) => ({
      id: s.id,
      desc: s.desc,
    }));

    return {
      page: String(pagination.pageIndex + 1),
      perPage: String(pagination.pageSize),
      globalFilter,
      paginationFormate: 'separated',
      filters: filters.length ? encodeDynamicParam(filters) : undefined,
      sorting: sortPayload.length ? encodeDynamicParam(sortPayload) : undefined,
      fields: 'id,title,date,category.name,is_active',
    };
  }, [pagination, globalFilter, columnFilters, sorting]);

  // fetch with queryParams...
}
```

### Column id mapping

Frontend column `accessorKey` must match backend whitelist ids:

| Table column | Filter/sort `id` |
|--------------|------------------|
| Title | `translation.title` |
| Category | `category_id` |
| Published | `published_at` |
| Active toggle | `is_active` |

Check the model's `getFilterableExtra()` / `getSortableExtra()` for allowed ids.

---

## Vue / Axios example

```javascript
import axios from 'axios';

function encodeParam(data) {
  return btoa(unescape(encodeURIComponent(JSON.stringify(data))));
}

export async function fetchDecrees({ page, perPage, search, filters, sorting }) {
  const { data } = await axios.get('/api/decrees', {
    params: {
      page,
      perPage,
      globalFilter: search || undefined,
      filters: filters?.length ? encodeParam(filters) : undefined,
      sorting: sorting?.length ? encodeParam(sorting) : undefined,
      fields: 'id,title,date,category.name',
      paginationFormate: 'separated',
    },
  });
  return data;
}
```

---

## Common mistakes

| Mistake | Fix |
|---------|-----|
| Filter id `title` but backend expects `translation.title` | Use ids from model field map |
| Invalid `filterFns` string | Must match `FilterFnsEnum` exactly (camelCase) |
| Forgetting base64 encoding | Raw JSON in URL will not parse |
| Sorting HasMany relation column | Not supported — sort by main table or use custom scope |
| Expecting `fields` to load relations automatically | Backend eager-loads based on field paths; ensure relation is in `defineRelationships()` |
| `except` not working on nested keys | Use dot notation: `except=category.translations` |

---

## TypeScript types (optional)

```typescript
export type FilterFn =
  | 'equals' | 'notEquals' | 'contains' | 'startsWith' | 'endsWith'
  | 'in' | 'notIn' | 'between' | 'betweenInclusive'
  | 'greaterThan' | 'lessThan' | 'isNull' | 'notIsNull'
  | 'empty' | 'notEmpty' | 'dayEquals' | 'monthEquals' | 'yearEquals';

export interface ColumnFilter {
  id: string;
  value: string | number | boolean | null | Array<string | number>;
  filterFns: FilterFn;
}

export interface ColumnSort {
  id: string;
  desc: boolean;
}

export interface AdvanceFilterGroup {
  condition: 'AND' | 'OR';
  rules: Array<ColumnFilter | AdvanceFilterGroup>;
}
```

---

## See also

- [../README.md](../README.md) — complete feature documentation with all use cases
- [04-SETUP-CHECKLIST.md](./04-SETUP-CHECKLIST.md) — backend setup checklist
- [01-BACKEND-ARCHITECTURE.md](./01-BACKEND-ARCHITECTURE.md) — full backend workflow
- [02-BACKEND-INTEGRATION.md](./02-BACKEND-INTEGRATION.md) — model setup for new resources

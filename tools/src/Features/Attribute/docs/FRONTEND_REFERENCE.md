# EAV Frontend Reference

Guide for frontend / mobile developers consuming the EAV Attribute Admin API and syncing values on entities.

**Base URL:** `{APP_URL}/api`  
**Auth:** Bearer token (Sanctum) or session cookie per project setup  
**Locale header:** `Accept-Language: ar` (recommended)

---

## Concepts

| Term | Meaning |
|------|---------|
| `scope` / `entity_type` | Entity morph alias from URL: `blogs`, `items`, `services` |
| `code` | Optional stable field key. Auto-generated from title if omitted |
| `input_type` | UI control type shown to admin |
| `value_type` | How the backend stores the value |
| `attribute_id` | Numeric ID — use when `code` is null |

---

## Input types → frontend UI

| input_type | Render as | Value sent on entity save |
|------------|-----------|---------------------------|
| `text` | Single-line input per locale | `{ "ar": "...", "en": "..." }` |
| `textarea` | Rich / multi-line per locale | `{ "ar": "...", "en": "..." }` |
| `select` | Dropdown | option `id` (number) |
| `radio` | Radio group | option `id` (number) |
| `multi_select` | Multi dropdown | `[1, 3, 5]` option ids |
| `checkbox` | Checkbox group | `[1, 3, 5]` option ids |
| `color` | Color picker | `"#FF5733"` string |
| `number` | Number input | `12.5` |
| `date` | Date picker | `"2026-07-18"` |
| `boolean` | Toggle / checkbox | `true` / `false` |

---

## Admin API routes

All routes use `{scope}` = plural route segment (e.g. `blogs` → stored as `entity_type: blog`).

| Method | Path | Purpose |
|--------|------|---------|
| GET | `/api/{scope}/{valuable_id}/attributes` | **Definitions + selected values for one object** |
| GET | `/api/{scope}/attributes` | List attribute definitions (admin) |
| GET | `/api/{scope}/attributes/{id}` | Show one definition |
| POST | `/api/{scope}/attributes` | Create definition |
| POST | `/api/{scope}/attributes/{id}` | Update definition |
| POST | `/api/{scope}/attributes/updateAll` | Bulk update |
| DELETE | `/api/{scope}/attributes/{id}` | Delete one |
| DELETE | `/api/{scope}/attributes/bulk-delete` | Bulk delete |
| POST | `/api/{scope}/attributes/{id}/image` | Legacy image upload (prefer `icon`) |

Full request/response examples: [POSTMAN_API.md](./POSTMAN_API.md)

---

## Standard response envelope

```json
{
  "message": "",
  "data": {},
  "errors": [],
  "error_code": null,
  "state": 200,
  "success": true,
  "pagination": null,
  "meta": null
}
```

List endpoints include `pagination`:

```json
{
  "pagination": {
    "current_page": 1,
    "last_page": 3,
    "per_page": 15,
    "total": 42
  }
}
```

---

## Load form for editing an object

When opening the blog/product edit screen, fetch definitions **with current values**:

```http
GET /api/blogs/15/attributes
Accept-Language: ar
Authorization: Bearer {token}
```

Optional category filter (when object has a category):

```http
GET /api/blogs/15/attributes?category_type=blog_categories&category_id=2
```

Each item in `data[]` includes:
- Full attribute definition (`title`, `options`, `input_type`, …)
- **`value`** — the resolved value for this object
- **`value_id`** — internal EAV row id (nullable)

```typescript
type EavFieldWithValue = EavAttribute & {
  value: unknown;       // shape depends on input_type
  value_id: number | null;
};
```

Use `value` to pre-fill form controls. On save, send back via `syncEavAttributes` payload (see below).

---

## Building the admin form (attribute definition CRUD)

### Step 1 — Load definitions

```http
GET /api/blogs/attributes?is_active=1&sort=id&direction=desc
Accept-Language: ar
Authorization: Bearer {token}
```

Use response `data[]` to render dynamic form fields for blog edit screen.

### Step 2 — Map each attribute to a field

```typescript
type EavAttribute = {
  id: number;
  entity_type: string;
  code: string | null;          // optional — may be auto-generated
  input_type: string;
  value_type: string;
  title: string;                // current locale label
  translations: Record<string, {
    title: string;
    placeholder?: string;
    help_text?: string;
  }>;
  options?: EavOption[];        // for select / radio / multi_select / checkbox
  is_required: boolean;
  default_value?: unknown;
  categories?: { category_type: string; category_id: number }[];
};

type EavOption = {
  id: number;
  code: string | null;
  label: string;
  color?: string;
  icon?: string;
  is_default: boolean;
};
```

### Step 3 — Filter by category (if applicable)

If `categories` is non-empty, show the attribute only when the entity's category matches:

```typescript
function isAttributeVisible(attr: EavAttribute, entity: { category_id: number }, categoryType: string) {
  if (!attr.categories?.length) return true;
  return attr.categories.some(
    c => c.category_type === categoryType && c.category_id === entity.category_id
  );
}
```

---

## Saving entity EAV values (on blog/product save)

Send values in your entity create/update payload and call sync on backend, **or** expose a dedicated endpoint in your feature action.

### Payload format (backend sync)

```json
[
  { "code": "weight", "value": 12.5 },
  { "attribute_id": 3, "value": "#FFD700" },
  { "code": "tags", "value": [1, 4, 7] },
  { "code": "extra_note", "value": {
      "ar": "ملاحظة",
      "en": "Note"
  }}
]
```

**Rules:**
- Use `code` when available; fallback to `attribute_id`
- Translatable fields (`text`, `textarea`): object keyed by locale
- Select/radio: single option id
- Multi-select/checkbox: array of option ids

---

## `code` field behavior

| Scenario | Backend behavior |
|----------|------------------|
| `code` provided | Stored as normalized snake_case slug |
| `code` omitted | Auto-generated from first locale `title` |
| `code` null in response | Use `attribute_id` for sync/filter keys (`eav.id_5`) |

Frontend should treat `code` as **optional on create** but always display returned `code` after save.

---

## Filter keys (listing pages)

When building filter UI for entity lists, filter keys are:

```
eav.{code}        → e.g. eav.weight
eav.id_{id}       → when code is null, e.g. eav.id_5
```

Only attributes with `is_filterable: true` appear.

---

## DynamicFilters query params (list attributes)

Same as other CMS resources:

```http
GET /api/blogs/attributes?filters[is_active][0][value]=1&filters[is_active][0][filterFns]=equals&sorting[0][id]=sort_number&sorting[0][desc]=false
```

Supported on `Attribute` model columns: `id`, `code`, `input_type`, `is_active`, `is_filterable`, `sort_number`, etc.

---

## TypeScript helpers (suggested)

```typescript
export function buildEavPayload(
  fields: Record<string | number, unknown>,
  attributes: EavAttribute[]
): Array<{ code?: string; attribute_id?: number; value: unknown }> {
  return attributes.map(attr => ({
    ...(attr.code ? { code: attr.code } : { attribute_id: attr.id }),
    value: fields[attr.code ?? attr.id],
  }));
}
```

---

## Error handling

Validation errors return:

```json
{
  "success": false,
  "state": 422,
  "message": "The given data was invalid.",
  "errors": {
    "code": ["The code has already been taken."],
    "locales.0.title": ["The locales.0.title field is required."]
  }
}
```

---

## Related docs

| Doc | Audience |
|-----|----------|
| [POSTMAN_API.md](./POSTMAN_API.md) | Full HTTP examples |
| [DEVELOPER_GUIDE.md](./DEVELOPER_GUIDE.md) | Backend integration |
| [DATABASE_SCHEMA.md](./DATABASE_SCHEMA.md) | Table reference |

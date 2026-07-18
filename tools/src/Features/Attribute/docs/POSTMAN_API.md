# EAV Postman API Reference

Complete HTTP examples for every EAV attribute admin route.

**Variables (Postman environment):**

| Variable | Example |
|----------|---------|
| `base_url` | `http://localhost:8000` |
| `token` | `{sanctum_bearer_token}` |
| `scope` | `blogs` |
| `valuable_id` | `15` |
| `attribute_id` | `1` |

**Common headers:**

```http
Accept: application/json
Content-Type: application/json
Accept-Language: ar
Authorization: Bearer {{token}}
```

---

## Response envelope

All endpoints return:

```json
{
  "message": "string",
  "data": {},
  "errors": [],
  "error_code": null,
  "state": 200,
  "success": true,
  "pagination": null,
  "meta": null
}
```

---

## 0. Get attributes for object (definitions + selected values)

Use this when editing an existing blog/product — returns field definitions **with `value` filled** for that record.

```http
GET {{base_url}}/api/{{scope}}/{{valuable_id}}/attributes
```

### Query params (optional)

| Param | Example | Description |
|-------|---------|-------------|
| `category_type` | `blog_categories` | Filter category-scoped attributes |
| `category_id` | `2` | Category ID on the object |

Example with category filter:

```http
GET {{base_url}}/api/blogs/15/attributes?category_type=blog_categories&category_id=2
```

### Success response `200`

```json
{
  "message": "",
  "data": [
    {
      "id": 1,
      "entity_type": "blog",
      "code": "weight",
      "input_type": "number",
      "value_type": "number",
      "default_value": null,
      "validation_rules": null,
      "icon": "scale",
      "is_active": true,
      "is_filterable": true,
      "is_sortable": true,
      "is_searchable": false,
      "is_required": false,
      "sort_number": 0,
      "title": "الوزن",
      "translations": {
        "ar": { "title": "الوزن", "placeholder": "أدخل الوزن", "help_text": null },
        "en": { "title": "Weight", "placeholder": "Enter weight", "help_text": null }
      },
      "categories": [],
      "options": [],
      "value": 12.5,
      "value_id": 88,
      "scope": "blog",
      "type": "number",
      "created_at": "2026-07-18T10:00:00.000000Z",
      "updated_at": "2026-07-18T10:00:00.000000Z"
    },
    {
      "id": 2,
      "entity_type": "blog",
      "code": "material",
      "input_type": "select",
      "value_type": "option",
      "title": "المادة",
      "options": [
        {
          "id": 1,
          "code": "gold",
          "label": "ذهب",
          "is_default": true,
          "is_active": true,
          "sort_number": 0,
          "translations": { "ar": { "label": "ذهب" }, "en": { "label": "Gold" } }
        }
      ],
      "value": 1,
      "value_id": 89
    },
    {
      "id": 3,
      "code": "extra_note",
      "input_type": "text",
      "value_type": "string",
      "title": "ملاحظة",
      "value": null,
      "value_id": null
    },
    {
      "id": 4,
      "code": "tags",
      "input_type": "multi_select",
      "value_type": "options",
      "title": "الوسوم",
      "options": [
        { "id": 10, "label": "عاجل" },
        { "id": 11, "label": "مميز" }
      ],
      "value": [10, 11],
      "value_id": 90
    },
    {
      "id": 5,
      "code": "description_extra",
      "input_type": "textarea",
      "value_type": "text",
      "title": "وصف إضافي",
      "value": {
        "ar": "نص عربي",
        "en": "English text"
      },
      "value_id": 91
    }
  ],
  "errors": [],
  "error_code": null,
  "state": 200,
  "success": true,
  "pagination": null,
  "meta": null
}
```

### `value` shapes returned

| input_type | `value` in response |
|------------|---------------------|
| `text` / `textarea` | `{ "ar": "...", "en": "..." }` or `null` |
| `select` / `radio` | option `id` (number) or `null` |
| `multi_select` / `checkbox` | `[1, 3, 5]` or `[]` |
| `number` | `12.5` or `null` |
| `date` | `"2026-07-18"` or `null` |
| `boolean` | `true` / `false` or `null` |
| `color` | `"#FF5733"` or `null` |

When no value saved yet, `value` falls back to `default_value` if defined, otherwise `null`.

---

## 1. List attributes

```http
GET {{base_url}}/api/{{scope}}/attributes?page=1&per_page=15
```

### Query params (optional — DynamicFilters)

| Param | Example | Description |
|-------|---------|-------------|
| `page` | `1` | Page number |
| `per_page` | `15` | Items per page |
| `filters[is_active][0][value]` | `1` | Filter active only |
| `filters[is_active][0][filterFns]` | `equals` | Filter operator |
| `sorting[0][id]` | `sort_number` | Sort column |
| `sorting[0][desc]` | `false` | Ascending |

### Success response `200`

```json
{
  "message": "",
  "data": [
    {
      "id": 1,
      "entity_type": "blog",
      "code": "weight",
      "input_type": "number",
      "value_type": "number",
      "default_value": null,
      "validation_rules": null,
      "icon": "scale",
      "is_active": true,
      "is_filterable": true,
      "is_sortable": true,
      "is_searchable": false,
      "is_required": false,
      "sort_number": 0,
      "title": "الوزن",
      "translations": {
        "ar": { "title": "الوزن", "placeholder": "أدخل الوزن", "help_text": null },
        "en": { "title": "Weight", "placeholder": "Enter weight", "help_text": null }
      },
      "categories": [],
      "options": [],
      "scope": "blog",
      "type": "number",
      "created_at": "2026-07-18T10:00:00.000000Z",
      "updated_at": "2026-07-18T10:00:00.000000Z"
    }
  ],
  "errors": [],
  "error_code": null,
  "state": 200,
  "success": true,
  "pagination": {
    "current_page": 1,
    "last_page": 1,
    "per_page": 15,
    "total": 1
  },
  "meta": null
}
```

---

## 2. Show attribute

```http
GET {{base_url}}/api/{{scope}}/attributes/{{attribute_id}}
```

### Success response `200`

```json
{
  "message": "",
  "data": {
    "id": 2,
    "entity_type": "blog",
    "code": "material",
    "input_type": "select",
    "value_type": "option",
    "default_value": { "option_id": 1 },
    "validation_rules": null,
    "icon": "list",
    "is_active": true,
    "is_filterable": true,
    "is_sortable": false,
    "is_searchable": false,
    "is_required": true,
    "sort_number": 1,
    "title": "المادة",
    "translations": {
      "ar": { "title": "المادة", "placeholder": null, "help_text": "اختر المادة" },
      "en": { "title": "Material", "placeholder": null, "help_text": "Choose material" }
    },
    "categories": [
      { "category_type": "blog_categories", "category_id": 2 }
    ],
    "options": [
      {
        "id": 1,
        "code": "gold",
        "color": "#FFD700",
        "icon": null,
        "is_default": true,
        "is_active": true,
        "sort_number": 0,
        "label": "ذهب",
        "translations": {
          "ar": { "label": "ذهب" },
          "en": { "label": "Gold" }
        }
      },
      {
        "id": 2,
        "code": "silver",
        "color": "#C0C0C0",
        "icon": null,
        "is_default": false,
        "is_active": true,
        "sort_number": 1,
        "label": "فضة",
        "translations": {
          "ar": { "label": "فضة" },
          "en": { "label": "Silver" }
        }
      }
    ],
    "scope": "blog",
    "type": "select",
    "created_at": "2026-07-18T10:05:00.000000Z",
    "updated_at": "2026-07-18T10:05:00.000000Z"
  },
  "errors": [],
  "error_code": null,
  "state": 200,
  "success": true,
  "pagination": null,
  "meta": null
}
```

---

## 3. Create attribute

```http
POST {{base_url}}/api/{{scope}}/attributes
```

### Body — number field (with explicit code)

```json
{
  "entity_type": "blog",
  "code": "weight",
  "input_type": "number",
  "is_active": true,
  "is_filterable": true,
  "is_sortable": true,
  "is_searchable": false,
  "is_required": false,
  "sort_number": 0,
  "icon": "scale",
  "default_value": { "value": 0 },
  "locales": [
    { "locale": "ar", "title": "الوزن", "placeholder": "أدخل الوزن" },
    { "locale": "en", "title": "Weight", "placeholder": "Enter weight" }
  ]
}
```

### Body — without code (auto-generated from title)

```json
{
  "input_type": "text",
  "is_filterable": false,
  "locales": [
    { "locale": "ar", "title": "ملاحظة إضافية" },
    { "locale": "en", "title": "Extra Note" }
  ]
}
```

> `entity_type` is inferred from URL scope (`blogs` → `blog`).  
> `code` omitted → backend generates e.g. `mlahz_adafy` from Arabic title or `extra_note` from English.

### Body — select with options and categories

```json
{
  "code": "material",
  "input_type": "select",
  "is_required": true,
  "is_filterable": true,
  "categories": [
    { "category_type": "blog_categories", "category_id": 2 }
  ],
  "locales": [
    { "locale": "ar", "title": "المادة", "help_text": "اختر المادة" },
    { "locale": "en", "title": "Material", "help_text": "Choose material" }
  ],
  "options": [
    {
      "code": "gold",
      "color": "#FFD700",
      "is_default": true,
      "sort_number": 0,
      "locales": [
        { "locale": "ar", "label": "ذهب" },
        { "locale": "en", "label": "Gold" }
      ]
    },
    {
      "sort_number": 1,
      "locales": [
        { "locale": "ar", "label": "فضة" },
        { "locale": "en", "label": "Silver" }
      ]
    }
  ]
}
```

### Body — multi_select

```json
{
  "code": "tags",
  "input_type": "multi_select",
  "is_filterable": true,
  "locales": [
    { "locale": "ar", "title": "الوسوم" },
    { "locale": "en", "title": "Tags" }
  ],
  "options": [
    { "code": "urgent", "locales": [{ "locale": "ar", "label": "عاجل" }, { "locale": "en", "label": "Urgent" }] },
    { "code": "featured", "locales": [{ "locale": "ar", "label": "مميز" }, { "locale": "en", "label": "Featured" }] }
  ]
}
```

### Body — boolean / date / color

```json
{
  "code": "is_featured_custom",
  "input_type": "boolean",
  "default_value": { "value": false },
  "locales": [
    { "locale": "ar", "title": "مميز مخصص" },
    { "locale": "en", "title": "Custom Featured" }
  ]
}
```

```json
{
  "code": "publish_date",
  "input_type": "date",
  "locales": [
    { "locale": "ar", "title": "تاريخ النشر" },
    { "locale": "en", "title": "Publish Date" }
  ]
}
```

```json
{
  "code": "brand_color",
  "input_type": "color",
  "default_value": { "value": "#003366" },
  "locales": [
    { "locale": "ar", "title": "لون العلامة" },
    { "locale": "en", "title": "Brand Color" }
  ]
}
```

### Success response `200`

```json
{
  "message": "Added successfully",
  "data": {
    "id": 3,
    "entity_type": "blog",
    "code": "weight",
    "input_type": "number",
    "value_type": "number",
    "title": "الوزن",
    "is_active": true,
    "is_filterable": true,
    "sort_number": 0
  },
  "errors": [],
  "error_code": null,
  "state": 200,
  "success": true,
  "pagination": null,
  "meta": null
}
```

### Validation error `422`

```json
{
  "message": "The given data was invalid.",
  "data": [],
  "errors": {
    "input_type": ["The selected input type is invalid."],
    "locales": ["The locales field is required."]
  },
  "error_code": null,
  "state": 422,
  "success": false,
  "pagination": null,
  "meta": null
}
```

---

## 4. Update attribute

```http
POST {{base_url}}/api/{{scope}}/attributes/{{attribute_id}}
```

### Body — partial update

```json
{
  "is_active": false,
  "sort_number": 5,
  "locales": [
    { "locale": "ar", "title": "الوزن (غرام)" },
    { "locale": "en", "title": "Weight (grams)" }
  ]
}
```

### Body — change code (optional)

```json
{
  "code": "weight_grams"
}
```

### Success response `200`

```json
{
  "message": "Updated successfully",
  "data": {
    "id": 1,
    "entity_type": "blog",
    "code": "weight_grams",
    "input_type": "number",
    "is_active": false,
    "sort_number": 5,
    "title": "الوزن (غرام)"
  },
  "errors": [],
  "error_code": null,
  "state": 200,
  "success": true,
  "pagination": null,
  "meta": null
}
```

---

## 5. Bulk update attributes

```http
POST {{base_url}}/api/{{scope}}/attributes/updateAll
```

### Body

```json
{
  "attributes": [
    {
      "id": 1,
      "sort_number": 0,
      "is_active": true,
      "locales": [
        { "locale": "ar", "title": "الوزن" },
        { "locale": "en", "title": "Weight" }
      ]
    },
    {
      "id": 2,
      "sort_number": 1,
      "is_active": true,
      "options": [
        {
          "id": 1,
          "sort_number": 0,
          "locales": [{ "locale": "ar", "label": "ذهب" }, { "locale": "en", "label": "Gold" }]
        }
      ]
    }
  ]
}
```

### Success response `200`

```json
{
  "message": "Updated successfully",
  "data": [
    { "id": 1, "code": "weight", "sort_number": 0, "title": "الوزن" },
    { "id": 2, "code": "material", "sort_number": 1, "title": "المادة" }
  ],
  "errors": [],
  "error_code": null,
  "state": 200,
  "success": true,
  "pagination": null,
  "meta": null
}
```

---

## 6. Delete attribute

```http
DELETE {{base_url}}/api/{{scope}}/attributes/{{attribute_id}}
```

### Success response `200`

```json
{
  "message": "Deleted successfully",
  "data": [],
  "errors": [],
  "error_code": null,
  "state": 200,
  "success": true,
  "pagination": null,
  "meta": null
}
```

---

## 7. Bulk delete attributes

```http
DELETE {{base_url}}/api/{{scope}}/attributes/bulk-delete
```

### Body

```json
{
  "ids": [1, 2, 3]
}
```

### Success response `200`

```json
{
  "message": "Deleted successfully",
  "data": [],
  "errors": [],
  "error_code": null,
  "state": 200,
  "success": true,
  "pagination": null,
  "meta": null
}
```

---

## 8. Update attribute icon/image (legacy)

```http
POST {{base_url}}/api/{{scope}}/attributes/{{attribute_id}}/image
Content-Type: multipart/form-data
```

### Body (form-data)

| Key | Type | Value |
|-----|------|-------|
| `icon` | text | `scale` |
| `image` | file | *(optional file upload — legacy)* |
| `delete_image` | boolean | `false` |

> Prefer setting `icon` string on create/update JSON body instead of this endpoint.

### Success response `200`

```json
{
  "message": "Image uploaded successfully",
  "data": {
    "id": 1,
    "code": "weight",
    "icon": "scale",
    "title": "الوزن"
  },
  "errors": [],
  "error_code": null,
  "state": 200,
  "success": true,
  "pagination": null,
  "meta": null
}
```

---

## Entity value sync (backend — not a direct API route)

When saving a blog/product, the backend syncs values via `syncEavAttributes()`:

```json
[
  { "code": "weight", "value": 12.5 },
  { "code": "material", "value": 1 },
  { "code": "tags", "value": [1, 3] },
  { "code": "extra_note", "value": { "ar": "ملاحظة", "en": "Note" } },
  { "attribute_id": 5, "value": true }
]
```

Include this array in your feature's store/update payload (e.g. `attributes` key on blog create).

---

## Postman collection import (JSON snippet)

```json
{
  "info": { "name": "HMsoft EAV Attributes", "schema": "https://schema.getpostman.com/json/collection/v2.1.0/collection.json" },
  "variable": [
    { "key": "base_url", "value": "http://localhost:8000" },
    { "key": "scope", "value": "blogs" },
    { "key": "attribute_id", "value": "1" },
    { "key": "token", "value": "" }
  ],
  "item": [
    { "name": "Get Object Attributes", "request": { "method": "GET", "url": "{{base_url}}/api/{{scope}}/{{valuable_id}}/attributes" } },
    { "name": "List Attributes", "request": { "method": "GET", "url": "{{base_url}}/api/{{scope}}/attributes" } },
    { "name": "Show Attribute", "request": { "method": "GET", "url": "{{base_url}}/api/{{scope}}/attributes/{{attribute_id}}" } },
    { "name": "Create Attribute", "request": { "method": "POST", "url": "{{base_url}}/api/{{scope}}/attributes", "body": { "mode": "raw", "raw": "{\n  \"input_type\": \"number\",\n  \"code\": \"weight\",\n  \"locales\": [{\"locale\":\"ar\",\"title\":\"الوزن\"},{\"locale\":\"en\",\"title\":\"Weight\"}]\n}" } } },
    { "name": "Update Attribute", "request": { "method": "POST", "url": "{{base_url}}/api/{{scope}}/attributes/{{attribute_id}}" } },
    { "name": "Bulk Update", "request": { "method": "POST", "url": "{{base_url}}/api/{{scope}}/attributes/updateAll" } },
    { "name": "Delete Attribute", "request": { "method": "DELETE", "url": "{{base_url}}/api/{{scope}}/attributes/{{attribute_id}}" } },
    { "name": "Bulk Delete", "request": { "method": "DELETE", "url": "{{base_url}}/api/{{scope}}/attributes/bulk-delete", "body": { "mode": "raw", "raw": "{\"ids\":[1,2]}" } } }
  ]
}
```

---

## HTTP status codes

| Code | Meaning |
|------|---------|
| `200` | Success |
| `401` | Unauthenticated |
| `403` | Forbidden |
| `404` | Attribute not found or wrong scope |
| `422` | Validation error |
| `500` | Server error |

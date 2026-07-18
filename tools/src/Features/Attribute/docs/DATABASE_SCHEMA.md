# EAV Database Schema

Complete reference for all EAV tables, columns, constraints, and indexes.

---

## 1. `eav_attributes`

Attribute definitions (metadata).

| Column | Type | Notes |
|--------|------|-------|
| `id` | bigint PK | |
| `entity_type` | varchar(100) | Morph alias: `blogs`, `items` |
| `code` | varchar(100) | **Optional.** Stable key: `weight`, `color`. Auto-generated from title if omitted |
| `input_type` | varchar(50) | See `InputTypeEnum` |
| `value_type` | varchar(50) | See `ValueTypeEnum` |
| `default_value` | json | Typed default payload |
| `validation_rules` | json | Optional Laravel-style rules |
| `icon` | varchar | UI icon |
| `sort_number` | int | Display order |
| `is_required` | bool | Validation flag |
| `is_active` | bool | |
| `is_filterable` | bool | AutoFilter registration |
| `is_sortable` | bool | AutoFilter sort |
| `is_searchable` | bool | Global search inclusion |
| `created_at`, `updated_at` | timestamp | |
| `deleted_at` | timestamp | Soft delete |

**Constraints:**
- `UNIQUE (entity_type, code)`
- `INDEX (entity_type, is_active)`

---

## 2. `eav_attribute_translations`

| Column | Type | Notes |
|--------|------|-------|
| `id` | bigint PK | |
| `attribute_id` | FK → eav_attributes | cascade delete |
| `locale` | varchar(10) | `ar`, `en` |
| `title` | varchar | Field label |
| `placeholder` | varchar | Nullable |
| `help_text` | text | Nullable |

**Constraints:** `UNIQUE (attribute_id, locale)`

---

## 3. `eav_attribute_options`

Options for select/radio/multi_select/checkbox.

| Column | Type | Notes |
|--------|------|-------|
| `id` | bigint PK | |
| `attribute_id` | FK | |
| `code` | varchar(100) | Optional stable key |
| `sort_number` | int | |
| `color` | varchar(20) | Optional swatch |
| `icon` | varchar | |
| `is_default` | bool | |
| `is_active` | bool | |

**Constraints:** `INDEX (attribute_id, is_active)`

---

## 4. `eav_attribute_option_translations`

| Column | Type | Notes |
|--------|------|-------|
| `id` | bigint PK | |
| `attribute_option_id` | FK | |
| `locale` | varchar(10) | |
| `label` | varchar | Display label |

**Constraints:** `UNIQUE (attribute_option_id, locale)`

---

## 5. `eav_attribute_categories`

Category scoping (replaces JSON `category_ids`).

| Column | Type | Notes |
|--------|------|-------|
| `id` | bigint PK | |
| `attribute_id` | FK | |
| `category_type` | varchar(100) | Morph alias: `blog_categories` |
| `category_id` | bigint | Category record ID |

**Constraints:** `UNIQUE (attribute_id, category_type, category_id)`

---

## 6. `eav_values`

Stored values (transactional).

| Column | Type | Notes |
|--------|------|-------|
| `id` | bigint PK | |
| `valuable_type` | varchar(100) | Morph alias (NOT FQCN) |
| `valuable_id` | bigint | Owner PK |
| `attribute_id` | FK | |
| `value_text` | varchar(500) | Short non-translated text, color hex |
| `value_long_text` | text | Long non-translated text |
| `value_number` | decimal(20,6) | Numbers |
| `value_boolean` | bool | Boolean |
| `value_date` | date | Date |
| `attribute_option_id` | FK nullable | Single option (select/radio) |

**Constraints:**
- `UNIQUE (valuable_type, valuable_id, attribute_id)`
- `INDEX (valuable_type, valuable_id)`
- `INDEX (attribute_id, value_number)`
- `INDEX (attribute_id, value_boolean)`
- `INDEX (attribute_id, value_date)`
- `INDEX (attribute_id, attribute_option_id)`
- `INDEX (value_text)`

---

## 7. `eav_value_translations`

Translatable text/textarea values only.

| Column | Type | Notes |
|--------|------|-------|
| `id` | bigint PK | |
| `value_id` | FK → eav_values | |
| `locale` | varchar(10) | |
| `value_text` | varchar(500) | Short translated text |
| `value_long_text` | text | Long translated text |

**Constraints:** `UNIQUE (value_id, locale)`

---

## 8. `eav_value_options`

Multi-select / checkbox pivot.

| Column | Type | Notes |
|--------|------|-------|
| `id` | bigint PK | |
| `value_id` | FK → eav_values | |
| `attribute_option_id` | FK → eav_attribute_options | |
| `created_at` | timestamp | |

**Constraints:**
- `UNIQUE (value_id, attribute_option_id)`
- `INDEX (attribute_option_id)`

---

## default_value JSON shapes

```json
// select / radio
{ "option_id": 3 }

// multi_select / checkbox
{ "option_ids": [1, 3, 5] }

// translatable text
{ "ar": "...", "en": "..." }

// number
{ "value": 12.5 }

// boolean
{ "value": true }
```

---

## Migration from legacy `attributes` tables

| Legacy | New |
|--------|-----|
| `attributes.scope` | `eav_attributes.entity_type` |
| `attributes.type` | `eav_attributes.input_type` |
| — | `eav_attributes.code` (generate from slug) |
| `attributes.category_ids` JSON | `eav_attribute_categories` rows |
| `attribute_values.owner_*` | `eav_values.valuable_*` |
| `attribute_values.value` TEXT | Typed columns + pivot |
| `attribute_values.locale` | `eav_value_translations` |

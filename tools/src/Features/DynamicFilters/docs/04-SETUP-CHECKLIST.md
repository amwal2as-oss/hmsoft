# DynamicFilters — Setup Checklist

Printable checklist for integrating DynamicFilters on a new resource.

---

## Understand the two parts

| Part | What it does | Required files |
|------|--------------|----------------|
| **A. Auto Filter** | Filters, sorting, global search, pagination from URL | Model + GetListAction |
| **B. fields / except** | Shrinks JSON response size | **Data extends BaseData** + `filterableCollect()` |

> Part A works without Part B.  
> Part B **cannot** work without a Data class.

---

## Part A — Auto Filter

```
[ ] Model implements AutoFilterable interface
[ ] Model uses IsAutoFilterable trait
[ ] Define DEFAULT_INCLUDES constant (relations to eager load)
[ ] Override getRelationshipsExtra() — allowed relations for joins/eager load
[ ] Override getFilterableExtra() — relation + custom filter columns
[ ] Override getSortableExtra() — relation + custom sort columns
[ ] Override getFieldSelectionMapExtra() — API alias → DB path (optional)
[ ] Override defineGlobalSearchRelatedAttributes() — search in relations (optional)
[ ] Override cmsDefaultFilters() — default filters when request empty (optional)
[ ] Override cmsDefaultSorts() — default sort when request empty (optional)
[ ] Create GetListAction calling dynamicSearchFromRequest()
[ ] Service::list() returns GetListAction result
[ ] Controller index calls service (no Data needed yet for Part A)
```

### Minimal model example

```php
class Blog extends Model implements AutoFilterable
{
    use IsAutoFilterable;
    public const DEFAULT_INCLUDES = ['translations', 'sector'];
}
```

### Minimal GetListAction

```php
return AutoFilterAndSortService::dynamicSearchFromRequest(
    model: Blog::class,
    extraOperation: fn ($q) => $q->with(Blog::DEFAULT_INCLUDES),
);
```

---

## Part B — fields / except (JSON pruning)

```
[ ] Create {Feature}Data class extending BaseData
[ ] Define all response properties in constructor (names = fields param keys)
[ ] Implement fromModel() to map Eloquent → Data
[ ] Nested relation Data classes extend BaseData (if using nested fields/except)
[ ] Controller index: {Feature}Data::filterableCollect($result['data'])
[ ] Controller show: {Feature}Data::fromModel() (auto-applies fields/except)
[ ] Verify relations are loaded when requested in fields (DEFAULT_INCLUDES)
```

### Minimal Data example

```php
class BlogData extends BaseData
{
    public function __construct(
        public ?int $id,
        public ?string $title,
        public ?bool $is_active,
    ) {}

    public static function fromModel(Blog $blog): self
    {
        return new self(
            id: $blog->id,
            title: $blog->translation?->title,
            is_active: $blog->is_active,
        );
    }
}
```

### Minimal controller

```php
public function index()
{
    $result = $this->blogService->list();
    $result['data'] = BlogData::filterableCollect($result['data']);
    return CmsResponse::success(data: $result['data'], pagination: $result['pagination']);
}
```

---

## Required vs optional

| Step | Auto Filter | fields / except |
|------|:-----------:|:---------------:|
| Model + IsAutoFilterable | ✅ Required | — |
| getFilterableExtra() | Recommended | — |
| GetListAction | ✅ Required | — |
| Data extends BaseData | — | ✅ Required |
| filterableCollect() | — | ✅ Required (list) |
| Nested Data extends BaseData | — | If nested pruning |
| getFieldSelectionMapExtra() | Optional | Uses Data property names |

---

## Verify after setup

### Auto Filter tests

```http
GET /api/blogs?page=1&perPage=10
GET /api/blogs?globalFilter=test
GET /api/blogs?filters=<base64:[{"id":"is_active","value":true,"filterFns":"equals"}]>
GET /api/blogs?sorting=<base64:[{"id":"published_at","desc":true}]>
GET /api/blogs?count_only=1
```

### fields / except tests

```http
GET /api/blogs?fields=id,title,is_active
GET /api/blogs?except=translations,content
GET /api/blogs?fields=id,title,sector.name
GET /api/blogs/1?fields=id,title
```

---

## Common mistakes

| Mistake | Fix |
|---------|-----|
| `fields` ignored | Extend BaseData + call filterableCollect() |
| Filter ignored | Add column to getFilterableExtra() |
| Nested field empty | Load relation + nested Data extends BaseData |
| Property name mismatch | fields param must match Data constructor property names |

---

## See also

- [Main README](../README.md) — full feature documentation
- [02-BACKEND-INTEGRATION.md](./02-BACKEND-INTEGRATION.md) — detailed integration guide
- [03-FRONTEND-GUIDE.md](./03-FRONTEND-GUIDE.md) — frontend encoding guide

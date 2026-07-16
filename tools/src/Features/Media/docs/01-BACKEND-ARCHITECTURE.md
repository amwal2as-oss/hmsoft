# Media — Backend Architecture

How the Media feature works internally.

---

## Package structure

```
Media/
├── Actions/
│   ├── CreateAction.php          # Create Medium row + upload
│   ├── CreateBulkAction.php
│   ├── UpdateAction.php
│   ├── UpdateBulkAction.php
│   ├── DeleteAction.php
│   ├── GetListAction.php         # DynamicFilters list
│   └── StreamExternalFileAction.php
├── config/cms_media.php
├── Controllers/MediaController.php
├── Data/
│   ├── MediaData.php             # Response DTO
│   ├── StoreMediaData.php
│   ├── StoreBulkMediaData.php
│   ├── UpdateMediaData.php
│   ├── UpdateAllMediaData.php
│   └── BulkDeleteMediaData.php
├── Database/Migrations/
├── Facades/MediaUploader.php
├── Models/
│   ├── Medium.php
│   └── MediumTranslation.php
├── Providers/MediaServiceProvider.php
├── Routes/api.php
├── Rules/FileOrUrl.php
├── Service/
│   ├── MediaService.php          # CRUD orchestrator
│   └── MediaUploadService.php    # Disk I/O + WebP
└── Traits/
    ├── HasMedia.php
    ├── HandlesSingleMedia.php
    ├── HandlesMultipleMedia.php
    ├── InteractsWithMediaRules.php
    └── ExtractsOwnerFromRoute.php
```

---

## Storage decision flow

```
syncSingleImage(model, file, field, ...)
        │
        ▼
   delete_image? ──yes──► deleteSingleImage()
        │no
        ▼
   file provided? ──no──► return 'unchanged'
        │yes
        ▼
   uploadSingleImage()
        │
        ▼
   Schema::hasColumn(table, field)?
        │
   yes ─┴─ no
   │       │
   ▼       ▼
 Column   Polymorphic
 update   MediaService::store()
 path     → media table row
```

---

## MediaUploadService pipeline

```
UploadedFile
    │
    ├─ Non-image (pdf, doc, …) → storeRawFile() → disk
    │
    └─ Image (jpg, png, webp, …)
           │
           ├─ Resize if > max_image_dimension
           ├─ Convert to WebP (main file)
           └─ If sizeSet configured:
                  Generate {name}_{suffix}.webp per image_sets
```

**Facade:** `MediaUploader::upload($file, $directory, $disk, $sizeSet)`

---

## HasMedia — magic accessors

`getAttribute()` intercepts:

| Pattern | Example | Behavior |
|---------|---------|----------|
| `{field}_url` | `image_url` | `Storage::url(path)` or external URL |
| `{field}_url_{suffix}` | `image_url_thumb` | Inserts `_thumb` before extension |
| `{field}_object` | `image_object` | Full object with srcset |

Only works for fields listed in `$cmsMediaFields` (or `$mediaFields`).

### Cascade delete

On model `deleting` / `forceDeleting`:

1. Loop `$cmsMediaFields` → `MediaUploader::deleteFile()` each path
2. Load all `mediaList` → collect paths → `deleteFiles()` → delete rows

---

## MediaService layer

| Method | Action | Description |
|--------|--------|-------------|
| `list()` | GetListAction | Filtered paginated media for owner |
| `store()` | CreateAction | Single upload |
| `storeBulk()` | CreateBulkAction | Transactional bulk create |
| `update()` | UpdateAction | Replace file + metadata |
| `updateAll()` | UpdateBulkAction | Batch metadata |
| `delete()` | DeleteAction | Single delete + default reassignment |
| `deleteBulk()` | DeleteAction | Bulk delete |

### Default media logic

- First media for owner auto-becomes `is_default = true` if none exists
- Setting new default clears previous default
- On delete of default → next by `sort_number` promoted

---

## MediaController routes

Prefix: `api/{owner_type}/{owner_id}/media`

`ExtractsOwnerFromRoute` injects `owner_id` / `owner_type` into DTOs from route params.

`verifyOwner()` ensures `{medium}` belongs to route owner.

---

## Integration with other features

| Feature | Integration |
|---------|-------------|
| **Translations** | `Medium` implements Translatable; locales on store/update |
| **DynamicFilters** | `GetListAction` + `Medium` implements AutoFilterable |
| **Audit** | `Medium` uses Auditable traits |
| **Response** | `CmsResponse` wrapper in controller |

---

## FileOrUrl validation

Accepts:

- Valid `UploadedFile`
- String passing `FILTER_VALIDATE_URL`

Used for CMS forms that allow paste-URL instead of upload.

---

## Security notes

1. Validation via Spatie Data DTOs + `InteractsWithMediaRules`
2. Owner verification on Media API show/update/delete
3. Files stored outside public web root unless using `public` disk + symlink
4. Image dimension limit prevents memory exhaustion on large uploads

---

## See also

- [02-BACKEND-INTEGRATION.md](./02-BACKEND-INTEGRATION.md)
- [05-COMPLETE-API-REFERENCE.md](./05-COMPLETE-API-REFERENCE.md)

# Media — Analysis & Notes

Code review notes for the Media feature — known issues, config gaps, and recommendations.

---

## Feature overview

Media provides:

1. **HasMedia trait** — magic URL accessors, placeholders, cascade delete
2. **HandlesSingleMedia** — sync one field (column or polymorphic row)
3. **HandlesMultipleMedia** — sync gallery with optional delete-by-id
4. **MediaUploadService** — WebP conversion, thumbnails, raw file storage
5. **MediaService + MediaController** — full CRUD on `media` table
6. **InteractsWithMediaRules** — reusable validation for DTOs

---

## Architecture strengths

- Dual storage (column + polymorphic) with automatic detection via `Schema::hasColumn()`
- Accepts file upload **or** external URL (`FileOrUrl` rule)
- WebP conversion + configurable image size sets
- Cascade media cleanup when owner model is deleted
- Translations support on `Medium` via Translations feature
- List endpoint integrates with DynamicFilters

---

## Known issues & gaps

### 1. Config key inconsistency

| Code location | Config key used | Actual key |
|---------------|-----------------|------------|
| `Medium::fileUrl()` | `cms_media.disk` | ✅ exists |
| `HasMedia::getMediaDisk()` | `cms_media.default_disk` | ❌ not in vendor config |
| `HasMedia::getMediaObject()` placeholder | `cms_media.default_placeholder` | ❌ use `cms_media.placeholders.default` |
| App `config/cms.php` | `cms.media.default_disk` | Different namespace |

**Impact:** Disk/placeholder may silently fall back to `'public'` or wrong path.

**Recommendation:** Standardize on `cms_media.disk` and `cms_media.placeholders.default`.

---

### 2. `UpdateMediaData` incomplete vs `UpdateAction`

`UpdateAction` reads `$data->file`, `$data->is_default`, `$data->media_type` but `UpdateMediaData` constructor does not declare `file` or `is_default`.

**Impact:** File replacement via `POST /api/{owner}/media/{medium}` may not bind request fields.

**Recommendation:** Add `file`, `is_default`, `media_type` to `UpdateMediaData` constructor.

---

### 3. Image sets not configured for app models

Models use `$cmsMediaSet = 'blog_items'`, `'gallery_items'`, etc. but vendor config only defines `default` and `avatar`. App `config/cms.php` has empty `image_sets`.

**Impact:** `image_url_thumb`, `image_object`, srcset won't generate variants.

**Recommendation:** Add matching keys to `cms_media.image_sets` or merge from app config.

---

### 4. `syncSingleImage` rarely passes `$sizeSet`

Upload does not pass `$model->cmsMediaSet` to `MediaUploader::upload()`, so variants may not generate even when config exists.

**Recommendation:** Pass `$sizeSet ?? $model->cmsMediaSet ?? null` in `uploadSingleImage()`.

---

### 5. Thumbnail orphan files on delete

Upload creates `{name}_thumb.webp`, `{name}_medium.webp` but `deleteFile()` only removes main path.

**Impact:** Orphan variant files on disk over time.

---

### 6. External URL mime type

`CreateAction` sets `media_type = 'video'` for any string URL regardless of content type.

---

### 7. `MediaData` extends app namespace

`MediaData extends App\Data\BaseData` — couples vendor package to consuming app.

---

### 8. `cleanupMediaFiles()` vs `purgeAssociatedMedia()`

- `purgeAssociatedMedia()` — used on model delete; deletes disk files ✅
- `cleanupMediaFiles()` — deletes DB rows without guaranteed disk cleanup ⚠️

Prefer `purgeAssociatedMedia()` behavior.

---

### 9. Morph map for Media API

Media API uses `{owner_type}` route param. App morph map may be commented out — short aliases like `blog` won't resolve.

---

### 10. `StreamExternalFileAction` unused

Exists in vendor but not wired; apps duplicate streaming logic in DownloadPdfAction.

---

## Verification checklist

- [ ] Upload image → column updated → `image_url` works
- [ ] Replace image → old file removed
- [ ] `delete_image: true` → column null, file gone
- [ ] External URL stored and returned as-is
- [ ] PDF/non-image stored without WebP conversion
- [ ] Model delete → all media files removed
- [ ] Polymorphic upload → row in `media` table
- [ ] Gallery bulk upload via `syncMultipleMedia`
- [ ] Media API list/store/delete (if used)
- [ ] Thumbnails/srcset (if image_sets configured)

---

## See also

- [../README.md](../README.md)
- [05-COMPLETE-API-REFERENCE.md](./05-COMPLETE-API-REFERENCE.md)

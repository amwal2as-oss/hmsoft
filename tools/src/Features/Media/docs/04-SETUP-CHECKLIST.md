# Media — Setup Checklist

---

## Column mode (single file) — most common

```
[ ] Entity table has column (image, pdf_path, icon, …)
[ ] Model uses HasMedia trait
[ ] Model: protected array $cmsMediaFields = ['field_name']
[ ] Model: public const MEDIA_FOLDER = 'folder/name'
[ ] Model: $cmsMediaSet = 'set_name' (optional — for thumbnails)
[ ] Create Sync{Feature}{Image|Pdf|Icon}Data
[ ] DTO uses InteractsWithMediaRules::getSingleMediaRules('field')
[ ] Create Sync{Image|Pdf|Icon}Action using HandlesSingleMedia
[ ] Service method calls action
[ ] Controller route (POST multipart)
[ ] Response DTO exposes getMediaObject() or _url accessor
[ ] config/cms_media.php image_sets matches $cmsMediaSet (optional)
[ ] php artisan storage:link (if using public disk)
```

---

## Polymorphic mode (gallery / attachments)

```
[ ] Model uses HasMedia (mediaList relation available)
[ ] media + media_translations tables migrated
[ ] Create SyncFilesAction using HandlesMultipleMedia
[ ] DTO uses getGalleryRules('attachments') if delete support needed
[ ] Pass deletedIds array when removing gallery items
[ ] Response loads mediaList relation
[ ] Optional: morph map for Media API owner_type
```

---

## Standalone Media API (optional)

```
[ ] Owner model uses HasMedia
[ ] Morph map registered for owner_type alias
[ ] Frontend uses /api/{owner_type}/{owner_id}/media routes
[ ] List uses ?fields= for response pruning
```

---

## Verify after setup

```http
# Upload
POST /api/blogs/1/sync-image  (multipart: image)

# Delete
POST /api/blogs/1/sync-image  (delete_image=1)

# Read
GET /api/blogs/1  → check image / image_url in response

# Gallery (if applicable)
POST /api/legislation/1/sync-files  (attachments[])

# Media API (if applicable)
GET /api/blog/1/media
POST /api/blog/1/media  (file)
DELETE /api/blog/1/media/5
```

---

## Required vs optional

| Item | Column mode | Polymorphic |
|------|:-----------:|:-----------:|
| HasMedia trait | ✅ | ✅ |
| $cmsMediaFields | ✅ | Optional |
| MEDIA_FOLDER | ✅ | ✅ |
| DB column on entity | ✅ | ❌ |
| HandlesSingleMedia | ✅ | Optional |
| HandlesMultipleMedia | ❌ | ✅ |
| image_sets config | Optional | Optional |

---

## See also

- [../README.md](../README.md)
- [02-BACKEND-INTEGRATION.md](./02-BACKEND-INTEGRATION.md)

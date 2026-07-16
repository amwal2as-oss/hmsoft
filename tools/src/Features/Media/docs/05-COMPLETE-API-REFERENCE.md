# Media — Complete API Reference

Full reference for traits, services, DTOs, and API endpoints.

---

## Traits

### HasMedia

| Method / accessor | Description |
|-------------------|-------------|
| `mediaList()` | `morphMany(Medium)` ordered by sort_number |
| `getMediaDisk()` | Disk name (`$cmsMediaDisk` or config) |
| `getMediaObject($field)` | `{ url, thumb, medium, srcset }` |
| `{field}_url` | Magic accessor — public URL |
| `{field}_url_{suffix}` | Magic accessor — variant URL |
| `{field}_object` | Magic accessor — calls getMediaObject |
| `purgeAssociatedMedia()` | Delete all column + polymorphic files (on model delete) |

**Model properties:**

| Property | Type | Description |
|----------|------|-------------|
| `$cmsMediaFields` | `string[]` | Column names for media accessors |
| `$cmsMediaSet` | `string` | Key in `cms_media.image_sets` |
| `$cmsMediaDisk` | `string` | Per-model filesystem disk |
| `$cmsPlaceholder` | `string` | Override placeholder URL |
| `$cmsNoPlaceholder` | `bool` | Skip placeholder when empty |

---

### HandlesSingleMedia

```php
protected function syncSingleImage(
    Model $model,
    $file = null,              // UploadedFile|string|null
    string $field = 'image',
    bool $deleteImage = false,
    ?string $folder = null,
    ?string $sizeSet = null,
    ?string $disk = null,
): string; // 'uploaded' | 'deleted' | 'unchanged'
```

**Behavior:**
- If `$file` provided → upload (replaces existing)
- If `$deleteImage` and no file → delete
- Column exists → update model column
- Column missing → create `media` row via MediaService

---

### HandlesMultipleMedia

```php
protected function syncMultipleMedia(
    Model $model,
    array $files = [],
    string $field = 'gallery',
    array $deletedIds = [],
    ?string $folder = null,
): void;
```

---

### InteractsWithMediaRules

| Method | Returns |
|--------|---------|
| `getSingleMediaRules($field, $maxSize?)` | `{field}`, `delete_{field}` rules |
| `getGalleryRules($field)` | array upload + `deleted_{field}_ids` rules |
| `getMediaMetadataRules($prefix?)` | locales/title/alt for polymorphic metadata |

---

## MediaUploader facade

```php
MediaUploader::upload(UploadedFile $file, string $directory, ?string $disk, ?string $sizeSet): string
MediaUploader::deleteFile(?string $path, ?string $disk): bool
MediaUploader::deleteFiles(array $paths, ?string $disk): void
MediaUploader::getUrl(Model $model, string $field): string
MediaUploader::resolveActualUrl(Model $model, string $field): ?string
MediaUploader::getPlaceholder(Model $model, string $field): string
```

---

## MediaService

```php
$service = app(MediaService::class);

$service->list(string $ownerId, string $ownerType): array;
$service->store(Model $owner, StoreMediaData $data): Medium;
$service->storeBulk(Model $owner, StoreBulkMediaData $data): Collection;
$service->show(Medium $medium): Medium;
$service->update(Model $owner, Medium $medium, UpdateMediaData $data): Medium;
$service->updateAll(Model $owner, UpdateAllMediaData $data): Collection;
$service->delete(Model $owner, Medium $medium): bool;
$service->deleteBulk(Model $owner, BulkDeleteMediaData $data): bool;
```

---

## DTOs

### StoreMediaData

| Field | Type | Required |
|-------|------|----------|
| `file` | File \| URL string | ✅ |
| `is_default` | bool | Optional |
| `media_type` | string | Optional |
| `folder` | string | Optional |
| `locales` | array | Optional |
| `owner_id` | string | Auto from route |
| `owner_type` | string | Auto from route |

### Sync pattern DTOs (app-level)

```php
// Typical app DTO
public readonly ?bool $delete_image;
public readonly mixed $image;
// rules: getSingleMediaRules('image')
```

### MediaData (response)

| Field | Description |
|-------|-------------|
| `id` | Media row ID |
| `file_path` | Storage path or URL |
| `file_url` | Resolved public URL |
| `file_name` | Original name |
| `mime_type` | MIME or `link` |
| `media_type` | Slot name |
| `is_default` | Default flag |
| `sort_number` | Sort order |
| `translations` | Locale metadata map |

---

## REST API reference

Base: **`/api/{owner_type}/{owner_id}/media`**

### GET / — List

Query: DynamicFilters params (`page`, `perPage`, `filters`, `fields`, …)

Response:
```json
{
  "data": [ { "id": 1, "file_url": "...", "media_type": "gallery" } ],
  "pagination": { "current_page": 1, "total": 5 }
}
```

### POST / — Store

Multipart fields: `file`, `is_default`, `media_type`, `folder`, `locales[*]`

### POST /bulk — Bulk store

Body: `{ media: [{ file, media_type, is_default }, ...], folder }`

### POST /{medium} — Update

Metadata + optional file replacement.

### DELETE /{medium} — Destroy

### DELETE /bulk-delete

Body: `{ ids: [1, 2, 3] }`

### POST /bulk-update

Body: `{ media: [{ id, sort_number, locales, ... }] }`

---

## FileOrUrl rule

Valid values:
- `UploadedFile` (valid upload)
- `string` passing `filter_var($value, FILTER_VALIDATE_URL)`

Error message: `media::validation.file_or_url`

---

## Config reference (`cms_media`)

```php
return [
    'disk' => env('MEDIA_DISK', 'public'),
    'max_image_dimension' => env('MEDIA_MAX_IMAGE_DIMENSION', 4096),
    'models' => [
        'medium' => Medium::class,
        'translation' => MediumTranslation::class,
    ],
    'placeholders' => [
        'default' => 'assets/images/placeholder.png',
        'models' => ['user' => '...'],
        'fields' => ['icon' => '...'],
    ],
    'image_sets' => [
        'default' => [
            'thumb'  => ['width' => 150, 'height' => 150],
            'medium' => ['width' => 600, 'height' => null],
        ],
    ],
];
```

---

## syncSingleImage return values

| Status | Condition |
|--------|-----------|
| `uploaded` | New file saved |
| `deleted` | File removed via delete flag |
| `unchanged` | No file, no delete flag |

---

## Supported upload types

| Extension | Processing |
|-----------|------------|
| jpg, jpeg, png, bmp, webp | WebP conversion + optional variants |
| svg | Stored raw (not processed as raster) |
| pdf, doc, zip, … | Stored raw via `storeRawFile()` |
| URL string | Stored in path column / file_path as link |

---

## Dependencies

- Laravel Filesystem
- Intervention Image (GD driver)
- Spatie Laravel Data
- HMsoft Translations (Medium metadata)
- HMsoft DynamicFilters (list endpoint)
- HMsoft Response (CmsResponse)

---

## See also

- [../README.md](../README.md)
- [00-ANALYSIS-AND-NOTES.md](./00-ANALYSIS-AND-NOTES.md)

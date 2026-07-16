# Media — Documentation Index

> **GitHub main doc:** [../README.md](../README.md)

The **Media** feature handles file upload, storage, URL generation, and polymorphic media management.

| Capability | Main classes |
|------------|--------------|
| **Column storage** (single file on model) | `HasMedia`, `HandlesSingleMedia` |
| **Polymorphic storage** (`media` table) | `MediaService`, `HandlesMultipleMedia` |
| **Upload engine** | `MediaUploadService`, `MediaUploader` facade |
| **REST API** | `MediaController` |

---

## Two storage modes

| Mode | When | Setup |
|------|------|-------|
| **Column** | Table has `image` / `pdf_path` column | `HasMedia` + `SyncImageAction` |
| **Polymorphic** | No column / gallery | `mediaList()` + `HandlesMultipleMedia` or Media API |

---

## Documentation files

| File | Audience | Description |
|------|----------|-------------|
| [../README.md](../README.md) | **Everyone** | Main doc — setup, usage, all use cases |
| [00-ANALYSIS-AND-NOTES.md](./00-ANALYSIS-AND-NOTES.md) | Backend devs | Known issues & recommendations |
| [01-BACKEND-ARCHITECTURE.md](./01-BACKEND-ARCHITECTURE.md) | Backend devs | How the code works |
| [02-BACKEND-INTEGRATION.md](./02-BACKEND-INTEGRATION.md) | Backend devs | Step-by-step integration |
| [03-FRONTEND-GUIDE.md](./03-FRONTEND-GUIDE.md) | Frontend devs | Upload forms & display |
| [04-SETUP-CHECKLIST.md](./04-SETUP-CHECKLIST.md) | Backend devs | Printable checklist |
| [05-COMPLETE-API-REFERENCE.md](./05-COMPLETE-API-REFERENCE.md) | Backend devs | Full API & trait reference |

---

## Quick start

**Model:**

```php
use HasMedia;
public const MEDIA_FOLDER = 'blogs';
protected array $cmsMediaFields = ['image'];
```

**Sync action:**

```php
$this->syncSingleImage($blog, $file, 'image', $delete, Blog::MEDIA_FOLDER);
```

**Response:**

```php
'image' => $blog->getMediaObject('image'),
```

See [../README.md](../README.md) for complete examples.

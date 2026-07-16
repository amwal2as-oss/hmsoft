# Media — Backend Integration Guide

Step-by-step guide to add media upload to a Laravel feature module.

> **Full reference:** [../README.md](../README.md) | **Checklist:** [04-SETUP-CHECKLIST.md](./04-SETUP-CHECKLIST.md)

---

## Choose your storage mode

| Your case | Mode | Guide section |
|-----------|------|---------------|
| One image/PDF/icon column on entity table | **Column** | [Single file setup](#single-file-column-mode) |
| Multiple attachments, no column | **Polymorphic** | [Gallery setup](#gallery-polymorphic-mode) |
| Generic media CRUD from CMS | **Media API** | [Standalone API](#standalone-media-api) |

---

## Single file (column mode)

Used by: Blog, News, Decree, Sector, Service, Statistic, User profile, etc.

### Step 1 — Database column

Ensure entity table has the column:

```php
$table->string('image')->nullable();
// or
$table->string('pdf_path')->nullable();
```

### Step 2 — Model

```php
use HMsoft\Tools\Features\Media\Traits\HasMedia;

class Blog extends Model
{
    use HasMedia;

    public const MEDIA_FOLDER = 'blogs';

    protected array $cmsMediaFields = ['image'];
    public string $cmsMediaSet = 'blog_items'; // optional
}
```

For PDF field:

```php
protected array $cmsMediaFields = ['pdf_path'];
public const MEDIA_FOLDER = 'decrees/pdfs';
```

### Step 3 — Sync DTO

```php
namespace App\Features\Blog\Blog\Data;

use HMsoft\Tools\Features\Media\Traits\InteractsWithMediaRules;
use Spatie\LaravelData\Data;

class SyncBlogImageData extends Data
{
    use InteractsWithMediaRules;

    public function __construct(
        public readonly ?bool $delete_image = null,
        public readonly mixed $image = null,
    ) {}

    public static function rules(): array
    {
        return array_merge(
            ['image' => ['required_without:delete_image']],
            self::getSingleMediaRules('image'),
        );
    }
}
```

**Include in Store DTO** (create + upload in one request):

```php
class StoreBlogData extends Data
{
    use InteractsWithMediaRules;

    public static function rules(): array
    {
        return array_merge(
            [ /* other rules */ ],
            self::getSingleMediaRules('image'),
        );
    }
}
```

### Step 4 — Sync action

```php
namespace App\Features\Blog\Blog\Actions;

use App\Features\Blog\Blog\Data\SyncBlogImageData;
use App\Features\Blog\Blog\Models\Blog;
use HMsoft\Tools\Features\Media\Traits\HandlesSingleMedia;

class SyncImageAction
{
    use HandlesSingleMedia;

    public function execute(Blog $blog, SyncBlogImageData $data): array
    {
        $mediaStatus = $this->syncSingleImage(
            model: $blog,
            file: $data->image ?? null,
            field: 'image',
            deleteImage: (bool) ($data->delete_image ?? false),
            folder: Blog::MEDIA_FOLDER,
        );

        return [
            'model'        => $blog->refresh(),
            'media_status' => $mediaStatus,
        ];
    }
}
```

### Step 5 — Service + controller

```php
// BlogService
public function syncImage(Blog $blog, SyncBlogImageData $data): array
{
    return app(SyncImageAction::class)->execute($blog, $data);
}

// BlogController
public function syncImage(Blog $blog, SyncBlogImageData $data)
{
    $result = $this->blogService->syncImage($blog, $data);
    return CmsResponse::success(
        message: __('blog::messages.updated_successfully'),
        data: BlogData::fromModel($result['model']),
    );
}
```

### Step 6 — Response DTO

```php
class BlogData extends BaseData
{
    public function __construct(
        public ?array $image,
        public readonly ?string $image_url,
        // ...
    ) {}

    public static function fromModel(Blog $blog): self
    {
        return new self(
            image: $blog->getMediaObject('image'),
            image_url: $blog->image_url,
            // ...
        );
    }
}
```

---

## Gallery (polymorphic mode)

Used by: Legislation attachments, Complaint files, Suggestion files.

### Step 1 — Model (no column needed)

```php
class Legislation extends Model
{
    use HasMedia;
    public const MEDIA_FOLDER = 'legislation/files';
}
```

### Step 2 — Sync action

```php
use HMsoft\Tools\Features\Media\Traits\HandlesMultipleMedia;

class SyncFilesAction
{
    use HandlesMultipleMedia;

    public function execute(
        Legislation $legislation,
        ?array $files,
        array $deletedIds = [],
    ): void {
        $this->syncMultipleMedia(
            model: $legislation,
            files: $files ?? [],
            field: 'attachment',
            deletedIds: $deletedIds,
            folder: Legislation::MEDIA_FOLDER,
        );
    }
}
```

### Step 3 — DTO with gallery rules

```php
class SyncLegislationFilesData extends Data
{
    use InteractsWithMediaRules;

    public static function rules(): array
    {
        return self::getGalleryRules('attachments');
    }
}
```

### Step 4 — Read files in response

```php
// Eager load
$legislation->load('mediaList');

// Map to MediaData or simple array
'files' => $legislation->mediaList->map(fn ($m) => [
    'id'       => $m->id,
    'url'      => $m->file_url,
    'name'     => $m->file_name,
    'mime'     => $m->mime_type,
]),
```

---

## Standalone Media API

For generic CMS media manager on any `HasMedia` model.

### Register morph map (recommended)

```php
// AppServiceProvider::boot()
Relation::enforceMorphMap([
    'blog'  => \App\Features\Blog\Blog\Models\Blog::class,
    'news'  => \App\Features\News\News\Models\News::class,
]);
```

### API endpoints

```http
GET    /api/blog/5/media
POST   /api/blog/5/media
POST   /api/blog/5/media/bulk
POST   /api/blog/5/media/{id}
DELETE /api/blog/5/media/{id}
DELETE /api/blog/5/media/bulk-delete
```

---

## Provider & migrations

Registered in `bootstrap/providers.php`:

```php
HMsoft\Tools\Features\Media\Providers\MediaServiceProvider::class,
```

Migrations load automatically. Publish if needed:

```bash
php artisan vendor:publish --tag=cms_media-migrations
php artisan migrate
```

---

## Configure image sets (recommended)

Add to `config/cms_media.php` or merge in service provider:

```php
'image_sets' => [
    'blog_items' => [
        'thumb'  => ['width' => 150, 'height' => 150],
        'medium' => ['width' => 600, 'height' => null],
    ],
],
```

---

## Complete flow (Blog image sync)

```
POST /api/blogs/3/sync-image  (multipart: image=file)

1. SyncBlogImageData validates (FileOrUrl)
2. SyncImageAction::execute()
3. HandlesSingleMedia::syncSingleImage()
   - deleteSingleImage() if replacing
   - MediaUploader::upload() → blogs/2024-01-01-xyz.webp
   - blogs.image = path
4. BlogData::fromModel() → image_object with url/thumb/srcset
5. CmsResponse::success()
```

---

## Project examples

| Feature | Field | Folder | Pattern |
|---------|-------|--------|---------|
| Blog | `image` | `blogs` | SyncImageAction |
| Decree | `pdf_path` | `decrees/pdfs` | SyncPdfAction |
| Statistic | `icon` | `statistics` | SyncIconAction |
| NewsGallery | `media_path` | gallery folder | SyncMediaAction |
| Legislation | polymorphic | `legislation/files` | SyncFilesAction |
| User | `image` | user folder | UpdateProfileAction |

---

## Troubleshooting

| Problem | Check |
|---------|-------|
| Upload returns unchanged | File not in request? `delete_image` without file? |
| Column not updated | Column exists on table? Field name matches? |
| 404 on image URL | `storage:link`? Correct disk? |
| No thumbnails | `image_sets` + `$cmsMediaSet` configured? |
| Polymorphic row not created | Column accidentally exists on table? |

---

## See also

- [03-FRONTEND-GUIDE.md](./03-FRONTEND-GUIDE.md)
- [05-COMPLETE-API-REFERENCE.md](./05-COMPLETE-API-REFERENCE.md)

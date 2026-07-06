```python
markdown_content = """# Media Feature Integration & Usage Guide

This documentation provides an exhaustive overview and technical guide for using the **Media Feature**, refactored into a Domain-Driven Design (DDD) Plug-and-Play architecture mirroring the FAQ component structure. It details how the Media feature decouples file uploads, management, and tracking across all common engineering use cases in Laravel enterprise applications.

---

## 1. Core Architecture Overview

The Media feature is designed as an isolated, modular plug-and-play component. It cleanly separates concerns using:
- **Data Transfer Objects (DTOs)** via `Spatie\\LaravelData\\Data` to encapsulate and validate inputs.
- **Dedicated Actions** to isolate specific business logic processing (Creation, Single Updates, Bulk Updates, Deletion).
- **A Unified Service layer** acting as the coordinator.
- **Reusable Traits** enabling rapid model integration.

### Component Map
- `App\\Features\\Media\\Actions\\`: Contains specialized discrete actions (`CreateAction`, `UpdateAction`, `DeleteAction`, `UpdateBulkAction`, `GetListAction`).
- `App\\Features\\Media\\Data\\`: Contains specialized data transfer objects handling pipelines and rules.
- `App\\Features\\Media\\Models\\`: Outlines the polymorphic `Medium` and its corresponding `MediumTranslation`.
- `App\\Features\\Media\\Traits\\`: Incorporates integration helpers (`HasMedia`, `HandlesSingleMedia`, `HandlesMultipleMedia`, `InteractsWithMediaRules`).

---

## 2. Built-In Validation & Validation Rules

### `FileOrUrl` Custom Validation Rule
The feature comes with a smart `FileOrUrl` rule. It allows inputs to be either a valid uploaded binary file (`Illuminate\\Http\\UploadedFile`) or a valid absolute URL string (e.g., external CDN asset, YouTube link, or cloud-hosted resource).


```

````text
File media_feature_documentation.md written successfully.

```php
use App\\Features\\Media\\Rules\\FileOrUrl;

$rules = [
    'image' => ['required', new FileOrUrl()],
];

````

---

## 3. Use Case 1: Direct Table Column (Single Media Field)

Use this approach when an entity has a dedicated image/file column directly in its own migration table (e.g., a category image or user avatar path) but you want to leverage smart URL resolution, automatic cleanup, and easy uploading traits.

### A. Database Migration Example

```php
Schema::create('categories', function (Blueprint $table) {
    $table->id();
    $table->string('slug');
    $table->string('image')->nullable(); // Holds the string path relative to storage path
    $table->timestamps();
});

```

### B. Model Configuration

Apply the `HasMedia` trait and define the explicit media fields in the `$cmsMediaFields` array property.

```php
namespace App\\Models;

use App\\Features\\Media\\Traits\\HasMedia;
use Illuminate\\Database\\Eloquent\\Model;

class Category extends Model
{
    use HasMedia;

    protected $fillable = ['slug', 'image'];

    /**
     * Identify fields that store media paths for automatic accessor mapping.
     */
    protected array $cmsMediaFields = ['image'];
}

```

### C. Creating / Updating inside a Controller or Action

Apply the `HandlesSingleMedia` trait within your business layer to handle creation or in-place replacement flawlessly.

```php
namespace App\\Features\\Shop\\Actions;

use App\\Models\\Category;
use App\\Features\\Media\\Traits\\HandlesSingleMedia;

class SaveCategoryAction
{
    use HandlesSingleMedia;

    public function execute(array $data, ?Category $category = null): Category
    {
        $category = $category ?? Category::create(['slug' => $data['slug']]);

        // Automatically uploads binary file or saves URL string path.
        // Also deletes the old file automatically if an existing path is present in $category->image.
        if (isset($data['image'])) {
            $this->syncSingleImage(
                model: $category,
                file: $data['image'],
                field: 'image',
                shouldDelete: true // Replaces and cleans up the previous file instantly
            );
        }

        return $category;
    }
}

```

### D. Asset URL Retrieval

The `HasMedia` trait overrides model attribute lookups. Appending `_url` dynamically evaluates and returns the full qualified asset path or the placeholder fallback asset config.

```php
$category = Category::find(1);

// Outputs full absolute path: [https://yourdomain.com/storage/categories/image_filename.jpg](https://yourdomain.com/storage/categories/image_filename.jpg)
echo $category->image_url;

// Supports thumbnail suffixes out of the box if configured
echo $category->image_url_thumb;

```

---

## 4. Use Case 2: Polymorphic Single Media (Stored in `media` Table)

Use this scenario when you do not want to alter the entity table structure with image columns, or want to decouple your entities completely by storing single media assets in the dedicated shared polymorphic `media` table instead.

### A. Model Configuration

Simply add `HasMedia` but **exclude** the field from `$cmsMediaFields`.

```php
namespace App\\Models;

use App\\Features\\Media\\Traits\\HasMedia;
use Illuminate\\Database\\Eloquent\\Model;

class Brand extends Model
{
    use HasMedia;

    protected $fillable = ['name'];
}

```

### B. Uploading / Replacing Assets via Trait

The `syncSingleImage` method automatically intercepts that the entity table lacks a column named after the field, and delegates storage to the polymorphic `media` table under a distinct `media_type`.

```php
namespace App\\Features\\Shop\\Actions;

use App\\Models\\Brand;
use App\\Features\\Media\\Traits\\HandlesSingleMedia;

class SaveBrandAction
{
    use HandlesSingleMedia;

    public function execute(Brand $brand, $fileInput): void
    {
        // Creates or overrides a single row in the polymorphic `media` table where media_type = 'logo'
        $this->syncSingleImage(
            model: $brand,
            file: $fileInput,
            field: 'logo',
            shouldDelete: true
        );
    }
}

```

### C. Asset Access via Relationship

```php
$brand = Brand::with('mediaList')->find(1);

// Fetch specific polymorphic single media item
$logo = $brand->cmsMedia()->where('media_type', 'logo')->first();
echo $logo->file_url;

```

---

## 5. Use Case 3: Polymorphic Media Galleries (Multiple Files)

Ideal for complex multi-image structures like product photo galleries, document sets, or multilingual slider attachments where each file requires localized titles, descriptions, and custom sort orders.

### A. Model Configuration

```php
namespace App\\Models;

use App\\Features\\Media\\Traits\\HasMedia;
use Illuminate\\Database\\Eloquent\\Model;

class Product extends Model
{
    use HasMedia;

    protected $fillable = ['title', 'sku'];
}

```

### B. Syncing Galleries in Bulk via Trait

Use the `HandlesMultipleMedia` trait to synchronize attached galleries, delete target photos by ID, and upload new incoming sets efficiently.

```php
namespace App\\Features\\Shop\\Actions;

use App\\Models\\Product;
use App\\Features\\Media\\Traits\\HandlesMultipleMedia;

class UpdateProductGalleryAction
{
    use HandlesMultipleMedia;

    public function execute(Product $product, array $requestData): void
    {
        $this->syncMultipleMedia(
            model: $product,
            files: $requestData['gallery_files'] ?? [], // Array of files/URLs
            field: 'product_gallery',
            deletedIds: $requestData['removed_image_ids'] ?? [], // Cleans up selected records from disk and DB
            folder: 'products/gallery'
        );
    }
}

```

### C. Direct Interaction via API Controller (Zero-Hardcoding Pipeline)

Because the routes utilize a flexible, automated structure `{owner_type}/{owner_id}/media`, you can manage a product's gallery directly via dedicated API endpoints.

#### Sample JSON Payload for Bulk Creation (`POST /api/products/45/media`)

This endpoint directly targets the refactored `MediaController@store` action using `StoreMediaData` to support multi-lingual metadata arrays seamlessly:

```json
{
    "media": [
        {
            "file": "[https://cdn.example.com/assets/promo_image.jpg](https://cdn.example.com/assets/promo_image.jpg)",
            "media_type": "product_gallery",
            "is_default": true,
            "locales": [
                {
                    "locale": "ar",
                    "title": "الصورة الرئيسية للمنتج",
                    "alt": "حذاء رياضي مريح"
                },
                {
                    "locale": "en",
                    "title": "Product Main Image",
                    "alt": "Comfortable running shoe"
                }
            ]
        }
    ]
}
```

---

## 6. Smart Updating & In-Place Replacement (In a Single Request)

The refactored `UpdateAction` completely eliminates the overhead of sending two decoupled requests (one for deletion and one for upload) from frontend frameworks like Vue or React.

When a file field is passed into `POST /api/{owner_type}/{owner_id}/media/{medium_id}`, the action orchestrates the following flow automatically:

1. Validates the incoming entity asset via `UpdateMediaData` and the `FileOrUrl` rule.
2. Identifies if the existing model maps to an absolute file path on local/cloud disks or an external text URL link.
3. Completely purges the stale file asset from the underlying system storage using `MediaUploader::deleteFile()`.
4. Uploads the newly initialized file stream or stores the revised raw link text string.
5. Dynamically sniffs the incoming file's `Mime Type` to re-classify and map the record's target `media_type` (`image`, `video`, `audio`, or `file`).
6. Persists metadata records across active multi-lingual translation tables synchronously.

### Code Sample of the Update Process:

```php
// Handled internally inside App\\Features\\Media\\Actions\\UpdateAction:
if ($data->file) {
    // 1. Purge physical disk footprints safely
    if ($medium->mime_type !== 'link' && $medium->file_path) {
        MediaUploader::deleteFile("{$owner->getMorphClass()}/{$owner->id}/media/{$medium->file_path}");
    }

    // 2. Stream new file asset online & evaluate Mime Type mapping
    $path = MediaUploader::upload(file: $data->file, directory: $folder);
    $medium->update([
        'file_path'  => $path,
        'mime_type'  => $data->file->getMimeType(),
        'media_type' => str_starts_with($mimeType, 'image/') ? 'image' : 'file',
    ]);
}

```

---

## 7. API Routing Reference Matrix

All media endpoints operate cleanly under an implicit, dynamically evaluated polymorphic prefix route handler:

| HTTP Verb  | Route Path                                       | Action Controller            | Scope / Purpose                                                                                             |
| ---------- | ------------------------------------------------ | ---------------------------- | ----------------------------------------------------------------------------------------------------------- |
| **GET**    | `/api/{owner_type}/{owner_id}/media`             | `MediaController@index`      | Lists all associated media files for the target entity with pagination.                                     |
| **POST**   | `/api/{owner_type}/{owner_id}/media`             | `MediaController@store`      | Uploads single/multiple assets attached with optional multi-lingual titles and alt text.                    |
| **POST**   | `/api/{owner_type}/{owner_id}/media/bulk-update` | `MediaController@updateAll`  | Batch updates sort ordering, default switches, or metadata maps.                                            |
| **DELETE** | `/api/{owner_type}/{owner_id}/media/bulk-delete` | `MediaController@deleteBulk` | Mass purges a distinct collection of media records by an array of unique IDs.                               |
| **GET**    | `/api/{owner_type}/{owner_id}/media/{medium}`    | `MediaController@show`       | Retreives specific single file model detailed with its active translation lines.                            |
| **POST**   | `/api/{owner_type}/{owner_id}/media/{medium}`    | `MediaController@update`     | **In-place asset substitution:** Safely replaces old files/URLs and localized descriptors.                  |
| **DELETE** | `/api/{owner_type}/{owner_id}/media/{medium}`    | `MediaController@destroy`    | Deletes a standalone file, handles automatic storage path purging, and assigns new default image candidate. |

---

## 8. Automatic Garbage Collection & Cleanup

When any model utilizing `HasMedia` is deleted via Eloquent models, the feature hooks natively into the model lifecycle boot sequence:

```php
public static function bootHasMedia()
{
    static::deleted(fn($model) => $model->cleanupMediaFiles());
}

```

This guarantees zero dangling storage leaks. It instantly searches through both direct columns (`$cmsMediaFields`) and matching database rows inside the `media` polymorphic table, freeing disk storage space on active file hosts cleanly.
"""

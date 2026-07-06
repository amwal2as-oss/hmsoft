# HMsoft Media Feature Toolkit — Architecture & Integration Guide

This documentation provides an authoritative, comprehensive technical reference for the **HMsoft Media Feature Toolkit** (`HMsoft\Tools\Features\Media`). Built on **Domain-Driven Design (DDD)** and **Clean Architecture** principles, this plug-and-play package decouples file uploads, multi-lingual asset metadata, polymorphic association, image resizing, and storage lifecycle management across scalable Laravel enterprise applications.

---

## 1. Core Architecture & Component Roster

The toolkit isolates media operations into granular, single-responsibility layers:

* **Data Transfer Objects (DTOs):** Powered by `Spatie\LaravelData\Data` to sanitize, validate, and structure incoming payloads (`StoreMediaData`, `StoreBulkMediaData`, `UpdateMediaData`, `UpdateAllMediaData`, `MediaData`, `BulkDeleteMediaData`).
* **Discrete Actions:** Execute isolated business logic pipelines (`CreateAction`, `CreateBulkAction`, `UpdateAction`, `UpdateBulkAction`, `DeleteAction`, `GetListAction`, `StreamExternalFileAction`).
* **Unified Service Layer:** Coordinate execution flows between controllers and domain actions (`MediaService`).
* **Integration Traits:** Provide instant capabilities to Eloquent models and business layer actions (`HasMedia`, `HandlesSingleMedia`, `HandlesMultipleMedia`, `ExtractsOwnerFromRoute`).

---

## 2. Dual-Context Execution Pipeline (API Routes vs. Programmatic Calls)

A foundational design pillar of the toolkit is its ability to seamlessly operate across two execution contexts without throwing missing-parameter exceptions:

1. **RESTful API Route Context:**
   When triggered via REST endpoints matching `api/{owner_type}/{owner_id}/media`, the `ExtractsOwnerFromRoute` trait automatically intercepts the HTTP route parameters inside `prepareForPipeline()` and injects them into `StoreMediaData` or `StoreBulkMediaData`.
2. **Internal Programmatic Context:**
   When media operations are invoked programmatically within domain services (e.g., saving attachments inside `ComplaintService` or icons inside `AboutUsService`), the caller explicitly passes `$model->getKey()` and `$model->getMorphClass()`. The DTO constructors safely accept `Optional|string|null`, defaulting to explicit inputs over route extraction when available.

---

## 3. Built-In Validation Layers

### The `FileOrUrl` Custom Rule
Unlike standard Laravel validation rules that force developers to separate binary uploads from URL strings, the `FileOrUrl` rule intelligently validates both inputs seamlessly:
* **Uploaded Binary Files:** Validates instances of `Illuminate\Http\UploadedFile`.
* **Remote Absolute URLs:** Validates external URL strings (e.g., S3 cloud assets, CDN links, or streaming links).

```php
use HMsoft\Tools\Features\Media\Rules\FileOrUrl;

$rules = [
    'attachment' => ['required', new FileOrUrl()],
];
```

4. Use Case 1: Direct Table Column (Single Media Field)
Use this pattern when an Eloquent entity explicitly maintains a file path column directly in its own database table (e.g., an icon_path column on an about_us table or avatar on users), while still requiring automated image scaling, old asset cleanup, and responsive URL accessors.

A. Database Migration

```PHP
Schema::create('about_us', function (Blueprint $table) {
    $table->id();
    $table->string('type', 50)->index();
    $table->string('icon_path', 255)->nullable(); // Holds disk relative path
    $table->timestamps();
});
```


B. Model Configuration
Implement HasMedia, register the target column in $cmsMediaFields, and optionally specify the thumbnail scale set configured in config/cms_media.php:

```php
namespace App\Features\AboutUs\Models;

use HMsoft\Tools\Features\Media\Traits\HasMedia;
use Illuminate\Database\Eloquent\Model;

class AboutUs extends Model
{
    use HasMedia;

    protected $guarded = ['id'];

    // 1. Identify direct database columns storing media paths
    protected array $cmsMediaFields = ['icon_path'];

    // 2. Map to defined thumbnail dimensions set in config/cms_media.php
    public string $cmsMediaSet = 'about_us_icons';
}
```


C. Programmatic Sync via Action Layer
Inject the HandlesSingleMedia trait into your action. Calling syncSingleImage() automatically uploads new binaries or URL strings and immediately purges any stale physical file from disk before saving:

```php
namespace App\Features\AboutUs\Actions;

use App\Features\AboutUs\Models\AboutUs;
use HMsoft\Tools\Features\Media\Traits\HandlesSingleMedia;

class SyncIconAction
{
    use HandlesSingleMedia;

    public function execute(AboutUs $aboutUs, mixed $iconInput, bool $shouldDelete = false): void
    {
        $this->syncSingleImage(
            model: $aboutUs,
            file: $iconInput,        // UploadedFile instance or URL string
            field: 'icon_path',      // Direct column name
            deleteImage: $shouldDelete,
            folder: 'about_us/icons'
        );
    }
}
```


D. Magic Accessors & Frontend Resolution
The HasMedia trait overrides standard Eloquent attribute access to dynamically generate secure public URLs and structured objects:

```php
$aboutUs = AboutUs::find(1);

// 1. Get full absolute URL string
echo $aboutUs->icon_path_url;

// 2. Get customized image thumbnail suffix (e.g., _thumb)
echo $aboutUs->icon_path_url_thumb;

// 3. Get rich JSON representation with responsive srcsets
// Returns: ['url' => '...', 'thumb' => '...', 'medium' => '...', 'srcset' => '...']
return response()->json($aboutUs->icon_path_object);
```

5. Use Case 2: Polymorphic Single Media (Stored in media Table)
Use this pattern when you want to store a single asset (such as a corporate logo, brand watermark, or PDF contract) without altering the host entity's database schema.

A. Model Configuration
Apply HasMedia but omit the field name from $cmsMediaFields:

```php
namespace App\Models;

use HMsoft\Tools\Features\Media\Traits\HasMedia;
use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
    use HasMedia;
}
```

B. Programmatic Sync via Trait
When syncSingleImage() is executed, it inspects the host model's database schema. Detecting that the column does not exist on the table, it automatically delegates storage to the shared polymorphic media table under media_type:

```php
$this->syncSingleImage(
    model: $company,
    file: $request->file('contract'),
    field: 'contract_doc', // Stored as media_type = 'contract_doc' in `media` table
    deleteImage: false
);
```


C. Relationship Retrieval
```php
$company = Company::with('mediaList')->find(1);

// Retrieve the polymorphic item via mediaList() relationship
$contract = $company->mediaList()->where('media_type', 'contract_doc')->first();
echo $contract?->file_url;
```

6. Use Case 3: Polymorphic Media Galleries (Multiple Files)
Ideal for multi-file attachments, e-commerce product galleries, or document submissions requiring sorting (sort_number), default item flags (is_default), and multi-lingual translations (title, alt, short_description).

A. Programmatic Bulk Sync (HandlesMultipleMedia)
Leverage syncMultipleMedia() inside domain actions (such as complaint attachments) to handle concurrent deletions and new file synchronizations while explicitly passing owner_id and owner_type:

```php
namespace App\Features\Complaint\Actions;

use App\Features\Complaint\Models\Complaint;
use HMsoft\Tools\Features\Media\Traits\HandlesMultipleMedia;

class SyncComplaintFilesAction
{
    use HandlesMultipleMedia;

    public function execute(Complaint $complaint, ?array $files, array $deletedIds = []): void
    {
        $this->syncMultipleMedia(
            model: $complaint,
            files: $files ?? [],           // Array of UploadedFile or URL strings
            field: 'attachment',           // Target media_type in `media` table
            deletedIds: $deletedIds,       // Media IDs to purge from disk & database
            folder: 'complaints/files'
        );
    }
}
```


B. Transforming via Resource Layer (MediaData)
When serializing relationships inside Spatie Resource DTOs, transform the loaded mediaList collection directly:
```php
use HMsoft\Tools\Features\Media\Data\MediaData;

// Inside ComplaintResourceData::fromModel():
'files' => $model->relationLoaded('mediaList') || $model->mediaList
    ? MediaData::collect($model->mediaList)->toArray()
    : null,
```


7. RESTful API Routing Matrix
All endpoints operate under the dynamically evaluated polymorphic route prefix:

api/{owner_type}/{owner_id}/media

⚠️ Architectural Requirement: The {owner_type} URI parameter must strictly match the Morph Map alias enforced in your application's Service Provider via Relation::enforceMorphMap() (e.g., 'complaint', 'about_us', 'product'), never raw fully-qualified class namespaces.

HTTP Verb,Endpoint URI,Controller Action,Description / Execution Scope
GET,/api/{owner_type}/{owner_id}/media,MediaController@index,Lists all polymorphic media for the target entity with dynamic sorting & pagination.
POST,/api/{owner_type}/{owner_id}/media,MediaController@store,Uploads a single polymorphic media asset with optional localized translations.
POST,/api/{owner_type}/{owner_id}/media/bulk,MediaController@storeBulk,Batch uploads multiple files or URL streams along with localized metadata.
POST,/api/{owner_type}/{owner_id}/media/bulk-update,MediaController@updateAll,"Batch modifies sort ordering, default statuses, and translation lines across records."
DELETE,/api/{owner_type}/{owner_id}/media/bulk-delete,MediaController@deleteBulk,Mass deletes a collection of media IDs from both storage disks and database.
GET,/api/{owner_type}/{owner_id}/media/{medium},MediaController@show,Retrieves detailed JSON data for a standalone media record including loaded translations.
POST,/api/{owner_type}/{owner_id}/media/{medium},MediaController@update,In-Place Substitution: Replaces underlying binary files/URLs and synchronizes translations.
DELETE,/api/{owner_type}/{owner_id}/media/{medium},MediaController@destroy,"Purges a standalone media record, deletes physical files, and reassigns defaults."


Sample Bulk Creation Payload (POST /api/products/45/media/bulk)
When submitting files via REST clients (e.g., Postman), use multipart/form-data for binary uploads or raw JSON for external CDN streams:

```json
{
  "media": [
    {
      "file": "[https://cdn.example.com/assets/promo_banner.jpg](https://cdn.example.com/assets/promo_banner.jpg)",
      "media_type": "product_gallery",
      "is_default": true,
      "locales": [
        {
          "locale": "ar",
          "title": "الصورة الإعلانية للمنتج",
          "alt": "عرض أمامي للمنتج بوضوح عالٍ"
        },
        {
          "locale": "en",
          "title": "Product Promotional Banner",
          "alt": "High resolution front view of the product"
        }
      ]
    }
  ]
}
```


8. In-Place Asset Substitution & Mime-Type Inference
When an update request is issued to an existing asset record via POST /api/{owner_type}/{owner_id}/media/{medium_id}, UpdateAction executes an intelligent, zero-downtime substitution lifecycle:

Validates the incoming payload while stripping omitted Optional properties.

Identifies whether the existing record maps to a physical disk file or a remote text URL.

Purges the stale physical file from storage instantly using MediaUploader::deleteFile() to prevent disk storage leaks.

Uploads the new binary file or updates the raw URL string.

Sniffs the incoming file's MIME type via $file->getMimeType() and re-classifies the database media_type attribute (image, video, audio, or file).

Synchronizes multi-lingual translations (title, alt, short_description) across the media_translations table.

9. Automated Garbage Collection Lifecycle
To guarantee zero dangling disk footprints or orphaned records upon entity deletion, HasMedia hooks directly into the Eloquent model lifecycle sequence:

```php
public static function bootHasMedia(): void
{
    static::deleting(function ($model) {
        $model->purgeAssociatedMedia();
    });

    if (method_exists(static::class, 'forceDeleting')) {
        static::forceDeleting(function ($model) {
            $model->purgeAssociatedMedia();
        });
    }
}
```

Execution Flow Upon $model->delete():
Direct Column Cleanup: Iterates over all properties declared in $cmsMediaFields. If any file path string is present, it invokes MediaUploader::deleteFile() to purge the physical asset from disk.

Polymorphic Cascade Cleanup: Retrieves all attached records via $model->mediaList(). For each item, it deletes the primary file and all generated thumbnail scale sets (_thumb, _medium, etc.) from storage, purges associated translations, and removes the records from the database.
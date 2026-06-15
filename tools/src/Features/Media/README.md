## Installation & Core Configuration

### Step 1: Register Service Provider

Ensure the package provider is registered within your application bootstrap (`bootstrap/providers.php` or `config/app.php` depending on Laravel version):

```php
HMsoft\\Tools\\Features\\Media\\Providers\\MediaServiceProvider::class
```

Step 2: Publish Assets & Configurations
Execute the Artisan console command to publish the database schema migrations and the primary configuration file:

```Bash
php artisan vendor:publish --tag="cms_media-config"
php artisan vendor:publish --tag="cms_media-migrations"
```

Step 3: Run Migrations
Run your migrations to generate the underlying schema:

```Bash
php artisan migrate
```

Step 4: Review Configuration File (config/hmsoft-media.php)This file controls the default behaviors, underlying drivers, fallback assets, and thumbnail scaling rules across the package:

```PHP
<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default Torage Storage Disk
    |--------------------------------------------------------------------------
    | The default filesystem disk mapped from config/filesystems.php where
    | uploaded media assets will be streamed.
    */
    'disk' => env('MEDIA_DISK', 'public'),

    /*
    |--------------------------------------------------------------------------
    | Image Fallback Placeholders
    |--------------------------------------------------------------------------
    | Global asset path fallbacks rendered dynamically by accessors when an
    | entity or specific image field lacks a physical path asset.
    */
    'placeholders' => [
        'default' => 'assets/images/placeholder.png',
        'models' => [
            'user' => 'assets/images/avatar-placeholder.png',
            'product' => 'assets/images/product-placeholder.png',
        ],
        'fields' => [
            'icon' => 'assets/images/default-icon.png',
            'cover_image' => 'assets/images/default-cover.png',
        ]
    ],

    /*
    |--------------------------------------------------------------------------
    | Image Sizing Sets (Thumbnails / Scale Sets)
    |--------------------------------------------------------------------------
    | Defines configuration arrays for automatic image manipulation.
    | When a specific sizing set is targeted, Intervention Image handles
    | scaling and writes dedicated suffixed versions to the disk.
    */
    'image_sets' => [
        'gallery' => [
            'thumb'  => ['width' => 150, 'height' => 150],
            'medium' => ['width' => 800, 'height' => null], // Aspect ratio preserved
        ],
        'avatar' => [
            'small' => ['width' => 64, 'height' => 64],
        ]
    ]
];
```

3. Core Architectural ComponentsA. The Validation Layer: FileOrUrl Custom RuleUnlike standard Laravel rules that force a hard split between binary fields and text links, the FileOrUrl rule accepts an Illuminate\\Http\\UploadedFile binary instance or a valid absolute external URL string (e.g., cloud assets, YouTube streams, external CDN resources).

```PHP
use HMsoft\\Tools\\Features\\Media\\Rules\\FileOrUrl;

$rules = [
    'attachment' => ['required', new FileOrUrl()],
];
```

B. Context-Agnostic Context Loading: ExtractsOwnerFromRouteDTO pipelines use this trait within prepareForPipeline to automatically intercept parameters from the active HTTP request route ({owner_type}/{owner_id}).Crucially, it uses structural fallback operators (??). If called programmatically within a console command, an asynchronous queue job, or a test factory where an active HTTP route context is entirely absent, it falls back to explicit data arrays without raising exceptions.

```PHP
$properties['owner_id']   = $properties['owner_id'] ?? $ownerData['owner_id'];
$properties['owner_type'] = $properties['owner_type'] ?? $ownerData['owner_type'];
```

4. Deep-Dive Integration Use CasesThe toolkit supports three primary structural patterns that cover all file and media management requirements in modern applications.Use Case 1: Direct Table Columns (Single Media Field)Use this approach when your entity table explicitly defines an image or file path column directly within its own database schema migration (e.g., avatar on a users table or image on a categories table), but you want smart accessors, automatic disk cleanup, and effortless trait-based streaming.1. Database Schema Setup

```PHP
Schema::create('categories', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->string('image')->nullable(); // Direct column holding the file path string
    $table->timestamps();
});
```

2. Model SetupImplement the HasMedia trait and define the explicit target columns inside the $cmsMediaFields protected array property. This tells the internal magic accessors which properties to monitor.

```PHP
namespace App\\Models;

use HMsoft\\Tools\\Features\\Media\\Traits\\HasMedia;
use Illuminate\\Database\\Eloquent\\Model;

class Category extends Model
{
    use HasMedia;

    protected $fillable = ['name', 'image'];

    /**
     * Map direct table columns to automatically intercept and trigger magic accessors.
     */
    protected array $cmsMediaFields = ['image'];
}
```

3. Uploading & In-Place File Replacement within an ActionInject the HandlesSingleMedia trait within your business action layer. The syncSingleImage method automatically streams the file, deletes any previously configured physical asset, updates the target attribute, and persists modifications.

```PHP
namespace App\\Actions;

use App\\Models\\Category;
use HMsoft\\Tools\\Features\\Media\\Traits\\HandlesSingleMedia;

class UpdateCategoryAction
{
    use HandlesSingleMedia;

    public function execute(Category $category, array $payload): Category
    {
        $category->update(['name' => $payload['name']]);

        if (isset($payload['image'])) {
            // Automatically uploads binary file or stores absolute string URL.
            // Instantly purges old physical files from disk if an asset path existed.
            $this->syncSingleImage(
                model: $category,
                file: $payload['image'], // Accepts UploadedFile or absolute URL string
                field: 'image',
                shouldDelete: true
            );
        }

        return $category;
    }
}
```

4. Magic Accessor Retrieval in the Frontend LayerThe HasMedia trait overrides standard model attribute lookups. Appending \_url or \_object suffixes activates
   programmatic resolution:

```PHP
$category = Category::find(1);

// 1. Get absolute public URL string (Resolves to Storage::disk()->url() or the raw URL)
echo $category->image_url;

// 2. Get customized image sizing suffixes (e.g., thumb)
echo $category->image_url_thumb;

// 3. Get rich JSON representation for API payloads containing standard formats and responsive srcsets
// Returns: ['url' => '...', 'thumb' => '...', 'medium' => '...', 'srcset' => '...']
return response()->json($category->image_object);
```

Use Case 2: Polymorphic Single Media (Stored in media Table)Use this approach when you want to store single explicit assets (such as a company logo or contract pdf) without modifying the host table schema, keeping entity migrations completely decoupled from media assets.1. Model ConfigurationSimply apply the HasMedia trait, but omit the field name from the $cmsMediaFields array.

```PHP
namespace App\\Models;

use HMsoft\\Tools\\Features\\Media\\Traits\\HasMedia;
use Illuminate\\Database\\Eloquent\\Model;

class Company extends Model
{
    use HasMedia;

    protected $fillable = ['company_name'];
}
```

2. Streaming via Trait ExecutionThe syncSingleImage method automatically checks if the host model's underlying database table contains a matching physical column name. Seeing that it lacks one, it handles storage inside the polymorphic shared media table instead, setting the structural media_type flag to your field name.

```PHP
namespace App\\Actions;

use App\\Models\\Company;
use HMsoft\\Tools\\Features\\Media\\Traits\\HandlesSingleMedia;

class SaveCompanyLogoAction
{
    use HandlesSingleMedia;

    public function execute(Company $company, mixed $fileInput): void
    {
        // Creates, maps, or replaces a single row inside the shared `media` table
        // where owner_id = $company->id, owner_type = 'companies', and media_type = 'logo'
        $this->syncSingleImage(
            model: $company,
            file: $fileInput,
            field: 'logo',
            shouldDelete: true
        );
    }
}
```

3. Data Querying via Polymorphic Access

```PHP
$company = Company::with('mediaList')->find(1);

// Isolate the polymorphic item
$logo = $company->mediaList()->where('media_type', 'logo')->first();
echo $logo->file_url;
```

Use Case 3: Polymorphic Media Galleries (Multiple Files)Ideal for rich multi-file attachments, such as e-commerce product image galleries, estate portfolios, or slider banners where each asset requires customizable sequential ordering (sort_number), default item selection (is_default), and multilingual translatable data.1. Model Setup

```PHP
namespace App\\Models;

use HMsoft\\Tools\\Features\\Media\\Traits\\HasMedia;
use Illuminate\\Database\\Eloquent\\Model;

class Product extends Model
{
    use HasMedia;

    protected $fillable = ['title', 'sku'];
}
```

2. Syncing Galleries in Bulk via TraitLeverage the HandlesMultipleMedia trait to synchronize extensive media arrays, cleanly purging targeted deletion sets while processing new file arrays concurrently.

```PHP
namespace App\\Actions;

use App\\Models\\Product;
use HMsoft\\Tools\\Features\\Media\\Traits\\HandlesMultipleMedia;

class SyncProductGalleryAction
{
    use HandlesMultipleMedia;

    public function execute(Product $product, array $data): void
    {
        $this->syncMultipleMedia(
            model: $product,
            files: $data['gallery_images'] ?? [],   // Array of binaries or links
            field: 'product_gallery',               // Mapped media_type
            deletedIds: $data['removed_photo_ids'] ?? [], // Cleans up database records and disk files
            folder: 'products/galleries'            // Disk storage sub-directory
        );
    }
}
```

5. In-Place Asset Substitution & Mime-Type Inference
   The toolkit's UpdateAction eliminates frontend complexity by resolving modifications in a single payload request. When a file modification is issued to an active asset record via POST /api/{owner_type}/{owner_id}/media/{medium_id}, the toolkit executes the following pipeline:

Validation & Extraction: Intercepts incoming inputs via UpdateMediaData filtering out any unpassed fields flagged as Spatie\\LaravelData\\Optional.

Physical Purging: Identifies if the stale record was an internal file asset. If so, it purges its disk footprint using MediaUploader::deleteFile($medium->file_path) to eliminate dead storage leaks.

Mime-Type Sniffing: If a raw binary upload is supplied, it extracts the mime-type via $file->getMimeType() and automatically categorizes the database media_type attribute column as image, video, audio, or file.

URL Adaptability: If a plain text URL string is passed instead, it immediately sets the record's database mime_type to link and maps the path to the raw text string directly.

Metadata Synchronization: Sequentially maps localized structural descriptions and titles into the media_translations database tables.

6. API Routing & Endpoint Matrix
   All endpoints leverage a dynamically evaluated polymorphic prefix structure (api/{owner_type}/{owner_id}/media).
   Method,Endpoint,Target Controller Action,Purpose / Execution Behavior

GET,/api/{owner_type}/{owner_id}/media,MediaController@index,Lists all associated media for an entity with pagination and filtering support.
POST,/api/{owner_type}/{owner_id}/media,MediaController@store,Uploads a single media asset; validates via StoreMediaData FormRequest.
POST,/api/{owner_type}/{owner_id}/media/bulk,MediaController@storeBulk,Batch uploads an array of mixed binaries or URL streams with localized translation rows.
POST,/api/{owner_type}/{owner_id}/media/bulk-update,MediaController@updateAll,"Batch modifies sort orders, updates default statuses, or adjusts structural metadata."
DELETE,/api/{owner_type}/{owner_id}/media/bulk-delete,MediaController@deleteBulk,Mass deletes a collection of media assets from both storage disks and the database.
GET,/api/{owner_type}/{owner_id}/media/{medium},MediaController@show,Fetches details for a single media record along with all registered translation lines.
POST,/api/{owner_type}/{owner_id}/media/{medium},MediaController@update,In-place asset substitution: Replaces underlying binary files/URLs and localized translations.
DELETE,/api/{owner_type}/{owner_id}/media/{medium},MediaController@destroy,"Purges a standalone media record, triggers disk cleanup, and reassigns defaults."

⚠️ Architectural Note: The {owner_type} parameter must match the Morph Alias registered inside your application's AppServiceProvider via Relation::enforceMorphMap(). Never pass raw namespace paths to the API layer.

7. Automated Garbage Collection LifecycleTo prevent dangling file assets or unlinked footprints, the HasMedia trait listens directly to the Eloquent model lifecycle boot sequence.

```PHP
public static function bootHasMedia()
{
    static::deleted(fn($model) => $model->cleanupMediaFiles());
}
```

Execution Flow:When any Eloquent model incorporating HasMedia executes a $model->delete() call:Direct Columns Inspection: It scans the properties configured inside $cmsMediaFields. If any file path string is found, it triggers MediaUploader::deleteFile() to purge it from the targeted filesystem storage disk.Polymorphic Rows Selection: It loads all records configured inside the shared media polymorphic table where owner_id and owner_type match the entity.Cascading Footprint Purging: For each related model, it deletes the database translation rows, triggers disk deletion for the main file and all corresponding scaled image thumbnails, and then deletes the database media row.8. Package Customization & Logic OverridingThe toolkit is designed to be fully extensible. You can easily override core models, actions, or services using Laravel's Service Container bindings.Customizing the Core Media ModelIf you need to add custom relations or helper methods to the Medium model:Create your own model extending the toolkit's base model:

```PHP
namespace App\\Models;

use HMsoft\\Tools\\Features\\Media\\Models\\Medium as BaseMedium;

class CustomMedium extends BaseMedium
{
    public function customExtraRelation()
    {
        return $this->hasOne(CustomMeta::class);
    }
}
```

Register your custom model in the published configuration file (config/hmsoft-media.php):

```PHP
'models' => [
    'medium' => \\App\\Models\\CustomMedium::class,
],
```

Overriding Business ActionsIf you want to completely change the upload logic (for example, uploading to a third-party API instead of local/cloud storage), extend the base action and re-bind it in your AppServiceProvider:

```PHP
namespace App\\Providers;

use Illuminate\\Support\ServiceProvider;
use HMsoft\\Tools\\Features\\Media\\Actions\\CreateAction;
use App\\CustomMedia\\CustomCreateAction;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Re-bind the package action to your custom execution class
        $this->app->bind(CreateAction::class, CustomCreateAction::class);
    }
}
```

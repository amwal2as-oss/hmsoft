2. Attribute Feature (EAV System)
   The Attribute feature provides a robust Entity-Attribute-Value (EAV) infrastructure, allowing fields (such as text, select dropdowns, checkboxes, etc.) to be created dynamically and bound onto any application domain model.

Basic Usage

1. Prepare your Domain Model
   Incorporate the HasAttributes trait into your target entity (e.g., Product, Post):

```php
<?php

namespace App\Features\Product\Models;

use Illuminate\Database\Eloquent\Model;
use HMsoft\Tools\Features\Attribute\Traits\HasAttributes;

class Product extends Model
{
    use HasAttributes;

    protected $table = 'products';
}
```

2. Synchronize EAV Values in your Pipeline
   When storing or updating your model payload, utilize the dedicated SyncNestedAttributesAction execution wrapper to handle casting and structural saving seamlessly:

```php
<?php

namespace App\Features\Product\Actions;

use App\Features\Product\Models\Product;
use HMsoft\Tools\Features\Attribute\Traits\SyncNestedAttributesAction;

class StoreProductAction
{
    public function execute(array $payload): Product
    {
        $product = Product::create($payload['basic_info']);

        // Sync EAV attributes array matching types (checkbox, translatable text, text fields)
        (new SyncNestedAttributesAction())->execute($product, $payload['attributes'] ?? []);

        return $product;
    }
}
```

How to Override Attributes Configuration
Scope Customization
The package routes are scoped dynamically through URLs (e.g., /api/product/attributes). The context validation rules automatically deduce scope matching. If you want to bypass or hardcode specific boundaries, override prepareForPipeline inside StoreAttributeData or UpdateAttributeData:

```php
public static function prepareForPipeline(array $properties): array
{
    // Force a specific immutable domain partition scope
    $properties['scope'] = 'e_commerce_products';
    return $properties;
}
```

EAV Value Parsing Type Modification
If you add specialized proprietary attribute field variations (e.g., markdown, color_picker), append the structural blueprint handling matrices within SyncNestedAttributesAction or extend DynamicValueCast.

3. Faq Feature (Polymorphic Contexts)
   The refined Faq feature supports highly flexible contextual polymorphic bindings executing flawlessly under three unified structural operational shapes.

Three Structural Shapes

Pattern,API Route Example,Description
Shape 1: General FAQs,GET /api/faqs,System-wide general FAQs (owner_type and owner_id are both null).
Shape 2: Entity Bound FAQs,GET /api/blogs/15/faqs,"FAQs associated with a specific entity record (owner_type = 'blog', owner_id = '15')."
Shape 3: Entity Type FAQs,GET /api/blogs/faqs,"General FAQs bound across an entire entity structure scope (owner_type = 'blog', owner_id = null)."

Basic Usage
Include the relation interface map using the explicit HasFaqs trait on target domain layers:

```php
<?php

namespace App\Features\Blog\Models;

use Illuminate\Database\Eloquent\Model;
use App\Features\Faq\Traits\HasFaqs;

class Blog extends Model
{
    use HasFaqs;
}
```

Syncing Inline Nested Elements via Parent Actions

```php
use App\Features\Faq\Actions\SyncNestedFaqsAction;

// Synchronizes and updates cascading translations automatically on nested structural requests
(new SyncNestedFaqsAction())->execute($blogModel, $request->input('faqs', []));
```

How to Override FAQ Dynamic Resolving
The core mechanism powering the three operational shapes relies on ExtractsOwnerFromRoute. If your application infrastructure utilizes specialized route model binding terms or unique prefixes, you can override how the package discovers entity anchors.

Overriding Route Owner Resolution
You can easily override the parsing strategy inside FaqController or the Data Transfer Objects by modifying getOwnerFromRoute:

```php
<?php

namespace App\Features\Faq\Traits;

trait ExtractsOwnerFromRoute
{
    public static function getOwnerFromRoute(): array
    {
        $request = request();

        // CUSTOM OVERRIDE: e.g., Resolving ownership header values or custom parameter matching
        if ($request->hasHeader('X-Custom-Faq-Scope')) {
            return [
                'owner_id'   => $request->header('X-Custom-Faq-Id'),
                'owner_type' => $request->header('X-Custom-Faq-Scope'),
            ];
        }

        // Default automated cascading route parameter detection fallback...
    }
}
```

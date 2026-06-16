# HMsoft Tools Package - Features Integration & Overriding Guide

This documentation provides a comprehensive architectural guide on how to integrate, use, and override the core features (`Translations`, `Attribute`, and `Faq`) within your Laravel application using Domain-Driven Design (DDD) principles.

---

## 1. Translations Feature

The `Translations` feature decouples structural localization from your main models. It automatically handles localized relation eager-loading, translatable records synchronization, and garbage collection for obsolete locales.

### Basic Usage

To make any Eloquent model support translations, implement the `Translatable` contract and use the `HasTranslations` trait:

```php
<?php

namespace App\Features\Feature\Models;

use Illuminate\Database\Eloquent\Model;
use HMsoft\Tools\Features\Translations\Contracts\Translatable;
use HMsoft\Tools\Features\Translations\Traits\HasTranslations;

class Feature extends Model implements Translatable
{
    use HasTranslations;

    protected $table = 'features';
    protected $guarded = ['id'];
}

```

```php

// Eager load the active locale translation automatically
$feature = Feature::with('translation')->first();

// Access the current translation safely
$title = $feature->translation?->title;

// Access all translations array
$allTranslations = $feature->translations;

```

Syncing Data in Actions/Services

```php
$localesData = [
    ['locale' => 'en', 'title' => 'High Performance', 'description' => 'Fast speed'],
    ['locale' => 'ar', 'title' => 'أداء عالي', 'description' => 'سرعة فائقة'],
];

$feature->syncTranslations($feature, $localesData);
```

How to Override Translations
If your localization schema deviates from package conventions (e.g., custom model naming or customized foreign keys), you can override the respective methods directly inside your main model.

```php
<?php

namespace App\Features\Feature\Models;

use Illuminate\Database\Eloquent\Model;
use HMsoft\Tools\Features\Translations\Contracts\Translatable;
use HMsoft\Tools\Features\Translations\Traits\HasTranslations;

class Feature extends Model implements Translatable
{
    use HasTranslations;

    /**
     * OVERRIDE: Custom Translation Model Class Name
     */
    public function getTranslationModelName(): string
    {
        return \App\Models\CustomLocalizedFeature::class;
    }

    /**
     * OVERRIDE: Custom Foreign Key Definition
     */
    public function getTranslationRelationKey(): string
    {
        return 'parent_feature_identifier_id';
    }
}
```

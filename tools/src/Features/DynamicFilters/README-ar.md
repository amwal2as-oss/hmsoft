# محرك الفلاتر والترتيب الديناميكي (Dynamic Filters & Sorting Engine)

هذا المحرك هو نظام متقدم جداً (Surgical Engine) للتعامل مع استعلامات قاعدة البيانات في Laravel. يقوم بإدارة الفلترة (Filtering)، الترتيب (Sorting)، جلب الأعمدة المحددة فقط (Surgical Selects)، وتحميل العلاقات (Eager Loading) بذكاء لمنع مشكلة `N+1`، مع دعمه للبحث العام والـ Magic Scopes.

---

## 🚀 1. التثبيت والتهيئة (Plug & Play)

لجعل أي مودل (Model) متوافقاً تماماً مع محرك الفلاتر، كل ما عليك فعله هو تطبيق العقد `AutoFilterable` واستخدام السمة `IsAutoFilterable`.

```php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use HMsoft\Tools\Features\DynamicFilters\Contracts\AutoFilterable;
use HMsoft\Tools\Features\DynamicFilters\Traits\IsAutoFilterable;

class Product extends Model implements AutoFilterable
{
    use IsAutoFilterable;

    protected $fillable = ['name', 'price', 'sku', 'is_active'];
}
السلوك الافتراضي (Default Behavior): بمجرد إضافة السمة أعلاه، سيقوم المحرك تلقائياً بقراءة أعمدة الجدول (مع استخدام Caching لمرة واحدة يومياً لتخفيف الضغط)، وسيجعل كل الأعمدة الحقيقية قابلة للفلترة، الترتيب، والبحث، مع استبعاد الحقول الحساسة (مثل password, remember_token) تلقائياً.

🛠️ 2. حالات الاستخدام والتخصيص (Use Cases)
المحرك مصمم ليكون مرناً جداً لتلبية أعقد متطلبات الـ Frontend. يمكنك التحكم في كل شيء عبر عمل (Override) للدوال المخصصة Extra.

الحالة الأولى: إضافة حقول فلترة وترتيب مخصصة (Custom Filters & Sorts)
إذا أردت السماح للـ Frontend بالفلترة أو الترتيب بناءً على حقول غير موجودة مباشرة في الجدول (مثل حقول العلاقات المتداخلة أو حقول JSON)، استخدم دوال الـ Extra:

PHP
    /**
     * حقول إضافية مسموح الفلترة بها
     */
    protected function getFilterableExtra(): array
    {
        return [
            'category.name',       // فلترة بناءً على اسم القسم (علاقة)
            'translations.title',  // فلترة عبر جدول الترجمات
            'options->color'       // فلترة داخل حقل JSON
        ];
    }

    /**
     * حقول إضافية مسموح الترتيب بها
     */
    protected function getSortableExtra(): array
    {
        return [
            'category.sort_index', // ترتيب بناءً على حقل في جدول مرتبط
            'price_after_discount' // ترتيب بحقل محسوب (يتطلب Magic Scope)
        ];
    }
الحالة الثانية: الأسماء المستعارة (Field Selection Map / Aliasing)
أحياناً يرسل الـ Frontend اسم حقل مختلف (Alias) لأسباب أمنية أو تنظيمية، وتحتاج لربطه بالمسار الحقيقي في قاعدة البيانات.

PHP
    /**
     * خريطة ربط الأسماء المستعارة بالحقول الحقيقية
     */
    protected function getFieldSelectionMapExtra(): array
    {
        return [
            'author_name' => 'user.profile.full_name', // مسار علاقة متداخل
            'status'      => 'is_active',              // تغيير اسم عمود مباشر
            'brand'       => 'manufacturer_id'
        ];
    }
الحالة الثالثة: حماية العلاقات والـ Eager Loading
لمنع استعلامات N+1 عندما يطلب المستخدم جلب حقول من علاقات (مثلاً: fields=id,name,category.title)، يجب أن تخبر المحرك بأسماء العلاقات المسموح بتحميلها (Eager Load). المحرك سيقوم تلقائياً بعمل with لهذه العلاقات.

PHP
    /**
     * العلاقات المسموح بتحميلها ديناميكياً
     */
    protected function getRelationshipsExtra(): array
    {
        return [
            'category' => 'category',
            'tags'     => 'tags',
            'creator'  => 'user', // إذا طلب الـ Frontend 'creator'، سيتم تحميل العلاقة 'user'
        ];
    }
الحالة الرابعة: تبعيات الحقول الوهمية (Virtual Fields Dependencies) 🔥
هذه أقوى ميزة في المحرك: عندما يحتوي المودل على حقل وهمي (Accessor) مثل getProfitAttribute()، ويطلب الـ Frontend جلب هذا الحقل fields=profit، سيفشل الاستعلام لأن profit ليس عموداً في الداتا بيز.
هنا تخبر المحرك بالأعمدة الحقيقية التي يجب جلبها من الداتا بيز لكي تعمل دالة الحقل الوهمي بشكل صحيح!

PHP
    /**
     * تبعيات الحقول المحسوبة من أعمدة وعلاقات
     */
    protected function getVirtualFieldsDependenciesExtra(): array
    {
        return [
            // لحساب الربح، نحتاج أن يجلب المحرك السعر والتكلفة من قاعدة البيانات
            'profit'        => ['price', 'cost'],

            // لجلب صورة الكاتب، نحتاج جلب الـ ID، وتحميل علاقة الـ media الخاصة بالكاتب
            'author_avatar' => [
                'columns'   => ['author_id'],
                'relations' => ['author.media']
            ],
        ];
    }
الحالة الخامسة: البحث العام (Global Search)
يتحكم هذا الجزء في كيفية عمل ميزة البحث الموحد (Global Search) عند تمرير بارامتر globalFilter.

PHP
    // 1. الحقول النصية العادية التي سيتم البحث فيها (LIKE %...%)
    public function defineGlobalSearchBaseAttributes(): array
    {
        return ['name', 'sku'];
    }

    // 2. البحث عالي الأداء باستخدام الفهرس الكامل (MATCH AGAINST)
    public function defineFullTextSearchableAttributes(): array
    {
        return ['description']; // يتطلب أن يكون العمود FULLTEXT في MySQL
    }

    // 3. البحث داخل جداول العلاقات (تلقائياً يقوم المحرك بعمل whereHas أو whereHasMorph)
    public function defineGlobalSearchRelatedAttributes(): array
    {
        return [
            'category'     => ['name', 'slug'],
            'translations' => ['title']
        ];
    }
🎩 3. النطاقات السحرية (Magic Scopes) لتجاوز سلوك المحرك
إذا كان السلوك الافتراضي للمحرك (والذي يبني جمل Where و OrderBy تلقائياً) لا يغطي حالة استثنائية أو معقدة جداً، يمكنك التدخل جراحياً باستخدام الـ Magic Scopes.

يعتمد المحرك على تسمية (Naming Convention): scopeFilter[StudlyColumnName] أو scopeSort[StudlyColumnName].

مثال 1: فلترة مخصصة (Custom Filter)
لنفترض أن الـ Frontend يرسل فلتر {"id": "active_status", "value": true} وهذا الحقل ليس موجوداً فعلياً، بل هو عبارة عن منطق معقد:

PHP
    use Illuminate\Database\Eloquent\Builder;
    use HMsoft\Tools\Features\DynamicFilters\Data\ColumnFilterData;

    /**
     * سيقوم المحرك بتشغيل هذا الـ Scope تلقائياً بدلاً من السلوك الافتراضي
     * عندما يكون filter.id = active_status
     */
    public function scopeFilterActiveStatus(Builder $query, ColumnFilterData $filter)
    {
        if ($filter->value === true) {
            $query->whereNotNull('published_at')->where('is_banned', false);
        } else {
            $query->whereNull('published_at');
        }
    }
مثال 2: ترتيب مخصص بمعادلات (Custom Sort)
إذا أراد المستخدم الترتيب بناءً على "المسافة" (Distance) جغرافياً باستخدام استعلام SQL خام:

PHP
    /**
     * سيتم استدعاؤه عندما يكون sort.id = distance
     */
    public function scopeSortDistance(Builder $query, string $direction)
    {
        $lat = request()->header('X-Lat', 0);
        $lng = request()->header('X-Lng', 0);

        // المحرك سيحترم هذا الترتيب المخصص!
        $query->orderByRaw("ST_Distance(coordinates, POINT($lng, $lat)) $direction");
    }
🔧 4. الاستخدام خارج المودل (الاستدعاء في Controllers)
كيف يتم استدعاء وتشغيل هذا المحرك من داخل وحدات التحكم (Controllers)؟

PHP
namespace App\Http\Controllers;

use App\Models\Product;
use HMsoft\Tools\Features\DynamicFilters\Services\AutoFilterAndSortService;

class ProductController
{
    public function index()
    {
        // سطر واحد فقط يكفي لاستدعاء كل هذا السحر!
        $result = AutoFilterAndSortService::dynamicSearchFromRequest(
            model: Product::class,

            // يمكنك حقن شروط إضافية لا يمكن للـ Frontend تجاوزها (Security)
            extraOperation: function ($query) {
                $query->where('store_id', auth()->user()->store_id);
            },

            // خيار الكاش الممتاز: حفظ نتائج هذا الاستعلام لمدة 10 دقائق
            // (يتم توليد مفتاح الكاش تلقائياً بناءً على الفلاتر المدخلة)
            cacheDuration: 10
        );

        return response()->json($result);
    }
}
```

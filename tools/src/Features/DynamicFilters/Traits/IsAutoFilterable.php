<?php

namespace HMsoft\Tools\Features\DynamicFilters\Traits;

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Collection;

/**
 * Trait IsAutoFilterable
 * * EN: The advanced version that supports smart default behavior for filtering, sorting, and searching.
 * It provides advanced mechanisms to dynamically fetch table columns with caching, automatically exclude
 * sensitive fields, and support custom dynamic attributes.
 * * AR: النسخة المطورة التي تدعم السلوك الافتراضي الذكي للفلترة، الترتيب، والبحث.
 * توفر هذه السمة آليات متقدمة لجلب حقول الجدول ديناميكياً مع التخزين المؤقت، واستبعاد الحقول
 * الحساسة تلقائياً، بالإضافة إلى دعم الحقول الديناميكية (Custom Attributes).
 *
 * @package HMsoft\Tools\Traits\General
 */
trait IsAutoFilterable
{
    /**
     * EN: Runtime cache for table columns during the request lifecycle to reduce database queries.
     * AR: تخزين مؤقت لحقول الجداول أثناء دورة حياة الطلب لتقليل الضغط على قاعدة البيانات.
     * * @var array
     */
    protected static array $tableColumnsCache = [];

    /**
     * EN: Determine whether to automatically include all table columns (excluding sensitive ones).
     * AR: تحديد ما إذا كان يجب تضمين جميع حقول الجدول تلقائياً (باستثناء الحساسة).
     * * @var bool
     */
    protected bool $autoIncludeAllColumns = true;

    /**
     * EN: Define the primary key name for the model.
     * AR: تحديد اسم المفتاح الأساسي للموديل.
     * * @return string
     */
    public function definePrimaryKeyName(): string
    {
        return $this->getKeyName();
    }

    /* -----------------------------------------------------------------
     |  Filtering | الفلترة
     | ----------------------------------------------------------------- */

    /**
     * EN: Get all filterable attributes (Base + Extra).
     * AR: جلب جميع الحقول القابلة للفلترة (الأساسية + الإضافية).
     * * @return array
     */
    public function defineFilterableAttributes(): array
    {
        return array_unique(array_merge(
            $this->getFilterableBase(),
            $this->getFilterableExtra()
        ));
    }

    /**
     * EN: Get the base filterable attributes (Table columns + Custom Attributes).
     * AR: جلب الحقول الأساسية القابلة للفلترة (أعمدة الجدول + الحقول الديناميكية).
     * * @return array
     */
    protected function getFilterableBase(): array
    {
        // EN: 1. Automatically fetch real table columns | AR: 1. جلب أعمدة الجدول الحقيقية تلقائياً
        $columns = $this->getCachedTableColumns($this->getTable());

        // EN: 2. Fetch custom attributes marked as filterable | AR: 2. جلب الحقول الديناميكية المحددة كقابلة للفلترة
        $customFilters = [];
        // if (method_exists($this, 'getMorphClass')) {
        //     $customFilters = Attribute::ofScope($this->getMorphClass())
        //         ->where('is_filterable', true)
        //         ->pluck('id')
        //         ->map(fn($id) => 'attribute_' . $id)
        //         ->toArray();
        // }

        return array_merge($columns, $customFilters);
    }

    /**
     * EN: A hook for the model to add extra filterable attributes (e.g., relation fields).
     * AR: هوك مخصص للموديل لإضافة حقول فلترة إضافية (مثل حقول العلاقات).
     * * @return array
     */
    protected function getFilterableExtra(): array
    {
        // EN: Example: ['translations.title', 'category.name'] | AR: مثال
        return [];
    }

    /* -----------------------------------------------------------------
     |  Sorting | الترتيب
     | ----------------------------------------------------------------- */

    /**
     * EN: Get all sortable attributes (Base + Extra).
     * AR: جلب جميع الحقول القابلة للترتيب (الأساسية + الإضافية).
     * * @return array
     */
    public function defineSortableAttributes(): array
    {
        return array_unique(array_merge(
            $this->getSortableBase(),
            $this->getSortableExtra()
        ));
    }

    /**
     * EN: Get the base sortable attributes.
     * AR: جلب الحقول الأساسية القابلة للترتيب.
     * * @return array
     */
    protected function getSortableBase(): array
    {
        $columns = $this->getCachedTableColumns($this->getTable());

        $customSorts = [];
        // if (method_exists($this, 'getMorphClass')) {
        //     $customSorts = Attribute::ofScope($this->getMorphClass())
        //         ->where('is_sortable', true)
        //         ->pluck('id')
        //         ->map(fn($id) => 'attribute_' . $id)
        //         ->toArray();
        // }

        return array_merge($columns, $customSorts);
    }

    /**
     * EN: A hook for the model to add extra sortable attributes.
     * AR: هوك مخصص للموديل لإضافة حقول ترتيب إضافية.
     * * @return array
     */
    protected function getSortableExtra(): array
    {
        return [];
    }

    /* -----------------------------------------------------------------
     |  Field Selection Map | خريطة اختيار الحقول
     | ----------------------------------------------------------------- */

    /**
     * EN: Define the map of fields allowed to be selected from the database.
     * AR: تحديد خريطة الحقول المسموح بجلبها من قاعدة البيانات (Selects).
     * * @return array
     */
    public function defineFieldSelectionMap(): array
    {
        return array_merge(
            $this->getFieldSelectionMapBase(),
            $this->getFieldSelectionMapExtra()
        );
    }

    /**
     * EN: Get the base field selection map.
     * AR: جلب خريطة الحقول الأساسية.
     * * @return array
     */
    protected function getFieldSelectionMapBase(): array
    {
        $columns = $this->getCachedTableColumns($this->getTable());

        // EN: Map each column to itself as a default map | AR: ربط كل عمود بنفسه كخريطة افتراضية
        // ['id' => 'id', 'name' => 'name']
        return array_combine($columns, $columns);
    }

    /**
     * EN: A hook to map aliases to relation fields or computed fields.
     * AR: هوك مخصص لربط الأسماء المستعارة (Aliases) بحقول العلاقات أو الحقول المحسوبة.
     * * @return array
     */
    protected function getFieldSelectionMapExtra(): array
    {
        return [];
    }

    /* -----------------------------------------------------------------
     |  Relationships | العلاقات
     | ----------------------------------------------------------------- */

    /**
     * EN: Define the relationships allowed for eager loading.
     * AR: تحديد العلاقات المسموح بتحميلها (Eager Loading).
     * * @return array
     */
    public function defineRelationships(): array
    {
        return array_merge(
            $this->getRelationshipsBase(),
            $this->getRelationshipsExtra()
        );
    }

    /**
     * EN: The default allowed relationships across the Tools.
     * AR: العلاقات الافتراضية المسموح بها عبر النظام.
     * * @return array
     */
    protected function getRelationshipsBase(): array
    {
        return [
            'translations' => 'translations',
        ];
    }

    /**
     * EN: A hook for the model to define extra allowed relationships.
     * AR: هوك للموديل لتحديد علاقات إضافية مسموح بتحميلها.
     * * @return array
     */
    protected function getRelationshipsExtra(): array
    {
        return [];
    }

    /* -----------------------------------------------------------------
     |  Global Search | البحث العام
     | ----------------------------------------------------------------- */

    /**
     * EN: Define the base text-based fields included in the global search.
     * AR: تحديد الحقول الأساسية التي يشملها البحث العام المستند إلى النصوص.
     * * @return array
     */
    public function defineGlobalSearchBaseAttributes(): array
    {
        return $this->getCachedTableColumns($this->getTable());
    }

    /**
     * EN: Define the related fields included in the global search.
     * AR: تحديد الحقول المرتبطة (Relations) التي يشملها البحث العام.
     * * @return array
     */
    public function defineGlobalSearchRelatedAttributes(): array
    {
        return [];
    }

    /**
     * EN: Define the fields that support Full-Text search.
     * AR: تحديد الحقول التي تدعم البحث الفهرسي الكامل (Full-Text Search).
     * * @return array
     */
    public function defineFullTextSearchableAttributes(): array
    {
        return [];
    }

    /* -----------------------------------------------------------------
     |  New Suggested Functions | الدوال الإضافية المقترحة
     | ----------------------------------------------------------------- */

    /**
     * EN: Define fields that support Date Range filtering (useful for dashboards).
     * AR: تحديد الحقول التي تدعم الفلترة بناءً على نطاق زمني (مفيدة للوحات التحكم).
     * * @return array
     */
    public function defineDateFilterableAttributes(): array
    {
        return ['created_at', 'updated_at', 'deleted_at'];
    }

    /**
     * EN: Define relationships that can be searched within.
     * AR: تحديد العلاقات التي يمكن تطبيق فلاتر البحث داخلها.
     * * @return array
     */
    public function defineSearchableRelations(): array
    {
        // EN: Example: ['translations' => ['title', 'description']] | AR: مثال
        return [];
    }

    /* -----------------------------------------------------------------
     |  Tools Default Settings | إعدادات النظام الافتراضية
     | ----------------------------------------------------------------- */

    /**
     * EN: Define the default sorting applied in the dashboard when no sort criteria are passed.
     * AR: تحديد الترتيب الافتراضي المطبق في لوحة التحكم عند عدم تمرير معايير ترتيب.
     * * @return array
     */
    public function ToolsDefaultSorts(): Collection
    {
        return new Collection();
        // return [
        //     // 'created_at' => 'desc'
        // ];
    }

    /**
     * EN: Define the default filters always applied in the dashboard (e.g., hide archived).
     * AR: تحديد الفلاتر الافتراضية المطبقة دائماً في لوحة التحكم (مثل إخفاء المؤرشف).
     * * @return array
     */
    public function ToolsDefaultFilters(): array
    {
        return [];
    }


    /* -----------------------------------------------------------------
     |  Schema & Caching Helpers | مساعدات جلب الهيكلية والتخزين المؤقت
     | ----------------------------------------------------------------- */

    /**
     * EN: Get table columns with caching applied to exclude sensitive fields.
     * AR: جلب أعمدة الجدول مع تطبيق التخزين المؤقت لاستبعاد الحقول الحساسة.
     * * @param string $table EN: Table name | AR: اسم الجدول
     * @return array
     */
    public function getCachedTableColumns(string $table): array
    {
        if (!$this->autoIncludeAllColumns) {
            return [];
        }

        // EN: Use Runtime Cache to avoid calling Redis/File Cache repeatedly
        // AR: استخدام التخزين المؤقت الفوري لتجنب استدعاء الكاش الخارجي مراراً
        if (isset(self::$tableColumnsCache[$table])) {
            return self::$tableColumnsCache[$table];
        }

        $cacheKey = "schema_columns_{$table}";

        // EN: Cache the schema for a day to reduce DB queries
        // AR: تخزين الهيكلية في الكاش لمدة يوم لتقليل استعلامات قاعدة البيانات
        $finalColumns = Cache::remember($cacheKey, now()->addDay(), function () use ($table) {
            $excludedColumns = [
                'password',
                'remember_token',
                'api_token',
                'access_token',
                'secret_key',
                'credit_card',
                'ssn',
                'encrypted',
                'salt'
            ];

            $columns = Schema::getColumnListing($table);
            $filteredColumns = array_diff($columns, $excludedColumns);

            $extraColumns = $this->getAdditionalColumns($table);

            return array_unique(array_merge($filteredColumns, $extraColumns));
        });

        self::$tableColumnsCache[$table] = $finalColumns;

        return $finalColumns;
    }

    /**
     * EN: Get any additional columns not physically present in the table but treated as such.
     * AR: جلب أي أعمدة إضافية غير موجودة فعلياً في الجدول ولكن يجب التعامل معها كأعمدة.
     * * @param string $table
     * @return array
     */
    protected function getAdditionalColumns(string $table): array
    {
        return [];
    }

    /* -----------------------------------------------------------------
     |  Virtual Fields Dependencies | تبعيات الحقول الافتراضية
     | ----------------------------------------------------------------- */

    /**
     * EN: Define the database column dependencies for virtual (computed) fields.
     * This ensures that when a virtual field is requested, its required actual columns are selected from the DB.
     * AR: تحديد تبعيات الحقول الافتراضية (المحسوبة) من أعمدة قاعدة البيانات.
     * يضمن ذلك جلب الأعمدة الحقيقية المطلوبة من قاعدة البيانات عند طلب حقل افتراضي.
     * * @return array
     */
    public function defineVirtualFieldsDependencies(): array
    {
        return array_merge(
            $this->getVirtualFieldsDependenciesBase(),
            $this->getVirtualFieldsDependenciesExtra()
        );
    }

    /**
     * EN: Get the base virtual fields dependencies.
     * AR: جلب تبعيات الحقول الافتراضية الأساسية عبر النظام.
     * * @return array
     */
    protected function getVirtualFieldsDependenciesBase(): array
    {
        return [];
    }

    /**
     * EN: A hook for the model to define extra virtual fields dependencies.
     * AR: هوك مخصص للموديل لتحديد تبعيات إضافية للحقول الافتراضية.
     * * @return array
     */
    protected function getVirtualFieldsDependenciesExtra(): array
    {
        // EN: Example: ['full_name' => ['first_name', 'last_name']]
        // AR: مثال: إذا طُلب الاسم الكامل، يجب جلب الاسم الأول والأخير من قاعدة البيانات
        return [];
    }
}

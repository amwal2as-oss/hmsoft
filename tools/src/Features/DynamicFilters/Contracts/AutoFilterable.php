<?php

namespace HMsoft\Tools\Features\DynamicFilters\Contracts;

use HMsoft\Tools\Features\DynamicFilters\Data\ColumnFilterData;
use HMsoft\Tools\Features\DynamicFilters\Data\ColumnSortData;
use Illuminate\Support\Collection;

/**
 * [EN] Interface AutoFilterable
 *
 * This interface is the essential contract that a model must implement to become compatible
 * with the advanced `AutoFilterAndSortService`. It acts as a "control panel" for each model,
 * giving the developer explicit control over how the service can interact with its data and relationships.
 * ---
 * [AR] واجهة AutoFilterable
 *
 * هذه الواجهة هي العقد الأساسي الذي يجب على أي موديل تطبيقه ليصبح متوافقًا مع خدمة الفلترة
 * والفرز المتقدمة `AutoFilterAndSortService`. هي بمثابة "لوحة تحكم" لكل موديل، تمنح المطور
 * تحكمًا صريحًا في كيفية تفاعل الخدمة مع بياناته وعلاقاته.
 */
interface AutoFilterable
{
    /**
     * [EN] Defines the model's relationships that the service is allowed to join.
     * This is the gateway for all cross-table operations.
     * - **Key:** The public-facing API name for the relationship (e.g., 'categories').
     * - **Value:** The actual Eloquent method name in the model (e.g., 'categories', 'authorDetails').
     *
     * [AR] تحدد علاقات الموديل المسموح للخدمة بربطها (Join).
     * هذه هي البوابة لكل العمليات التي تتطلب جداول متعددة.
     * - **المفتاح (Key):** اسم العلاقة العام المستخدم في الـ API (مثل 'categories').
     * - **القيمة (Value):** اسم دالة العلاقة الفعلي في الموديل (مثل 'categories', 'authorDetails').
     *
     * @return array An associative array mapping API names to Eloquent method names.
     *
     * ### Example / مثال:
     * ```php
     * public function defineRelationships(): array
     * {
     * return [
     * 'categories' => 'categories',
     * 'author'     => 'authorDetails',
     * ];
     * }
     * ```
     */
    public function defineRelationships(): array;

    /**
     * [EN] Defines the security whitelist of attributes that are allowed for filtering.
     * The service will ignore any filter requests for attributes not in this list.
     * Use dot-notation for related attributes.
     *
     * [AR] تحدد القائمة البيضاء الأمنية للحقول المسموح بالفلترة عليها.
     * الخدمة ستتجاهل أي طلب فلترة لحقل غير موجود في هذه القائمة.
     * استخدم صيغة النقطة للوصول لحقول العلاقات.
     *
     * @return array A simple array of filterable attribute names.
     *
     * ### Example / مثال:
     * ```php
     * public function defineFilterableAttributes(): array
     * {
     * return [
     * 'is_active',
     * 'categories.id',
     * 'author.status',
     * ];
     * }
     * ```
     */
    public function defineFilterableAttributes(): array;

    /**
     * [EN] Defines the security whitelist of attributes that are allowed for sorting (ORDER BY).
     * This prevents unwanted or slow sorting on unindexed columns.
     *
     * [AR] تحدد القائمة البيضاء الأمنية للحقول المسموح بالفرز عليها (ORDER BY).
     * هذا يمنع الفرز غير المرغوب فيه أو البطيء على أعمدة غير مفهرسة.
     *
     * @return array A simple array of sortable attribute names.
     *
     * ### Example / مثال:
     * ```php
     * public function defineSortableAttributes(): array
     * {
     * return [
     * 'created_at',
     * 'translations.title',
     * ];
     * }
     * ```
     */
    public function defineSortableAttributes(): array;

    /**
     * [EN] Defines the map of API-friendly field names to their database column paths.
     * This acts as a translator for the `fields` API parameter, creating a clean abstraction layer.
     * - **Key:** The public API field name (e.g., 'title').
     * - **Value:** The database column name or a dot-notation path (e.g., 'translations.title').
     *
     * [AR] تحدد خريطة ربط أسماء الحقول الصديقة للـ API بمساراتها في قاعدة البيانات.
     * تعمل كمترجم للـ `fields` parameter في الـ API، مما ينشئ طبقة تجريد نظيفة.
     * - **المفتاح (Key):** اسم الحقل العام في الـ API (مثل 'title').
     * - **القيمة (Value):** اسم عمود قاعدة البيانات أو مسار بصيغة النقطة (مثل 'translations.title').
     *
     * @return array An associative array mapping API fields to database columns/paths.
     *
     * ### Example / مثال:
     * ```php
     * public function defineFieldSelectionMap(): array
     * {
     * return [
     * 'status'      => 'is_active',
     * 'title'       => 'translations.title',
     * 'author_name' => 'author.name',
     * ];
     * }
     * ```
     */
    public function defineFieldSelectionMap(): array;

    /**
     * [EN] Defines the list of columns on the **main model's table only** for the global search.
     *
     * [AR] تحدد قائمة الأعمدة الموجودة في **الجدول الأساسي للموديل فقط** للبحث الشامل.
     *
     * @return array A simple array of column names from the primary table.
     *
     * ### Example / مثال:
     * ```php
     * public function defineGlobalSearchBaseAttributes(): array
     * {
     * return ['slug', 'internal_code'];
     * }
     * ```
     */
    public function defineGlobalSearchBaseAttributes(): array;

    /**
     * [EN] Specifies the primary key column name for the model's table.
     * Crucial for the `GROUP BY` clause to prevent duplicate results after joins.
     *
     * [AR] تحدد اسم حقل المفتاح الأساسي لجدول الموديل.
     * ضرورية لتطبيق جملة `GROUP BY` بشكل صحيح لمنع تكرار النتائج بعد عمليات الربط.
     *
     * @return string The primary key column name, typically 'id'.
     *
     * ### Example / مثال:
     * ```php
     * public function definePrimaryKeyName(): string
     * {
     * return 'uuid'; // If the primary key is not 'id'
     * }
     * ```
     */
    public function definePrimaryKeyName(): string;

    /**
     * [EN] Defines the columns that support fast full-text search (MATCH AGAINST) in MySQL.
     *
     * [AR] تحدد الأعمدة التي تدعم محرك البحث الشامل السريع (MATCH AGAINST) في قاعدة البيانات.
     * * @return array A simple array of indexed full-text column names.
     */
    public function defineFullTextSearchableAttributes(): array;

    /**
     * [EN] Defines relationships and their specific columns to be included in the global search.
     * * [AR] تحدد العلاقات والأعمدة الخاصة بها التي يجب تضمينها في عملية البحث الشامل.
     * * @return array An associative array mapping relation paths to arrays of column names.
     * * ### Example / مثال:
     * ```php
     * public function defineGlobalSearchRelatedAttributes(): array
     * {
     * return [
     * 'translations' => ['title', 'description'],
     * 'brand'        => ['name']
     * ];
     * }
     * ```
     */
    public function defineGlobalSearchRelatedAttributes(): array;

    /**
     * [EN] Defines the default sorting applied to the model queries if no sorting is explicitly requested.
     * * [AR] تعريف الترتيب الافتراضي للموديل الذي يتم تطبيقه في حال لم يطلب المستخدم ترتيباً معيناً.
     * * @return Collection<int, ColumnSortData>|array
     * @example [['id' => 'id', 'desc' => false]]
     */
    public function ToolsDefaultSorts();

    /**
     * [EN] Defines the default filters applied to the model queries if no filters are explicitly requested.
     * * [AR] تعريف الفلاتر الافتراضية للموديل التي يتم تطبيقها في حال لم يرسل المستخدم فلاتر.
     * * @return Collection<int, ColumnFilterData>|array
     * @example [['id' => 'is_active', 'filterFn' => FilterFnsEnum::equals, 'value' => true]]
     */
    public function ToolsDefaultFilters();


    /**
     * [EN] Defines dependencies (relationships and physical database columns) required by virtual/computed fields.
     * This prevents N+1 query issues and ensures calculations have the necessary data when a virtual field is requested.
     * * [AR] تحدد الاعتماديات (العلاقات وأعمدة قاعدة البيانات الحقيقية) التي تحتاجها الحقول الوهمية/المحسوبة.
     * يمنع هذا أخطاء (N+1 Queries) ويضمن توافر البيانات اللازمة للعمليات الحسابية عند طلب حقل وهمي.
     * * @return array An associative array mapping virtual fields to their dependencies.
     * * ### Example / مثال:
     * ```php
     * public function defineVirtualFieldsDependencies(): array
     * {
     * return [
     * 'active_price' => [
     * 'relations' => ['tieredPrices'],
     * 'columns'   => ['price']
     * ]
     * ];
     * }
     * ```
     */
    public function defineVirtualFieldsDependencies(): array;


    /**
     * [EN] Defines the default sorting applied to the model queries in the CMS/Dashboard 
     * if no sorting is explicitly requested by the frontend.
     * * [AR] يحدد الترتيب الافتراضي المطبق على استعلامات الموديل في لوحة التحكم (CMS)
     * في حال لم يتم إرسال طلب ترتيب صريح من الواجهة الأمامية.
     *
     * @return array|iterable An array of objects defining the sort column and direction.
     *
     * ### Example / مثال:
     * ```php
     * public function cmsDefaultSorts(): array
     * {
     * return [
     * (object) ['id' => 'created_at', 'desc' => true]
     * ];
     * }
     * ```
     */
    public function cmsDefaultSorts(): array;

    /**
     * [EN] Defines the default filters applied to the model queries in the CMS/Dashboard 
     * if no filters are explicitly requested by the frontend.
     * * [AR] يحدد الفلاتر الافتراضية المطبقة على استعلامات الموديل في لوحة التحكم (CMS)
     * في حال لم يتم إرسال أي فلاتر صريحة من الواجهة الأمامية.
     *
     * @return array|iterable An array of objects defining the filter column, value, and condition.
     *
     * ### Example / مثال:
     * ```php
     * public function cmsDefaultFilters(): array
     * {
     * return [
     * 'is_active' => (object) [
     * 'value' => 1,
     * 'filterFn' => \HMsoft\Tools\Features\DynamicFilters\Enums\FilterFnsEnum::equals->value
     * ]
     * ];
     * }
     * ```
     */
    public function cmsDefaultFilters(): array;
}

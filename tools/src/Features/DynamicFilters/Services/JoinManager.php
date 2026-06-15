<?php

namespace HMsoft\Tools\Features\DynamicFilters\Services;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Str;

class JoinManager
{
    private array $aliases = [];
    private Builder $query;
    private Model $mainModel;
    private string $mainTableAlias;

    public function __construct(Builder $query, string $mainTableAlias)
    {
        $this->query = $query;
        $this->mainModel = $query->getModel();
        $this->mainTableAlias = $mainTableAlias;
    }

    public function getMainTableAlias(): string
    {
        return $this->mainTableAlias;
    }

    /**
     * Ensures that all relationships in a given path are joined to the query.
     * It handles nested relationships by joining them sequentially.
     * Example: 'categories.sector.translations'
     *
     * @param string $relationPath The dot-notation path of relationships.
     * @return string The final alias for the last table in the path.
     * @throws \Exception
     */
    public function ensureJoin(string $relationPath): string
    {
        if (isset($this->aliases[$relationPath])) {
            return $this->aliases[$relationPath];
        }

        $currentModel = $this->mainModel;
        $parentAlias = $this->mainTableAlias;
        $pathSegments = explode('.', $relationPath);
        $currentPath = '';

        foreach ($pathSegments as $relationName) {
            $currentPath = $currentPath ? "{$currentPath}.{$relationName}" : $relationName;

            if (isset($this->aliases[$currentPath])) {
                $parentAlias = $this->aliases[$currentPath];
                // Resolve the related model for the next iteration
                $eloquentMethod = Str::camel($relationName);
                if (method_exists($currentModel, $eloquentMethod)) {
                    $currentModel = $currentModel->$eloquentMethod()->getRelated();
                }
                continue;
            }

            // 💡 التقليم الذكي للـ Alias لمنع خطأ (Identifier name is too long) الذي يظهر إذا تجاوز 64 حرفاً
            $rawAlias = 't_' . Str::snake(str_replace('.', '_', $currentPath));
            // $alias = strlen($rawAlias) > 50
            //     ? substr($rawAlias, 0, 40) . '_' . substr(md5($currentPath), 0, 8)
            //     : $rawAlias;

            $alias = strlen($rawAlias) > 50
                ? substr($rawAlias, 0, 35) . '_' . substr(hash('crc32', $currentPath), 0, 8) . '_' . uniqid()
                : $rawAlias;

            $eloquentMethod = Str::camel($relationName);
            if (!method_exists($currentModel, $eloquentMethod)) {
                throw new \Exception("Relation '{$eloquentMethod}' does not exist on model " . get_class($currentModel));
            }

            /** @var Relation $relationObject */
            $relationObject = $currentModel->$eloquentMethod();
            $relatedTable = $relationObject->getRelated()->getTable();

            $this->performJoin($relationObject, $relatedTable, $alias, $parentAlias);

            $currentModel = $relationObject->getRelated();
            $parentAlias = $alias;
            $this->aliases[$currentPath] = $alias;
        }

        return $parentAlias;
    }
    // public function ensureJoin(string $relationPath): string
    // {
    //     if (isset($this->aliases[$relationPath])) {
    //         return $this->aliases[$relationPath];
    //     }

    //     $currentModel = $this->mainModel;
    //     $parentAlias = $this->mainTableAlias;
    //     $pathSegments = explode('.', $relationPath);
    //     $currentPath = '';

    //     foreach ($pathSegments as $relationName) {
    //         $currentPath = $currentPath ? "{$currentPath}.{$relationName}" : $relationName;

    //         if (isset($this->aliases[$currentPath])) {
    //             $parentAlias = $this->aliases[$currentPath];
    //             // Resolve the related model for the next iteration
    //             $eloquentMethod = Str::camel($relationName);
    //             if (method_exists($currentModel, $eloquentMethod)) {
    //                 $currentModel = $currentModel->$eloquentMethod()->getRelated();
    //             }
    //             continue;
    //         }

    //         $alias = 't_' . Str::snake(str_replace('.', '_', $currentPath));
    //         // Ensure alias is unique enough or manageable.
    //         // Ideally, handle excessively long aliases if DB has limits, but this suffices for most cases.

    //         $eloquentMethod = Str::camel($relationName);
    //         if (!method_exists($currentModel, $eloquentMethod)) {
    //             throw new \Exception("Relation '{$eloquentMethod}' does not exist on model " . get_class($currentModel));
    //         }

    //         /** @var Relation $relationObject */
    //         $relationObject = $currentModel->$eloquentMethod();
    //         $relatedTable = $relationObject->getRelated()->getTable();

    //         $this->performJoin($relationObject, $relatedTable, $alias, $parentAlias);

    //         $currentModel = $relationObject->getRelated();
    //         $parentAlias = $alias;
    //         $this->aliases[$currentPath] = $alias;
    //     }

    //     return $parentAlias;
    // }
    /**
     * Performs the appropriate database join based on the Eloquent relation type.
     */
    private function performJoin(Relation $relation, string $relatedTable, string $alias, string $parentAlias): void
    {
        // 💡 التحقق الذكي: هل الجدول المرتبط يستخدم الحذف المنطقي (SoftDeletes)؟
        $usesSoftDeletes = in_array(\Illuminate\Database\Eloquent\SoftDeletes::class, class_uses_recursive(get_class($relation->getRelated())));

        // 1. Handle MorphOne / MorphMany (Polymorphic Support)
        if ($relation instanceof MorphOne || $relation instanceof MorphMany) {
            $this->query->leftJoin(
                "{$relatedTable} as {$alias}",
                function ($join) use ($relation, $parentAlias, $alias, $usesSoftDeletes) {
                    $join->on("{$parentAlias}." . $relation->getLocalKeyName(), '=', "{$alias}." . $relation->getForeignKeyName());
                    $join->where("{$alias}." . $relation->getMorphType(), '=', $relation->getMorphClass());

                    if ($usesSoftDeletes) $join->whereNull("{$alias}.deleted_at"); // 🛡️ حماية الحذف

                    $this->injectExtraRelationWheres($join, $alias, $relation); // 💡 السحر هنا
                }
            );
        }
        // 2. Handle HasOne / HasMany
        elseif ($relation instanceof HasOne || $relation instanceof HasMany) {
            $this->query->leftJoin(
                "{$relatedTable} as {$alias}",
                function ($join) use ($relation, $parentAlias, $alias, $usesSoftDeletes) {
                    $join->on("{$parentAlias}." . $relation->getLocalKeyName(), '=', "{$alias}." . $relation->getForeignKeyName());

                    if ($usesSoftDeletes) $join->whereNull("{$alias}.deleted_at"); // 🛡️ حماية الحذف

                    $this->injectExtraRelationWheres($join, $alias, $relation); // 💡 السحر هنا
                }
            );
        }
        // 3. Handle BelongsTo
        elseif ($relation instanceof BelongsTo) {
            $this->query->leftJoin(
                "{$relatedTable} as {$alias}",
                function ($join) use ($relation, $parentAlias, $alias, $usesSoftDeletes) {
                    $join->on("{$parentAlias}." . $relation->getForeignKeyName(), '=', "{$alias}." . $relation->getOwnerKeyName());

                    if ($usesSoftDeletes) $join->whereNull("{$alias}.deleted_at"); // 🛡️ حماية الحذف

                    $this->injectExtraRelationWheres($join, $alias, $relation); // 💡 السحر هنا
                }
            );
        }
        // 4. Handle BelongsToMany (Pivot Table)
        elseif ($relation instanceof BelongsToMany) {
            $pivotTable = $relation->getTable();
            $pivotAlias = 'pivot_' . $alias;

            // Join Pivot
            $this->query->leftJoin(
                "{$pivotTable} as {$pivotAlias}",
                function ($join) use ($relation, $parentAlias, $pivotAlias) {
                    $join->on("{$parentAlias}." . $relation->getParentKeyName(), '=', "{$pivotAlias}." . $relation->getForeignPivotKeyName());

                    if ($relation instanceof MorphToMany) {
                        $join->where("{$pivotAlias}." . $relation->getMorphType(), $relation->getMorphClass());
                    }
                }
            );

            // Join Related Table
            $this->query->leftJoin(
                "{$relatedTable} as {$alias}",
                function ($join) use ($relation, $pivotAlias, $alias, $usesSoftDeletes) {
                    $join->on("{$pivotAlias}." . $relation->getRelatedPivotKeyName(), '=', "{$alias}." . $relation->getRelatedKeyName());

                    if ($usesSoftDeletes) $join->whereNull("{$alias}.deleted_at"); // 🛡️ حماية الحذف

                    $this->injectExtraRelationWheres($join, $alias, $relation); // 💡 السحر هنا
                }
            );
        } else {
            throw new \Exception("Unsupported relationship type for JoinManager: " . get_class($relation));
        }
    }

    /**
     * يحقن الشروط الإضافية المعرفة في العلاقة بشكل ديناميكي كامل
     * بغض النظر عن مدى تعقيدها (Nested, In, Null, etc.)
     */
    private function injectExtraRelationWheres($join, string $alias, Relation $relation): void
    {
        $baseQuery = $relation->getQuery()->getQuery();
        $wheres = $baseQuery->wheres;

        if (empty($wheres)) return;

        $originalTable = $relation->getRelated()->getTable();

        // 💡 استخراج الأعمدة الهيكلية (Structural) لتجاهلها (لأن Laravel يضيفها افتراضياً للعلاقات الفارغة)
        $ignoreColumns = $this->getStructuralColumnsToIgnore($relation);

        $this->applyWheresRecursively($join, $wheres, $alias, $originalTable, $ignoreColumns);
    }

    /**
     * يستخرج مفاتيح الربط الأساسية (مثل foreign_key و morph_type) لكي لا يتم حقنها كشروط Where
     */
    private function getStructuralColumnsToIgnore(\Illuminate\Database\Eloquent\Relations\Relation $relation): array
    {
        $columns = [];

        // 1. المفاتيح الأجنبية للعلاقات (HasOne, HasMany, MorphOne, MorphMany)
        // Laravel يضع شروطه الهيكلية دائماً على الـ Foreign Key
        if (method_exists($relation, 'getForeignKeyName')) {
            $columns[] = $relation->getForeignKeyName();
        }

        // 2. نوع الموديل في العلاقات المتعددة (Polymorphic)
        if (method_exists($relation, 'getMorphType')) {
            $columns[] = $relation->getMorphType();
        }

        // 3. مفتاح المالك في علاقة BelongsTo فقط
        // لأن Laravel في BelongsTo يبحث باستخدام الـ Owner Key
        if ($relation instanceof \Illuminate\Database\Eloquent\Relations\BelongsTo) {
            if (method_exists($relation, 'getOwnerKeyName')) {
                $columns[] = $relation->getOwnerKeyName();
            }
        }

        // تنظيف الأسماء من بادئة الجداول لضمان المقارنة الدقيقة
        return array_map(function ($col) {
            return \Illuminate\Support\Str::afterLast($col, '.');
        }, array_filter($columns));
    }

    /**
     * ناسخ ومحلل هيكلي (AST Parser) يحول شروط الـ Eloquent إلى Join clauses
     */
    private function applyWheresRecursively($join, array $wheres, string $alias, string $originalTable, array $ignoreColumns): void
    {
        foreach ($wheres as $where) {
            $type = $where['type'];
            $boolean = $where['boolean'] ?? 'and';

            // 1. معالجة الشروط المتداخلة (Nested Closures)
            if ($type === 'Nested') {
                $join->where(function ($subJoin) use ($where, $alias, $originalTable, $ignoreColumns) {
                    $this->applyWheresRecursively($subJoin, $where['query']->wheres, $alias, $originalTable, $ignoreColumns);
                }, null, null, $boolean);
                continue;
            }

            // 2. محاولة التقاط وتعديل اسم العمود
            $column = $where['column'] ?? null;
            $aliasedColumn = null;

            if (is_string($column)) {
                $cleanColumn = \Illuminate\Support\Str::afterLast($column, '.');

                // 🛡️ تجاهل الشروط الهيكلية التي يضيفها Laravel للعلاقة الفارغة
                if (in_array($cleanColumn, $ignoreColumns)) {
                    continue;
                }

                $aliasedColumn = "{$alias}.{$cleanColumn}";
            } elseif ($column instanceof \Illuminate\Database\Query\Expression) {
                // معالجة الأعمدة المكتوبة بـ DB::raw
                $rawString = str_replace("{$originalTable}.", "{$alias}.", $column->getValue());
                $aliasedColumn = \Illuminate\Support\Facades\DB::raw($rawString);
            }

            // إذا لم نستطع تحديد عمود آمن، والنوع ليس من الأنواع الخاصة، نتخطاه
            if (!$aliasedColumn && !in_array($type, ['Raw', 'Column'])) {
                continue;
            }

            // 3. تطبيق كافة أنواع الشروط ديناميكياً
            switch ($type) {
                case 'Basic':
                    $join->where($aliasedColumn, $where['operator'], $where['value'], $boolean);
                    break;
                case 'In':
                    $join->whereIn($aliasedColumn, $where['values'], $boolean);
                    break;
                case 'NotIn':
                    $join->whereNotIn($aliasedColumn, $where['values'], $boolean);
                    break;
                case 'Null':
                    $join->whereNull($aliasedColumn, $boolean);
                    break;
                case 'NotNull':
                    $join->whereNotNull($aliasedColumn, $boolean);
                    break;
                case 'Between':
                    $join->whereBetween($aliasedColumn, $where['values'], $boolean);
                    break;
                case 'NotBetween':
                    $join->whereNotBetween($aliasedColumn, $where['values'], $boolean);
                    break;
                case 'Date':
                    $join->whereDate($aliasedColumn, $where['operator'], $where['value'], $boolean);
                    break;
                case 'Column':
                    $firstClean = \Illuminate\Support\Str::afterLast($where['first'], '.');
                    $secondClean = \Illuminate\Support\Str::afterLast($where['second'], '.');
                    $join->whereColumn("{$alias}.{$firstClean}", $where['operator'], "{$alias}.{$secondClean}", $boolean);
                    break;
                case 'Raw':
                    // استبدال اسم الجدول الأساسي بالـ Alias داخل الأكواد الخام
                    $sql = str_replace("{$originalTable}.", "{$alias}.", $where['sql']);
                    $join->whereRaw($sql, $where['bindings'] ?? [], $boolean);
                    break;
            }
        }
    }
}

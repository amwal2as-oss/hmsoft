<?php

namespace HMsoft\Tools\Features\Audit\Traits;

use HMsoft\Tools\Features\Audit\Jobs\ProcessAuditLogJob;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Request;

trait HasDynamicSyncAndAudit
{
    /**
     * تابع موحد وديناميكي لمزامنة العلاقات (HasMany & BelongsToMany) مع تسجيل الـ Audit تلقائياً.
     */
    public function syncRelation(string $relationName, array $incomingData, ?string $matchKey = null, array $callbacks = []): void
    {
        if (!method_exists($this, $relationName)) {
            return;
        }

        // نأخذ نسخة واحدة فقط لمعرفة نوع العلاقة (بدون استخدامها في الاستعلامات المعقدة)
        $relationTypeInfo = $this->$relationName();
        $isManyToMany = $relationTypeInfo instanceof BelongsToMany;
        $isHasMany = $relationTypeInfo instanceof HasMany;

        if (!$isManyToMany && (!$isHasMany || $matchKey === null)) {
            throw new \InvalidArgumentException("العلاقة {$relationName} غير مدعومة أو الـ matchKey مفقود.");
        }

        // 1. جلب البيانات القديمة للـ Audit (باستخدام استعلام نظيف)
        $oldRelationData = $this->getRelationDataForAudit($relationName, $isManyToMany, $matchKey);

        // 2. تنفيذ المزامنة بناءً على نوع العلاقة
        if ($isManyToMany) {
            $this->handleManyToManySync($relationName, $incomingData, $callbacks);
        } else {
            $this->handleHasManySync($relationName, $incomingData, $matchKey, $callbacks);
        }

        // 3. إعادة تحميل العلاقة وجلب البيانات الجديدة للمقارنة (باستخدام استعلام نظيف)
        $this->unsetRelation($relationName);
        $newRelationData = $this->getRelationDataForAudit($relationName, $isManyToMany, $matchKey);

        // 4. التحقق من التغييرات وإطلاق الـ Audit
        $this->triggerAuditIfChanged($relationName, $oldRelationData, $newRelationData);
    }

    /**
     * جلب البيانات بذكاء (استعلام نظيف في كل مرة)
     */
    private function getRelationDataForAudit(string $relationName, bool $isManyToMany, ?string $matchKey): array
    {
        $relation = $this->$relationName();

        if ($isManyToMany) {
            return DB::table($relation->getTable())
                ->where($relation->getForeignPivotKeyName(), $this->getKey())
                ->get()
                ->map(fn($row) => (array) $row)
                ->toArray();
        }

        return $relation->get()->keyBy($matchKey)->toArray();
    }

    /**
     * معالجة علاقات BelongsToMany (MtM)
     */
    private function handleManyToManySync(string $relationName, array $incomingData, array $callbacks): void
    {
        if (isset($callbacks['beforeSave'])) {
            $incomingData = $callbacks['beforeSave']($incomingData);
        }

        // استخدام استعلام نظيف
        $this->$relationName()->sync($incomingData);

        if (isset($callbacks['afterSave'])) {
            $callbacks['afterSave']($incomingData);
        }
    }

    /**
     * معالجة علاقات HasMany بإصلاح الـ Query Mutation
     */
    private function handleHasManySync(string $relationName, array $incomingData, string $matchKey, array $callbacks): void
    {
        $incomingKeys = collect($incomingData)->pluck($matchKey)->filter()->toArray();
        
        // 1. استعلام نظيف للحذف
        $this->$relationName()->whereNotIn($matchKey, $incomingKeys)->delete();

        foreach ($incomingData as $data) {
            if (!isset($data[$matchKey])) continue;

            // 2. استعلام نظيف لجلب السجل الموجود (إن وُجد) للـ Callbacks
            $existingRecord = clone $this->$relationName();
            $existingRecord = $existingRecord->where($matchKey, $data[$matchKey])->first();

            if (isset($callbacks['beforeSave'])) {
                $data = $callbacks['beforeSave']($data, $existingRecord);
            }

            // 3. استعلام نظيف لعملية الـ UpdateOrCreate لتفادي تداخل شروط الاستعلامات السابقة
            $this->$relationName()->updateOrCreate(
                [$matchKey => $data[$matchKey]],
                Arr::except($data, [$matchKey])
            );

            if (isset($callbacks['afterSave'])) {
                $callbacks['afterSave']($data);
            }
        }
    }

    /**
     * المقارنة وإرسال الحدث إلى طابور الـ Audit
     */
    private function triggerAuditIfChanged(string $relationName, array $oldData, array $newData): void
    {
        if (json_encode($oldData) !== json_encode($newData)) {
            
            $actionStatus = empty($oldData) ? 'created' : 'updated';
            $oldSnapshot = array_merge($this->getAttributes(), [$relationName => $oldData]);
            $newSnapshot = array_merge($this->getAttributes(), [$relationName => $newData]);

            ProcessAuditLogJob::dispatch(
                $this->getMorphClass(),
                $this->getKey(),
                $actionStatus,
                $oldSnapshot,
                $newSnapshot,
                [
                    'user_id'    => Auth::id(),
                    'ip_address' => Request::ip(),
                    'user_agent' => Request::userAgent(),
                    'session_id' => Request::hasSession() ? session()->getId() : null,
                ]
            );
        }
    }
}
<?php

namespace HMsoft\Tools\Features\Audit\Traits;

use HMsoft\Tools\Features\Audit\Jobs\ProcessAuditLogJob;
use HMsoft\Tools\Features\Audit\Support\AuditConfig;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Request;

trait Auditable
{
    public static function bootAuditable(): void
    {
        if (! AuditConfig::shouldLogModelEvents()) {
            return;
        }

        static::created(function (Model $model) {
            self::dispatchAudit($model, 'created', [], $model->toArray());
        });

        static::updated(function (Model $model) {
            $changes = $model->getChanges();
            $original = array_intersect_key($model->getOriginal(), $changes);

            self::dispatchAudit($model, 'updated', $original, $changes);
        });

        static::deleted(function (Model $model) {
            self::dispatchAudit($model, 'deleted', $model->toArray(), []);
        });
    }

    protected static function dispatchAudit(Model $model, string $event, array $oldValues, array $newValues): void
    {
        if (! AuditConfig::shouldLogModelEvents()) {
            return;
        }

        // Strip out hidden/internal attributes like passwords or remember_tokens if necessary
        $hidden = $model->getHidden();
        $oldValues = array_diff_key($oldValues, array_flip($hidden));
        $newValues = array_diff_key($newValues, array_flip($hidden));

        $context = [
            'user_id' => auth()->id(),
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
            'session_id' => session()->getId(),
        ];

        ProcessAuditLogJob::dispatch(
            $model->getMorphClass(),
            $model->getKey(),
            $event,
            $oldValues,
            $newValues,
            $context
        );
    }
}

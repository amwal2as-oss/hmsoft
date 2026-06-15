<?php

namespace HMsoft\Tools\Features\UserStamps\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

trait HasUserStamps
{
    public static function bootHasUserStamps(): void
    {
        static::creating(function (Model $model) {
            $userId = $model->getStampUserId();

            if ($userId) {
                if ($col = $model->getCreatedByColumn()) {
                    $model->{$col} = $userId;
                }
                if ($col = $model->getUpdatedByColumn()) {
                    $model->{$col} = $userId;
                }
            }
        });

        static::updating(function (Model $model) {
            $userId = $model->getStampUserId();
            if ($userId && $col = $model->getUpdatedByColumn()) {
                $model->{$col} = $userId;
            }
        });

        static::deleting(function (Model $model) {
            $userId = $model->getStampUserId();
            if ($userId && $col = $model->getDeletedByColumn()) {
                if (in_array(\Illuminate\Database\Eloquent\SoftDeletes::class, class_uses_recursive($model))) {
                    $model->{$col} = $userId;
                    $model->saveQuietly();
                }
            }
        });
    }

    /**
     * الافتراضي: جلب معرف المستخدم عبر Auth::id().
     * يمكن للمطور عمل Override إذا كان يستخدم Guard محدد مثل Auth::guard('admin')->id().
     */
    public function getStampUserId(): int|string|null
    {
        return Auth::id();
    }

    public function getCreatedByColumn(): ?string
    {
        return defined('static::CREATED_BY') ? static::CREATED_BY : config('user-stamps.created_by_column', 'created_by');
    }

    public function getUpdatedByColumn(): ?string
    {
        return defined('static::UPDATED_BY') ? static::UPDATED_BY : config('user-stamps.updated_by_column', 'updated_by');
    }

    public function getDeletedByColumn(): ?string
    {
        return defined('static::DELETED_BY') ? static::DELETED_BY : config('user-stamps.deleted_by_column', 'deleted_by');
    }
}

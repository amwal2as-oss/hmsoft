<?php

namespace HMsoft\Tools\Features\Audit\Support;

class AuditConfig
{
    public static function isEnabled(): bool
    {
        return (bool) config('cms_audit.enabled', false);
    }

    public static function shouldLogModelEvents(): bool
    {
        return self::isEnabled() && (bool) config('cms_audit.log_model_events', true);
    }

    public static function shouldLogRelationSync(): bool
    {
        return self::isEnabled() && (bool) config('cms_audit.log_relation_sync', true);
    }

    public static function shouldLogAuthentication(): bool
    {
        return self::isEnabled() && (bool) config('cms_audit.log_authentication', true);
    }

    public static function shouldRegisterRoutes(): bool
    {
        return self::isEnabled() && (bool) config('cms_audit.register_routes', true);
    }

    public static function shouldLoadMigrations(): bool
    {
        return self::isEnabled() && (bool) config('cms_audit.load_migrations', true);
    }
}

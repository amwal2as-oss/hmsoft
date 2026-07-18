<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Audit System Enabled
    |--------------------------------------------------------------------------
    | Master switch for the audit log system. When disabled, no audit records
    | are written, authentication listeners are not registered, API routes are
    | skipped, and migrations are not loaded.
    |
    | Set CMS_AUDIT_ENABLED=false when your project has no audit_logs table
    | or you intentionally want auditing turned off.
    */
    'enabled' => env('CMS_AUDIT_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Feature Toggles (only apply when enabled=true)
    |--------------------------------------------------------------------------
    */
    'log_model_events' => env('CMS_AUDIT_LOG_MODEL_EVENTS', true), //Model create/update/delete via Auditable trait
    'log_relation_sync' => env('CMS_AUDIT_LOG_RELATION_SYNC', true), //Relation sync via Auditable trait
    'log_authentication' => env('CMS_AUDIT_LOG_AUTHENTICATION', true), //Login / logout / failed login events
    'register_routes' => env('CMS_AUDIT_REGISTER_ROUTES', true), //Register API routes for audit log management
    'load_migrations' => env('CMS_AUDIT_LOAD_MIGRATIONS', true), //Load migrations for audit log management
];

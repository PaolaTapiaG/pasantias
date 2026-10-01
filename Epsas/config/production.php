<?php

return [
    'enforce_https' => filter_var(env('ENFORCE_HTTPS', false), FILTER_VALIDATE_BOOL),
    'monitoring_enabled' => filter_var(env('PRODUCTION_MONITORING_ENABLED', false), FILTER_VALIDATE_BOOL),
    'health_token' => env('MONITORING_HEALTH_TOKEN'),
    'external_monitor_configured' => filter_var(env('EXTERNAL_MONITOR_CONFIGURED', false), FILTER_VALIDATE_BOOL),
    'scheduler_heartbeat_key' => 'production:scheduler-heartbeat',
    'scheduler_max_age_minutes' => (int) env('SCHEDULER_HEARTBEAT_MAX_AGE_MINUTES', 5),
    'queue_backlog_limit' => (int) env('MONITORING_QUEUE_BACKLOG_LIMIT', 100),
    'minimum_free_disk_mb' => (int) env('MONITORING_MINIMUM_FREE_DISK_MB', 1024),
    'backup_verification_file' => storage_path('app/private/production/backup-verification.json'),
    'backup_max_age_days' => (int) env('BACKUP_VERIFICATION_MAX_AGE_DAYS', 90),
    'primary_database_name' => env('PRODUCTION_DATABASE_NAME', env('DB_DATABASE')),
];

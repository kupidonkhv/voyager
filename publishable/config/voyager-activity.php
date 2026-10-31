<?php

return [
    'enabled' => env('VOYAGER_ACTIVITY_ENABLED', true),

    'retention_days' => (int) env('VOYAGER_ACTIVITY_RETENTION_DAYS', 180),

    'store_ip' => env('VOYAGER_ACTIVITY_STORE_IP', true),

    'max_details_bytes' => 4096,

    'page_size' => 50,

    'max_page_size' => 100,

    'cleanup_chunk_size' => 1000,

    'fallback_log_days' => 7,

    'excluded_fields' => [
        'id',
        'created_at',
        'updated_at',
        'deleted_at',
        'remember_token',
        'password',
        'password_confirmation',
        'current_password',
        '_token',
        '_method',
    ],

    'excluded_resources' => [],

    'custom_events' => [],
];

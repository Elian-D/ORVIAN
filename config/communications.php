<?php

return [

    'chatwoot' => [
        'base_url'   => env('CHATWOOT_BASE_URL', 'https://chat.orvian.com.do'),
        'api_token'  => env('CHATWOOT_API_ACCESS_TOKEN'),
        'account_id' => env('CHATWOOT_ACCOUNT_ID', 1),
        'hmac_token' => env('CHATWOOT_HMAC_TOKEN'),
    ],

    'evolution' => [
        'base_url'       => env('EVOLUTION_API_URL', 'https://evolution.orvian.com.do'),
        'api_key'        => env('EVOLUTION_API_KEY'),
        'instance_name'  => env('EVOLUTION_INSTANCE_NAME', 'orvian_school'),
    ],

    'notifications' => [
        'absence_threshold'   => env('ALERT_ABSENCE_THRESHOLD', 3),
        'tardiness_threshold' => env('ALERT_TARDINESS_THRESHOLD', 3),
    ],

];
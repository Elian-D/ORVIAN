<?php

return [

    'chatwoot' => [
        'base_url'     => env('CHATWOOT_BASE_URL', 'http://localhost:3001'),
        'api_token'    => env('CHATWOOT_API_ACCESS_TOKEN'),
        'account_id'   => env('CHATWOOT_ACCOUNT_ID', 1),
        'hmac_token'   => env('CHATWOOT_HMAC_TOKEN'),
        'iframe_url'   => env('CHATWOOT_BASE_URL', 'http://localhost:3001'),
    ],

    'evolution' => [
        'base_url'      => env('EVOLUTION_API_URL', 'http://localhost:8085'),
        'api_key'       => env('EVOLUTION_API_KEY'),
        'instance_name' => env('EVOLUTION_INSTANCE_NAME', 'orvian_school'),
    ],

    'notifications' => [
        // Umbral de ausencias en el mes para disparar alerta al tutor
        'absence_threshold'   => 3,
        // Umbral de tardanzas en el mes para disparar alerta al tutor
        'tardiness_threshold' => 3,
    ],

];
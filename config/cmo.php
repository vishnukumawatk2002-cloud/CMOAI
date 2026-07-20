<?php

return [
    'queue_content_generation' => env('QUEUE_CONTENT_GENERATION', true),

    'cache' => [
        'plans_ttl' => (int) env('CACHE_PLANS_TTL', 3600),
        'settings_ttl' => (int) env('CACHE_SETTINGS_TTL', 3600),
    ],

    'images' => [
        'max_width' => (int) env('IMAGE_MAX_WIDTH', 800),
        'max_height' => (int) env('IMAGE_MAX_HEIGHT', 800),
        'quality' => (int) env('IMAGE_QUALITY', 85),
    ],
];

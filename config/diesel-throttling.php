<?php

return [
    'per_minute' => env('PKG_LARAVEL_THROTTLING_MIN', 60),
    'limiter_name' => env('PKG_LARAVEL_LIMITER_NAME', 'diesel-api'),
];

<?php

return [

    'online_seconds' => (int) env('RITELIN_ONLINE_SECONDS', 90),

    'match_window_minutes' => (int) env('RITELIN_MATCH_WINDOW_MINUTES', 10),

    'match_lead_minutes' => (int) env('RITELIN_MATCH_LEAD_MINUTES', 2),

    'stock_window_minutes' => (int) env('RITELIN_STOCK_WINDOW_MINUTES', 2),

    'threshold_grams' => (float) env('RITELIN_THRESHOLD_GRAMS', 15),

    'verify_ms' => (int) env('RITELIN_VERIFY_MS', 1500),

    'pos_token' => env('POS_WEBHOOK_TOKEN', ''),

    'require_tls' => (bool) env('RITELIN_REQUIRE_TLS', env('APP_ENV') === 'production'),

];

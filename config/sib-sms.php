<?php

return [
    // طبق داک سرویس، Base URL نسخه V3
    'base_url' => env('SIBSMS_BASE_URL', 'https://api.sms-webservice.com/api/V3'),

    'api_key' => env('SIBSMS_API_KEY'),

    // خط خدماتی (مثلا 3000xxxxxx)
    'default_sender' => env('SIBSMS_DEFAULT_SENDER'),

    'timeout' => (int) env('SIBSMS_TIMEOUT', 20),

    // ذخیره در دیتابیس (اختیاری)
    'persist' => (bool) env('SIBSMS_PERSIST', false),

    // ذخیره raw response (برای دیباگ/لاگ)
    'store_raw' => (bool) env('SIBSMS_STORE_RAW', true),
];

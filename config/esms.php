<?php

return [
    // Base URL for ESMS
    'base_url'   => env('ESMS_BASE_URL', 'https://login.esms.com.bd'),

    // Bearer token (REQUIRED)
    'api_token'  => env('ESMS_API_TOKEN', ''),

    // Default sender and sms type
    'sender_id'  => env('ESMS_SENDER_ID', '8809601003650'),
    'type'       => env('ESMS_TYPE', 'plain'), // text sms

    // Request timeout (seconds)
    'timeout'    => (int) env('ESMS_TIMEOUT', 10),

    /**
     * HTTP payload mode:
     *  - 'json' (default)  => Content-Type: application/json
     *  - 'form'            => application/x-www-form-urlencoded
     */
    'http_mode'  => env('ESMS_HTTP_MODE', 'json'),
];

<?php

    return [
        'base_url'   => env('ESMS_BASE_URL', 'https://login.esms.com.bd'),
        'api_token'  => env('ESMS_API_TOKEN', ''),
        'sender_id'  => env('ESMS_SENDER_ID', '8809601003650'),
        'type'       => env('ESMS_TYPE', 'plain'), // text SMS
        'timeout'    => (int) env('ESMS_TIMEOUT', 10),
    ];
<?php

namespace Asifulmamun\DianasmsEsms;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;

class Esms
{
    /**
     * Send SMS (single/multiple).
     *
     * $recipient: "01600103032" | "016...,88017..." | ['016...','88017...']
     * $options (overrides take priority over env/config):
     *   - 'api_token'   => '...'            // override ESMS_API_TOKEN
     *   - 'base_url'    => 'https://...'    // override ESMS_BASE_URL
     *   - 'sender_id'   => '...'            // override ESMS_SENDER_ID
     *   - 'type'        => 'plain'          // override ESMS_TYPE
     *   - 'timeout'     => 15               // override ESMS_TIMEOUT
     *   - 'http_mode'   => 'json'|'form'    // override ESMS_HTTP_MODE
     *   - 'schedule_time' => 'Y-m-d H:i'
     */
    public static function send(string|array $recipient, string $message, array $options = []): array
    {
        $apiToken = (string) self::option('api_token',  $options, '');
        if ($apiToken === '') {
            return ['status' => 'error', 'message' => 'Missing ESMS_API_TOKEN'];
        }

        $baseUrl  = (string) self::option('base_url',  $options, 'https://login.esms.com.bd');
        $senderId = (string) self::option('sender_id', $options, '8809601003650');
        $type     = (string) self::option('type',      $options, 'plain');
        $timeout  = (int)    self::option('timeout',   $options, 10);
        $httpMode = (string) self::option('http_mode', $options, 'json'); // 'json' | 'form'

        $recipients = self::normalizeRecipients($recipient);
        if ($recipients === '') {
            return ['status' => 'error', 'message' => 'No valid recipient'];
        }

        $payload = [
            'recipient' => $recipients,
            'sender_id' => $senderId,
            'type'      => $type,
            'message'   => $message,
        ];
        if (!empty($options['schedule_time'])) {
            $payload['schedule_time'] = $options['schedule_time']; // 'Y-m-d H:i'
        }

        $url = rtrim($baseUrl, '/') . '/api/v3/sms/send';

        try {
            $http = Http::timeout($timeout)->acceptJson()->withToken($apiToken);

            if ($httpMode === 'form') {
                $http = $http->asForm();
            } else {
                $http = $http->withHeaders(['Content-Type' => 'application/json']);
            }

            $res = $http->post($url, $payload)->throw();
            return $res->json() ?? [];
        } catch (RequestException|ConnectionException $e) {
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    /* ---------------- helpers --------------- */

    /** Per-call option overrides > app config('esms.*') > package config/env > default */
    protected static function option(string $key, array $options, mixed $default = null): mixed
    {
        if (array_key_exists($key, $options)) {
            return $options[$key];
        }
        return self::cfg($key, $default);
    }

    // Prefer app config('esms.*'), else load package config (which uses env()).
    protected static function cfg(string $key, mixed $default = null): mixed
    {
        if (function_exists('config')) {
            $val = config("esms.$key");
            if ($val !== null) return $val;
        }
        static $pkg;
        if ($pkg === null) {
            $path = __DIR__ . '/../config/esms.php';
            $pkg  = is_file($path) ? require $path : [];
        }
        return $pkg[$key] ?? $default;
    }

    protected static function normalizeRecipients(string|array $recipients): string
    {
        if (is_string($recipients)) {
            $recipients = array_map('trim', explode(',', $recipients));
        }
        $valid = [];
        foreach ($recipients as $r) {
            $f = self::formatPhone($r);
            if ($f !== '') $valid[$f] = true;
        }
        return implode(',', array_keys($valid));
    }

    /**
     * - Bangla digits → English
     * - strip '+' and non-digits
     * - BD 11-digit starting '0' → '880' + rest
     * - keep '880' + 10 digits (13 total)
     * - otherwise allow >=8 digits (e.g., intl numbers like 31612345678)
     */
    protected static function formatPhone(?string $phone): string
    {
        $phone = (string) $phone;

        $bn = ['০','১','২','৩','৪','৫','৬','৭','৮','৯'];
        $en = ['0','1','2','3','4','5','6','7','8','9'];
        $phone = str_replace($bn, $en, $phone);

        $phone = ltrim($phone, '+');
        $phone = preg_replace('/\D/', '', $phone ?? '');

        if ($phone === '') return '';
        if (strlen($phone) === 11 && str_starts_with($phone, '0')) {
            return '880' . substr($phone, 1);
        }
        if (str_starts_with($phone, '880') && strlen($phone) === 13) {
            return $phone;
        }
        if (strlen($phone) === 14 && str_starts_with($phone, '+')) {
            return substr($phone, 1);
        }
        if (str_starts_with($phone, '+880') && strlen($phone) === 14) {
            return '880' . substr($phone, 4);
        }
        return strlen($phone) >= 8 ? $phone : '';
    }
}

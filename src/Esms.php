<?php

namespace Asifulmamun\DianasmsEsms;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;

class Esms
{
    /**
     * Send SMS (single or multiple).
     *
     * $recipient: "01600103032" | "016...,88017..." | ['016...','88017...']
     * $options: [
     *   'sender_id' => '...',         // override default
     *   'type'      => 'plain',       // override default
     *   'schedule_time' => 'Y-m-d H:i'
     * ]
     */
    public static function send(string|array $recipient, string $message, array $options = []): array
    {
        $apiToken = (string) self::cfg('api_token', '');
        if ($apiToken === '') {
            return ['status' => 'error', 'message' => 'Missing ESMS_API_TOKEN'];
        }

        $baseUrl   = (string) self::cfg('base_url', 'https://login.esms.com.bd');
        $senderId  = (string) ($options['sender_id'] ?? self::cfg('sender_id', '8809601003650'));
        $type      = (string) ($options['type']      ?? self::cfg('type', 'plain'));
        $timeout   = (int) self::cfg('timeout', 10);
        $httpMode  = (string) self::cfg('http_mode', 'json'); // 'json' | 'form'

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
                // default: JSON
                $http = $http->withHeaders(['Content-Type' => 'application/json']);
            }

            $res = $http->post($url, $payload)->throw();

            return $res->json() ?? [];
        } catch (RequestException|ConnectionException $e) {
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    /* ---------------- helpers --------------- */

    // Prefer app config('esms.*'), else load package config file directly (which uses env())
    protected static function cfg(string $key, mixed $default = null): mixed
    {
        if (function_exists('config')) {
            $fromApp = config("esms.$key");
            if ($fromApp !== null) {
                return $fromApp;
            }
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
            if ($f !== '') {
                $valid[$f] = true; // unique
            }
        }
        return implode(',', array_keys($valid));
    }

    /**
     * Phone formatting:
     * - Converts Bangla digits → English
     * - Removes '+' and non-digits
     * - If BD 11-digit starting with 0, convert to 880XXXXXXXXXX
     * - If starts with 880 (BD), keep
     * - For other countries (e.g., 31612345678), keep digits if length >= 8
     */
    protected static function formatPhone(?string $phone): string
    {
        $phone = (string) $phone;

        // Bangla → English digits
        $bn = ['০','১','২','৩','৪','৫','৬','৭','৮','৯'];
        $en = ['0','1','2','3','4','5','6','7','8','9'];
        $phone = str_replace($bn, $en, $phone);

        // Remove leading plus then non-digits
        $phone = ltrim($phone, '+');
        $phone = preg_replace('/\D/', '', $phone ?? '');

        if ($phone === '') return '';

        // BD specific normalization
        if (strlen($phone) === 11 && str_starts_with($phone, '0')) {
            return '880' . substr($phone, 1);
        }
        if (str_starts_with($phone, '880') && strlen($phone) === 13) {
            return $phone;
        }

        // Allow other country codes like 31612345678 (>=8 digits)
        return strlen($phone) >= 8 ? $phone : '';
    }
}

<?php

namespace Asifulmamun\DianasmsEsms;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;

class Esms
{
    /**
     * Send SMS (single/multiple). $recipient = "016...,8801..." or ["016...", "8801..."].
     *
     * @param string|array $recipient
     * @param string       $message
     * @param array        $options ['sender_id','type','schedule_time' => 'Y-m-d H:i']
     * @return array
     */
    public static function send(string|array $recipient, string $message, array $options = []): array
    {
        $apiToken = (string) self::cfg('api_token', '');
        if ($apiToken === '') {
            return ['status' => 'error', 'message' => 'Missing ESMS_API_TOKEN'];
        }

        $baseUrl  = (string) self::cfg('base_url', 'https://login.esms.com.bd');
        $senderId = $options['sender_id'] ?? (string) self::cfg('sender_id', '8809601003650');
        $type     = $options['type']      ?? (string) self::cfg('type', 'plain');
        $timeout  = (int) self::cfg('timeout', 10);

        $recipient = self::normalizeRecipients($recipient);
        if ($recipient === '') {
            return ['status' => 'error', 'message' => 'No valid recipient'];
        }

        $payload = [
            'recipient' => $recipient,
            'sender_id' => $senderId,
            'type'      => $type,
            'message'   => $message,
        ];

        if (!empty($options['schedule_time'])) {
            $payload['schedule_time'] = $options['schedule_time']; // 'Y-m-d H:i'
        }

        $url = rtrim($baseUrl, '/') . '/api/v3/sms/send';

        try {
            $res = Http::timeout($timeout)
                ->acceptJson()
                ->asForm()                 // ESMS docs: form-encoded
                ->withToken($apiToken)     // Authorization: Bearer {token}
                ->post($url, $payload)
                ->throw();

            return $res->json() ?? [];
        } catch (RequestException|ConnectionException $e) {
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    /* ---------------- helpers --------------- */

    // Try config('esms.key') if app has it; else load package config file directly (env() still works)
    protected static function cfg(string $key, mixed $default = null): mixed
    {
        // If app config is present and has esms.*, use it
        if (function_exists('config')) {
            $fromApp = config("esms.$key");
            if ($fromApp !== null) {
                return $fromApp;
            }
        }

        // Fallback: read package config file directly
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
            $f = self::formatBdPhone($r);
            if ($f !== '') {
                $valid[$f] = true; // unique
            }
        }
        return implode(',', array_keys($valid));
    }

    protected static function formatBdPhone(?string $phone): string
    {
        $phone = preg_replace('/\D/', '', (string) $phone);

        // 11 digits starting with 0 → to 880XXXXXXXXXX
        if (strlen($phone) === 11 && str_starts_with($phone, '0')) {
            return '880' . substr($phone, 1);
        }

        // 13 digits starting with 880 → keep
        if (strlen($phone) === 13 && str_starts_with($phone, '880')) {
            return $phone;
        }

        return '';
    }
}

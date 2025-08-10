<?php

namespace Asifulmamun\DianasmsEsms;

use Asifulmamun\DianasmsEsms\Support\Phone;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;

class EsmsClient
{
    public function __construct(
        protected string $baseUrl,
        protected string $apiToken,
        protected string $defaultSender = '8809601003650',
        protected string $defaultType = 'plain',
        protected int $timeout = 10,
    ) {}

    /**
     * Send SMS (single/multiple). $recipient can be "016...,8801..." or array.
     *
     * @param  string|array  $recipient
     * @param  string        $message
     * @param  array         $options ['sender_id','type','schedule_time' => 'Y-m-d H:i']
     * @return array
     * @throws RequestException|ConnectionException
     */
    public function send(string|array $recipient, string $message, array $options = []): array
    {
        $recipient = Phone::normalize($recipient);
        if ($recipient === '') {
            return ['status' => 'error', 'message' => 'No valid recipient after validation.'];
        }

        $payload = [
            'recipient'   => $recipient,
            'sender_id'   => $options['sender_id'] ?? $this->defaultSender,
            'type'        => $options['type']      ?? $this->defaultType,
            'message'     => $message,
        ];

        if (!empty($options['schedule_time'])) {
            $payload['schedule_time'] = $options['schedule_time']; // 'Y-m-d H:i'
        }

        $url = rtrim($this->baseUrl, '/') . '/api/v3/sms/send';

        $response = Http::timeout($this->timeout)
            ->acceptJson()
            ->asForm()                 // <- IMPORTANT: send as x-www-form-urlencoded
            ->withToken($this->apiToken)
            ->post($url, $payload)
            ->throw();

        return $response->json() ?? [];
    }
}

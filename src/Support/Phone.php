<?php

namespace Asifulmamun\DianasmsEsms\Support;

class Phone
{
    /**
     * Validate & format a BD number:
     * - 11 digits starting with 0 => to 880XXXXXXXXXX
     * - 13 digits starting with 880 => keep
     * Otherwise invalid => return ''
     */
    public static function format(string $phone): string
    {
        $phone = preg_replace('/\D/', '', $phone ?? '');

        if (strlen($phone) === 11 && $phone[0] === '0') {
            return '880' . substr($phone, 1);
        }

        if (strlen($phone) === 13 && str_starts_with($phone, '880')) {
            return $phone;
        }

        return '';
    }

    /**
     * Accepts string with commas or array; returns comma-separated valid uniques.
     */
    public static function normalize(string|array $recipients): string
    {
        if (is_string($recipients)) {
            $recipients = array_map('trim', explode(',', $recipients));
        }

        $valid = [];
        foreach ($recipients as $r) {
            $f = self::format($r);
            if ($f !== '') {
                $valid[$f] = true; // unique
            }
        }
        return implode(',', array_keys($valid));
    }
}

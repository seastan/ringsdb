<?php

declare(strict_types=1);

namespace App\Helper;

/**
 * Replacement for the FILTER_SANITIZE_STRING filter, deprecated in PHP 8.1.
 */
class StringSanitizer
{
    /**
     * Strips the tags of a request value, and encodes its quotes unless $encodeQuotes is false
     * (FILTER_FLAG_NO_ENCODE_QUOTES). A value that is not a string (an array) gives ''.
     */
    public static function sanitize(mixed $value, bool $encodeQuotes = true): string
    {
        if (!is_scalar($value)) {
            return '';
        }

        $value = (string) $value;
        if ($encodeQuotes) {
            $value = str_replace(['"', "'"], ['&#34;', '&#39;'], $value);
        }

        return strip_tags($value);
    }
}

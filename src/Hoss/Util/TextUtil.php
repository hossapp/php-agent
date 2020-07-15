<?php

namespace Hoss\Util;

/**
 * TextUtil provides conversions between text based formats.
 */
class TextUtil
{
    /**
     * Returns lowercase camelcase from specified underscore text.
     *
     * Example: curl_multi_exec -> curlMultiExec
     *
     * @param  string $underscore Lowercased text.
     *
     * @return string Lowercase camelcased version of specified text.
     */
    public static function underscoreToLowerCamelcase($underscore)
    {
        return lcfirst(
            str_replace(
                ' ',
                '',
                ucwords(str_replace('_', ' ', $underscore))
            )
        );
    }

    /**
     * Generate random uuid to be used as event id
     * @return string
     */
    public static function generateUUID()
    {
        return sprintf("%04x%04x-%04x-%04x-%04x-%04x%04x%04x",
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff)
        );
    }
}

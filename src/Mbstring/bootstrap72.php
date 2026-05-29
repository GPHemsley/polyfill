<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Symfony\Polyfill\Mbstring as p;

if (\extension_loaded('mbstring')) {
    return;
}

if (!\defined('MB_OVERLOAD_MAIL')) {
    \define('MB_OVERLOAD_MAIL', 1);
}
if (!\defined('MB_OVERLOAD_STRING')) {
    \define('MB_OVERLOAD_STRING', 2);
}
if (!\defined('MB_OVERLOAD_REGEX')) {
    \define('MB_OVERLOAD_REGEX', 4);
}
if (!\defined('MB_CASE_UPPER')) {
    \define('MB_CASE_UPPER', 0);
}
if (!\defined('MB_CASE_LOWER')) {
    \define('MB_CASE_LOWER', 1);
}
if (!\defined('MB_CASE_TITLE')) {
    \define('MB_CASE_TITLE', 2);
}

if (!\function_exists('mb_convert_case')) {
    function mb_convert_case($sourcestring, $mode, $encoding = null) { return p\Mbstring::mb_convert_case($sourcestring, $mode, $encoding); }
}
if (!\function_exists('mb_strtoupper')) {
    function mb_strtoupper($sourcestring, $encoding = null) { return p\Mbstring::mb_strtoupper($sourcestring, $encoding); }
}
if (!\function_exists('mb_strtolower')) {
    function mb_strtolower($sourcestring, $encoding = null) { return p\Mbstring::mb_strtolower($sourcestring, $encoding); }
}
if (!\function_exists('mb_language')) {
    function mb_language($language = null) { return p\Mbstring::mb_language($language); }
}
if (!\function_exists('mb_internal_encoding')) {
    function mb_internal_encoding($encoding = null) { return p\Mbstring::mb_internal_encoding($encoding); }
}
if (!\function_exists('mb_http_input')) {
    function mb_http_input($type = null) { return p\Mbstring::mb_http_input($type); }
}
if (!\function_exists('mb_http_output')) {
    function mb_http_output($encoding = null) { return p\Mbstring::mb_http_output($encoding); }
}
if (!\function_exists('mb_detect_order')) {
    function mb_detect_order($encoding = null) { return p\Mbstring::mb_detect_order($encoding); }
}
if (!\function_exists('mb_substitute_character')) {
    function mb_substitute_character($substchar = null) { return p\Mbstring::mb_substitute_character($substchar); }
}
if (!\function_exists('mb_output_handler')) {
    function mb_output_handler($contents, $status) { return p\Mbstring::mb_output_handler($contents, $status); }
}
if (!\function_exists('mb_strlen')) {
    function mb_strlen($str, $encoding = null) { return p\Mbstring::mb_strlen($str, $encoding); }
}
if (!\function_exists('mb_strpos')) {
    function mb_strpos($haystack, $needle, $offset = null, $encoding = null) { return p\Mbstring::mb_strpos($haystack, $needle, $offset, $encoding); }
}
if (!\function_exists('mb_strrpos')) {
    function mb_strrpos($haystack, $needle, $offset = null, $encoding = null) { return p\Mbstring::mb_strrpos($haystack, $needle, $offset, $encoding); }
}
if (!\function_exists('mb_stripos')) {
    function mb_stripos($haystack, $needle, $offset = null, $encoding = null) { return p\Mbstring::mb_stripos($haystack, $needle, $offset, $encoding); }
}
if (!\function_exists('mb_strripos')) {
    function mb_strripos($haystack, $needle, $offset = null, $encoding = null) { return p\Mbstring::mb_strripos($haystack, $needle, $offset, $encoding); }
}
if (!\function_exists('mb_strstr')) {
    function mb_strstr($haystack, $needle, $part = null, $encoding = null) { return p\Mbstring::mb_strstr($haystack, $needle, $part, $encoding); }
}
if (!\function_exists('mb_strrchr')) {
    function mb_strrchr($haystack, $needle, $part = null, $encoding = null) { return p\Mbstring::mb_strrchr($haystack, $needle, $part, $encoding); }
}
if (!\function_exists('mb_stristr')) {
    function mb_stristr($haystack, $needle, $part = null, $encoding = null) { return p\Mbstring::mb_stristr($haystack, $needle, $part, $encoding); }
}
if (!\function_exists('mb_strrichr')) {
    function mb_strrichr($haystack, $needle, $part = null, $encoding = null) { return p\Mbstring::mb_strrichr($haystack, $needle, $part, $encoding); }
}
if (!\function_exists('mb_substr_count')) {
    function mb_substr_count($haystack, $needle, $encoding = null) { return p\Mbstring::mb_substr_count($haystack, $needle, $encoding); }
}
if (!\function_exists('mb_substr')) {
    function mb_substr($str, $start, $length = null, $encoding = null) { return p\Mbstring::mb_substr($str, $start, $length, $encoding); }
}
if (!\function_exists('mb_strwidth')) {
    function mb_strwidth($str, $encoding = null) { return p\Mbstring::mb_strwidth($str, $encoding); }
}
if (!\function_exists('mb_convert_encoding')) {
    function mb_convert_encoding($str, $to, $from = null) { return p\Mbstring::mb_convert_encoding($str, $to, $from); }
}
if (!\function_exists('mb_detect_encoding')) {
    function mb_detect_encoding($str, $encoding_list = null, $strict = null) { return p\Mbstring::mb_detect_encoding($str, $encoding_list, $strict); }
}
if (!\function_exists('mb_list_encodings')) {
    function mb_list_encodings() { return p\Mbstring::mb_list_encodings(); }
}
if (!\function_exists('mb_encoding_aliases')) {
    function mb_encoding_aliases($encoding) { return p\Mbstring::mb_encoding_aliases($encoding); }
}
if (!\function_exists('mb_encode_mimeheader')) {
    function mb_encode_mimeheader($str, $charset = null, $transfer = null, $linefeed = null, $indent = null) { return p\Mbstring::mb_encode_mimeheader($str, $charset, $transfer, $linefeed, $indent); }
}
if (!\function_exists('mb_decode_mimeheader')) {
    function mb_decode_mimeheader($string) { return p\Mbstring::mb_decode_mimeheader($string); }
}
if (!\function_exists('mb_convert_variables')) {
    function mb_convert_variables($to, $from, &...$vars) { return p\Mbstring::mb_convert_variables($to, $from, $vars); }
}
if (!\function_exists('mb_encode_numericentity')) {
    function mb_encode_numericentity($string, $convmap, $encoding = null, $is_hex = null) { return p\Mbstring::mb_encode_numericentity($string, $convmap, $encoding, $is_hex); }
}
if (!\function_exists('mb_decode_numericentity')) {
    function mb_decode_numericentity($string, $convmap, $encoding = null, $is_hex = null) { return p\Mbstring::mb_decode_numericentity($string, $convmap, $encoding); }
}
if (!\function_exists('mb_get_info')) {
    function mb_get_info($type = null) { return p\Mbstring::mb_get_info($type); }
}
if (!\function_exists('mb_check_encoding')) {
    function mb_check_encoding($var = null, $encoding = null) { return p\Mbstring::mb_check_encoding($var, $encoding); }
}
if (!\function_exists('mb_ord')) {
    function mb_ord($str, $encoding = null) { return p\Mbstring::mb_ord($str, $encoding); }
}
if (!\function_exists('mb_chr')) {
    function mb_chr($cp, $encoding = null) { return p\Mbstring::mb_chr($cp, $encoding); }
}
if (!\function_exists('mb_scrub')) {
    function mb_scrub($str, $encoding = null) { return p\Mbstring::mb_scrub($str, $encoding); }
}

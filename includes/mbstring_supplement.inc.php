<?php

/*
 * PHP MBString-Supplement (implemented using IConv) adapter
 * Copyright 2020 Daniel Marschall, ViaThinkSoft
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

if (function_exists('iconv') && !function_exists('mb_convert_encoding')) {
    function mb_convert_encoding($s, $toEncoding, $fromEncoding = null)
    {
	include_once __DIR__.'/../3p/symfony-mbstring-polyfill/Mbstring.class.php';
        return Symfony\Polyfill\Mbstring\Mbstring::mb_convert_encoding($s, $toEncoding, $fromEncoding);
    }
}

if (function_exists('iconv') && !function_exists('mb_convert_variables')) {
    function mb_convert_variables($toEncoding, $fromEncoding, &$vars)
    {
	include_once __DIR__.'/../3p/symfony-mbstring-polyfill/Mbstring.class.php';
        return Symfony\Polyfill\Mbstring\Mbstring::mb_convert_variables($toEncoding, $fromEncoding, $vars);
    }
}

if (function_exists('iconv') && !function_exists('mb_decode_mimeheader')) {
    function mb_decode_mimeheader($s)
    {
	include_once __DIR__.'/../3p/symfony-mbstring-polyfill/Mbstring.class.php';
        return Symfony\Polyfill\Mbstring\Mbstring::mb_decode_mimeheader($s);
    }
}

if (function_exists('iconv') && !function_exists('mb_encode_mimeheader')) {
    function mb_encode_mimeheader($s, $charset = null, $transferEncoding = null, $linefeed = null, $indent = null)
    {
	include_once __DIR__.'/../3p/symfony-mbstring-polyfill/Mbstring.class.php';
        return Symfony\Polyfill\Mbstring\Mbstring::mb_encode_mimeheader($s, $charset, $transferEncoding, $linefeed, $indent);
    }
}

if (function_exists('iconv') && !function_exists('mb_decode_numericentity')) {
    function mb_decode_numericentity($s, $convmap, $encoding = null)
    {
	include_once __DIR__.'/../3p/symfony-mbstring-polyfill/Mbstring.class.php';
        return Symfony\Polyfill\Mbstring\Mbstring::mb_decode_numericentity($s, $convmap, $encoding);
    }
}

if (function_exists('iconv') && !function_exists('mb_encode_numericentity')) {
    function mb_encode_numericentity($s, $convmap, $encoding = null, $is_hex = false)
    {
	include_once __DIR__.'/../3p/symfony-mbstring-polyfill/Mbstring.class.php';
        return Symfony\Polyfill\Mbstring\Mbstring::mb_encode_numericentity($s, $convmap, $encoding, $is_hex);
    }
}

if (function_exists('iconv') && !function_exists('mb_convert_case')) {
    function mb_convert_case($s, $mode, $encoding = null)
    {
	include_once __DIR__.'/../3p/symfony-mbstring-polyfill/Mbstring.class.php';
        return Symfony\Polyfill\Mbstring\Mbstring::mb_convert_case($s, $mode, $encoding);
    }
}

if (function_exists('iconv') && !function_exists('mb_internal_encoding')) {
    function mb_internal_encoding($encoding = null)
    {
	include_once __DIR__.'/../3p/symfony-mbstring-polyfill/Mbstring.class.php';
        return Symfony\Polyfill\Mbstring\Mbstring::mb_internal_encoding($encoding);
    }
}

if (function_exists('iconv') && !function_exists('mb_language')) {
    function mb_language($lang = null)
    {
	include_once __DIR__.'/../3p/symfony-mbstring-polyfill/Mbstring.class.php';
        return Symfony\Polyfill\Mbstring\Mbstring::mb_lang($lang);
    }
}

if (function_exists('iconv') && !function_exists('mb_list_encodings')) {
    function mb_list_encodings()
    {
	include_once __DIR__.'/../3p/symfony-mbstring-polyfill/Mbstring.class.php';
        return Symfony\Polyfill\Mbstring\Mbstring::mb_list_encodings();
    }
}

if (function_exists('iconv') && !function_exists('mb_encoding_aliases')) {
    function mb_encoding_aliases($encoding)
    {
	include_once __DIR__.'/../3p/symfony-mbstring-polyfill/Mbstring.class.php';
        return Symfony\Polyfill\Mbstring\Mbstring::mb_encoding_aliases($encoding);
    }
}

if (function_exists('iconv') && !function_exists('mb_check_encoding')) {
    function mb_check_encoding($var = null, $encoding = null)
    {
	include_once __DIR__.'/../3p/symfony-mbstring-polyfill/Mbstring.class.php';
        return Symfony\Polyfill\Mbstring\Mbstring::mb_check_encoding($var, $encoding);
    }
}

if (function_exists('iconv') && !function_exists('mb_detect_encoding')) {
    function mb_detect_encoding($str, $encodingList = null, $strict = false)
    {
	include_once __DIR__.'/../3p/symfony-mbstring-polyfill/Mbstring.class.php';
        return Symfony\Polyfill\Mbstring\Mbstring::mb_detect_encoding($str, $encodingList, $strict);
    }
}

if (function_exists('iconv') && !function_exists('mb_detect_order')) {
    function mb_detect_order($encodingList = null)
    {
	include_once __DIR__.'/../3p/symfony-mbstring-polyfill/Mbstring.class.php';
        return Symfony\Polyfill\Mbstring\Mbstring::mb_detect_order($encodingList);
    }
}

if (function_exists('iconv') && !function_exists('mb_strlen')) {
    function mb_strlen($s, $encoding = null)
    {
	include_once __DIR__.'/../3p/symfony-mbstring-polyfill/Mbstring.class.php';
        return Symfony\Polyfill\Mbstring\Mbstring::mb_strlen($s, $encoding);
    }
}

if (function_exists('iconv') && !function_exists('mb_strpos')) {
    function mb_strpos($haystack, $needle, $offset = 0, $encoding = null)
    {
	include_once __DIR__.'/../3p/symfony-mbstring-polyfill/Mbstring.class.php';
        return Symfony\Polyfill\Mbstring\Mbstring::mb_strpos($haystack, $needle, $offset, $encoding);
    }
}

if (function_exists('iconv') && !function_exists('mb_strrpos')) {
    function mb_strrpos($haystack, $needle, $offset = 0, $encoding = null)
    {
	include_once __DIR__.'/../3p/symfony-mbstring-polyfill/Mbstring.class.php';
        return Symfony\Polyfill\Mbstring\Mbstring::mb_strrpos($haystack, $needle, $offset, $encoding);
    }
}

if (function_exists('iconv') && !function_exists('mb_str_split')) {
    function mb_str_split($string, $split_length = 1, $encoding = null)
    {
	include_once __DIR__.'/../3p/symfony-mbstring-polyfill/Mbstring.class.php';
        return Symfony\Polyfill\Mbstring\Mbstring::mb_str_split($string, $split_length, $encoding);
    }
}

if (function_exists('iconv') && !function_exists('mb_strtolower')) {
    function mb_strtolower($s, $encoding = null)
    {
	include_once __DIR__.'/../3p/symfony-mbstring-polyfill/Mbstring.class.php';
        return Symfony\Polyfill\Mbstring\Mbstring::mb_strtolower($s, $encoding);
    }
}

if (function_exists('iconv') && !function_exists('mb_strtoupper')) {
    function mb_strtoupper($s, $encoding = null)
    {
	include_once __DIR__.'/../3p/symfony-mbstring-polyfill/Mbstring.class.php';
        return Symfony\Polyfill\Mbstring\Mbstring::mb_strtoupper($s, $encoding);
    }
}

if (function_exists('iconv') && !function_exists('mb_substitute_character')) {
    function mb_substitute_character($c = null)
    {
	include_once __DIR__.'/../3p/symfony-mbstring-polyfill/Mbstring.class.php';
        return Symfony\Polyfill\Mbstring\Mbstring::mb_substitute_character($c);
    }
}

if (function_exists('iconv') && !function_exists('mb_substr')) {
    function mb_substr($s, $start, $length = null, $encoding = null)
    {
	include_once __DIR__.'/../3p/symfony-mbstring-polyfill/Mbstring.class.php';
        return Symfony\Polyfill\Mbstring\Mbstring::mb_substr($s, $start, $length, $encoding);
    }
}

if (function_exists('iconv') && !function_exists('mb_stripos')) {
    function mb_stripos($haystack, $needle, $offset = 0, $encoding = null)
    {
	include_once __DIR__.'/../3p/symfony-mbstring-polyfill/Mbstring.class.php';
        return Symfony\Polyfill\Mbstring\Mbstring::mb_stripos($haystack, $needle, $offset, $encoding);
    }
}

if (function_exists('iconv') && !function_exists('mb_stristr')) {
    function mb_stristr($haystack, $needle, $part = false, $encoding = null)
    {
	include_once __DIR__.'/../3p/symfony-mbstring-polyfill/Mbstring.class.php';
        return Symfony\Polyfill\Mbstring\Mbstring::mb_stristr($haystack, $needle, $part, $encoding);
    }
}

if (function_exists('iconv') && !function_exists('mb_strrchr')) {
    function mb_strrchr($haystack, $needle, $part = false, $encoding = null)
    {
	include_once __DIR__.'/../3p/symfony-mbstring-polyfill/Mbstring.class.php';
        return Symfony\Polyfill\Mbstring\Mbstring::mb_strrchr($haystack, $needle, $part, $encoding);
    }
}

if (function_exists('iconv') && !function_exists('mb_strrichr')) {
    function mb_strrichr($haystack, $needle, $part = false, $encoding = null)
    {
	include_once __DIR__.'/../3p/symfony-mbstring-polyfill/Mbstring.class.php';
        return Symfony\Polyfill\Mbstring\Mbstring::mb_strrichr($haystack, $needle, $part, $encoding);
    }
}

if (function_exists('iconv') && !function_exists('mb_strripos')) {
    function mb_strripos($haystack, $needle, $offset = 0, $encoding = null)
    {
	include_once __DIR__.'/../3p/symfony-mbstring-polyfill/Mbstring.class.php';
        return Symfony\Polyfill\Mbstring\Mbstring::mb_strripos($haystack, $needle, $offset, $encoding);
    }
}

if (function_exists('iconv') && !function_exists('mb_strstr')) {
    function mb_strstr($haystack, $needle, $part = false, $encoding = null)
    {
	include_once __DIR__.'/../3p/symfony-mbstring-polyfill/Mbstring.class.php';
        return Symfony\Polyfill\Mbstring\Mbstring::mb_strstr($haystack, $needle, $part, $encoding);
    }
}

if (function_exists('iconv') && !function_exists('mb_get_info')) {
    function mb_get_info($type = 'all')
    {
	include_once __DIR__.'/../3p/symfony-mbstring-polyfill/Mbstring.class.php';
        return Symfony\Polyfill\Mbstring\Mbstring::mb_get_info($type);
    }
}

if (function_exists('iconv') && !function_exists('mb_http_input')) {
    function mb_http_input($type = '')
    {
	include_once __DIR__.'/../3p/symfony-mbstring-polyfill/Mbstring.class.php';
        return Symfony\Polyfill\Mbstring\Mbstring::mb_http_input($type);
    }
}

if (function_exists('iconv') && !function_exists('mb_http_output')) {
    function mb_http_output($encoding = null)
    {
	include_once __DIR__.'/../3p/symfony-mbstring-polyfill/Mbstring.class.php';
        return Symfony\Polyfill\Mbstring\Mbstring::mb_http_output($encoding);
    }
}

if (function_exists('iconv') && !function_exists('mb_strwidth')) {
    function mb_strwidth($s, $encoding = null)
    {
	include_once __DIR__.'/../3p/symfony-mbstring-polyfill/Mbstring.class.php';
        return Symfony\Polyfill\Mbstring\Mbstring::mb_strwidth($s, $encoding);
    }
}

if (function_exists('iconv') && !function_exists('mb_substr_count')) {
    function mb_substr_count($haystack, $needle, $encoding = null)
    {
	include_once __DIR__.'/../3p/symfony-mbstring-polyfill/Mbstring.class.php';
        return Symfony\Polyfill\Mbstring\Mbstring::mb_substr_count($haystack, $needle, $encoding);
    }
}

if (function_exists('iconv') && !function_exists('mb_output_handler')) {
    function mb_output_handler($contents, $status)
    {
	include_once __DIR__.'/../3p/symfony-mbstring-polyfill/Mbstring.class.php';
        return Symfony\Polyfill\Mbstring\Mbstring::mb_output_handler($contents, $status);
    }
}

if (function_exists('iconv') && !function_exists('mb_chr')) {
    function mb_chr($code, $encoding = null)
    {
	include_once __DIR__.'/../3p/symfony-mbstring-polyfill/Mbstring.class.php';
        return Symfony\Polyfill\Mbstring\Mbstring::mb_chr($code, $encoding);
    }
}

if (function_exists('iconv') && !function_exists('mb_ord')) {
    function mb_ord($s, $encoding = null)
    {
	include_once __DIR__.'/../3p/symfony-mbstring-polyfill/Mbstring.class.php';
        return Symfony\Polyfill\Mbstring\Mbstring::mb_ord($s, $encoding);
    }
}


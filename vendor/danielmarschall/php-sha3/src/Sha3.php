<?php

/*
 * Pure PHP implementation of SHA-3.
 * Revision 2023-02-28
 *
 * The MIT License (MIT)
 * Copyright (c) 2015 Bruno Bierbaumer
 * Copyright (c) 2022-2023 Daniel Marschall
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 */

namespace bb\Sha3;

final class Sha3
{
    const KECCAK_ROUNDS = 24;
    const STATE_SIZE = 1600;
    private static $keccakf_rotc = [1, 3, 6, 10, 15, 21, 28, 36, 45, 55, 2, 14, 27, 41, 56, 8, 25, 43, 62, 18, 39, 61, 20, 44];
    private static $keccakf_piln = [10, 7, 11, 17, 18, 3, 5, 16, 8, 21, 24, 4, 15, 23, 19, 13, 12,2, 20, 14, 22, 9, 6, 1];

    private static function keccakf64(&$st, $rounds)
    {
        $keccakf_rndc = [
            [0x00000000, 0x00000001], [0x00000000, 0x00008082], [0x80000000, 0x0000808a], [0x80000000, 0x80008000],
            [0x00000000, 0x0000808b], [0x00000000, 0x80000001], [0x80000000, 0x80008081], [0x80000000, 0x00008009],
            [0x00000000, 0x0000008a], [0x00000000, 0x00000088], [0x00000000, 0x80008009], [0x00000000, 0x8000000a],
            [0x00000000, 0x8000808b], [0x80000000, 0x0000008b], [0x80000000, 0x00008089], [0x80000000, 0x00008003],
            [0x80000000, 0x00008002], [0x80000000, 0x00000080], [0x00000000, 0x0000800a], [0x80000000, 0x8000000a],
            [0x80000000, 0x80008081], [0x80000000, 0x00008080], [0x00000000, 0x80000001], [0x80000000, 0x80008008]
        ];

        $bc = [];
        for ($round = 0; $round < $rounds; $round++) {

            // Theta
            for ($i = 0; $i < 5; $i++) {
                $bc[$i] = [
                    $st[$i][0] ^ $st[$i + 5][0] ^ $st[$i + 10][0] ^ $st[$i + 15][0] ^ $st[$i + 20][0],
                    $st[$i][1] ^ $st[$i + 5][1] ^ $st[$i + 10][1] ^ $st[$i + 15][1] ^ $st[$i + 20][1]
                ];
            }

            for ($i = 0; $i < 5; $i++) {
                $t = [
                    $bc[($i + 4) % 5][0] ^ (($bc[($i + 1) % 5][0] << 1) | ($bc[($i + 1) % 5][1] >> 31)) & (0xFFFFFFFF),
                    $bc[($i + 4) % 5][1] ^ (($bc[($i + 1) % 5][1] << 1) | ($bc[($i + 1) % 5][0] >> 31)) & (0xFFFFFFFF)
                ];

                for ($j = 0; $j < 25; $j += 5) {
                    $st[$j + $i] = [
                        $st[$j + $i][0] ^ $t[0],
                        $st[$j + $i][1] ^ $t[1]
                    ];
                }
            }

            // Rho Pi
            $t = $st[1];
            for ($i = 0; $i < 24; $i++) {
                $j = self::$keccakf_piln[$i];

                $bc[0] = $st[$j];

                $n = self::$keccakf_rotc[$i];
                $hi = $t[0];
                $lo = $t[1];
                if ($n >= 32) {
                    $n -= 32;
                    $hi = $t[1];
                    $lo = $t[0];
                }

                $st[$j] =[
                    (($hi << $n) | ($lo >> (32 - $n))) & (0xFFFFFFFF),
                    (($lo << $n) | ($hi >> (32 - $n))) & (0xFFFFFFFF)
                ];

                $t = $bc[0];
            }

            //  Chi
            for ($j = 0; $j < 25; $j += 5) {
                for ($i = 0; $i < 5; $i++) {
                    $bc[$i] = $st[$j + $i];
                }
                for ($i = 0; $i < 5; $i++) {
                    $st[$j + $i] = [
                        $st[$j + $i][0] ^ ~$bc[($i + 1) % 5][0] & $bc[($i + 2) % 5][0],
                        $st[$j + $i][1] ^ ~$bc[($i + 1) % 5][1] & $bc[($i + 2) % 5][1]
                    ];
                }
            }

            // Iota
            $st[0] = [
                $st[0][0] ^ $keccakf_rndc[$round][0],
                $st[0][1] ^ $keccakf_rndc[$round][1]
            ];
        }
    }

    private static function keccak64($in_raw, $capacity, $outputlength, $suffix, $raw_output)
    {
        $capacity /= 8;

        $inlen = self::ourStrlen($in_raw);

        $rsiz = 200 - 2 * $capacity;
        $rsizw = $rsiz / 8;

        $st = [];
        for ($i = 0; $i < 25; $i++) {
            $st[] = [0, 0];
        }

        for ($in_t = 0; $inlen >= $rsiz; $inlen -= $rsiz, $in_t += $rsiz) {
            for ($i = 0; $i < $rsizw; $i++) {
                $t = unpack('V*', self::ourSubstr($in_raw, $i * 8 + $in_t, 8));

                $st[$i] = [
                    $st[$i][0] ^ $t[2],
                    $st[$i][1] ^ $t[1]
                ];
            }

            self::keccakf64($st, self::KECCAK_ROUNDS);
        }

        $temp = self::ourSubstr($in_raw, $in_t, $inlen);
        $temp = str_pad($temp, $rsiz, "\x0", STR_PAD_RIGHT);

        $temp[$inlen] = chr($suffix);
        $temp[$rsiz - 1] = chr(ord($temp[$rsiz - 1]) | 0x80);

        for ($i = 0; $i < $rsizw; $i++) {
            $t = unpack('V*', self::ourSubstr($temp, $i * 8, 8));

            $st[$i] = [
                $st[$i][0] ^ $t[2],
                $st[$i][1] ^ $t[1]
            ];
        }

        self::keccakf64($st, self::KECCAK_ROUNDS);

        $out = '';
        for ($i = 0; $i < 25; $i++) {
            $out .= $t = pack('V*', $st[$i][1], $st[$i][0]);
        }
        $r = self::ourSubstr($out, 0, $outputlength / 8);

        return $raw_output ? $r : bin2hex($r);
    }

    private static function keccakf32(&$st, $rounds)
    {
        $keccakf_rndc = [
            [0x0000, 0x0000, 0x0000, 0x0001], [0x0000, 0x0000, 0x0000, 0x8082], [0x8000, 0x0000, 0x0000, 0x0808a], [0x8000, 0x0000, 0x8000, 0x8000],
            [0x0000, 0x0000, 0x0000, 0x808b], [0x0000, 0x0000, 0x8000, 0x0001], [0x8000, 0x0000, 0x8000, 0x08081], [0x8000, 0x0000, 0x0000, 0x8009],
            [0x0000, 0x0000, 0x0000, 0x008a], [0x0000, 0x0000, 0x0000, 0x0088], [0x0000, 0x0000, 0x8000, 0x08009], [0x0000, 0x0000, 0x8000, 0x000a],
            [0x0000, 0x0000, 0x8000, 0x808b], [0x8000, 0x0000, 0x0000, 0x008b], [0x8000, 0x0000, 0x0000, 0x08089], [0x8000, 0x0000, 0x0000, 0x8003],
            [0x8000, 0x0000, 0x0000, 0x8002], [0x8000, 0x0000, 0x0000, 0x0080], [0x0000, 0x0000, 0x0000, 0x0800a], [0x8000, 0x0000, 0x8000, 0x000a],
            [0x8000, 0x0000, 0x8000, 0x8081], [0x8000, 0x0000, 0x0000, 0x8080], [0x0000, 0x0000, 0x8000, 0x00001], [0x8000, 0x0000, 0x8000, 0x8008]
        ];

        $bc = [];
        for ($round = 0; $round < $rounds; $round++) {

            // Theta
            for ($i = 0; $i < 5; $i++) {
                $bc[$i] = [
                    $st[$i][0] ^ $st[$i + 5][0] ^ $st[$i + 10][0] ^ $st[$i + 15][0] ^ $st[$i + 20][0],
                    $st[$i][1] ^ $st[$i + 5][1] ^ $st[$i + 10][1] ^ $st[$i + 15][1] ^ $st[$i + 20][1],
                    $st[$i][2] ^ $st[$i + 5][2] ^ $st[$i + 10][2] ^ $st[$i + 15][2] ^ $st[$i + 20][2],
                    $st[$i][3] ^ $st[$i + 5][3] ^ $st[$i + 10][3] ^ $st[$i + 15][3] ^ $st[$i + 20][3]
                ];
            }

            for ($i = 0; $i < 5; $i++) {
                $t = [
                    $bc[($i + 4) % 5][0] ^ ((($bc[($i + 1) % 5][0] << 1) | ($bc[($i + 1) % 5][1] >> 15)) & (0xFFFF)),
                    $bc[($i + 4) % 5][1] ^ ((($bc[($i + 1) % 5][1] << 1) | ($bc[($i + 1) % 5][2] >> 15)) & (0xFFFF)),
                    $bc[($i + 4) % 5][2] ^ ((($bc[($i + 1) % 5][2] << 1) | ($bc[($i + 1) % 5][3] >> 15)) & (0xFFFF)),
                    $bc[($i + 4) % 5][3] ^ ((($bc[($i + 1) % 5][3] << 1) | ($bc[($i + 1) % 5][0] >> 15)) & (0xFFFF))
                ];

                for ($j = 0; $j < 25; $j += 5) {
                    $st[$j + $i] = [
                        $st[$j + $i][0] ^ $t[0],
                        $st[$j + $i][1] ^ $t[1],
                        $st[$j + $i][2] ^ $t[2],
                        $st[$j + $i][3] ^ $t[3]
                    ];
                }
            }

            // Rho Pi
            $t = $st[1];
            for ($i = 0; $i < 24; $i++) {
                $j = self::$keccakf_piln[$i];
                $bc[0] = $st[$j];


                $n = self::$keccakf_rotc[$i] >> 4;
                $m = self::$keccakf_rotc[$i] % 16;

                $st[$j] =  [
                    ((($t[(0+$n) %4] << $m) | ($t[(1+$n) %4] >> (16-$m))) & (0xFFFF)),
                    ((($t[(1+$n) %4] << $m) | ($t[(2+$n) %4] >> (16-$m))) & (0xFFFF)),
                    ((($t[(2+$n) %4] << $m) | ($t[(3+$n) %4] >> (16-$m))) & (0xFFFF)),
                    ((($t[(3+$n) %4] << $m) | ($t[(0+$n) %4] >> (16-$m))) & (0xFFFF))
                ];

                $t = $bc[0];
            }

            //  Chi
            for ($j = 0; $j < 25; $j += 5) {
                for ($i = 0; $i < 5; $i++) {
                    $bc[$i] = $st[$j + $i];
                }
                for ($i = 0; $i < 5; $i++) {
                    $st[$j + $i] = [
                        $st[$j + $i][0] ^ ~$bc[($i + 1) % 5][0] & $bc[($i + 2) % 5][0],
                        $st[$j + $i][1] ^ ~$bc[($i + 1) % 5][1] & $bc[($i + 2) % 5][1],
                        $st[$j + $i][2] ^ ~$bc[($i + 1) % 5][2] & $bc[($i + 2) % 5][2],
                        $st[$j + $i][3] ^ ~$bc[($i + 1) % 5][3] & $bc[($i + 2) % 5][3]
                    ];
                }
            }

            // Iota
            $st[0] = [
                $st[0][0] ^ $keccakf_rndc[$round][0],
                $st[0][1] ^ $keccakf_rndc[$round][1],
                $st[0][2] ^ $keccakf_rndc[$round][2],
                $st[0][3] ^ $keccakf_rndc[$round][3]
            ];
        }
    }

    private static function keccak32($in_raw, $capacity, $outputlength, $suffix, $raw_output)
    {
        $capacity /= 8;

        $inlen = self::ourStrlen($in_raw);

        $rsiz = 200 - 2 * $capacity;
        $rsizw = $rsiz / 8;

        $st = [];
        for ($i = 0; $i < 25; $i++) {
            $st[] = [0, 0, 0, 0];
        }

        for ($in_t = 0; $inlen >= $rsiz; $inlen -= $rsiz, $in_t += $rsiz) {
            for ($i = 0; $i < $rsizw; $i++) {
                $t = unpack('v*', self::ourSubstr($in_raw, $i * 8 + $in_t, 8));

                $st[$i] = [
                    $st[$i][0] ^ $t[4],
                    $st[$i][1] ^ $t[3],
                    $st[$i][2] ^ $t[2],
                    $st[$i][3] ^ $t[1]
                ];
            }

            self::keccakf32($st, self::KECCAK_ROUNDS);
        }

        $temp = self::ourSubstr($in_raw, $in_t, $inlen);
        $temp = str_pad($temp, $rsiz, "\x0", STR_PAD_RIGHT);

        $temp[$inlen] = chr($suffix);
        $temp[$rsiz - 1] = chr(ord($temp[$rsiz - 1]) | 0x80);

        for ($i = 0; $i < $rsizw; $i++) {
            $t = unpack('v*', self::ourSubstr($temp, $i * 8, 8));

            $st[$i] = [
                $st[$i][0] ^ $t[4],
                $st[$i][1] ^ $t[3],
                $st[$i][2] ^ $t[2],
                $st[$i][3] ^ $t[1]
            ];
        }

        self::keccakf32($st, self::KECCAK_ROUNDS);

        $out = '';
        for ($i = 0; $i < 25; $i++) {
            $out .= $t = pack('v*', $st[$i][3],$st[$i][2], $st[$i][1], $st[$i][0]);
        }
        $r = self::ourSubstr($out, 0, $outputlength / 8);

        return $raw_output ? $r: bin2hex($r);
    }

    private static $force_bits = 0; // for testing purposes
    private static function keccak($in_raw, $capacity, $outputlength, $suffix, $raw_output)
    {
        $bits = self::$force_bits;
        if ($bits == 0) $bits = PHP_INT_SIZE == 4 ? 32 : 64;
        if ($bits == 32) {
            return self::keccak32($in_raw, $capacity, $outputlength, $suffix, $raw_output);
        } else {
            return self::keccak64($in_raw, $capacity, $outputlength, $suffix, $raw_output);
        }
    }

    public static function hash($in, $mdlen, $raw_output = false)
    {
        if( ! in_array($mdlen, [224, 256, 384, 512], true)) {
            throw new \Exception('Unsupported Sha3 Hash output size.');
        }

        return self::keccak($in, $mdlen, $mdlen, 0x06, $raw_output);
    }

    public static function hash_hmac($message, $key, $mdlen, $raw_output=false) {
        // RFC 2104 HMAC
        $blocksize = self::STATE_SIZE - (2 * $mdlen);

        if (strlen($key) > ($blocksize/8)) {
            $k_ = self::hash($key,$mdlen,true);
        } else {
            $k_ = $key;
        }

        $k_opad = str_repeat(chr(0x5C),($blocksize/8));
        $k_ipad = str_repeat(chr(0x36),($blocksize/8));
        for ($i=0; $i<strlen($k_); $i++) {
            $k_opad[$i] = $k_opad[$i] ^ $k_[$i];
            $k_ipad[$i] = $k_ipad[$i] ^ $k_[$i];
        }

        return self::hash($k_opad . self::hash($k_ipad . $message, $mdlen, true), $mdlen, $raw_output);
    }

    public static function hash_pbkdf2($password, $salt, $iterations, $mdlen, $length=0, $binary=false) {
        // https://www.php.net/manual/en/function.hash-pbkdf2.php#118301
        // TODO: This is extremely slow! Can we improve it somehow?

        if (!is_numeric($iterations)) trigger_error(__FUNCTION__ . '(): expects parameter 3 to be long, ' . gettype($iterations) . ' given', E_USER_WARNING);
        if (!is_numeric($length)) trigger_error(__FUNCTION__ . '(): expects parameter 4 to be long, ' . gettype($length) . ' given', E_USER_WARNING);
        if ($iterations <= 0) trigger_error(__FUNCTION__ . '(): Iterations must be a positive integer: ' . $iterations, E_USER_WARNING);
        if ($length < 0) trigger_error(__FUNCTION__ . '(): Length must be greater than or equal to 0: ' . $length, E_USER_WARNING);

        $output = '';
        $block_count = $length ? ceil($length / strlen(self::hash('', $mdlen, $binary))) : 1;
        for ($i = 1; $i <= $block_count; $i++)
        {
            $last = $xorsum = self::hash_hmac($salt . pack('N', $i), $password, $mdlen, true);
            for ($j = 1; $j < $iterations; $j++)
            {
                $xorsum ^= ($last = self::hash_hmac($last, $password, $mdlen, true));
            }
            $output .= $xorsum;
        }

        if (!$binary) $output = bin2hex($output);
        return $length ? substr($output, 0, $length) : $output;
    }

    public static function shake($in, $security_level, $outlen, $raw_output = false)
    {
        if( ! in_array($security_level, [128, 256], true)) {
            throw new \Exception('Unsupported Sha3 Shake security level.');
        }

        return self::keccak($in, $security_level, $outlen, 0x1f, $raw_output);
    }

    /**
     *  Multi-byte-safe string functions borrowed from https://github.com/sarciszewski/php-future
     */

    /**
     * Multi-byte-safe string length calculation
     *
     * @param string $str
     * @return int
     */
    private static function ourStrlen($str)
    {
        // Premature optimization: cache the function_exists() result
        static $exists = null;
        if ($exists === null) {
            $exists = \function_exists('\\mb_strlen');
        }
        // If it exists, we need to make sure we're using 8bit mode
        if ($exists) {
            $length =  \mb_strlen($str, '8bit');
            if ($length === false) {
                throw new \Exception('mb_strlen() failed.');
            }
            return $length;
        }

        return \strlen($str);
    }

    /**
     * Multi-byte-safe substring calculation
     *
     * @param string $str
     * @param int $start
     * @param int $length (optional)
     * @return string
     */
    private static function ourSubstr($str, $start = 0, $length = null)
    {
        // Premature optimization: cache the function_exists() result
        static $exists = null;
        if ($exists === null) {
            $exists = \function_exists('\\mb_substr');
        }
        // If it exists, we need to make sure we're using 8bit mode
        if ($exists) {
            return \mb_substr($str, $start, $length, '8bit');
        } elseif ($length !== null) {
            return \substr($str, $start, $length);
        }
        return \substr($str, $start);
    }
}

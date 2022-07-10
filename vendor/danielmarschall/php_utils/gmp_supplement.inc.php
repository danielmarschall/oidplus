<?php

/*
 * PHP GMP-Supplement implemented using BCMath
 * Copyright 2020-2022 Daniel Marschall, ViaThinkSoft
 * Version 2021-06-29
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

if (function_exists('bcadd')) {
	// ----------------- Implementation of GMP functions using BCMath -----------------

	if (!function_exists('gmp_init') ) {
		define('GMP_ROUND_ZERO',     0);
		define('GMP_ROUND_PLUSINF',  1);
		define('GMP_ROUND_MINUSINF', 2);
		define('GMP_MSW_FIRST',      1);
		define('GMP_LSW_FIRST',      2);
		define('GMP_LITTLE_ENDIAN',  4);
		define('GMP_BIG_ENDIAN',     8);
		define('GMP_NATIVE_ENDIAN', 16);
		define('GMP_VERSION',  '6.0.0');

		// gmp_abs ( GMP $a ) : GMP
		// Absolute value
		function gmp_abs($a) {
			bcscale(0);
			if (bccomp($a, "0") == 1) {
				return $a;
			} else {
				return bcmul($a, "-1");
			}
		}

		// gmp_add ( GMP $a , GMP $b ) : GMP
		// Add numbers
		function gmp_add($a, $b) {
			bcscale(0);

			// bcadd ( string $left_operand , string $right_operand [, int $scale = 0 ] ) : string
			return bcadd($a, $b);
		}

		// gmp_and ( GMP $a , GMP $b ) : GMP
		// Bitwise AND
		function gmp_and($a, $b) {
			bcscale(0);
			// Convert $a and $b to a binary string
			$ab = bc_dec2bin($a);
			$bb = bc_dec2bin($b);
			$length = max(strlen($ab), strlen($bb));
			$ab = str_pad($ab, $length, "0", STR_PAD_LEFT);
			$bb = str_pad($bb, $length, "0", STR_PAD_LEFT);

			// Do the bitwise binary operation
			$cb = '';
			for ($i=0; $i<$length; $i++) {
				$cb .= (($ab[$i] == 1) and ($bb[$i] == 1)) ? '1' : '0';
			}

			// Convert back to a decimal number
			return bc_bin2dec($cb);
		}

		// gmp_binomial ( mixed $n , int $k ) : GMP
		// Calculates binomial coefficient
		function gmp_binomial($n, $k) {
			bcscale(0);
			throw new Exception("gmp_binomial() NOT IMPLEMENTED");
		}

		// gmp_clrbit ( GMP $a , int $index ) : void
		// Clear bit
		function gmp_clrbit(&$a, $index) {
			bcscale(0);
			gmp_setbit($a, $index, false);
		}

		// gmp_cmp ( GMP $a , GMP $b ) : int
		// Compare numbers
		function gmp_cmp($a, $b) {
			bcscale(0);

			// bccomp ( string $left_operand , string $right_operand [, int $scale = 0 ] ) : int
			return bccomp($a, $b);
		}

		// gmp_com ( GMP $a ) : GMP
		// Calculates one's complement
		function gmp_com($a) {
			bcscale(0);
			// Convert $a and $b to a binary string
			$ab = bc_dec2bin($a);

			// Swap every bit
			for ($i=0; $i<strlen($ab); $i++) {
				$ab[$i] = ($ab[$i] == '1' ? '0' : '1');
			}

			// Convert back to a decimal number
			return bc_bin2dec($ab);
		}

		// gmp_div_q ( GMP $a , GMP $b [, int $round = GMP_ROUND_ZERO ] ) : GMP
		// Divide numbers
		function gmp_div_q($a, $b, $round = GMP_ROUND_ZERO/*$round not implemented*/) {
			bcscale(0);

			// bcdiv ( string $dividend , string $divisor [, int $scale = 0 ] ) : string
			return bcdiv($a, $b);
		}

		// Divide numbers and get quotient and remainder
		// gmp_div_qr ( GMP $n , GMP $d [, int $round = GMP_ROUND_ZERO ] ) : array
		function gmp_div_qr($n, $d, $round = GMP_ROUND_ZERO/*$round not implemented*/) {
			bcscale(0);
			return array(
				gmp_div_q($n, $d, $round),
				gmp_div_r($n, $d, $round)
			);
		}

		// Remainder of the division of numbers
		// gmp_div_r ( GMP $n , GMP $d [, int $round = GMP_ROUND_ZERO ] ) : GMP
		function gmp_div_r($n, $d, $round = GMP_ROUND_ZERO/*$round not implemented*/) {
			bcscale(0);
			// The remainder operator can be used with negative integers. The rule is:
			// - Perform the operation as if both operands were positive.
			// - If the left operand is negative, then make the result negative.
			// - If the left operand is positive, then make the result positive.
			// - Ignore the sign of the right operand in all cases.
			$r = bcmod($n, $d);
			if (bccomp($n, "0") < 0) $r = bcmul($r, "-1");
			return $r;
		}

		// gmp_div ( GMP $a , GMP $b [, int $round = GMP_ROUND_ZERO ] ) : GMP
		// Divide numbers
		function gmp_div($a, $b, $round = GMP_ROUND_ZERO/*$round not implemented*/) {
			bcscale(0);
			return gmp_div_q($a, $b, $round); // Alias von gmp_div_q
		}

		// gmp_divexact ( GMP $n , GMP $d ) : GMP
		// Exact division of numbers
		function gmp_divexact($n, $d) {
			bcscale(0);
			return bcdiv($n, $d);
		}

		// gmp_export ( GMP $gmpnumber [, int $word_size = 1 [, int $options = GMP_MSW_FIRST | GMP_NATIVE_ENDIAN ]] ) : string
		// Export to a binary string
		function gmp_export($gmpnumber, $word_size = 1, $options = GMP_MSW_FIRST | GMP_NATIVE_ENDIAN) {
			if ($word_size != 1) throw new Exception("Word size != 1 not implemented");
			if ($options != GMP_MSW_FIRST | GMP_NATIVE_ENDIAN) throw new Exception("Different options not implemented");

			bcscale(0);
			$gmpnumber = bcmul($gmpnumber,"1"); // normalize
			return gmp_init(bin2hex($gmpnumber), 16);
		}

		// gmp_fact ( mixed $a ) : GMP
		// Factorial
		function gmp_fact($a) {
			bcscale(0);
			return bcfact($a);
		}

		// gmp_gcd ( GMP $a , GMP $b ) : GMP
		// Calculate GCD
		function gmp_gcd($a, $b) {
			bcscale(0);
			return gmp_gcdext($a, $b)['g'];
		}

		// gmp_gcdext ( GMP $a , GMP $b ) : array
		// Calculate GCD and multipliers
		function gmp_gcdext($a, $b) {
			bcscale(0);

			// Source: https://github.com/phpseclib/phpseclib/blob/master/phpseclib/Math/BigInteger/Engines/BCMath.php#L285
			// modified to make it fit here and to be compatible with gmp_gcdext

			$s = '1';
			$t = '0';
			$s_ = '0';
			$t_ = '1';

			while (bccomp($b, '0', 0) != 0) {
				$q = bcdiv($a, $b, 0);

				$temp = $a;
				$a = $b;
				$b = bcsub($temp, bcmul($b, $q, 0), 0);

				$temp = $s;
				$s = $s_;
				$s_ = bcsub($temp, bcmul($s, $q, 0), 0);

				$temp = $t;
				$t = $t_;
				$t_ = bcsub($temp, bcmul($t, $q, 0), 0);
			}

			return [
				'g' => /*$this->normalize*/($a),
				's' => /*$this->normalize*/($s),
				't' => /*$this->normalize*/($t)
			];
		}

		// gmp_hamdist ( GMP $a , GMP $b ) : int
		// Hamming distance
		function gmp_hamdist($a, $b) {
			bcscale(0);
			throw new Exception("gmp_hamdist() NOT IMPLEMENTED");
		}

		// gmp_import ( string $data [, int $word_size = 1 [, int $options = GMP_MSW_FIRST | GMP_NATIVE_ENDIAN ]] ) : GMP
		// Import from a binary string
		function gmp_import($data, $word_size=1, $options=GMP_MSW_FIRST | GMP_NATIVE_ENDIAN) {
			bcscale(0);

			if ($word_size != 1) throw new Exception("Word size != 1 not implemented");
			if ($options != GMP_MSW_FIRST | GMP_NATIVE_ENDIAN) throw new Exception("Different options not implemented");

			return gmp_init(hex2bin(gmp_strval(gmp_init($data), 16)));
		}

		// gmp_init ( mixed $number [, int $base = 0 ] ) : GMP
		// Create GMP number
		function gmp_init($number, $base=0) {
			bcscale(0);
			if ($base == 0) {
				// If base is 0 (default value), the actual base is determined from the leading characters:
				// if the first two characters are 0x or 0X, hexadecimal is assumed,
				// otherwise if the first character is "0", octal is assumed,
				// otherwise decimal is assumed.
				if (strtoupper(substr($number, 0, 2)) == '0X') {
					$base = 16;
				} else if (strtoupper(substr($number, 0, 1)) == '0') {
					$base = 8;
				} else {
					$base = 10;
				}
			}

			if ($base == 10) {
				return $number;
			} else {
				return base_convert_bigint($number, $base, 10);
			}
		}

		// gmp_intval ( GMP $gmpnumber ) : int
		// Convert GMP number to integer
		function gmp_intval($gmpnumber) {
			bcscale(0);
			return (int)$gmpnumber;
		}

		// gmp_invert ( GMP $a , GMP $b ) : GMP
		// Inverse by modulo
		function gmp_invert($a, $b) {
			bcscale(0);

			// Source: https://github.com/CityOfZion/neo-php/blob/master/src/Crypto/NumberTheory.php#L246

			while (bccomp($a, '0')==-1) {
				$a=bcadd($b, $a);
			}
			while (bccomp($b, $a)==-1) {
				$a=bcmod($a, $b);
			}
			$c=$a;
			$d=$b;
			$uc=1;
			$vc=0;
			$ud=0;
			$vd=1;
			while (bccomp($c, '0')!=0) {
				$temp1=$c;
				$q=bcdiv($d, $c, 0);
				$c=bcmod($d, $c);
				$d=$temp1;
				$temp2=$uc;
				$temp3=$vc;
				$uc=bcsub($ud, bcmul($q, $uc));
				$vc=bcsub($vd, bcmul($q, $vc));
				$ud=$temp2;
				$vd=$temp3;
			}
			$result='';
			if (bccomp($d, '1')==0) {
				if (bccomp($ud, '0')==1) {
					$result=$ud;
				} else {
					$result=bcadd($ud, $b);
				}
			} else {
				throw new ErrorException("ERROR: $a and $b are NOT relatively prime.");
			}
			return $result;
		}

		// gmp_jacobi ( GMP $a , GMP $p ) : int
		// Jacobi symbol
		function gmp_jacobi($a, $p) {
			bcscale(0);

			// Source: https://github.com/CityOfZion/neo-php/blob/master/src/Crypto/NumberTheory.php#L136

			if ($p>=3 && $p%2==1) {
				$a = bcmod($a, $p);
				if ($a == '0') return '0';
				if ($a == '1') return '1';
				$a1 = $a;
				$e = 0;
				while (bcmod($a1, '2') == '0') {
					$a1 = bcdiv($a1, '2');
					$e = bcadd($e, '1');
				}
				$s = (bcmod($e, '2')=='0' || bcmod($p, '8')=='1' || bcmod($p, '8')=='7') ? '1' : '-1';
				if ($a1 == '1') return $s;
				if (bcmod($p, '4')=='3' && bcmod($a1, '4')=='3') $s = -$s;
				return bcmul($s, (string)gmp_jacobi(bcmod($p, $a1), $a1));
			} else {
				return false;
			}
		}

		// gmp_kronecker ( mixed $a , mixed $b ) : int
		// Kronecker symbol
		function gmp_kronecker($a, $b) {
			bcscale(0);
			throw new Exception("gmp_kronecker() NOT IMPLEMENTED");
		}

		// gmp_lcm ( mixed $a , mixed $b ) : GMP
		// Calculate LCM
		function gmp_lcm($a, $b) {
			bcscale(0);

			if ((bccomp($a,'0')==0) && (bccomp($b,'0')==0)) {
				return '0';
			} else {
				return gmp_div(gmp_abs(gmp_mul($a,$b)), gmp_gcd($a,$b));
			}
		}

		// gmp_legendre ( GMP $a , GMP $p ) : int
		// Legendre symbol
		function gmp_legendre($a, $p) {
			bcscale(0);
			throw new Exception("gmp_legendre() NOT IMPLEMENTED");
		}

		// gmp_mod ( GMP $n , GMP $d ) : GMP
		// Modulo operation
		function gmp_mod($n, $d) {
			bcscale(0);

			// bcmod ( string $dividend , string $divisor [, int $scale = 0 ] ) : string
			return bcmod($n, $d);
		}

		// gmp_mul ( GMP $a , GMP $b ) : GMP
		// Multiply numbers
		function gmp_mul($a, $b) {
			bcscale(0);

			// bcmul ( string $left_operand , string $right_operand [, int $scale = 0 ] ) : string
			return bcmul($a, $b);
		}

		// gmp_neg ( GMP $a ) : GMP
		// Negate number
		function gmp_neg($a) {
			bcscale(0);
			return bcmul($a, "-1");
		}

		// gmp_nextprime ( int $a ) : GMP
		// Find next prime number
		function gmp_nextprime($a) {
			bcscale(0);

			// Source: https://github.com/CityOfZion/neo-php/blob/master/src/Crypto/NumberTheory.php#L692

			if (bccomp($a, '2') == '-1') {
				return '2';
			}
			$result = gmp_or(bcadd($a, '1'), '1');
			while (!gmp_prob_prime($result)) {
				$result = bcadd($result, '2');
			}
			return $result;
		}

		// gmp_or ( GMP $a , GMP $b ) : GMP
		// Bitwise OR
		function gmp_or($a, $b) {
			bcscale(0);
			// Convert $a and $b to a binary string
			$ab = bc_dec2bin($a);
			$bb = bc_dec2bin($b);
			$length = max(strlen($ab), strlen($bb));
			$ab = str_pad($ab, $length, "0", STR_PAD_LEFT);
			$bb = str_pad($bb, $length, "0", STR_PAD_LEFT);

			// Do the bitwise binary operation
			$cb = '';
			for ($i=0; $i<$length; $i++) {
				$cb .= (($ab[$i] == 1) or ($bb[$i] == 1)) ? '1' : '0';
			}

			// Convert back to a decimal number
			return bc_bin2dec($cb);
		}

		// gmp_perfect_power ( mixed $a ) : bool
		// Perfect power check
		function gmp_perfect_power($a) {
			bcscale(0);
			throw new Exception("gmp_perfect_power() NOT IMPLEMENTED");
		}

		// gmp_perfect_square ( GMP $a ) : bool
		// Perfect square check
		function gmp_perfect_square($a) {
			bcscale(0);
			throw new Exception("gmp_perfect_square() NOT IMPLEMENTED");
		}

		// gmp_popcount ( GMP $a ) : int
		// Population count
		function gmp_popcount($a) {
			bcscale(0);
			$ab = bc_dec2bin($a);
			return substr_count($ab, '1');
		}

		// gmp_pow ( GMP $base , int $exp ) : GMP
		// Raise number into power
		function gmp_pow($base, $exp) {
			bcscale(0);

			// bcpow ( string $base , string $exponent [, int $scale = 0 ] ) : string
			return bcpow($base, $exp);
		}

		// gmp_powm ( GMP $base , GMP $exp , GMP $mod ) : GMP
		// Raise number into power with modulo
		function gmp_powm($base, $exp, $mod) {
			bcscale(0);

			// bcpowmod ( string $base , string $exponent , string $modulus [, int $scale = 0 ] ) : string
			return bcpowmod($base, $exp, $mod);
		}

		// gmp_prob_prime ( GMP $a [, int $reps = 10 ] ) : int
		// Check if number is "probably prime"
		function gmp_prob_prime($a, $reps=10) {
			bcscale(0);

			// Source: https://github.com/CityOfZion/neo-php/blob/master/src/Crypto/NumberTheory.php#L655

			$t = 40;
			$k = 0;
			$m = bcsub($reps, '1');
			while (bcmod($m, '2') == '0') {
				$k = bcadd($k, '1');
				$m = bcdiv($m, '2');
			}
			for ($i=0; $i<$t; $i++) {
				$a = bcrand('1', bcsub($reps, '1'));
				if ($m < 0) {
					return new ErrorException("Negative exponents ($m) not allowed");
				} else {
					$b0 = bcpowmod($a, $m, $reps);
				}
				if ($b0!=1 && $b0!=bcsub($reps, '1')) {
					$j = 1;
					while ($j<=$k-1 && $b0!=bcsub($reps, '1')) {
						$b0 = bcpowmod($b0, '2', $reps);
						if ($b0 == 1) {
							return false;
						}
						$j++;
					}
					if ($b0 != bcsub($reps, '1')) {
						return false;
					}
				}
			}
			return true;
		}

		// gmp_random_bits ( int $bits ) : GMP
		// Random number
		function gmp_random_bits($bits) {
			bcscale(0);
			$min = 0;
			$max = bcsub(bcpow('2', $bits), '1');
			return bcrand($min, $max);
		}

		// gmp_random_range ( GMP $min , GMP $max ) : GMP
		// Random number
		function gmp_random_range($min, $max) {
			bcscale(0);
			return bcrand($min, $max);
		}

		// gmp_random_seed ( mixed $seed ) : void
		// Sets the RNG seed
		function gmp_random_seed($seed) {
			bcscale(0);
			bcrand_seed($seed);
		}

		// gmp_random ([ int $limiter = 20 ] ) : GMP
		// Random number (deprecated)
		function gmp_random($limiter=20) {
			bcscale(0);
			throw new Exception("gmp_random() is deprecated! Please use gmp_random_bits() or gmp_random_range() instead.");
		}

		// gmp_root ( GMP $a , int $nth ) : GMP
		// Take the integer part of nth root
		function gmp_root($a, $nth) {
			bcscale(0);
			throw new Exception("gmp_root() NOT IMPLEMENTED");
		}

		// gmp_rootrem ( GMP $a , int $nth ) : array
		// Take the integer part and remainder of nth root
		function gmp_rootrem($a, $nth) {
			bcscale(0);
			throw new Exception("gmp_rootrem() NOT IMPLEMENTED");
		}

		// gmp_scan0 ( GMP $a , int $start ) : int
		// Scan for 0
		function gmp_scan0($a, $start) {
			bcscale(0);

			$ab = bc_dec2bin($a);

			if ($start < 0) throw new Exception("Starting index must be greater than or equal to zero");
			if ($start >= strlen($ab)) return $start;

			for ($i=$start; $i<strlen($ab); $i++) {
				if ($ab[strlen($ab)-1-$i] == '0') {
					return $i;
				}
			}

			return false;
		}

		// gmp_scan1 ( GMP $a , int $start ) : int
		// Scan for 1
		function gmp_scan1($a, $start) {
			bcscale(0);

			$ab = bc_dec2bin($a);

			if ($start < 0) throw new Exception("Starting index must be greater than or equal to zero");
			if ($start >= strlen($ab)) return -1;

			for ($i=$start; $i<strlen($ab); $i++) {
				if ($ab[strlen($ab)-1-$i] == '1') {
					return $i;
				}
			}

			return false;
		}

		// gmp_setbit ( GMP $a , int $index [, bool $bit_on = TRUE ] ) : void
		// Set bit
		function gmp_setbit(&$a, $index, $bit_on=TRUE) {
			bcscale(0);
			$ab = bc_dec2bin($a);

			if ($index < 0) throw new Exception("Invalid index");
			if ($index >= strlen($ab)) {
				$ab = str_pad($ab, $index+1, '0', STR_PAD_LEFT);
			}

			$ab[strlen($ab)-1-$index] = $bit_on ? '1' : '0';

			$a = bc_bin2dec($ab);
		}

		// gmp_sign ( GMP $a ) : int
		// Sign of number
		function gmp_sign($a) {
			bcscale(0);
			return bccomp($a, "0");
		}

		// gmp_sqrt ( GMP $a ) : GMP
		// Calculate square root
		function gmp_sqrt($a) {
			bcscale(0);

			// bcsqrt ( string $operand [, int $scale = 0 ] ) : string
			return bcsqrt($a);
		}

		// gmp_sqrtrem ( GMP $a ) : array
		// Square root with remainder
		function gmp_sqrtrem($a) {
			bcscale(0);
			throw new Exception("gmp_sqrtrem() NOT IMPLEMENTED");
		}

		// gmp_strval ( GMP $gmpnumber [, int $base = 10 ] ) : string
		// Convert GMP number to string
		function gmp_strval($gmpnumber, $base=10) {
			bcscale(0);
			if ($base == 10) {
				return $gmpnumber;
			} else {
				return base_convert_bigint($gmpnumber, 10, $base);
			}
		}

		// gmp_sub ( GMP $a , GMP $b ) : GMP
		// Subtract numbers
		function gmp_sub($a, $b) {
			bcscale(0);

			// bcsub ( string $left_operand , string $right_operand [, int $scale = 0 ] ) : string
			return bcsub($a, $b);
		}

		// gmp_testbit ( GMP $a , int $index ) : bool
		// Tests if a bit is set
		function gmp_testbit($a, $index) {
			bcscale(0);
			$ab = bc_dec2bin($a);

			if ($index < 0) throw new Exception("Invalid index");
			if ($index >= strlen($ab)) return ('0' == '1');

			return $ab[strlen($ab)-1-$index] == '1';
		}

		// gmp_xor ( GMP $a , GMP $b ) : GMP
		// Bitwise XOR
		function gmp_xor($a, $b) {
			bcscale(0);
			// Convert $a and $b to a binary string
			$ab = bc_dec2bin($a);
			$bb = bc_dec2bin($b);
			$length = max(strlen($ab), strlen($bb));
			$ab = str_pad($ab, $length, "0", STR_PAD_LEFT);
			$bb = str_pad($bb, $length, "0", STR_PAD_LEFT);

			// Do the bitwise binary operation
			$cb = '';
			for ($i=0; $i<$length; $i++) {
				$cb .= (($ab[$i] == 1) xor ($bb[$i] == 1)) ? '1' : '0';
			}

			// Convert back to a decimal number
			return bc_bin2dec($cb);
		}
	}

	// ----------------- Helper functions -----------------

	function base_convert_bigint($numstring, $frombase, $tobase) {
		$numstring = "".$numstring;

		$frombase_str = '';
		for ($i=0; $i<$frombase; $i++) {
			$frombase_str .= strtoupper(base_convert((string)$i, 10, 36));
		}

		$tobase_str = '';
		for ($i=0; $i<$tobase; $i++) {
			$tobase_str .= strtoupper(base_convert((string)$i, 10, 36));
		}

		$length = strlen($numstring);
		$result = '';
		$number = array();
		for ($i = 0; $i < $length; $i++) {
			$number[$i] = stripos($frombase_str, $numstring[$i]);
		}
		do { // Loop until whole number is converted
			$divide = 0;
			$newlen = 0;
			for ($i = 0; $i < $length; $i++) { // Perform division manually (which is why this works with big numbers)
				$divide = $divide * $frombase + $number[$i];
				if ($divide >= $tobase) {
					$number[$newlen++] = (int)($divide / $tobase);
					$divide = $divide % $tobase;
				} else if ($newlen > 0) {
					$number[$newlen++] = 0;
				}
			}
			$length = $newlen;
			$result = $tobase_str[$divide] . $result; // Divide is basically $numstring % $tobase (i.e. the new character)
		}
		while ($newlen != 0);

		return $result;
	}

	function bc_dec2bin($decimal_i) {
		// https://www.exploringbinary.com/base-conversion-in-php-using-bcmath/

		bcscale(0);

		$binary_i = '';
		do {
			$binary_i = bcmod($decimal_i,'2') . $binary_i;
			$decimal_i = bcdiv($decimal_i,'2');
		} while (bccomp($decimal_i,'0'));

		return $binary_i;
	}

	function bc_bin2dec($binary_i) {
		// https://www.exploringbinary.com/base-conversion-in-php-using-bcmath/

		bcscale(0);

		$decimal_i = '0';
		for ($i = 0; $i < strlen($binary_i); $i++) {
			$decimal_i = bcmul($decimal_i,'2');
			$decimal_i = bcadd($decimal_i,$binary_i[$i]);
		}

		return $decimal_i;
	}

	// ----------------- New functions -----------------

	// Newly added: gmp_not / bcnot
	function bcnot($a) {
		bcscale(0);
		// Convert $a to a binary string
		$ab = bc_dec2bin($a);
		$length = strlen($ab);

		// Do the bitwise binary operation
		$cb = '';
		for ($i=0; $i<$length; $i++) {
			$cb .= ($ab[$i] == 1) ? '0' : '1';
		}

		// Convert back to a decimal number
		return bc_bin2dec($cb);
	}
	function gmp_not($a) {
		bcscale(0);
		return bcnot($a);
	}

	// Newly added: bcshiftl / gmp_shiftl
	function bcshiftl($num, $bits) {
		bcscale(0);
		return bcmul($num, bcpow('2', $bits));
	}
	function gmp_shiftl($num, $bits) {
		bcscale(0);
		return bcshiftl($num, $bits);
	}

	// Newly added: bcshiftr / gmp_shiftr
	function bcshiftr($num, $bits) {
		bcscale(0);
		return bcdiv($num, bcpow('2', $bits));
	}
	function gmp_shiftr($num, $bits) {
		bcscale(0);
		return bcshiftr($num, $bits);
	}

	// Newly added: bcfact (used by gmp_fact)
	function bcfact($a) {
		bcscale(0);

		// Source: https://www.php.net/manual/de/book.bc.php#116510

		if (!filter_var($a, FILTER_VALIDATE_INT) || $a <= 0) {
			throw new InvalidArgumentException(sprintf('Argument must be natural number, "%s" given.', $a));
		}

		for ($result = '1'; $a > 0; $a--) {
			$result = bcmul($result, $a);
		}

		return $result;
	}

	// Newly added (used by gmp_prob_prime, gmp_random_range and gmp_random_bits)
	function bcrand($min, $max = false) {
		bcscale(0);
		// Source: https://github.com/CityOfZion/neo-php/blob/master/src/Crypto/BCMathUtils.php#L7
		// Fixed: https://github.com/CityOfZion/neo-php/issues/16
		if (!$max) {
			$max = $min;
			$min = 0;
		}
		return bcadd(bcmul(bcdiv((string)mt_rand(), (string)mt_getrandmax(), strlen($max)), bcsub(bcadd($max, '1'), $min)), $min);
	}

	// Newly added (used by gmp_random_seed)
	function bcrand_seed($seed) {
		bcscale(0);
		mt_srand($seed);
	}
}

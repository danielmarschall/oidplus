<?php

/*
 * OidDerConverter.class.php, Version 1.1; Based on version 1.11 of oid.c
 * Copyright 2014-2015 Daniel Marschall, ViaThinkSoft
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


// Note: Leading zeros are permitted in dotted notation.

// TODO: define output format as parameter; don't force the user to use hexStrToArray

class OidDerConverter {

	/**
	 * @arg $str "\x12\x23" or "12 34"
	 * @return array(0x12, 0x23)
	 */
	// Doesn't need to be public, but it is a nice handy function, especially in the testcases
	public static function hexStrToArray($str) {
		$out = array();

		$str = str_replace('\x', ' ', $str);
		$str = trim($str);
		$ary = explode(' ', $str);

		foreach ($ary as &$a) {
			$out[] = hexdec($a);
		}

		return $out;
	}

	/**
	 * @return Outputs .<oid> for an absolute OID and <oid> for a relative OID.
	 */
	public static function derToOID($abBinary, $verbose=false) {
		$output_oid = array();
		$output_absolute = true;

		if (count($abBinary) < 3) {
			if ($verbose) fprintf(STDERR, "Encoded OID must have at least three bytes!\n");
			return false;
		}

		$nBinary = count($abBinary);
		$ll = gmp_init(0);
		$fOK = false;
		$fSub = 0; // Subtract value from next number output. Used when encoding {2 48} and up

		// 0 = Universal Class Identifier Tag (can be more than 1 byte, but not in our case)
		// 1 = Length part (may have more than 1 byte!)
		// 2 = First two arc encoding
		// 3 = Encoding of arc three and higher
		$part = 0;

		$lengthbyte_count = 0;
		$lengthbyte_pos = 0;
		$lengthfinished = false;

		$arcBeginning = true;

		foreach ($abBinary as $nn => &$pb) {
			if ($part == 0) { // Class Tag
				// Leading octet
				// Bit 7 / Bit 6 = Universal (00), Application (01), Context (10), Private(11)
				// Bit 5 = Primitive (0), Constructed (1)
				// Bit 4..0 = 00000 .. 11110 => Tag 0..30, 11111 for Tag > 30 (following bytes with the highest bit as "more" bit)
				// --> We don't need to respect 11111 (class-tag encodes in more than 1 octet)
				//     as we terminate when the tag is not of type OID or RELATEIVE-OID
				// See page 396 of "ASN.1 - Communication between Heterogeneous Systems" by Olivier Dubuisson.

				// Class: 8. - 7. bit
				// 0 (00) = Universal
				// 1 (01) = Application
				// 2 (10) = Context
				// 3 (11) = Private
				$cl = (($pb & 0xC0) >> 6) & 0x03;
				if ($cl != 0) {
					if ($verbose) fprintf(STDERR, "Error at type: The OID tags are only defined as UNIVERSAL class tags.\n");
					return false;
				}

				// Primitive/Constructed: 6. bit
				// 0 = Primitive
				// 1 = Constructed
				$pc = $pb & 0x20;
				if ($pc != 0) {
					if ($verbose) fprintf(STDERR, "Error at type: OIDs must be primitive, not constructed.\n");
					return false;
				}

				// Tag number: 5. - 1. bit
				$tag = $pb & 0x1F;
				if ($tag == 0x0D) {
					$isRelative = true;
				} else if ($tag == 0x06) {
					$isRelative = false;
				} else {
					if ($verbose) fprintf(STDERR, "Error at type: The tag number is neither an absolute OID (0x06) nor a relative OID (0x0D).\n");
					return false;
				}

				// Output
				$output_absolute = !$isRelative;

				$part++;
			} else if ($part == 1) { // Length
				// Find out the length and save it into $ll

				// [length] is encoded as follows:
				//  0x00 .. 0x7F = The actual length is in this byte, followed by [data].
				//  0x80 + n     = The length of [data] is spread over the following 'n' bytes. (0 < n < 0x7F)
				//  0x80         = "indefinite length" (only constructed form) -- Invalid
				//  0xFF         = Reserved for further implementations -- Invalid
				//  See page 396 of "ASN.1 - Communication between Heterogeneous Systems" by Olivier Dubuisson.

				if ($nn == 1) { // The first length byte
					$lengthbyte_pos = 0;
					if (($pb & 0x80) != 0) {
						// 0x80 + n => The length is spread over the following 'n' bytes
						$lengthfinished = false;
						$lengthbyte_count = $pb & 0x7F;
						if ($lengthbyte_count == 0x00) {
							if ($verbose) fprintf(STDERR, "Length value 0x80 is invalid (\"indefinite length\") for primitive types.\n");
							return false;
						} else if ($lengthbyte_count == 0x7F) {
							if ($verbose) fprintf(STDERR, "Length value 0xFF is reserved for further extensions.\n");
							return false;
						}
						$fOK = false;
					} else {
						// 0x01 .. 0x7F => The actual length

						if ($pb == 0x00) {
							if ($verbose) fprintf(STDERR, "Length value 0x00 is invalid for an OID.\n");
							return false;
						}

						$ll = gmp_init($pb);
						$lengthfinished = true;
						$lengthbyte_count = 0;
						$fOK = true;
					}
				} else {
					if ($lengthbyte_count > $lengthbyte_pos) {
						$ll = gmp_mul($ll, 0x100);
						$ll = gmp_add($ll, $pb);
						$lengthbyte_pos++;
					}

					if ($lengthbyte_count == $lengthbyte_pos) {
						$lengthfinished = true;
						$fOK = true;
					}
				}

				if ($lengthfinished) { // The length is now in $ll
					if (gmp_cmp($ll, $nBinary - 2 - $lengthbyte_count) != 0) {
						if ($verbose) fprintf(STDERR, "Invalid length (%d entered, but %s expected)\n", $nBinary - 2, gmp_strval($ll, 10));
						return false;
					}
					$ll = gmp_init(0); // reset for later usage
					$fOK = true;
					$part++;
					if ($isRelative) $part++; // Goto step 3!
				}
			} else if ($part == 2) { // First two arcs
				$first = $pb / 40;
				$second = $pb % 40;
				if ($first > 2) {
					$first = 2;
					$output_oid[] = $first;
					$arcBeginning = true;

					if (($pb & 0x80) != 0) {
						// 2.48 and up => 2+ octets
						// Output in "part 3"

						if ($arcBeginning && ($pb == 0x80)) {
							if ($verbose) fprintf(STDERR, "Encoding error. Illegal 0x80 paddings. (See Rec. ITU-T X.690, clause 8.19.2)\n");
							return false;
						} else {
							$arcBeginning = false;
						}

						$ll = gmp_add($ll, ($pb & 0x7F));
						$fSub = 80;
						$fOK = false;
					} else {
						// 2.0 till 2.47 => 1 octet
						$second = $pb - 80;
						$output_oid[] = $second;
						$arcBeginning = true;
						$fOK = true;
						$ll = gmp_init(0);
					}
				} else {
					// 0.0 till 0.37 => 1 octet
					// 1.0 till 1.37 => 1 octet
					$output_oid[] = $first;
					$output_oid[] = $second;
					$arcBeginning = true;
					$fOK = true;
					$ll = gmp_init(0);
				}
				$part++;
			} else if ($part == 3) { // Arc three and higher
				if (($pb & 0x80) != 0) {
					if ($arcBeginning && ($pb == 0x80)) {
						if ($verbose) fprintf(STDERR, "Encoding error. Illegal 0x80 paddings. (See Rec. ITU-T X.690, clause 8.19.2)\n");
						return false;
					} else {
						$arcBeginning = false;
					}

					$ll = gmp_mul($ll, 0x80);
					$ll = gmp_add($ll, ($pb & 0x7F));
					$fOK = false;
				} else {
					$fOK = true;
					$ll = gmp_mul($ll, 0x80);
					$ll = gmp_add($ll, $pb);
					$ll = gmp_sub($ll, $fSub);
					$output_oid[] = gmp_strval($ll, 10);

					// Happens only if 0x80 paddings are allowed
					// $fOK = gmp_cmp($ll, 0) >= 0;
					$ll = gmp_init(0);
					$fSub = 0;
					$arcBeginning = true;
				}
			}
		}

		if (!$fOK) {
			if ($verbose) fprintf(STDERR, "Encoding error. The OID is not constructed properly.\n");
			return false;
		}

		return ($output_absolute ? '.' : '').implode('.', $output_oid);
	}

	// Doesn't need to be public, but it is a nice handy function, especially in the testcases
	public static function hexarrayToStr($ary, $nCHex=false) {
		$str = '';
		if ($ary === false) return false;
		foreach ($ary as &$a) {
			if ($nCHex) {
				$str .= sprintf("\"\\x%02X", $a);
			} else {
				$str .= sprintf("%02X ", $a);
			}
		}
		return trim($str);
	}

	public static function oidToDER($oid, $isRelative=false, $verbose=false) {
		if ($oid[0] == '.') { // MIB notation
			$oid = substr($oid, 1);
			$isRelative = false;
		}

		$cl = 0x00; // Class. Always UNIVERSAL (00)

		// Tag for Universal Class
		if ($isRelative) {
			$cl |= 0x0D;
		} else {
			$cl |= 0x06;
		}

		$arcs = explode('.', $oid);

		$b = 0;
		$isjoint = false;
		$abBinary = array();

		if ((!$isRelative) && (count($arcs) < 2)) {
			if ($verbose) fprintf(STDERR, "Encoding error. The minimum depth of an encodeable absolute OID is 2. (e.g. 2.999)\n");
			return false;
		}

		foreach ($arcs as $n => &$arc) {
			if (!preg_match('@^\d+$@', $arc)) {
				if ($verbose) fprintf(STDERR, "Encoding error. Arc '%s' is invalid.\n", $arc);
				return false;
			}

			$l = gmp_init($arc, 10);

			if ((!$isRelative) && ($n == 0)) {
				if (gmp_cmp($l, 2) > 0) {
					if ($verbose) fprintf(STDERR, "Encoding error. The top arc is limited to 0, 1 and 2.\n");
					return false;
				}
				$b += 40 * gmp_intval($l);
				$isjoint = gmp_cmp($l, 2) == 0;
			} else if ((!$isRelative) && ($n == 1)) {
				if ((!$isjoint) && (gmp_cmp($l, 39) > 0)) {
					if ($verbose) fprintf(STDERR, "Encoding error. The second arc is limited to 0..39 for root arcs 0 and 1.\n");
					return false;
				}

				if (gmp_cmp($l, 47) > 0) {
					$l = gmp_add($l, 80);
					self::makeBase128($l, 1, $abBinary);
				} else {
					$b += gmp_intval($l);
					$abBinary[] = $b;
				}
			} else {
				self::makeBase128($l, 1, $abBinary);
			}
		}

		$output = array();

		// Write class-tag
		$output[] = $cl;

		// Write length
		$nBinary = count($abBinary);
		if ($nBinary <= 0x7F) {
			$output[] = $nBinary;
		} else {
			$lengthCount = 0;
			$nBinaryWork = $nBinary;
			do {
				$lengthCount++;
				$nBinaryWork /= 0x100;
			} while ($nBinaryWork > 0);

			if ($lengthCount >= 0x7F) {
				if ($verbose) fprintf(STDERR, "The length cannot be encoded.\n");
				return false;
			}

			$output[] = 0x80 + $lengthCount;

			$nBinaryWork = $nBinary;
			do {
				$output[] = nBinaryWork & 0xFF;
				$nBinaryWork /= 0x100;
			} while ($nBinaryWork > 0);
		}

		foreach ($abBinary as $b) {
			$output[] = $b;
		}

		return $output;
	}

	protected static function makeBase128($l, $first, &$abBinary) {
		if (gmp_cmp($l, 127) > 0) {
			$l2 = gmp_div($l, 128);
			self::makeBase128($l2 , 0, $abBinary);
		}
		$l = gmp_mod($l, 128);

		if ($first) {
			$abBinary[] = gmp_intval($l);
		} else {
			$abBinary[] = 0x80 | gmp_intval($l);
		}
	}
}

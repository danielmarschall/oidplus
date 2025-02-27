<?php

/**
 * WEID<=>OID Converter
 * (c) Webfan.de, ViaThinkSoft
 * Revision 2025-01-06
 **/

// What is a WEID?
//     A WEID (WEhowski IDentifier) is an alternative representation of an
//     OID (Object IDentifier) defined by Till Wehowski.
//     In OIDs, arcs are in decimal base 10. In WEIDs, the arcs are in base 36.
//     Also, each WEID has a check digit at the end (called WeLuhn Check Digit).
//
// The full specification can be found here: https://co.weid.info/spec.html
//
// This converter supports WEID as of Spec Change #15
//
// A few short notes:
//     - There are several classes of WEIDs which have different OID bases:
//           "Class A" WEID:          weid:root:2-RR-?
//                                    oid:2.999
//                                    WEID class base OID: (OID Root)
//           "Class B" PEN WEID:      weid:pen:SX0-7PR-?
//                                    oid:1.3.6.1.4.1.37476.9999
//                                    WEID class base OID: 1.3.6.1.4.1
//           "Class B" UUID WEID:     weid:uuid:019433d5-535f-7098-9e0b-f7b84cf74da3:SX0-?
//                                    oid:2.25.2098739235139107623796528785225371043.37476
//                                    WEID class base OID: 2.25.<uuid>
//           "Class C" WEID:          weid:EXAMPLE-?
//                                    oid:1.3.6.1.4.1.37553.8.32488192274
//                                    WEID class base OID: 1.3.6.1.4.1.37553.8
//           "Class D" Domain WEID:   weid:example.com:TEST-? is equal to weid:9-DNS-COM-EXAMPLE-TEST-?
//                                    Since the check digit is based on the OID, the check digit is equal for both notations.
//                                    oid:1.3.6.1.4.1.37553.8.9.17704.32488192274.16438.1372205
//                                    WEID class base OID: 1.3.6.1.4.1.37553.8.9.17704
//     - The last arc in a WEID is the check digit. A question mark is the wildcard for an unknown check digit.
//       In this case, the converter will return the correct expected check digit for the input.
//     - The namespace (weid:, weid:pen:, weid:root:) is case insensitive.
//     - Padding with '0' characters is valid (e.g. weid:000EXAMPLE-3)
//       The paddings do not count into the WeLuhn check digit.
//     - URN Notation "urn:x-weid:..." is equal to "weid:..."
//

namespace ViaThinkSoft\OIDplus\Plugins\ObjectTypes\OID;

class WeidOidConverter {

	/**
	 * @param string $str
	 * @return false|int
	 */
	protected static function weLuhnGetCheckDigit(string $str) {
		// Padding zeros don't count to the check digit (December 2021)
		$ary = explode('-', $str);
		foreach ($ary as &$a) {
			$a = ltrim($a, '0');
			if ($a === '') $a = '0';
		}
		unset($a);
		$str = implode('-', $ary);

		// remove separators of the WEID string
		$wrkstr = str_replace('-', '', $str);

		// Replace 'a' with '10', 'b' with '11', etc.
		for ($i=0; $i<26; $i++) {
			$wrkstr = str_ireplace(chr(ord('a')+$i), (string)($i+10), $wrkstr);
		}

		// At the end, $wrkstr should only contain digits! Verify it!
		for ($i=0; $i<strlen($wrkstr); $i++) {
			if (($wrkstr[$i]<'0') || ($wrkstr[$i]>'9')) return false;
		}

		// Now do the standard Luhn algorithm
		$nbdigits = strlen($wrkstr);
		$parity = $nbdigits & 1; // mod 2
		$sum = 0;
		for ($n=$nbdigits-1; $n>=0; $n--) {
			$digit = (int)$wrkstr[$n];
			if (($n & 1) != $parity) $digit *= 2;
			if ($digit > 9) $digit -= 9;
			$sum += $digit;
		}
		return ($sum%10) == 0 ? 0 : 10-($sum%10);
	}

	/**
	 * @param string $oid
	 * @return false|string
	 */
	private static function oidSanitize(string $oid) {
		$oid = trim($oid);

		if (substr($oid,0,1) == '.') $oid = substr($oid,1); // remove leading dot

		if ($oid != '') {
			$elements = explode('.', $oid);
			foreach ($elements as &$elem) {
				if (trim($elem) == '') return false;

				if (!preg_match('/^\d+$/', $elem, $m)) return false;

				if (preg_match('/^0+$/', $elem, $m)) {
					$elem = '0';
				} else {
					$elem = ltrim($elem, "0");
				}
			}
			unset($elem);
			$oid = implode('.', $elements);

			if ((count($elements) > 0) && ($elements[0] != '0') && ($elements[0] != '1') && ($elements[0] != '2')) return false;
			if ((count($elements) > 1) && (($elements[0] == '0') || ($elements[0] == '1')) && ((strlen($elements[1]) > 2) || ($elements[1] > 39))) return false;
		}

		return $oid;
	}

	/**
	 * Translates a weid to an oid
	 * "weid:EXAMPLE-3" becomes "1.3.6.1.4.1.37553.8.32488192274"
	 * If it failed (e.g. wrong namespace, wrong checksum, etc.) then false is returned.
	 * If the weid ends with '?', then it will be replaced with the checksum,
	 * e.g. weid:EXAMPLE-? becomes weid:EXAMPLE-3
	 * @param string $weid
	 * @return false|string
	 */
	public static function weid2oid(string &$weid) {
		$weid = trim($weid);

		$weid = preg_replace('@^urn:x-weid:@', 'weid:', $weid) ?? $weid;

		$p = strrpos($weid,':');
		$namespace = substr($weid, 0, $p+1);
		$rest = substr($weid, $p+1);
		if ($rest === false) $rest = '';

		$namespace = strtolower($namespace); // namespace is case insensitive

		if ($namespace == 'weid:uuid:') {
			// Spec Change 15: Class B UUID WEID ( https://github.com/WEID-Consortium/weid.info/issues/3 )
			if (count(explode(':',$weid)) != 3) return false;
			$uuidrest = explode(':', $weid)[2];
			$alt_weid = 'weid:root:2-P-'.$uuidrest;
			$oid = self::weid2oid($alt_weid);
			if ($oid === false) return false;
			$weid = substr($weid, 0, -1) . substr($alt_weid, -1); // fix wildcard checksum if required (transfer checksum from $alt_weid to $weid)
			return $oid;
		} else if (strpos($namespace, 'weid:uuid:') === 0) {
			// Spec Change 13: Class B UUID WEID ( https://github.com/WEID-Consortium/weid.info/issues/1 )
			if (count(explode(':',$weid)) != 4) return false;
			$uuid = explode(':', $weid)[2];
			$uuidrest = explode(':', $weid)[3];
			if (!preg_match('@^[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{12}$@', $uuid, $m)) return false;
			$alt_weid = 'weid:root:2-P-'.self::base_convert_bigint(str_replace('-','',$uuid), 16, 36).'-'.$uuidrest;
			$oid = self::weid2oid($alt_weid);
			if ($oid === false) return false;
			$weid = substr($weid, 0, -1) . substr($alt_weid, -1); // fix wildcard checksum if required (transfer checksum from $alt_weid to $weid)
			return $oid;
		}

		if (strpos($namespace, 'weid:') === 0) {
			$domainpart = explode('.', explode(':',$weid)[1]);
			if (count($domainpart) > 1) {
				// Spec Change 10: Class D / Domain-WEID ( https://github.com/frdl/weid/issues/3 )
				if (count(explode(':',$weid)) != 3) return false;
				$domainrest = explode(':',$weid)[2];
				$alt_weid = "weid:9-DNS-" . strtoupper(implode('-',array_reverse($domainpart))) . "-" . $domainrest;
				$oid = self::weid2oid($alt_weid);
				if ($oid === false) return false;
				$weid = substr($weid, 0, -1) . substr($alt_weid, -1); // fix wildcard checksum if required (transfer checksum from $alt_weid to $weid)
				return $oid;
			}
		}

		if (strpos($namespace, 'weid:x-') === 0) {
			// Spec Change 11: Proprietary Namespaces ( https://github.com/frdl/weid/issues/4 )
			return "[Proprietary WEID Namespace]";
		} else if ($namespace == 'weid:') {
			// Class C
			$base = '1-3-6-1-4-1-SZ5-8';
		} else if ($namespace == 'weid:pen:') {
			// Class B (PEN)
			$base = '1-3-6-1-4-1';
		} else if (strpos($namespace, 'weid:pen:') === 0) {
			// Spec Change 15: "weid:pen:<pen-base10>:?" as alias of "weid:pen:<pen-base36>-?"
			if (count(explode(':',$weid)) != 4) return false;
			$pen = explode(':', $weid)[2];
			$penrest = explode(':', $weid)[3];
			$alt_weid = 'weid:root:1-3-6-1-4-1-'.self::base_convert_bigint($pen, 10, 36) . "-" . $penrest;
			$oid = self::weid2oid($alt_weid);
			if ($oid === false) return false;
			$weid = substr($weid, 0, -1) . substr($alt_weid, -1); // fix wildcard checksum if required (transfer checksum from $alt_weid to $weid)
			return $oid;
		} else if ($namespace == 'weid:root:') {
			// Class A
			$base = '';
		} else {
			// Wrong namespace
			return false;
		}

		$weid = $rest;

		$elements = array_merge(($base != '') ? explode('-', $base) : array(), explode('-', $weid));

		foreach ($elements as $elem) {
			if ($elem == '') return false;
		}

		$actual_checksum = array_pop($elements);
		$expected_checksum = self::weLuhnGetCheckDigit(implode('-',$elements));
		if ($actual_checksum != '?') {
			if ($actual_checksum != $expected_checksum) {
				return false; // wrong checksum
			}
		} else {
			// If checksum is '?', it will be replaced by the actual checksum,
			// e.g. weid:EXAMPLE-? becomes weid:EXAMPLE-3
			$weid = str_replace('?', "$expected_checksum", $weid);
		}
		foreach ($elements as &$arc) {
			//$arc = strtoupper(base_convert($arc, 36, 10));
			$arc = strtoupper(self::base_convert_bigint($arc, 36, 10));
		}
		unset($arc);
		$oid = implode('.', $elements);

		$weid = strtolower($namespace) . strtoupper($weid); // add namespace again

		$oid = self::oidSanitize($oid);
		if ($oid === false) return false;

		return $oid;
	}

	/**
	 * Converts an OID to WEID
	 * "1.3.6.1.4.1.37553.8.32488192274" becomes "weid:EXAMPLE-3"
	 * @param string $oid
	 * @return false|string
	 */
	public static function oid2weid(string $oid) {
		$oid = self::oidSanitize($oid);
		if ($oid === false) return false;

		if ($oid !== '') {
			$elements = explode('.', $oid);
			foreach ($elements as &$arc) {
				//$arc = strtoupper(base_convert($arc, 10, 36));
				$arc = strtoupper(self::base_convert_bigint($arc, 10, 36));
			}
			unset($arc);
			$weidstr = implode('-', $elements);
		} else {
			$weidstr = '';
		}

		$is_class_c = (strpos($weidstr, '1-3-6-1-4-1-SZ5-8-') === 0) || ($weidstr === '1-3-6-1-4-1-SZ5-8');
		$is_class_b_pen = ((strpos($weidstr, '1-3-6-1-4-1-') === 0) || ($weidstr === '1-3-6-1-4-1')) && !$is_class_c;
		$is_class_b_uuid = ((strpos($weidstr, '2-P-') === 0) || ($weidstr === '2-P'));
		$is_class_a = !$is_class_b_pen && !$is_class_b_uuid && !$is_class_c;

		$checksum = self::weLuhnGetCheckDigit($weidstr);

		if ($is_class_c) {
			$weidstr = substr($weidstr, strlen('1-3-6-1-4-1-SZ5-8-'));
			$namespace = 'weid:';
		} else if ($is_class_b_pen) {
			$weidstr = substr($weidstr, strlen('1-3-6-1-4-1-'));
			$namespace = 'weid:pen:';
		} else if ($is_class_b_uuid) {
			if ($weidstr == '2-P') {
				// Spec Change 14: Special case: OID 2.25 is weid:uuid:?
				$weidstr = '';
				$namespace = 'weid:uuid:';
			} else {
				// Spec Change 13: UUID WEID
				$uuid_base36 = explode('-', $weidstr)[2];
				$weidstr = substr($weidstr, strlen('2-P-') + strlen($uuid_base36) + strlen('-'));
				$namespace = 'weid:uuid:' . self::formatAsUUID(self::base_convert_bigint($uuid_base36, 36, 16)) . ':';
			}
		} else if ($is_class_a) {
			// $weidstr stays
			$namespace = 'weid:root:';
		} else {
			// should not happen
			return false;
		}

		return $namespace . ($weidstr == '' ? $checksum : $weidstr . '-' . $checksum);
	}

	/**
	 * Format a given string as a UUID.
	 *
	 * This function ensures the input string is 32 characters long by padding it with leading zeros.
	 * Then it applies the UUID format (8-4-4-4-12) and returns it in lowercase.
	 *
	 * @param string $input The input string to format as a UUID.
	 * @return string The formatted UUID in lowercase.
	 */
	static function formatAsUUID($input) {
		$paddedInput = str_pad($input, 32, '0', STR_PAD_LEFT);
		$uuid = sprintf(
			'%s-%s-%s-%s-%s',
			substr($paddedInput, 0, 8),
			substr($paddedInput, 8, 4),
			substr($paddedInput, 12, 4),
			substr($paddedInput, 16, 4),
			substr($paddedInput, 20)
		);
		return strtolower($uuid);
	}

	/**
	 * @param string $base10
	 * @return string
	 */
	public static function encodeSingleArc(string $base10): string {
		return self::base_convert_bigint($base10, 10, 36);
	}

	/**
	 * @param string $base36
	 * @return string
	 */
	public static function decodeSingleArc(string $base36): string {
		return self::base_convert_bigint($base36, 36, 10);
	}

	/**
	 * @param string $numstring
	 * @param int $frombase
	 * @param int $tobase
	 * @return string
	 */
	protected static function base_convert_bigint(string $numstring, int $frombase, int $tobase): string {
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
}


# --- Usage Example ---

/*
echo "Class B UUID tests\n\n";
$weid = 'weid:uuid:019433d5-535f-7098-9e0b-f7b84cf74da3:SX0-?';
echo $weid."\n";
echo WeidOidConverter::weid2oid($weid)."\n";
echo $weid."\n";
echo WeidOidConverter::oid2weid('2.25.2098739235139107623796528785225371043.37476')."\n";
echo "\n";

echo "Class D tests\n\n";
$weid = 'weid:welt.example.com:ABC-EXAMPLE-?';
echo $weid."\n";
echo WeidOidConverter::weid2oid($weid)."\n";
echo $weid."\n";
echo "\n";

echo "Class C tests:\n\n";

var_dump($oid = '1.3.6.1.4.1.37553.8')."\n";
var_dump(WeidOidConverter::oid2weid($oid))."\n";
$weid = 'weid:?';
var_dump(WeidOidConverter::weid2oid($weid))."\n";
var_dump($weid)."\n";
echo "\n";

var_dump($oid = '1.3.6.1.4.1.37553.8.32488192274')."\n";
var_dump(WeidOidConverter::oid2weid($oid))."\n";
$weid = 'weid:EXAMPLE-?';
var_dump(WeidOidConverter::weid2oid($weid))."\n";
var_dump($weid)."\n";
$weid = 'weid:00000example-?';
var_dump(WeidOidConverter::weid2oid($weid))."\n";
var_dump($weid)."\n";
echo "\n";

echo "Class B tests:\n\n";

var_dump($oid = '1.3.6.1.4.1')."\n";
var_dump(WeidOidConverter::oid2weid($oid))."\n";
$weid = 'weid:pen:?';
var_dump(WeidOidConverter::weid2oid($weid))."\n";
var_dump($weid)."\n";
echo "\n";

var_dump($oid = '1.3.6.1.4.1.37553.7.99.99.99')."\n";
var_dump(WeidOidConverter::oid2weid($oid))."\n";
$weid = 'weid:pen:SZ5-7-2R-2R-2R-?';
var_dump(WeidOidConverter::weid2oid($weid))."\n";
var_dump($weid)."\n";
$weid = 'weid:pen:000SZ5-7-02R-00002R-002r-?';
var_dump(WeidOidConverter::weid2oid($weid))."\n";
var_dump($weid)."\n";
echo "\n";

var_dump($oid = '1.3.6.1.4.1.37476.9999')."\n";
var_dump(WeidOidConverter::oid2weid($oid))."\n";
$weid = 'weid:pen:SX0-7PR-?';
var_dump(WeidOidConverter::weid2oid($weid))."\n";
var_dump($weid)."\n";
echo "\n";

echo "Class A tests:\n\n";

var_dump($oid = '')."\n";
var_dump(WeidOidConverter::oid2weid($oid))."\n";
$weid = 'weid:root:?';
var_dump(WeidOidConverter::weid2oid($weid))."\n";
var_dump($weid)."\n";
echo "\n";

var_dump($oid = '.2.999')."\n";
var_dump(WeidOidConverter::oid2weid($oid))."\n";
$weid = 'weid:root:2-RR-?';
var_dump(WeidOidConverter::weid2oid($weid))."\n";
var_dump($weid)."\n";
echo "\n";

var_dump($oid = '2.999')."\n";
var_dump(WeidOidConverter::oid2weid($oid))."\n";
$weid = 'weid:root:2-RR-?';
var_dump(WeidOidConverter::weid2oid($weid))."\n";
var_dump($weid)."\n";
echo "\n";
*/

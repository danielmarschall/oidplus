<?php

/**
 * WEID<=>OID Converter
 * (c) Webfan.de, ViaThinkSoft
 **/

class WeidOidConverter {

	protected static function weLuhnGetCheckDigit($str) {
		$wrkstr = str_replace('-', '', $str); // remove separators
		for ($i=0; $i<36; $i++) {
			$wrkstr = str_ireplace(chr(ord('a')+$i), $i+10, $wrkstr);
		}
		$nbdigits = strlen($wrkstr);
		$parity = $nbdigits & 1;
		$sum = 0;
		for ($n=$nbdigits-1; $n>=0; $n--) {
			$digit = $wrkstr[$n];
			if (($n & 1) != $parity) $digit *= 2;
			if ($digit > 9) $digit -= 9;
			$sum += $digit;
		}
		return ($sum%10) == 0 ? 0 : 10-($sum%10);
	}

	public static function weid2oid(&$weid, $namespace='weid:', $base='1-3-6-1-4-1-SZ5-8') {
		if (stripos($weid, $namespace) !== 0) return false; // wrong namespace
		$weid = explode(':', $weid, 2)[1]; // remove namespace

		$elements = array_merge(explode('-', $base), explode('-', $weid));
		$actual_checksum = array_pop($elements);
		$expected_checksum = self::weLuhnGetCheckDigit(implode('-',$elements));
		if ($actual_checksum != '?') {
			if ($actual_checksum != $expected_checksum) return false; // wrong checksum
		} else {
			// If checksum is '?', it will be replaced by the actual checksum,
			// e.g. weid:EXAMPLE-? becomes weid:EXAMPLE-3
			$weid = str_replace('?', $expected_checksum, $weid);
		}
		foreach ($elements as &$arc) {
			//$arc = strtoupper(base_convert($arc, 36, 10));
			$arc = strtoupper(self::base_convert_bigint($arc, 36, 10));
		}
		$oidstr = implode('.', $elements);

		return $oidstr;
	}

	public static function oid2weid($oid, $namespace='weid:', $base='1-3-6-1-4-1-SZ5-8') {
		$elements = explode('.', $oid);
		foreach ($elements as &$arc) {
			//$arc = strtoupper(base_convert($arc, 10, 36));
			$arc = strtoupper(self::base_convert_bigint($arc, 10, 36));
		}
		$weidstr = implode('-', $elements);

		if (stripos($weidstr.'-', $base.'-') !== 0) return false; // wrong base

		return $namespace . substr($weidstr.'-'.self::weLuhnGetCheckDigit($weidstr), strlen($base.'-'));
	}

	protected static function base_convert_bigint($numstring, $frombase, $tobase) {
		$frombase_str = '';
		for ($i=0; $i<$frombase; $i++) {
			$frombase_str .= strtoupper(base_convert($i, 10, 36));
		}

		$tobase_str = '';
		for ($i=0; $i<$tobase; $i++) {
			$tobase_str .= strtoupper(base_convert($i, 10, 36));
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

# --- TESTCASE ---

/*
$lines = file('E:\__fastphp\weid_test.txt');
$cnt = 0;
foreach ($lines as $line) {
	$line = trim($line);
	if (empty($line)) continue;
	list($testcase_oid, $testcase_weid) = explode("\t", $line);

	$weid = WeidOidConverter::oid2weid($testcase_oid);
	if ($weid != $testcase_weid) echo "OID2WEID ERROR: '$testcase_oid' = '$weid' (should be: '$testcase_weid')\n";

	$oid = WeidOidConverter::weid2oid($testcase_weid);
	if ($oid != $testcase_oid) echo "WEID2OID ERROR: '$testcase_weid' = '$oid' (should be: '$testcase_oid')\n";

	$cnt++;
}

echo "Done, checked $cnt testcases!\n";

$weid = 'weid:EXAMPLE-?';
echo "Autocomplete test: '$weid' => '".WeidOidConverter::weid2oid($weid)."' (checksum corrected to: '$weid')\n";
*/

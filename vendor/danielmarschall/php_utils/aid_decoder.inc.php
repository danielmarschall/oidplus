<?php

/*
 * ISO/IEC 7816 Application Identifier decoder for PHP
 * Copyright 2022 Daniel Marschall, ViaThinkSoft
 * Version 2022-09-20
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

// Definition of Application Identifiers (AID):
// - ISO 7816-05:1994 (1st ed.), clause 5.2
// - ISO 7816-04:2005 (2nd ed.), clause 8.2.1.2, Annex A.1, Annex D
// - ISO 7816-04:2013 (3rd ed.), clause 12.2.3, Annex A.1, Annex D
// - ISO 7816-04:2020 (4th ed.), clause 12.3.4, Annex A.1, Annex D

include_once __DIR__ . '/gmp_supplement.inc.php';
include_once __DIR__ . '/misc_functions.inc.php';

# ---

/*
#test2('A000000051AABBCC'); // International Registration
#test2('B01234567890'); // Illegal AID (RFU)
#test2('D276000098AABBCCDDEEFFAABBCCDDEE'); // National Registration
#test2('F01234567890'); // Unregistered AID
#test('91234FFF999'); // IIN based AID
#test('51234FFF999'); // IIN based AID
test('E828BD080F014E585031'); // ISO E8-OID 1.0.aaaa
test('E80704007F00070304'); // BSI Illegal E8-OID-AID (with DER Length)
test('E80704007F0007030499'); // BSI Illegal E8-OID-AID + PIX (PIX is never used by BSI; it's just for us to test)
test('E829112233'); // Possible other illegal E8-OID

function test2($aid) {
	while ($aid != '') {
		echo test($aid);
		$aid = substr($aid,0,strlen($aid)-1);
	}
}

function test($aid) {
	$out = _decode_aid($aid);
	$max_key_len = 32; // min width of first column
	foreach ($out as $a) {
		if (is_array($a)) {
			$max_key_len = max($max_key_len,strlen($a[0]));
		}
	}
	foreach ($out as $a) {
		if (is_array($a)) {
			echo str_pad($a[0],$max_key_len,' ',STR_PAD_RIGHT).'  '.$a[1]."\n";
		} else {
			echo $a."\n";
		}
	}
	echo "\n";
}
*/

# ---

function _aid_e8_oid_helper($output_oid,$by,$ii,&$ret,$minmax_measure,$min,$max) {
	if ($minmax_measure == 'ARC') {
		if (($min!=-1) && (count($output_oid)<$min)) return true;  // continue
		if (($max!=-1) && (count($output_oid)>$max)) return false; // stop
	} else if ($minmax_measure == 'DER') {
		if (($min!=-1) && ($ii+1<$min)) return true;  // continue
		if (($max!=-1) && ($ii+1>$max)) return false; // stop
	}

	$byy = $by;
	for ($i=0;$i<=$ii;$i++) array_shift($byy);

	$is_iso_standard = (count($output_oid) >= 3) && ($output_oid[0] == '1') && ($output_oid[1] == '0');

	$s_oid = implode('.',$output_oid);

	if ($is_iso_standard) {
		$std_hf = 'Standard ISO/IEC '.$output_oid[2];
		if (isset($output_oid[3])) $std_hf .= "-".$output_oid[3];
	} else {
		$std_hf = "Unknown Standard"; // should not happen
	}

	$pix = implode(':',$byy);

	if ($pix !== '') {
		$ret[] = array($ii+1,"$std_hf (OID $s_oid)",$pix);
	} else {
		$ret[] = array($ii+1,"$std_hf (OID $s_oid)","");
	}

	return true;
}

function _aid_e8_interpretations($pure_der, $minmax_measure='ARC', $min=-1, $max=-1) {
	$ret = array();

	$output_oid = array();

	$pure_der = strtoupper(str_replace(' ','',$pure_der));
	$pure_der = strtoupper(str_replace(':','',$pure_der));
	$by = str_split($pure_der,2);

	// The following part is partially taken from the DER decoder/encoder by Daniel Marschall:
	// https://github.com/danielmarschall/oidconverter/blob/master/php/OidDerConverter.class.phps
	// (Only the DER "value" part, without "type" and "length")

	$part = 2; // DER part 0 (type) and part 1 (length) not present
	$fSub = 0; // Subtract value from next number output. Used when encoding {2 48} and up
	$ll = gmp_init(0);
	$arcBeginning = true;

	foreach ($by as $ii => $pb) {

		$pb = hexdec($pb);

		if ($part == 2) { // First two arcs
			$first = floor($pb / 40);
			$second = $pb % 40;
			if ($first > 2) {
				$first = 2;
				$output_oid[] = $first;
				if (!_aid_e8_oid_helper($output_oid, $by, $ii, $ret, $minmax_measure, $min, $max)) break;
				$arcBeginning = true;

				if (($pb & 0x80) != 0) {
					// 2.48 and up => 2+ octets
					// Output in "part 3"

					if ($pb == 0x80) {
						throw new Exception("Encoding error. Illegal 0x80 paddings. (See Rec. ITU-T X.690, clause 8.19.2)\n");
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
					if (!_aid_e8_oid_helper($output_oid, $by, $ii, $ret, $minmax_measure, $min, $max)) break;
					$arcBeginning = true;
					$fOK = true;
					$ll = gmp_init(0);
				}
			} else {
				// 0.0 till 0.37 => 1 octet
				// 1.0 till 1.37 => 1 octet
				$output_oid[] = $first;
				$output_oid[] = $second;
				if (!_aid_e8_oid_helper($output_oid, $by, $ii, $ret, $minmax_measure, $min, $max)) break;
				$arcBeginning = true;
				$fOK = true;
				$ll = gmp_init(0);
			}
			$part++;
		} else { //else if ($part == 3) { // Arc three and higher
			if (($pb & 0x80) != 0) {
				if ($arcBeginning && ($pb == 0x80)) {
					throw new Exception("Encoding error. Illegal 0x80 paddings. (See Rec. ITU-T X.690, clause 8.19.2)");
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

				if (!_aid_e8_oid_helper($output_oid, $by, $ii, $ret, $minmax_measure, $min, $max)) break;

				// Happens only if 0x80 paddings are allowed
				// $fOK = gmp_cmp($ll, 0) >= 0;
				$ll = gmp_init(0);
				$fSub = 0;
				$arcBeginning = true;
			}
		}
	}

	return $ret;
}

function _aid_e8_length_usage($aid) {
	// Return true if $aid is most likely E8+Length+OID  (not intended by ISO)
	// Return false if $aid is most likely E8+OID     (defined by ISO for their OID 1.0)
	// Return null if it is ambiguous

	assert(substr($aid,0,2) === 'E8');
	$len = substr($aid,2,2);
	$rest = substr($aid,4);
	$rest_num_bytes = floor(strlen($rest)/2);

	$is_e8_len_oid = false;
	$is_e8_oid = false;

	// There are not enough following bytes, so it cannot be E8+Length+OID. It must be E8+OID
	if ($len > $rest_num_bytes) $is_e8_oid = true;

	// E8 00 ... must be E8+OID, with OID 0.0.xx (recommendation), because Length=0 is not possible
	if ($len == 0) $is_e8_oid = true;

	// E8 01 ... must be E8+Length+OID, because OID 0.1 (question) was never used
	if ($len == 1) $is_e8_len_oid = true;

	// E8 02 refers to OID 0.2 (administration) but could also refer to a length
	//if ($len == 2) return null;

	// E8 03 refers to OID 0.3 (network-operator) but could also refer to a length
	//if ($len == 3) return null;

	// E8 04 refers to OID 0.4 (identified-organization) but could also refer to a length
	//if ($len == 4) return null;

	// E8 05 refers to OID 0.5 (r-recommendation) but could also refer to a length
	//if ($len == 5) return null;

	// E8 06-08 refers to OID 0.6-8, which are not defined, E8+Length+OID
	if (($len >= 6) && ($len <= 8)) $is_e8_len_oid = true;

	// E8 09 refers to OID 0.9, which can be an OID or a Length
	if ($len == 9) {
		// The only legal child of OID 0.9 is OID 0.9.2342 ($len=09, $rest=9226); then it is E8+OID
		// An OID beginning with DER encoding 9226 would be 2.2262, which is very unlikely
		if (substr($rest,0,4) === '9226') {
			// 09 92 26 is OID 0.9.2342, which is a valid OID (the only valid OID) => valid OID
			// 92 26 would be OID 2.2262 which is most likely not a valid OID      => invalid Len
			$is_e8_oid = true;
		} else {
			// Any other child inside 0.9 except for 2342 is illegal, so it must be length
			$is_e8_len_oid = true;
		}
	}

	// E8 10-14 refers to OID 0.10-14 which is not defined. Therefore it must be E8+Length+OID
	if (($len >= 10) && ($len <= 14)) $is_e8_len_oid = true;

	// If E8+Length+OID, then Len can max be 14, because E8 takes 1 byte, length takes 1 byte, and AID must be max 16 bytes
	if ($len > 14) $is_e8_oid = true;

	// There is at least one case where the usage of E8+Length+OID is known:
	//    Including the DER Encoding "Length" is not defined by ISO but used illegally
	//    by German BSI (beside the fact that ISO never allowed anyone else to use E8-AIDs outside
	//    of OID arc 1.0),
	//    e.g. AID E80704007F00070302 defined by "BSI TR-03110" was intended to represent 0.4.0.127.0.7.3.2
	//                                "more correct" would have been AID E804007F00070302
	//         AID E80704007F00070304 defined by "BSI TR-03109-2" was intended to represent 0.4.0.127.0.7.3.4
	//                                "more correct" would have been AID E804007F00070304
	if (substr($rest,0,10) == '04007F0007'/*0.4.0.127.0.7*/) $is_e8_len_oid = $len <= 14;

	// Now conclude
	if (!$is_e8_oid &&  $is_e8_len_oid) return true/*E8+Length+OID*/;
	if ( $is_e8_oid && !$is_e8_len_oid) return false/*E8+OID*/;
	return null/*ambiguous*/;
}

function decode_aid($aid,$compact=true) {
	$sout = '';
	if (strtolower(substr($aid,0,2)) == '0x') $aid = substr($aid,2);
	$out = _decode_aid($aid);
	if ($compact) {
		$max_key_len = 0;
		foreach ($out as $a) {
			if (is_array($a)) {
				$max_key_len = max($max_key_len,strlen($a[0]));
			}
		}
	} else {
		$max_key_len = 32; // 16 bytes
	}
	foreach ($out as $a) {
		if (is_array($a)) {
			$sout .= str_pad($a[0],$max_key_len,' ',STR_PAD_RIGHT).'   '.$a[1]."\n";
		} else {
			$sout .= $a."\n";
		}
	}
	return $sout;
}

function _is_bcd($num) {
	return preg_match('@^[0-9]+$@', $num, $m);
}

function _decode_aid($aid) {

	// based on https://github.com/thephpleague/iso3166/blob/main/src/ISO3166.php
	// commit 26 July 2022
	// Generated using:
	/*
	$x = new ISO3166();
	$bla = $x->all();
	foreach ($bla as $data) {
		$out[] = "\t\$iso3166['".$data['numeric']."'] = \"".$data['name']."\";\n";
	}
	*/
	$iso3166['004'] = "Afghanistan";
	$iso3166['248'] = "Åland Islands";
	$iso3166['008'] = "Albania";
	$iso3166['012'] = "Algeria";
	$iso3166['016'] = "American Samoa";
	$iso3166['020'] = "Andorra";
	$iso3166['024'] = "Angola";
	$iso3166['660'] = "Anguilla";
	$iso3166['010'] = "Antarctica";
	$iso3166['028'] = "Antigua and Barbuda";
	$iso3166['032'] = "Argentina";
	$iso3166['051'] = "Armenia";
	$iso3166['533'] = "Aruba";
	$iso3166['036'] = "Australia";
	$iso3166['040'] = "Austria";
	$iso3166['031'] = "Azerbaijan";
	$iso3166['044'] = "Bahamas";
	$iso3166['048'] = "Bahrain";
	$iso3166['050'] = "Bangladesh";
	$iso3166['052'] = "Barbados";
	$iso3166['112'] = "Belarus";
	$iso3166['056'] = "Belgium";
	$iso3166['084'] = "Belize";
	$iso3166['204'] = "Benin";
	$iso3166['060'] = "Bermuda";
	$iso3166['064'] = "Bhutan";
	$iso3166['068'] = "Bolivia (Plurinational State of)";
	$iso3166['535'] = "Bonaire, Sint Eustatius and Saba";
	$iso3166['070'] = "Bosnia and Herzegovina";
	$iso3166['072'] = "Botswana";
	$iso3166['074'] = "Bouvet Island";
	$iso3166['076'] = "Brazil";
	$iso3166['086'] = "British Indian Ocean Territory";
	$iso3166['096'] = "Brunei Darussalam";
	$iso3166['100'] = "Bulgaria";
	$iso3166['854'] = "Burkina Faso";
	$iso3166['108'] = "Burundi";
	$iso3166['132'] = "Cabo Verde";
	$iso3166['116'] = "Cambodia";
	$iso3166['120'] = "Cameroon";
	$iso3166['124'] = "Canada";
	$iso3166['136'] = "Cayman Islands";
	$iso3166['140'] = "Central African Republic";
	$iso3166['148'] = "Chad";
	$iso3166['152'] = "Chile";
	$iso3166['156'] = "China";
	$iso3166['162'] = "Christmas Island";
	$iso3166['166'] = "Cocos (Keeling) Islands";
	$iso3166['170'] = "Colombia";
	$iso3166['174'] = "Comoros";
	$iso3166['178'] = "Congo";
	$iso3166['180'] = "Congo (Democratic Republic of the)";
	$iso3166['184'] = "Cook Islands";
	$iso3166['188'] = "Costa Rica";
	$iso3166['384'] = "Côte d'Ivoire";
	$iso3166['191'] = "Croatia";
	$iso3166['192'] = "Cuba";
	$iso3166['531'] = "Curaçao";
	$iso3166['196'] = "Cyprus";
	$iso3166['203'] = "Czechia";
	$iso3166['208'] = "Denmark";
	$iso3166['262'] = "Djibouti";
	$iso3166['212'] = "Dominica";
	$iso3166['214'] = "Dominican Republic";
	$iso3166['218'] = "Ecuador";
	$iso3166['818'] = "Egypt";
	$iso3166['222'] = "El Salvador";
	$iso3166['226'] = "Equatorial Guinea";
	$iso3166['232'] = "Eritrea";
	$iso3166['233'] = "Estonia";
	$iso3166['231'] = "Ethiopia";
	$iso3166['748'] = "Eswatini";
	$iso3166['238'] = "Falkland Islands (Malvinas)";
	$iso3166['234'] = "Faroe Islands";
	$iso3166['242'] = "Fiji";
	$iso3166['246'] = "Finland";
	$iso3166['250'] = "France";
	$iso3166['254'] = "French Guiana";
	$iso3166['258'] = "French Polynesia";
	$iso3166['260'] = "French Southern Territories";
	$iso3166['266'] = "Gabon";
	$iso3166['270'] = "Gambia";
	$iso3166['268'] = "Georgia";
	$iso3166['276'] = "Germany";
	$iso3166['288'] = "Ghana";
	$iso3166['292'] = "Gibraltar";
	$iso3166['300'] = "Greece";
	$iso3166['304'] = "Greenland";
	$iso3166['308'] = "Grenada";
	$iso3166['312'] = "Guadeloupe";
	$iso3166['316'] = "Guam";
	$iso3166['320'] = "Guatemala";
	$iso3166['831'] = "Guernsey";
	$iso3166['324'] = "Guinea";
	$iso3166['624'] = "Guinea-Bissau";
	$iso3166['328'] = "Guyana";
	$iso3166['332'] = "Haiti";
	$iso3166['334'] = "Heard Island and McDonald Islands";
	$iso3166['336'] = "Holy See";
	$iso3166['340'] = "Honduras";
	$iso3166['344'] = "Hong Kong";
	$iso3166['348'] = "Hungary";
	$iso3166['352'] = "Iceland";
	$iso3166['356'] = "India";
	$iso3166['360'] = "Indonesia";
	$iso3166['364'] = "Iran (Islamic Republic of)";
	$iso3166['368'] = "Iraq";
	$iso3166['372'] = "Ireland";
	$iso3166['833'] = "Isle of Man";
	$iso3166['376'] = "Israel";
	$iso3166['380'] = "Italy";
	$iso3166['388'] = "Jamaica";
	$iso3166['392'] = "Japan";
	$iso3166['832'] = "Jersey";
	$iso3166['400'] = "Jordan";
	$iso3166['398'] = "Kazakhstan";
	$iso3166['404'] = "Kenya";
	$iso3166['296'] = "Kiribati";
	$iso3166['408'] = "Korea (Democratic People's Republic of)";
	$iso3166['410'] = "Korea (Republic of)";
	$iso3166['414'] = "Kuwait";
	$iso3166['417'] = "Kyrgyzstan";
	$iso3166['418'] = "Lao People's Democratic Republic";
	$iso3166['428'] = "Latvia";
	$iso3166['422'] = "Lebanon";
	$iso3166['426'] = "Lesotho";
	$iso3166['430'] = "Liberia";
	$iso3166['434'] = "Libya";
	$iso3166['438'] = "Liechtenstein";
	$iso3166['440'] = "Lithuania";
	$iso3166['442'] = "Luxembourg";
	$iso3166['446'] = "Macao";
	$iso3166['807'] = "North Macedonia";
	$iso3166['450'] = "Madagascar";
	$iso3166['454'] = "Malawi";
	$iso3166['458'] = "Malaysia";
	$iso3166['462'] = "Maldives";
	$iso3166['466'] = "Mali";
	$iso3166['470'] = "Malta";
	$iso3166['584'] = "Marshall Islands";
	$iso3166['474'] = "Martinique";
	$iso3166['478'] = "Mauritania";
	$iso3166['480'] = "Mauritius";
	$iso3166['175'] = "Mayotte";
	$iso3166['484'] = "Mexico";
	$iso3166['583'] = "Micronesia (Federated States of)";
	$iso3166['498'] = "Moldova (Republic of)";
	$iso3166['492'] = "Monaco";
	$iso3166['496'] = "Mongolia";
	$iso3166['499'] = "Montenegro";
	$iso3166['500'] = "Montserrat";
	$iso3166['504'] = "Morocco";
	$iso3166['508'] = "Mozambique";
	$iso3166['104'] = "Myanmar";
	$iso3166['516'] = "Namibia";
	$iso3166['520'] = "Nauru";
	$iso3166['524'] = "Nepal";
	$iso3166['528'] = "Netherlands";
	$iso3166['540'] = "New Caledonia";
	$iso3166['554'] = "New Zealand";
	$iso3166['558'] = "Nicaragua";
	$iso3166['562'] = "Niger";
	$iso3166['566'] = "Nigeria";
	$iso3166['570'] = "Niue";
	$iso3166['574'] = "Norfolk Island";
	$iso3166['580'] = "Northern Mariana Islands";
	$iso3166['578'] = "Norway";
	$iso3166['512'] = "Oman";
	$iso3166['586'] = "Pakistan";
	$iso3166['585'] = "Palau";
	$iso3166['275'] = "Palestine, State of";
	$iso3166['591'] = "Panama";
	$iso3166['598'] = "Papua New Guinea";
	$iso3166['600'] = "Paraguay";
	$iso3166['604'] = "Peru";
	$iso3166['608'] = "Philippines";
	$iso3166['612'] = "Pitcairn";
	$iso3166['616'] = "Poland";
	$iso3166['620'] = "Portugal";
	$iso3166['630'] = "Puerto Rico";
	$iso3166['634'] = "Qatar";
	$iso3166['638'] = "Réunion";
	$iso3166['642'] = "Romania";
	$iso3166['643'] = "Russian Federation";
	$iso3166['646'] = "Rwanda";
	$iso3166['652'] = "Saint Barthélemy";
	$iso3166['654'] = "Saint Helena, Ascension and Tristan da Cunha";
	$iso3166['659'] = "Saint Kitts and Nevis";
	$iso3166['662'] = "Saint Lucia";
	$iso3166['663'] = "Saint Martin (French part)";
	$iso3166['666'] = "Saint Pierre and Miquelon";
	$iso3166['670'] = "Saint Vincent and the Grenadines";
	$iso3166['882'] = "Samoa";
	$iso3166['674'] = "San Marino";
	$iso3166['678'] = "Sao Tome and Principe";
	$iso3166['682'] = "Saudi Arabia";
	$iso3166['686'] = "Senegal";
	$iso3166['688'] = "Serbia";
	$iso3166['690'] = "Seychelles";
	$iso3166['694'] = "Sierra Leone";
	$iso3166['702'] = "Singapore";
	$iso3166['534'] = "Sint Maarten (Dutch part)";
	$iso3166['703'] = "Slovakia";
	$iso3166['705'] = "Slovenia";
	$iso3166['090'] = "Solomon Islands";
	$iso3166['706'] = "Somalia";
	$iso3166['710'] = "South Africa";
	$iso3166['239'] = "South Georgia and the South Sandwich Islands";
	$iso3166['728'] = "South Sudan";
	$iso3166['724'] = "Spain";
	$iso3166['144'] = "Sri Lanka";
	$iso3166['729'] = "Sudan";
	$iso3166['740'] = "Suriname";
	$iso3166['744'] = "Svalbard and Jan Mayen";
	$iso3166['752'] = "Sweden";
	$iso3166['756'] = "Switzerland";
	$iso3166['760'] = "Syrian Arab Republic";
	$iso3166['158'] = "Taiwan (Province of China)";
	$iso3166['762'] = "Tajikistan";
	$iso3166['834'] = "Tanzania, United Republic of";
	$iso3166['764'] = "Thailand";
	$iso3166['626'] = "Timor-Leste";
	$iso3166['768'] = "Togo";
	$iso3166['772'] = "Tokelau";
	$iso3166['776'] = "Tonga";
	$iso3166['780'] = "Trinidad and Tobago";
	$iso3166['788'] = "Tunisia";
	$iso3166['792'] = "Turkey";
	$iso3166['795'] = "Turkmenistan";
	$iso3166['796'] = "Turks and Caicos Islands";
	$iso3166['798'] = "Tuvalu";
	$iso3166['800'] = "Uganda";
	$iso3166['804'] = "Ukraine";
	$iso3166['784'] = "United Arab Emirates";
	$iso3166['826'] = "United Kingdom of Great Britain and Northern Ireland";
	$iso3166['840'] = "United States of America";
	$iso3166['581'] = "United States Minor Outlying Islands";
	$iso3166['858'] = "Uruguay";
	$iso3166['860'] = "Uzbekistan";
	$iso3166['548'] = "Vanuatu";
	$iso3166['862'] = "Venezuela (Bolivarian Republic of)";
	$iso3166['704'] = "Viet Nam";
	$iso3166['092'] = "Virgin Islands (British)";
	$iso3166['850'] = "Virgin Islands (U.S.)";
	$iso3166['876'] = "Wallis and Futuna";
	$iso3166['732'] = "Western Sahara";
	$iso3166['887'] = "Yemen";
	$iso3166['894'] = "Zambia";
	$iso3166['716'] = "Zimbabwe";

	$out = array();

	$aid = strtoupper($aid);
	$aid = trim($aid);
	$aid = str_replace(' ','',$aid);
	$aid = str_replace(':','',$aid);

	if ($aid == '') {
		$out[] = "INVALID: The AID is empty";
		return $out;
	}

	if (!preg_match('@^[0-9A-F]+$@', $aid, $m)) {
		$out[] = "INVALID: AID has invalid characters. Only A..F and 0..9 are allowed";
		return $out;
	}

	$aid_hf = implode(':',str_split($aid,2));
	if (strlen($aid)%2 == 1) $aid_hf .= '_';

	$out[] = array("$aid", "ISO/IEC 7816 Application Identifier (AID)");
	$out[] = array('', "> $aid_hf <");
	$out[] = array('', c_literal_hexstr($aid));

	if ((strlen($aid) == 32) && (substr($aid,-2) == 'FF')) {
		// Sometimes you read that a 16-byte AID must not end with FF, because it is reserved by ISO.
		// I have only found one official source:
		// ISO/IEC 7816-5 : 1994
		//        Identification cards - Integrated circuit(s) cards with contacts -
		//        Part 5 : Numbering system and registration procedure for application identifiers
		//        https://cdn.standards.iteh.ai/samples/19980/8ff6c7ccc9254fe4b7a8a21c0bf59424/ISO-IEC-7816-5-1994.pdf
		// Quote from clause 5.2:
		//       "The PIX has a free coding. If the AID is 16 bytes long,
		//        then the value 'FF' for the least significant byte is reserved for future use."
		// In the revisions of ISO/IEC 7816, parts of ISO 7816-5 (e.g. the AID categories)
		// have been moved to ISO 7816-4.
		// The "FF" reservation cannot be found in modern versions of 7816-4 or 7816-5.
		/*$out[] = array('',"INVALID: A 16-byte AID must not end with FF. (Reserved by ISO/IEC)");*/
		$out[] = array('',"Note: A 16-byte AID ending with FF was reserved by ISO/IEC 7816-5:1994");
	}

	if (strlen($aid) > 32) {
		$out[] = array('',"INVALID: An AID must not be longer than 16 bytes");
	}

	$category = substr($aid,0,1);

	// Category 0..9
	// RID = ISO/IEC 7812 Issuer Identification Number (IIN 6 or 8 digits)
	// AID = RID + 'FF' + PIX
	$iso7812_category = array();
	$iso7812_category['0'] = 'ISO/TC 68 and other industry assignments';
	$iso7812_category['1'] = 'Airlines';
	$iso7812_category['2'] = 'Airlines, financial and other future industry assignments';
	$iso7812_category['3'] = 'Travel and entertainment';
	$iso7812_category['4'] = 'Banking and financial';
	$iso7812_category['5'] = 'Banking and financial';
	$iso7812_category['6'] = 'Merchandising and banking/financial';
	$iso7812_category['7'] = 'Petroleum and other future industry assignments';
	$iso7812_category['8'] = 'Healthcare, telecommunications and other future industry assignments';
	$iso7812_category['9'] = 'Assignment by national standards bodies';
	foreach ($iso7812_category as $check_cat => $check_cat_name) {
		if ("$category" == "$check_cat") { // comparison as string is important so that "===" works. "==" does not work because 0=='A' for some reason!
			#$out[] = array($category, "AID based on category $category of ISO/IEC 7812 Issuer Identification Number (IIN)");
			#$out[] = array('',        "($check_cat = $check_cat_name)");
			$out[] = array('', "AID based on ISO/IEC 7812 Issuer Identification Number (IIN)");

			$iin = $aid;
			// IIN and PIX must be delimited with FF, but only if a PIX is available.
			// When the IIN has an odd number, then an extra 'F' must be added at the end
			$pos = strpos($aid,'F');
			if ($pos !== false) $iin = substr($iin, 0, $pos);

			if (!_is_bcd($iin)) {
				$out[] = array($iin, "INVALID: Expected BCD encoded IIN, optionally followed by FF and PIX");
				return $out;
			}

			$pad = '';

			$out[] = 'RID-HERE'; // will (must) be replaced below

			$out[] = array($iin, "ISO/IEC 7812 Issuer Identification Number (IIN)");
			if ((strlen($iin) != 6) && (strlen($iin) != 8)) {
				$out[] = array('',"Warning: IIN has an unusual length. 6 or 8 digits are expected!");
			}

			$out[] = array($category, "Major industry identifier $category = $check_cat_name");
			$pad .= str_repeat(' ', strlen("$category"));

			if ("$category" === "9") {
				$country = substr($iin,1,3);
				if ($country == '') {
					$out[] = array($pad.'___', 'ISO/IEC 3166-1 Numeric Country code (missing)');
				} else {
					$country_name = isset($iso3166[$country]) ? $iso3166[$country] : 'Unknown country';
					$out[] = array($pad.str_pad($country,3,'_',STR_PAD_RIGHT), "ISO/IEC 3166-1 Numeric Country code : $country ($country_name)");
				}
				$pad .= '   ';
				$asi = substr($iin,4);
				$asn = $asi;
			} else {
				$asi = substr($iin,1);
				$asn = $asi;
			}
			$out[] = array("$pad$asn", 'Assigned number'.($asi=='' ? ' (missing)' : ''));
			if ($asi!='') $out[] = array('', c_literal_hexstr($asi));
			$pad .= str_repeat(' ',strlen($asn));

			$padded_iin = $iin;
			if (strlen($iin)%2 != 0) {
				$odd_padding = substr($aid,strlen($iin),1);
				if ($odd_padding != 'F') {
					foreach ($out as $n => &$tmp) {
						if ($tmp == 'RID-HERE') {
							unset($out[$n]);
							break;
						}
					}
					$out[] = array("$pad!","INVALID: An IIN with odd length must be padded with F, e.g. 123 => 123F");
					return $out;
				}
				$out[] = array($pad.$odd_padding, 'Padding of IIN with odd length');
				$padded_iin .= $odd_padding;
				$pad .= ' ';
			}

			$rid = $padded_iin;
			foreach ($out as &$tmp) {
				if ($tmp == 'RID-HERE') {
					$tmp = array("$rid", "Registered Application Provider Identifier (RID)");
					break;
				}
			}

			if (strlen($aid) == strlen($padded_iin)) {
				// There is no PIX
				$out[] = "Proprietary application identifier extension (PIX) missing";
			} else {
				$delimiter = substr($aid,strlen($padded_iin),2);
				if ($delimiter != 'FF') {
					$out[] = array($pad.substr($aid,strlen($padded_iin)), "INVALID: RID/IIN and PIX must be delimited by FF");
					return $out;
				}
				$out[] = array($pad.$delimiter, 'Delimiter which separates RID/IIN from PIX');
				$pad .= str_repeat(' ',strlen($delimiter));

				$pix = substr($aid,strlen($padded_iin)+strlen('FF'));
				if ($pix == '') {
					$out[] = "Proprietary application identifier extension (PIX) missing";
					$out[] = "Warning: If PIX is available, FF delimites RID/IIN from PIX. Since PIX is empty, consider removing FF."; // not sure if this is an error or not
				} else {
					$out[] = array($pad.$pix, "Proprietary application identifier extension (PIX)");
					$out[] = array('', c_literal_hexstr($pix));
				}
			}

			return $out;
		}
	}

	// Category 'A' (International Registration)
	// RID = 'A' + 9 digits
	// AID = RID + PIX
	if ("$category" === "A") {
		$rid = substr($aid,0,10);
		$rid = str_pad($rid,10,'_',STR_PAD_RIGHT);

		$pix = substr($aid,10);

		$asi = substr($aid,1,9);
		$asn = str_pad($asi,9,'_',STR_PAD_RIGHT);

		$out[] = array("$rid", "Registered Application Provider Identifier (RID)");
		$out[] = array("$category", "Category $category: International registration");
		$out[] = array(" $asn", 'Assigned number, BCD recommended'.($asi=='' ? ' (missing)' : ''));
		if ($asi!='') $out[] = array('', c_literal_hexstr($asi));
		if ($pix == '') {
			$out[] = "Proprietary application identifier extension (PIX) missing";
		} else {
			$out[] = array(str_pad($pix,strlen($aid),' ',STR_PAD_LEFT), "Proprietary application identifier extension (PIX)");
			$out[] = array('', c_literal_hexstr($pix));
		}

		return $out;
	}

	// Category 'D' (Local/National Registration)
	// RID = 'D' + 3 digits country code (ISO/IEC 3166-1) + 6 digits
	// AID = RID + PIX
	if ("$category" === "D") {
		$rid = substr($aid,0,10);
		$rid = str_pad($rid,10,'_',STR_PAD_RIGHT);

		$pix = substr($aid,10);

		$country = substr($aid,1,3);

		$asi = substr($aid,4,6);
		$asn = str_pad($asi,6,'_',STR_PAD_RIGHT);

		$out[] = array("$rid", "Registered Application Provider Identifier (RID)");
		$out[] = array("$category", "Category $category: Local/National registration");
		if ($country == '') {
			$out[] = array(" ___", "ISO/IEC 3166-1 Numeric Country code (missing)");
		} else {
			$country_name = isset($iso3166[$country]) ? $iso3166[$country] : 'Unknown country';
			$out[] = array(" ".str_pad($country,3,'_',STR_PAD_RIGHT), "ISO/IEC 3166-1 Numeric Country code : $country ($country_name)");
		}
		$out[] = array("    $asn", 'Assigned number, BCD recommended'.($asi=='' ? ' (missing)' : ''));
		if ($asi!='') $out[] = array('', c_literal_hexstr($asi));
		if ($pix == '') {
			$out[] = "Proprietary application identifier extension (PIX) missing";
		} else {
			$out[] = array(str_pad($pix,strlen($aid),' ',STR_PAD_LEFT), "Proprietary application identifier extension (PIX)");
			$out[] = array('', c_literal_hexstr($pix));
		}

		return $out;
	}

	// Category 'E'
	// AID = 'E8' + OID + PIX   (OID is DER encoding without type and length)
	if ("$category" === "E") {
		$out[] = array("$category", "Category $category: Standard");

		$std_schema = substr($aid,1,1);
		if ($std_schema == '8') {
			$out[] = array(" $std_schema", "Standard identified by OID");

			// Start: Try to find out if it is E8+Length+OID (inofficial/illegal) or E8+OID (ISO)
			$len_usage = _aid_e8_length_usage($aid);
			$include_der_length = true; // In case it is ambiguous , let's say it is E8+Length+OID
			                            // Note that these ambiguous are rare and will only happen inside the root OID 0
			if ($len_usage === true)  $include_der_length = true;
			if ($len_usage === false) $include_der_length = false;
			if ($include_der_length) {
				// Case E8+Length+OID (inofficial/illegal)
				$der_length_hex = substr($aid,2,2);
				$der_length_dec = hexdec($der_length_hex);
				$out[] = array("  $der_length_hex", "DER encoding length (illegal usage not defined by ISO)");
				$pure_der = substr($aid,4);
				$indent = 4;
				$e8_minmax_measure = 'DER';
				$e8_min = $der_length_dec;
				$e8_max = $der_length_dec;
			} else {
				// Case E8+OID (defined by ISO, but only for their 1.0 OID)
				$pure_der = substr($aid,2);
				$indent = 2;
				if (substr($aid,2,2) == '28') { // '28' = OID 1.0 (ISO Standard)
					// ISO Standards (OID 1.0) will only have 1 or 2 numbers. (Number 1 is the standard, and number 2
					// is the part in case of a multi-part standard).
					$e8_minmax_measure = 'ARC';
					$e8_min = 3; // 1.0.aaaa   (ISO AAAA)
					$e8_max = 4; // 1.0.aaaa.b (ISO AAAA-B)
				} else {
					// This is the inofficial usage of E8+OID, i.e. using an OID outside of arc 1.0
					$e8_minmax_measure = 'ARC';
					$e8_min = 2;  // At least 2 arcs (OID x.y)
					$e8_max = -1; // no limit
				}
			}
			// End: Try to find out if it is E8+Length+OID (inofficial/illegal) or E8+OID (ISO)

			try {
				$interpretations = _aid_e8_interpretations($pure_der,$e8_minmax_measure,$e8_min,$e8_max);
				foreach ($interpretations as $ii => $interpretation) {
					$pos = $interpretation[0];
					$txt1 = $interpretation[1]; // Standard
					$txt2 = $interpretation[2]; // PIX (optional)

					$aid1 = str_repeat(' ',$indent).substr($pure_der,0,$pos*2);
					$aid2 = substr($pure_der,$pos*2);

					$out[] = array("$aid1", "$txt1");
					if ($txt2 !== '') {
						$pix = "$txt2 (".c_literal_hexstr(str_replace(':','',$txt2)).")";
						$out[] = array(str_repeat(' ',strlen($aid1))."$aid2", "with PIX $pix");
					}
					if ($ii < count($interpretations)-1) {
						$out[] = array('', 'or:');
					}
				}
			} catch (Exception $e) {
				$out[] = array(str_repeat(' ',$indent).$pure_der, "ERROR: ".$e->getMessage());
			}
		} else if ($std_schema != '') {
			// E0..E7, E9..EF are RFU
			$unknown = substr($aid,1);
			$out[] = array(" $unknown", "ILLEGAL USAGE / RESERVED");
		}

		return $out;
	}

	// Category 'F'
	// AID = 'F' + PIX
	if ("$category" === "F") {
		$out[] = array("$category", "Category $category: Non-registered / Proprietary");
		$rid = substr($aid,0,1);
		$pix = substr($aid,1);
		if ($pix == '') {
			$out[] = "Proprietary application identifier extension (PIX) missing";
		} else {
			$out[] = array(' '.$pix, "Proprietary application identifier extension (PIX)");
			$out[] = array('', c_literal_hexstr($pix));
		}
		return $out;
	}

	// Category 'B' and 'C' are reserved
	$out[] = array("$category", "Category $category: ILLEGAL USAGE / RESERVED");
	if (strlen($aid) > 1) {
		$aid_ = substr($aid,1);
		$out[] = array(" ".$aid_, "Unknown composition");
		$out[] = array('', c_literal_hexstr($aid_));
	}
	return $out;
}

/* --- Small extra function: not part of the decoder --- */

function aid_split_rid_pix($a, &$rid=null, &$pix=null) {
	// "Quick'n'Dirty" function which does not do any consistency checks!
	// It expects that the string is a valid AID!

	$cat = substr($a,0,1);
	if (is_numeric($cat)) {
		$p = strpos($a,'F');
		if ($p%2 != 0) $p++;
	} else if (($cat == 'A') || ($cat == 'D')) {
		$p = 10;
	} else if ($cat == 'F') {
		$p = 1;
	} else {
		$p = 0;
	}

	if ($rid !== null) $rid = substr($a, 0, $p);
	if ($pix !== null) $pix = substr($a, $p);

	return $p;
}

function aid_canonize(&$aid_candidate) {
	$aid_candidate = str_replace(' ', '', $aid_candidate);
	$aid_candidate = str_replace(':', '', $aid_candidate);
	$aid_candidate = strtoupper($aid_candidate);
	if (strlen($aid_candidate) > 16*2) {
		$aid_is_ok = false; // OID DER encoding is too long to fit into the AID
	} else if ((strlen($aid_candidate) == 16*2) && (substr($aid_candidate,-2) == 'FF')) {
		$aid_is_ok = false; // 16 byte AID must not end with 0xFF (reserved by ISO)
	} else {
		$aid_is_ok = true;
	}
	return $aid_is_ok;
}

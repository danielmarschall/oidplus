<?php

/*
 * ISO/IEC 7816-5 Application Identifier decoder for PHP
 * Copyright 2022 Daniel Marschall, ViaThinkSoft
 * Version 2022-07-31
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

/*
#test2('A000000051AABBCC');
#test2('B01234567890');
#test2('D276000098AABBCCDDEEFFAABBCCDDEE');
#test2('F01234567890');
test('91234FFF999');
test('51234FFF999');

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

function decode_aid($aid,$compact=true) {
	$sout = '';
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
	// commit 26.07.2022
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

	// ISO/IEC 7816-5 AID decoder

	$out = array();

	// A very good source about the coding
	// https://blog.actorsfit.com/a?ID=00250-166ef507-edff-4400-8d0e-9e85d6ae2310

	$aid = strtoupper($aid);
	$aid = trim($aid);
	$aid = str_replace(' ','',$aid);

	if ($aid == '') {
		$out[] = "Invalid : The AID is empty";
		return $out;
	}

	if (!preg_match('@^[0-9A-F]+$@', $aid, $m)) {
		$out[] = "Invalid : AID has invalid characters. Only A..F and 0..9 are allowed";
		return $out;
	}

	if ((strlen($aid) == 32) && (substr($aid,-2) == 'FF')) {
		// https://www.kartenbezogene-identifier.de/content/dam/kartenbezogene_identifier/de/PDFs/RID_Antrag_2006.pdf
		// https://docplayer.org/18719866-Rid-registered-application-provider-id-pix-proprietary-application-identifier-extension.html
		// writes:
		// "Wenn die PIX aus 11 Bytes besteht, muss das letzte Byte einen Hexadezimal-Wert ungleich ´FF´ aufweisen (´FF´ ist von ISO reserviert)."
		// ... I want to verify this ... !
		$out[] = "Invalid : A 16-byte AID must not end with FF. (Reserved for ISO)";
		return $out;
	}

	if (strlen($aid) > 32) {
		$out[] = "Invalid : An AID must not be longer than 16 bytes";
		return $out;
	}

	$aid_hf = '';
	for ($i=0; $i<strlen($aid); $i++) {
		$aid_hf .= $aid[$i];
		if ($i%2 == 1) $aid_hf .= ' ';
	}
	if (strlen($aid)%2 == 1) $aid_hf .= '_';
	$aid_hf = rtrim($aid_hf);
	$out[] = array("$aid", "ISO/IEC 7816-5 Application Identifier (AID)");
	$out[] = array('', "> $aid_hf <");

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
				$out[] = "Invalid : IIN is invalid , must be a BCD encoded number";
				return $out;
			}

			$pad = '';

			$out[] = 'RID-HERE'; // will (must) be replaced below

			$out[] = array($iin, "ISO/IEC 7812 Issuer Identification Number (IIN)");
			if ((strlen($iin) != 6) && (strlen($iin) != 8)) {
				$out[] = array('',"Warning: IIN has an unusual length. 6 or 8 digits are expected!");
			}
			$pad .= str_repeat(' ',strlen($iin));

			$out[] = array($check_cat, "IIN Category $check_cat = $check_cat_name");

			if ("$check_cat" === "9") {
				$country = substr($iin,1,3);
				if ($country == '') {
					$out[] = array(' ___', 'ISO/IEC 3166-1 Numeric Country code (missing)');
				} else {
					$country_name = isset($iso3166[$country]) ? $iso3166[$country] : 'Unknown country';
					$out[] = array(' '.str_pad($country,3,'_',STR_PAD_RIGHT), "ISO/IEC 3166-1 Numeric Country code : $country ($country_name)");
				}
				$out[] = array('    '.substr($iin,4), 'Assigned number');
			} else {
				$out[] = array(' '.substr($iin,1), 'Assigned number');
			}

			$padded_iin = $iin;
			if (strlen($iin)%2 != 0) {
				$odd_padding = substr($aid,strlen($iin),1);
				if ($odd_padding != 'F') {
					$out[] = "Invalid : An IIN with odd length must be padded with F, e.g. 123 => 123F";
					foreach ($out as $n => &$tmp) {
						if ($tmp == 'RID-HERE') {
							unset($out[$n]);
							break;
						}
					}
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
					$out[] = "Invalid: RID/IIN and PIX must be delimited by FF";
					return $out;
				}
				$out[] = array($pad.$delimiter, 'Delimiter which separates RID/IIN from PIX');
				$pad .= str_repeat(' ',strlen($delimiter));

				$pix = substr($aid,strlen($padded_iin)+strlen('FF'));
				if ($pix == '') {
					$out[] = "Proprietary application identifier extension (PIX) missing";
					$out[] = "Warning: If PIX is available, FF delimites RID/IIN from PIX. Since PIX is empty, consider removing FF.";
					return $out; // not sure if this is an error or not. I need the ISO document...
				} else {
					$out[] = array($pad.$pix, "Proprietary application identifier extension (PIX)");
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

		$asn = substr($aid,1,9);
		$asn = str_pad($asn,9,'_',STR_PAD_RIGHT);

		$out[] = array("$rid", "Registered Application Provider Identifier (RID)");
		$out[] = array("$category", "Category $category: International registration");
		$out[] = array(" $asn", 'Assigned number, BCD recommended');
		if ($pix == '') {
			$out[] = "Proprietary application identifier extension (PIX) missing";
		} else {
			$out[] = array(str_pad($pix,strlen($aid),' ',STR_PAD_LEFT), "Proprietary application identifier extension (PIX)");
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

		$asn = substr($aid,4,6);
		$asn = str_pad($asn,6,'_',STR_PAD_RIGHT);

		$out[] = array("$rid", "Registered Application Provider Identifier (RID)");
		$out[] = array("$category", "Category $category: Local/National registration");
		if ($country == '') {
			$out[] = array(" ___", "ISO/IEC 3166-1 Numeric Country code (missing)");
		} else {
			$country_name = isset($iso3166[$country]) ? $iso3166[$country] : 'Unknown country';
			$out[] = array(" ".str_pad($country,3,'_',STR_PAD_RIGHT), "ISO/IEC 3166-1 Numeric Country code : $country ($country_name)");
		}
		$out[] = array("    $asn", 'Assigned number, BCD recommended');
		if ($pix == '') {
			$out[] = "Proprietary application identifier extension (PIX) missing";
		} else {
			$out[] = array(str_pad($pix,strlen($aid),' ',STR_PAD_LEFT), "Proprietary application identifier extension (PIX)");
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
		}
		return $out;
	}

	// Category 'B', 'C', and 'E' are reserved
	$out[] = array("$category", "Category $category: ILLEGAL USAGE / RESERVED");
	if (strlen($aid) > 1) {
		$out[] = array(" ".substr($aid,1), "Unknown composition");
	}
	return $out;
}

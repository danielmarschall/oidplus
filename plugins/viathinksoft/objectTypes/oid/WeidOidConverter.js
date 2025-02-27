(function (globalObject) {
  'use strict';

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

var WeidOidConverter = {

	weLuhnCheckDigit: function(str) {
		// Padding zeros don't count to the check digit (December 2021)
		var ary = str.split('-');
		ary.forEach(function (o,i,a) {
			if (a[i].match(/^0+$/)) {
				a[i] = '0';
			} else {
				a[i] = a[i].replace(/^0+/, '');
			}
		});
		str = ary.join('-');

		// remove separators from the WEID string
		var wrkstr = str.replaceAll('-', '');

		// Replace 'a' with '10', 'b' with '1', etc.
		for (var i=0; i<26; i++) {
			wrkstr = wrkstr.toLowerCase().replaceAll(String.fromCharCode('a'.charCodeAt(0)+i).toLowerCase(), (10+i));
		}

		// At the end, wrkstr should only contain digits! Verify it!
		if (!wrkstr.match(/^\d*$/)) {
			console.error("weLuhnCheckDigit: Invalid input");
			return false;
		}

		// Now do the standard Luhn algorithm
		var nbdigits = wrkstr.length;
		var parity = nbdigits & 1; // mod 2
		var sum = 0;
		for (var n=nbdigits-1; n>=0; n--) {
			var digit = parseInt(wrkstr.substr(n,1));
			if ((n & 1) != parity) digit *= 2;
			if (digit > 9) digit -= 9;
			sum += digit;
		}
		return (sum%10) == 0 ? 0 : 10-(sum%10);
	},

	oidSanitize: function(oid) {
		var oid = oid.trim();

		if (oid.substr(0,1) == '.') oid = oid.substr(1); // remove leading dot

		if (oid != '') {
			var elements = oid.split('.');

			var fail = false;
			elements.forEach(function (o,i,a) {
				if (a[i].trim() == '') fail = true;

				if (!a[i].match(/^\d+$/)) fail = true;

				if (a[i].match(/^0+$/)) {
					a[i] = '0';
				} else {
					a[i] = a[i].replace(/^0+/, '');
				}
			});
			if (fail) return false;

			oid = elements.join(".");

			if ((elements.length > 0) && (elements[0] != '0') && (elements[0] != '1') && (elements[0] != '2')) return false;
			if ((elements.length > 1) && ((elements[0] == '0') || (elements[0] == '1')) && ((elements[1].length > 2) || (elements[1] > 39))) return false;
		}

		return oid;
	},

	// Translates a WEID to an OID
	// "weid:EXAMPLE-3" becomes "1.3.6.1.4.1.37553.8.32488192274"
	// If it failed (e.g. wrong namespace, wrong check digit, etc.) then false is returned.
	// If the weid ends with '?', the check digit will be added
	// Return value is an array with the elements "oid" and "weid".
	// Example:
	//     weid2oid("weid:EXAMPLE-?").weid == "weid:EXAMPLE-3"
	//     weid2oid("weid:EXAMPLE-?").oid  == "1.3.6.1.4.1.37553.8.32488192274"
	weid2oid: function(weid) {
		var weid = weid.trim();

		weid = weid.replace(/^urn:x-weid:/, 'weid:'); // Spec Change 12 (URN)

		var p = weid.lastIndexOf(':');
		var namespace = weid.substr(0, p+1);
		var rest = weid.substr(p+1);

		var base = null;

		namespace = namespace.toLowerCase(); // namespace is case insensitive

		if (namespace == "weid:uuid:") {
			// Spec Change 15: Class B UUID WEID ( https://github.com/WEID-Consortium/weid.info/issues/3 )
			if (weid.split(":").length != 3) return false;
			var uuidrest = weid.split(":")[2].split("-");
			var alt_weid = 'weid:root:2-P-' + uuidrest.join("-");
			var tmp = WeidOidConverter.weid2oid(alt_weid);

			if (!tmp) return false;
			var checksum = tmp["weid"].split("-").reverse()[0];
			var weid = weid.substr(0,weid.length-1) + checksum; // fix wildcard checksum if required
			return { "weid": weid, "oid" : tmp["oid"] };
		} else if (namespace.startsWith("weid:uuid:")) {
			// Spec Change 13: Class B UUID WEID ( https://github.com/WEID-Consortium/weid.info/issues/1 )
			if (weid.split(":").length != 4) return false;
			var uuid = weid.split(":")[2];
			var uuidrest = weid.split(":")[3].split("-");
			if (!uuid.match("^[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{12}$")) return false;
			var alt_weid = 'weid:root:2-P-' + WeidOidConverter.base_convert_bigint(uuid.replaceAll('-',''), 16, 36) + "-" + uuidrest.join("-");
			var tmp = WeidOidConverter.weid2oid(alt_weid);

			if (!tmp) return false;
			var checksum = tmp["weid"].split("-").reverse()[0];
			var weid = weid.substr(0,weid.length-1) + checksum; // fix wildcard checksum if required
			return { "weid": weid, "oid" : tmp["oid"] };
		}

		if (namespace.startsWith("weid:")) {
			var domainpart = weid.split(":")[1].split(".");
			if (domainpart.length > 1) {
				// Spec Change 10: Class D / Domain-WEID ( https://github.com/frdl/weid/issues/3 )
				if (weid.split(":").length != 3) return false;
				var domainrest = weid.split(":")[2].split("-");
				var alt_weid = "weid:9-DNS-" + domainpart.reverse().join("-").toUpperCase() + "-" + domainrest.join("-");
				var tmp = WeidOidConverter.weid2oid(alt_weid);

				if (!tmp) return false;
				var checksum = tmp["weid"].split("-").reverse()[0];
				var weid = weid.substr(0,weid.length-1) + checksum; // fix wildcard checksum if required
				return { "weid": weid, "oid" : tmp["oid"] };
			}
		}

		if (namespace.startsWith('weid:x-')) {
			// Spec Change 11: Proprietary Namespaces ( https://github.com/frdl/weid/issues/4 )
			return { "weid": weid, "oid" : "[Proprietary WEID Namespace]" };
		} else if (namespace == 'weid:') {
			// Class C
			base = '1-3-6-1-4-1-SZ5-8';
		} else if (namespace == 'weid:pen:') {
			// Class B (PEN)
			base = '1-3-6-1-4-1';
		} else if (namespace.startsWith("weid:pen:")) {
			// Spec Change 15: "weid:pen:<pen-base10>:?" as alias of "weid:pen:<pen-base36>-?"
			if (weid.split(":").length != 4) return false;
			var pen = weid.split(":")[2];
			var penrest = weid.split(":")[3].split("-");
			var alt_weid = 'weid:root:1-3-6-1-4-1-' + WeidOidConverter.base_convert_bigint(pen, 10, 36) + "-" + penrest.join("-");
			var tmp = WeidOidConverter.weid2oid(alt_weid);

			if (!tmp) return false;
			var checksum = tmp["weid"].split("-").reverse()[0];
			var weid = weid.substr(0,weid.length-1) + checksum; // fix wildcard checksum if required
			return { "weid": weid, "oid" : tmp["oid"] };
		} else if (namespace == 'weid:root:') {
			// Class A
			base = '';
		} else {
			// Wrong namespace
			console.error("weid2oid: Wrong input");
			return false;
		}

		weid = rest;

		var elements = ((base != '') ? base.split('-') : []).concat(weid.split('-'));

		var fail = false;
		elements.forEach(function (o,i,a) {
			if (a[i].trim() == '') fail = true;
		});
		if (fail) return false;

		var actual_checksum = elements.pop();
		var expected_checksum = WeidOidConverter.weLuhnCheckDigit(elements.join('-'));
		if (actual_checksum != '?') {
			if (actual_checksum != expected_checksum) {
				console.error("weid2oid: Wrong check digit");
				return false; // wrong checksum
			}
		} else {
			// If check digit is '?', it will be replaced by the actual check digit,
			// e.g. weid:EXAMPLE-? becomes weid:EXAMPLE-3
			weid = weid.replace('?', expected_checksum);
		}
		elements.forEach(function (o,i,a) {
			a[i] = WeidOidConverter.base_convert_bigint(a[i], 36, 10);
		});
		var oid = elements.join('.');

		weid = namespace.toLowerCase() + weid.toUpperCase(); // add namespace again

		oid = WeidOidConverter.oidSanitize(oid);
		if (oid === false) return false; // invalid OID

		return { "weid": weid, "oid" : oid };
	},

	// Converts an OID to WEID
	// "1.3.6.1.4.1.37553.8.32488192274" becomes "weid:EXAMPLE-3"
	oid2weid: function(oid) {
		var oid = WeidOidConverter.oidSanitize(oid);
		if (oid === false) return false;

		var weidstr = null;
		if (oid != '') {
			var elements = oid.split('.');
			elements.forEach(function (o,i,a) {
				a[i] = WeidOidConverter.base_convert_bigint(a[i], 10, 36);
			});
			weidstr = elements.join("-");
		} else {
			weidstr = '';
		}

		var is_class_c      = (weidstr.startsWith('1-3-6-1-4-1-SZ5-8-') || (weidstr == '1-3-6-1-4-1-SZ5-8'));
		var is_class_b_pen  = (weidstr.startsWith('1-3-6-1-4-1-') || (weidstr == '1-3-6-1-4-1')) && !is_class_c;
		var is_class_b_uuid = (weidstr.startsWith('2-P-') || (weidstr == '2-P'));
		var is_class_a      = !is_class_b_pen && !is_class_b_uuid && !is_class_c;

		var checksum = WeidOidConverter.weLuhnCheckDigit(weidstr);

		var namespace = null;
		if (is_class_c) {
			weidstr = weidstr.substr('1-3-6-1-4-1-SZ5-8-'.length);
			namespace = 'weid:';
		} else if (is_class_b_pen) {
			weidstr = weidstr.substr('1-3-6-1-4-1-'.length);
			namespace = 'weid:pen:';
		} else if (is_class_b_uuid) {
			if (weidstr == '2-P') {
				// Spec Change 14: Special case: OID 2.25 is weid:uuid:?
				weidstr = '';
				namespace = 'weid:uuid:';
			} else {
				// Spec Change 13: UUID WEID
				var uuid_base36 = weidstr.split('-')[2];
				weidstr = weidstr.substr('2-P-'.length + uuid_base36.length + '-'.length);
				namespace = 'weid:uuid:' + WeidOidConverter.formatAsUUID(WeidOidConverter.base_convert_bigint(uuid_base36, 36, 16)) + ':';
			}
		} else if (is_class_a) {
			// weidstr stays
			namespace = 'weid:root:';
		} else {
			// should not happen
			console.error("oid2weid: Cannot detect namespace");
			return false;
		}

		var weid = namespace + (weidstr == '' ? checksum : weidstr + '-' + checksum);

		return { "weid": weid, "oid": oid };
	},

	formatAsUUID: function(input) {
		// Auffüllen mit führenden Nullen, um sicherzustellen, dass die Länge 32 Zeichen beträgt
		const paddedInput = input.padStart(32, '0');

		// UUID-Format anwenden
		const uuid = `${paddedInput.slice(0, 8)}-${paddedInput.slice(8, 12)}-${paddedInput.slice(12, 16)}-${paddedInput.slice(16, 20)}-${paddedInput.slice(20)}`;

		return uuid.toLowerCase(); // Kleinbuchstaben zurückgeben
	},

	base_convert_bigint: function(numstring, frombase, tobase) {

		// This variant would require the "mikemcl/bignumber.js" library:
		//var x = BigNumber(numstr, frombase);
		//return isNaN(x) ? false : x.toString(tobase).toUpperCase();

		var frombase_str = '';
		for (var i=0; i<frombase; i++) {
			frombase_str += parseInt(i, 10).toString(36).toUpperCase();
		}

		var tobase_str = '';
		for (var i=0; i<tobase; i++) {
			tobase_str += parseInt(i, 10).toString(36).toUpperCase();
		}

		for (var i=0; i<numstring.length; i++) {
			if (frombase_str.toLowerCase().indexOf(numstring.substr(i,1).toLowerCase()) < 0) {
				console.error("base_convert_bigint: Invalid input");
				return false;
			}
		}

		var length = numstring.length;
		var result = '';
		var number = [];
		for (var i=0; i<length; i++) {
			number[i] = frombase_str.toLowerCase().indexOf(numstring[i].toLowerCase());
		}
		var newlen = null;
		do { // Loop until whole number is converted
			var divide = 0;
			var newlen = 0;
			for (var i=0; i<length; i++) { // Perform division manually (which is why this works with big numbers)
				divide = divide * frombase + parseInt(number[i]);
				if (divide >= tobase) {
					number[newlen++] = (divide / tobase);
					divide = divide % tobase;
				} else if (newlen > 0) {
					number[newlen++] = 0;
				}
			}
			length = newlen;
			result = tobase_str.substr(divide,1) + result; // Divide is basically numstring % tobase (i.e. the new character)
		}
		while (newlen != 0);

		return result;
	}
};

WeidOidConverter['default'] = WeidOidConverter.WeidOidConverter = WeidOidConverter;

if (typeof define == 'function' && define.amd) {
	define('WeidOidConverter', function () {
		return WeidOidConverter;
	});
} else if (typeof module != 'undefined' && module.exports) {
	module.exports = WeidOidConverter;
} else {
	if (!globalObject) {
		globalObject = typeof self != 'undefined' && self ? self : window;
	}
	globalObject.WeidOidConverter = WeidOidConverter;
}
})(this);

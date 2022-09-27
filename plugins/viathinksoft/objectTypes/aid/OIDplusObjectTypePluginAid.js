/*
 * OIDplus 2.0
 * Copyright 2019 - 2022 Daniel Marschall, ViaThinkSoft
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

var OIDplusObjectTypePluginAid = {

	OID: "1.3.6.1.4.1.37476.2.5.2.4.8.11",

	generateRandomAID: function() {
		var length     = 32; // 32 nibbles = 16 bytes
		var characters = '0123456789ABCDEF';
		do {
			var result = 'F'; // category "F" = Unregistered Proprietary AID
			for ( var i = 0 ; i < length - 1/*F*/ ; i++ ) {
				result += characters.charAt(Math.floor(Math.random() * characters.length));
			}
		} while (result.endsWith('FF') && (result.length==32));

		// Note that 16 byte AIDs ending with 0xFF *were* reserved by ISO in ISO 7816-4:1994,
		// but modern versions of ISO 7816-4 and ISO 7816-4 do not mention this case anymore.
		// It can be assumed that the usage is safe, but just to be sure, we exclude 16-byte
		// AIDs ending with 0xFF, in case there are some software implementations which
		// deny such AIDs.

		$("#id").val(result);
	}

};

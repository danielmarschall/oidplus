/*
 * OIDplus 2.0
 * Copyright 2019 - 2023 Daniel Marschall, ViaThinkSoft
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
		var aid_max_len = 32; // 32 nibbles = 16 bytes
		var characters  = '0123456789ABCDEF';
		var counter     = 0;

		var current_aid = current_node.split(":")[1];
		if (current_aid.length >= aid_max_len) {
				alertWarning(_L("The AID has reached its maximum size."));
				return;
		}


		do {
			var candidate = current_aid == "" ? "F" : current_aid; // category "F" = Unregistered Proprietary AID
			for ( var i = 0 ; i < aid_max_len - candidate.length ; i++ ) {
				candidate += characters.charAt(Math.floor(Math.random() * characters.length));
			}

			var isPrefixOfExistingAIDs = false;
			var currentExistingAIDs = [];
			var dummy = null;
			var element = null;
			$("#crudTable tbody a").each(function() {
			    var tmp = this.href.split("?goto=aid%3A");
			    if (tmp.length > 1) {
			        currentExistingAIDs.push(tmp[1].split("&")[0]);
			    }
			});
			$.each(currentExistingAIDs, function(dummy, element) {
			    if (candidate.startsWith(element)) {
			        isPrefixOfExistingAIDs = true;
			        return false; // breaks
			    }
			});

			if (counter++ > 1000) {
				alertWarning(_L("Cannot find a free slot for a random AID! Please try again, or try generating a random AID in a subordinate node."));
				return;
			}
		} while (isPrefixOfExistingAIDs || ((candidate.endsWith('FF') && (candidate.length==aid_max_len))));

		// Note that 16 byte AIDs ending with 0xFF *were* reserved by ISO in ISO 7816-4:1994,
		// but modern versions of ISO 7816-4 and ISO 7816-4 do not mention this case anymore.
		// It can be assumed that the usage is safe, but just to be sure, we exclude 16-byte
		// AIDs ending with 0xFF, in case there are some software implementations which
		// deny such AIDs.

		$("#id").val(candidate);
	}

};

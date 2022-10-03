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

/*jshint esversion: 6 */

/* Misc functions */

function htmlDecodeWithLineBreaks(html) {
	// https://stackoverflow.com/questions/4502673/jquery-text-function-loses-line-breaks-in-ie
	var breakToken = '_______break_______';
	var lineBreakedHtml = html.replace(/<br\s?\/?>/gi, breakToken).replace(/<p\.*?>(.*?)<\/p>/gi, breakToken + '$1' + breakToken);
	return $('<div>').html(lineBreakedHtml).text().replace(new RegExp(breakToken, 'g'), '\n');
}

function copyToClipboard(elem) {
	// Source: https://stackoverflow.com/questions/22581345/click-button-copy-to-clipboard-using-jquery
	// Modified (see below)

	// TODO: this function causes the page to scroll!

	// create hidden text element, if it doesn't already exist
	var targetId = "_hiddenCopyText_";
	var isInput = elem.tagName === "INPUT" || elem.tagName === "TEXTAREA";
	var origSelectionStart, origSelectionEnd;
	if (isInput) {
		// can just use the original source element for the selection and copy
		target = elem;
		origSelectionStart = elem.selectionStart;
		origSelectionEnd = elem.selectionEnd;
	} else {
		// must use a temporary form element for the selection and copy
		target = document.getElementById(targetId);
		if (!target) {
			var target = document.createElement("textarea");
			target.style.position = "absolute";
			target.style.left = "-9999px";
			target.style.top = "0";
			target.id = targetId;
			document.body.appendChild(target);
		}

		// Changed by Daniel Marschall, 3 Oct 2022
		// "textContent" will swallow the line breaks of a <div>xx<br>xx</div>
		// htmlDecodeWithLineBreaks convert <br> to linebreaks but strips the other
		// HTML tags.
		// target.textContent = elem.textContent;
		target.textContent = htmlDecodeWithLineBreaks(elem.innerHTML);
	}
	// select the content
	var currentFocus = document.activeElement;
	target.focus();
	target.setSelectionRange(0, target.value.length);

	// copy the selection
	var succeed;
	try {
		succeed = document.execCommand("copy");
	} catch(e) {
		succeed = false;
	}
	// restore original focus
	if (currentFocus && typeof currentFocus.focus === "function") {
		currentFocus.focus();
	}

	if (isInput) {
		// restore prior selection
		elem.setSelectionRange(origSelectionStart, origSelectionEnd);
	} else {
		// clear temporary content
		target.textContent = "";
	}
	return succeed;
}

function isInternetExplorer() {
	// see also includes/functions.inc.php
	var ua = window.navigator.userAgent;
	return ((ua.indexOf("MSIE ") > 0) || (ua.indexOf("Trident/") > 0));
}

function getMeta(metaName) {
	const metas = $('meta[name='+metaName+']');
	return (metas.length == 0) ? '' : metas[0].content;
}

String.prototype.explode = function (separator, limit) {
	// https://stackoverflow.com/questions/4514323/javascript-equivalent-to-php-explode
	const array = this.split(separator);
	if (limit !== undefined && array.length >= limit) {
		array.push(array.splice(limit - 1).join(separator));
	}
	return array;
};

String.prototype.htmlentities = function () {
	return this.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');//"
};

String.prototype.html_entity_decode = function () {
	return $('<textarea />').html(this).text();
};

if (!String.prototype.replaceAll) {
	/**
	 * String.prototype.replaceAll() polyfill
	 * https://gomakethings.com/how-to-replace-a-section-of-a-string-with-another-one-with-vanilla-js/
	 * @author Chris Ferdinandi
	 * @license MIT
	 */
	String.prototype.replaceAll = function(str, newStr){
		// If a regex pattern
		if (Object.prototype.toString.call(str).toLowerCase() === '[object regexp]') {
			return this.replace(str, newStr);
		}
		// If a string
		return this.replace(new RegExp(str, 'g'), newStr);
	};
}

if (!String.prototype.startsWith) {
	// https://developer.mozilla.org/en-US/docs/Web/JavaScript/Reference/Global_Objects/String/startsWith#polyfill
	Object.defineProperty(String.prototype, 'startsWith', {
		value: function(search, rawPos) {
			var pos = rawPos > 0 ? rawPos|0 : 0;
			return this.substring(pos, pos + search.length) === search;
		}
	});
}




function jumpToAnchor(anchor) {
	window.location.href = "#" + anchor;
}

function getCookie(cname) {
	// Source: https://www.w3schools.com/js/js_cookies.asp
	var name = cname + "=";
	var decodedCookie = decodeURIComponent(document.cookie);
	var ca = decodedCookie.split(';');
	for(var i = 0; i <ca.length; i++) {
		var c = ca[i];
		while (c.charAt(0) == ' ') {
			c = c.substring(1);
		}
		if (c.indexOf(name) == 0) {
			return c.substring(name.length, c.length);
		}
	}
	return undefined;
}

function setCookie(cname, cvalue, exdays, path) {
	var d = new Date();
	d.setTime(d.getTime() + (exdays*24*60*60*1000));
	var expires = exdays == 0 ? "" : "; expires="+d.toUTCString();
	document.cookie = cname + "=" + cvalue + expires + ";path=" + path + ";SameSite=" + samesite_policy;
}



function isNull(val, def) {
	// For compatibility with Internet Explorer, use isNull(a,b) instead of a??b
	if (val == null) {
		// since null==undefined, this also works with undefined
		return def;
	} else {
		return val;
	}
}

function btoa(bin) {
	var tableStr = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/";
	var table = tableStr.split("");
	for (var i = 0, j = 0, len = bin.length / 3, base64 = []; i < len; ++i) {
		var a = bin.charCodeAt(j++), b = bin.charCodeAt(j++), c = bin.charCodeAt(j++);
		if ((a | b | c) > 255) throw new Error(_L('String contains an invalid character'));
		base64[base64.length] = table[a >> 2] + table[((a << 4) & 63) | (b >> 4)] +
		                       (isNaN(b) ? "=" : table[((b << 2) & 63) | (c >> 6)]) +
		                       (isNaN(b + c) ? "=" : table[c & 63]);
	}
	return base64.join("");
};

function hexToBase64(str) {
	return btoa(String.fromCharCode.apply(null,
	            str.replace(/\r|\n/g, "").replace(/([\da-fA-F]{2}) ?/g, "0x$1 ").replace(/ +$/, "").split(" ")));
}

function _b64EncodeUnicode(str) {
	if (str == "") {
		return "''";
	} else {
		return "base64_decode('"+b64EncodeUnicode(str)+"')";
	}
}

function b64EncodeUnicode(str) {
	// first we use encodeURIComponent to get percent-encoded UTF-8,
	// then we convert the percent encodings into raw bytes which
	// can be fed into btoa.
	return btoa(encodeURIComponent(str).replace(/%([0-9A-F]{2})/g,
	function toSolidBytes(match, p1) {
		return String.fromCharCode('0x' + p1);
	}));
}

function generateRandomString(length) {
	var charset = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789",
	retVal = "";
	for (var i = 0, n = charset.length; i < length; ++i) {
		retVal += charset.charAt(Math.floor(Math.random() * n));
	}
	return retVal;
}

function RemoveLastDirectoryPartOf(the_url) {
	var the_arr = the_url.split('/');
	if (the_arr.pop() == '') the_arr.pop();
	return( the_arr.join('/') );
}


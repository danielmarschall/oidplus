<?php

/*
 * Anti XSS
 * Copyright 2019 Daniel Marschall, ViaThinkSoft
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

// This function prevents XSS, while still allowing the usage of HTML
function anti_xss($str, $forbidden_tags=array('script','base','meta','link')) {
	// Avoid usage of <script tags, but still allow tags that might
	// have the prefix forbidden tagname as prefix (useful if you want
	// to block other tags other than "script").
	// $str = preg_replace('@<(/{0,1}script[^a-zA-Z])@i', '&lt;\\1', $str);
	foreach ($forbidden_tags as $tagname) {
		if ($tagname == '*') {
			$str = str_replace('<', '&lt;', $str);
		} else {
			$str = preg_replace('@<(/{0,1}'.preg_quote($tagname,'@').'[^a-zA-Z])@i', '&lt;\\1', $str);
		}
	}

	if (preg_grep('@^script$@i' , $forbidden_tags)) {
		// (1) Avoid stuff like a href="javascript:"
		$str = preg_replace('@(javascript|livescript|vbscript)\s*:@im', '\\1<!-- -->:', $str);

		// (2) Avoid injection of JavaScript events like onload=, but still allow HTML tags that might start with <on...
		$str = preg_replace('@O([nN][a-zA-Z]+\s*=)@m', '&#x4F;\\1', $str);
		$str = preg_replace('@o([nN][a-zA-Z]+\s*=)@m', '&#x6F;\\1', $str);
	}

	return $str;
}



# Some testcases
#echo anti_xss('hallo welt <script>alert(1)</script>');
#echo anti_xss('<svg onload'."\n\n\r\t".'="alert(1)" src=""></svg><online></online> on august ONLINE');
#echo anti_xss('<svg/onload=alert(\'XSS\')>');
#echo '<a href="'.anti_xss('" onclick="alert(1)').'">Click me</a>';
#echo anti_xss('<a href="">foo</a> <abc>xxx</abc>', array('a'));
#echo anti_xss('<a href="">foo</a> <abc>xxx</abc>', array('*'));
#echo anti_xss("<a href=\"JaVaScRiPt:alert('XSS')\">foobar</a> <pre>JavaScript: is cool</pre>");
#echo anti_xss("<a href=\"JaVaScRiPt    :   alert('XSS')\">foobar</a> <pre>JavaScript  : is cool</pre>");
#echo anti_xss("<a href=\"#\" onclick=\"vbscript:msgbox(\"XSS\")\">foobar</a> <pre>VbScript: is cool</pre>");
#echo anti_xss('<META HTTP-EQUIV="Set-Cookie" Content="USERID=<SCRIPT>alert(\'XSS\')</SCRIPT>">');

# Currently we don't support these XSS vectors. But I am unsure if they work at all, in modern browsers
#echo anti_xss('<BR SIZE="&{alert(\'XSS\')}">');
#echo anti_xss('<a href="" onclick="java&#x0d;script:alert(\'1\')">bla</a>');

# Currently we are vulnerable to this vectors
# (does not work with Chrome)
#echo anti_xss('<EMBED SRC="data:image/svg+xml;base64,PHN2ZyB4bWxuczpzdmc9Imh0dH A6Ly93d3cudzMub3JnLzIwMDAvc3ZnIiB4bWxucz0iaHR0cDovL3d3dy53My5vcmcv MjAwMC9zdmciIHhtbG5zOnhsaW5rPSJodHRwOi8vd3d3LnczLm9yZy8xOTk5L3hs aW5rIiB2ZXJzaW9uPSIxLjAiIHg9IjAiIHk9IjAiIHdpZHRoPSIxOTQiIGhlaWdodD0iMjAw IiBpZD0ieHNzIj48c2NyaXB0IHR5cGU9InRleHQvZWNtYXNjcmlwdCI+YWxlcnQoIlh TUyIpOzwvc2NyaXB0Pjwvc3ZnPg==" type="image/svg+xml" AllowScriptAccess="always"></EMBED>');
#echo anti_xss('<IMG STYLE="xss:expr/*XSS*/ession(alert(\'XSS\'))">'); // only IE

# TODO: find more vectors from cheat sheets
# https://www.owasp.org/index.php/XSS_Filter_Evasion_Cheat_Sheet

/*
if (isset($_POST['blabla'])) {
echo anti_xss($_POST['blabla']);
#echo $_POST['blabla'];
} else {

echo '<form method="POST" action="anti_xss.php">';
#echo '<textarea name="blabla">'.$_POST['blabla'].'</textarea>';
echo '<textarea name="blabla"></textarea>';
echo '<input type="submit">';
echo '</form>';
}
*/


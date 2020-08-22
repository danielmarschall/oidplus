<?php

/*
 * OIDplus 2.0
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

class OIDplusMailUtils {

	public static function validMailAddress($email) {
		return !empty(filter_var($email, FILTER_VALIDATE_EMAIL));
	}

	public static function secureEmailAddress($email, $linktext, $level=1) {

		// see http://www.spamspan.de/

		/* Level 1 */
		/*
		<span class="spamspan">
		<span class="u">user</span>
		@
		<span class="d">beispiel.de</span>
		(<span class="t">Spam Hasser</span>)
		</span>
		*/

		if ($level == 1) {
			@list($user, $domain) = explode('@', $email);
			if (($linktext == $email) || empty($linktext)) {
				return '<span class="spamspan"><span class="u">'.htmlentities($user).'</span>&#64;<span class="d">'.htmlentities($domain).'</span></span>';
			} else {
				return '<span class="spamspan"><span class="u">'.htmlentities($user).'</span>&#64;<span class="d">'.htmlentities($domain).'</span>(<span class="t">'.htmlentities($linktext).'</span>)</span>';
			}
		}

		/* Level 2 */
		/*
		<span class="spamspan">
			<span class="u">user</span>
			<img alt="at" width="10" src="@.png">
			<span class="d">beispiel.de</span>
		</span>
		*/

		if ($level == 2) {
			list($user, $domain) = explode('@', $email);
			return '<span class="spamspan"><span class="u">'.htmlentities($user).'</span><img alt="at" width="10" src="@.png"><span class="d">'.htmlentities($domain).'</span></span>';
		}

		/* Level 3 */
		/*
		<span class="spamspan">
			<span class="u">user</span>
			[at]
			<span class="d">beispiel [dot] de</span>
		</span>
		*/

		if ($level == 3) {
			list($user, $domain) = explode('@', $email);
			$domain = str_replace('.', ' '._L('[dot]').' ', $domain);
			return '<span class="spamspan"><span class="u">'.htmlentities($user).'</span> '._L('[at]').' <span class="d">'.htmlentities($domain).'</span></span>';
		}

		return null;


		// --- Old code ---

		// Attention: document.write() JavaScript will damage the browser cache, which leads to bugs if you navigate back&forth with the browser navigation

		// No new lines to avoid a JavaScript error!
		$linktext = str_replace("\r", ' ', $linktext);
		$linktext = str_replace("\n", ' ', $linktext);

		if (!function_exists('alas_js_crypt'))
		{
			function alas_js_crypt($text)
			{
				$tmp = '';
				for ($i=0; $i<strlen($text); $i++)
				{
					$tmp .= 'document.write("&#'.ord(substr($text, $i, 1)).';");';
				}
				return $tmp;
			}
		}

		if (!function_exists('alas_js_write'))
		{
			function alas_js_write($text)
			{
				$text = str_replace('\\', '\\\\', $text);
				$text = str_replace('"', '\"', $text);
				$text = str_replace('/', '\/', $text); // W3C Validation </a> -> <\/a>
				return 'document.write("'.$text.'");';
			}
		}

		$aus = '';
		if ($email != '')
		{
			$aus .= '<script><!--'."\n"; // type="text/javascript" is not necessary in HTML5
			$aus .= alas_js_write('<a href="');
			$aus .= alas_js_crypt('mailto:'.$email);
			$aus .= alas_js_write('">');
			$aus .= $crypt_linktext ? alas_js_crypt($linktext) : alas_js_write($linktext);
			$aus .= alas_js_write('</a>').'// --></script>';
		}

		if ($crypt_linktext) $linktext = str_replace('@', '&', $linktext);
		$email = str_replace('@', '&', $email);
		return $aus.'<noscript>'.htmlentities($linktext).' ('.htmlentities($email).')</noscript>';
	}

	public static function sendMail($to, $title, $msg, $cc='', $bcc='') {
		$h = new SecureMailer();

		$title = $title;

		$h->addHeader('From', OIDplus::config()->getValue('admin_email'));

		if (!empty($cc)) $h->addHeader('Cc',  $cc);
		if (!empty($bcc)) $h->addHeader('Bcc',  $bcc);

		$h->addHeader('X-Mailer', 'PHP/'.phpversion());
		if (isset($_SERVER['REMOTE_ADDR'])) $h->addHeader('X-RemoteAddr', $_SERVER['REMOTE_ADDR']);
		$h->addHeader('MIME-Version', '1.0');
		$h->addHeader('Content-Type', 'text/plain; charset=ISO-8859-1');

		$sent = $h->sendMail($to, $title, $msg);
		if (!$sent) {
			throw new OIDplusMailException(_L('Sending mail failed'));
		}
	}

}
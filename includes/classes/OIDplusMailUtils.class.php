<?php

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

namespace ViaThinkSoft\OIDplus;

// phpcs:disable PSR1.Files.SideEffects
\defined('INSIDE_OIDPLUS') or die;
// phpcs:enable PSR1.Files.SideEffects

class OIDplusMailUtils extends OIDplusBaseClass {

	/**
	 * @param string $email
	 * @return bool
	 */
	public static function validMailAddress(string $email): bool {
		return !empty(filter_var($email, FILTER_VALIDATE_EMAIL));
	}

	/**
	 * @param string $email
	 * @param string $linktext
	 * @param int $level
	 * @return string|null
	 */
	public static function secureEmailAddress(string $email, string $linktext, int $level=1)/*: ?string*/ {

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

		/*
		// Attention: document.write() JavaScript will damage the browser cache, which leads to bugs if you navigate back&forth with the browser navigation

		$crypt_linktext = true;

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

		*/
	}

	/**
	 * @param string $to
	 * @param string $title
	 * @param string $msg
	 * @param string $cc
	 * @param string $bcc
	 * @return void
	 * @throws OIDplusException
	 * @throws OIDplusMailException
	 */
	public static function sendMail(string $to, string $title, string $msg, string $cc='', string $bcc='') {
		$h = new \SecureMailer();

		// DM 14.04.2022: Added Reply-To, because some servers might change the 'From' attribute (Anti-Spoof?)
		$h->addHeader('From', OIDplus::config()->getValue('admin_email'));
		$h->addHeader('Reply-To', OIDplus::config()->getValue('admin_email'));

		$cc = explode(';', $cc);
		$global_cc = trim(OIDplus::config()->getValue('global_cc'));
		if ($global_cc != '') $cc[] = trim($global_cc);
		foreach ($cc as $x) $h->addHeader('Cc', $x);

		$bcc = explode(';', $bcc);
		$global_bcc = trim(OIDplus::config()->getValue('global_bcc'));
		if ($global_bcc != '') $bcc[] = trim($global_bcc);
		foreach ($bcc as $x) $h->addHeader('Bcc', $x);

		$h->addHeader('X-Mailer', 'PHP/'.PHP_VERSION);

		// DM 14.04.2022: Commented out because of privacy
		//if (isset($_SERVER['REMOTE_ADDR'])) $h->addHeader('X-RemoteAddr', $_SERVER['REMOTE_ADDR']);

		$h->addHeader('MIME-Version', '1.0');

		// DM 14.04.2022: Changed from "ISO-8859-1" to "UTF-8"
		$h->addHeader('Content-Type', 'text/plain; charset=UTF-8');

		$sent = $h->sendMail($to, $title, $msg);
		if (!$sent) {
			throw new OIDplusMailException(_L('Sending mail failed'));
		}
	}

}

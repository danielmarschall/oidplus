<?php

/*
 * Secure Mailer PHP Class
 * Copyright 2009-2013 Daniel Marschall, ViaThinkSoft
 * QB_SECURE_MAIL_PARAM (C) Erich Kachel
 * Version 2013-04-14
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

// TODO: getHeaders() as single string , attachments , remove headers etc, headers as array in/out, do you also need addRawHeader()?

class SecureMailer {
	private $headers = '';

	// TODO: This should rather be private, but it won't work
	const endl = "\n"; // GMX doesn't like CRLF! wtf?! (tested in Postfix in Linux)

	private function QB_SECURE_MAIL_PARAM($param_ = '', $level_ = 2) {
		// Prevents eMail header injections
		// Source: http://www.erich-kachel.de/?p=26 (modified)

		/* replace until done */
		$filtered = null;
		while (is_null($filtered) || ($param_ != $filtered)) {
			if (!is_null($filtered)) {
				$param_ = $filtered;
			}

			$filtered = preg_replace("/(Content-Transfer-Encoding:|MIME-Version:|content-type:|Subject:|to:|cc:|bcc:|from:|reply-to:)/ims", '', $param_);
		}

		unset($filtered);

		if ($level_ >= 2) {
			/* replace until done */
			while (!isset($filtered) || ($param_ != $filtered)) {
				if (isset($filtered)) {
					$param_ = $filtered;
				}

				$filtered = preg_replace("/(%0A|\\\\r|%0D|\\\\n|%00|\\\\0|%09|\\\\t|%01|%02|%03|%04|%05|%06|%07|%08|%09|%0B|%0C|%0E|%0F|%10|%11|%12|%13)/ims", '', $param_);
			}
		}

		return $param_;
	}

	private function getHeaders() {
		return $this->headers;
	}

	private static function mail_base64_encode($text) {
		// Why 72? Seen here: http://linux.dsplabs.com.au/munpack-mime-base64-multi-part-attachment-php-perl-decode-email-pdf-p82/
		return wordwrap(base64_encode($text), 72, self::endl, true);
	}

	private function headerLine($name, $value) {
		// Change 2011-02-09
		// LF is OK! CRLF does lead to CR+CRLF on some systems!
		// http://bugs.php.net/bug.php?id=15841
		// The mail() function is not talking to an SMTP server, so RFC2822 does not apply here. mail() is talking to a command line program on the local system, and it is reasonable to expect that program to require system-native line breaks.
		return $this->QB_SECURE_MAIL_PARAM($name).': '.$this->QB_SECURE_MAIL_PARAM($value)."\n";
	}

	public function addHeader($name, $value) {
		$this->headers .= $this->headerLine($name, $value);
	}

	public static function utf8Subject($subject) {
		$subject = mb_convert_encoding($subject, 'UTF-8');
		return '=?UTF-8?B?'.base64_encode($subject).'?=';
	}

	private function _sendMail($recipient, $subject, $message, $add_headers='') {
		return @mail(
			$this->QB_SECURE_MAIL_PARAM($recipient),
			$this->QB_SECURE_MAIL_PARAM($subject),
			$this->QB_SECURE_MAIL_PARAM($message, 1),
			$this->getHeaders().$add_headers
		);
	}

	public function sendMail($recipient, $subject, $message) {
		return $this->_sendMail($recipient, $subject, $message, '');
	}

	// TODO: generate plain from html (strip tags), optional
	public function sendMailHTMLandPlainMultipart($to, $subject, $msg_html, $msg_plain) {
		$boundary = uniqid('np');

		$msg_html  = $this->QB_SECURE_MAIL_PARAM($msg_html,  1);
		$msg_plain = $this->QB_SECURE_MAIL_PARAM($msg_plain, 1);

		$add_headers  = $this->headerLine('MIME-Version', '1.0');
		$add_headers .= $this->headerLine('Content-Type', 'multipart/alternative; boundary="'.$boundary.'"');

		$message  = "This is a MIME encoded message.";
		$message .= self::endl;
		$message .= self::endl;
		$message .= "--" . $boundary . self::endl;
		$message .= "Content-type: text/plain; charset=utf-8".self::endl;
		$message .= "Content-Transfer-Encoding: base64".self::endl;
		$message .= self::endl;
		$message .= $this->mail_base64_encode($msg_plain); // better than wordwrap&quoted-printable because of long lines (e.g. links)
		$message .= self::endl;
		$message .= self::endl;
		$message .= "--" . $boundary . self::endl;
		$message .= "Content-type: text/html; charset=utf-8".self::endl;
		$message .= "Content-Transfer-Encoding: base64".self::endl;
		$message .= self::endl;
		$message .= $this->mail_base64_encode($msg_html);
		$message .= self::endl;
		$message .= self::endl."--" . $boundary . "--";

		return @mail(
			$this->QB_SECURE_MAIL_PARAM($to),
			$this->QB_SECURE_MAIL_PARAM($subject),
			$message,
			$this->getHeaders().$add_headers
		);
	}
}

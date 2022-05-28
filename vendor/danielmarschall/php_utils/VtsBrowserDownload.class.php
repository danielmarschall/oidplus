<?php

/*
 * VtsBrowserDownload.class.php
 * Copyright 2021 Daniel Marschall, ViaThinkSoft
 * Revision: 2021-05-21
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

class VtsBrowserDownload {

	private static function wellKnownInlineFile($file_extension) {
		// Windows Firefox: Browser decided wheater to display or download by looking at the mime type (inline disposition with explicit filename works)
		// Windows Chrome:  Browser decided wheater to display or download by looking at the mime type (inline disposition with explicit filename DOES NOT WORK)

		//$file_extension = strtolower($file_extension);
		//$array_listen = array('txt', 'mp3', 'wav', 'mid', 'ogg', 'pdf', 'avi', 'mov', 'mp4', 'mpeg', 'mpg', 'swf', 'gif', 'jpg', 'jpeg', 'png');
		//return in_array($file_extension, $array_listen);

		return true;
	}

	private static function getMimeType($file_extension) {
		$file_extension = strtolower($file_extension);
		if (!class_exists('VtsFileTypeDetect')) {
			// https://github.com/danielmarschall/fileformats
			throw new Exception("Require 'fileformats' package");
		}
		return VtsFileTypeDetect::getMimeType('dummy.'.$file_extension);
	}

	public static function output_file($file, $mime_type='', $inline_mode=2/*2=auto*/) {
		// Partitally taken from:
		// - https://stackoverflow.com/a/13821992/488539
		// - https://stackoverflow.com/a/32885706/488539

		if (connection_status() != 0) return false;

		$file_extension = pathinfo($file, PATHINFO_EXTENSION);

		if(!is_readable($file)) throw new Exception('File not found or inaccessible!');
		$size = filesize($file);
		$name = rawurldecode(basename($file));

		if ($mime_type == '') {
			$mime_type = self::getMimeType($file_extension);
			if (!$mime_type) $mime_type='application/force-download';
		}



		while (ob_get_level() > 0) @ob_end_clean();

		switch ($inline_mode) {

			case 0:
				$disposition = 'attachment';
				break;

			case 1:
				$disposition = 'inline';
				break;

			case 2:
				$disposition = self::wellKnownInlineFile($file_extension) ? 'inline' : 'attachment';
				break;

			default:
				throw new Exception('Invalid value for inline_mode');
		}

		if(ini_get('zlib.output_compression')){
			ini_set('zlib.output_compression', 'Off');
		}
		header('Content-Type: ' . $mime_type);

		$ua = isset($_SERVER['HTTP_USER_AGENT']) ? strtoupper($_SERVER['HTTP_USER_AGENT']) : '';
		if (strstr($ua, 'MSIE')) {
			$name_msie = preg_replace('/\./', '%2e', $name, substr_count($name, '.') - 1);
			header('Content-Disposition: '.$disposition.';filename="'.$name_msie.'"');
		} else if (strstr($ua, 'FIREFOX')) {
			// TODO: Implement "encodeRFC5987ValueChars" described at https://developer.mozilla.org/de/docs/Web/JavaScript/Reference/Global_Objects/encodeURIComponent ?
			header('Content-Disposition: '.$disposition.';filename*="UTF-8\'\''.utf8_encode($name).'"');
		} else {
			// Note: There is possibly a bug in Google Chrome: https://stackoverflow.com/questions/61866508/chrome-ignores-content-disposition-filename
			header('Content-Disposition: '.$disposition.';filename="'.$name.'"');
		}

		header('Content-Transfer-Encoding: binary');
		header('Accept-Ranges: bytes');
		header('Cache-Control: public');

		if (isset($_SERVER['HTTP_RANGE'])) {
			list($a, $range) = explode("=",$_SERVER['HTTP_RANGE'],2);
			list($range) = explode(",",$range,2);
			list($range, $range_end) = explode("-", $range);
			$range=intval($range);
			if(!$range_end) {
				$range_end=$size-1;
			} else {
				$range_end=intval($range_end);
			}

			$new_length = $range_end-$range+1;
			http_response_code(206); // 206 Partial Content
			header("Content-Length: $new_length");
			header("Content-Range: bytes $range-$range_end/$size");
		} else {
			$range = 0;
			$etag = md5_file($file);
			header("Etag: $etag");
			if (isset($_SERVER['HTTP_IF_NONE_MATCH']) && (trim($_SERVER['HTTP_IF_NONE_MATCH']) == $etag)) {
				http_response_code(304); // 304 Not Modified
				return true;
			}

			$new_length=$size;
			header("Content-Length: ".$size);
			header('Content-MD5: '.$etag); // RFC 2616 clause 14.15
		}

		set_time_limit(0);

		$chunksize = 1*(1024*1024);
		$bytes_send = 0;
		if ($file = fopen($file, 'r')) {
			if(isset($_SERVER['HTTP_RANGE']))
			fseek($file, $range);

			while(!feof($file) &&
			      (!connection_aborted()) &&  // connection_status() == 0
			      ($bytes_send<$new_length))
			{
				$buffer = fread($file, $chunksize);
				echo($buffer);
				flush();
				$bytes_send += strlen($buffer);
			}
			fclose($file);
		} else {
			throw new Exception("Cannot open file $file");
		}
		return((connection_status() == 0) and !connection_aborted());
	}

}

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

class OIDplusGui {

	public static function generateContentPage($id) {
		$out = array();

		$handled = false;
		$out['title'] = '';
		$out['icon'] = '';
		$out['text'] = '';

		foreach (OIDplus::getPagePlugins() as $plugin) {
			try {
				$plugin->gui($id, $out, $handled);
			} catch (Exception $e) {
				$out['title'] = 'Error';
				$out['icon'] = 'img/error_big.png';
				$out['text'] = $e->getMessage();
			}
			if ($handled) break;
		}

		if (!$handled) {
			$out['title'] = 'Error';
			$out['icon'] = 'img/error_big.png';
			$out['text'] = 'The resource cannot be found.';
		}

		return $out;
	}

	public static function link($goto, $new_window=false) {
		if ($new_window) {
			return 'href="?goto='.urlencode($goto).'" target="_blank"';
		} else {
			if (strpos($goto, '#') !== false) {
				list($goto, $anchor) = explode('#', $goto, 2);
				return 'href="?goto='.urlencode($goto).'#'.htmlentities($anchor).'" onclick="openOidInPanel('.js_escape($goto).', true, '.js_escape($anchor).'); return false;"';
			} else {
				return 'href="?goto='.urlencode($goto).'" onclick="openOidInPanel('.js_escape($goto).', true); return false;"';
			}
		}
	}
}

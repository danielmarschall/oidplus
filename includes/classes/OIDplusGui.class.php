<?php

/*
 * OIDplus 2.0
 * Copyright 2019 - 2021 Daniel Marschall, ViaThinkSoft
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

if (!defined('INSIDE_OIDPLUS')) die();

class OIDplusGui extends OIDplusBaseClass {

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
				$out['title'] = _L('Error');
				$out['icon'] = 'img/error.png';
				$out['text'] = $e->getMessage();
			}
			if ($handled) break;
		}

		if (!$handled) {
			$out['title'] = _L('Error');
			$out['icon'] = 'img/error.png';
			$out['text'] = _L('The resource cannot be found.');
		}

		return $out;
	}

	public static function link($goto, $new_window=false): string {
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

	public static function getLanguageBox($goto, $useJs) {
		echo '<div id="languageBox">';
		$langbox_entries = array();
		$non_default_languages = 0;
		foreach (OIDplus::getAllPluginManifests('language') as $pluginManifest) {
			$flag = $pluginManifest->getLanguageFlag();
			$code = $pluginManifest->getLanguageCode();
			if ($code != OIDplus::DEFAULT_LANGUAGE) $non_default_languages++;
			if ($code == OIDplus::getCurrentLang()) {
				$class = 'lng_flag';
			} else {
				$class = 'lng_flag picture_ghost';
			}
			$add = (!is_null($goto)) ? '&amp;goto='.urlencode($goto) : '';

			$dirs = glob(OIDplus::localpath().'plugins/'.'*'.'/language/'.$code.'/');

			if (count($dirs) > 0) {
				$dir = substr($dirs[0], strlen(OIDplus::localpath()));
				$langbox_entries[$code] = '<span class="lang_flag_bg"><a '.($useJs ? 'onclick="setLanguage(\''.$code.'\'); return false" ' : '').'href="?lang='.$code.$add.'"><img src="'.OIDplus::webpath(null,OIDplus::PATH_RELATIVE).$dir.$flag.'" alt="'.$pluginManifest->getName().'" title="'.$pluginManifest->getName().'" class="'.$class.'" id="lng_flag_'.$code.'" height="20"></a></span> ';
			}
		}
		if ($non_default_languages > 0) {
			foreach ($langbox_entries as $ent) {
				echo "$ent\n\t\t";
			}
		}
		echo '</div>';
	}

	public static function html_exception_handler($exception) {
		if ($exception instanceof OIDplusConfigInitializationException) {
			echo '<!DOCTYPE HTML>';
			echo '<html><head><title>'._L('OIDplus initialization error').'</title></head><body>';
			echo '<h1>'._L('OIDplus initialization error').'</h1>';
			echo '<p>'.htmlentities($exception->getMessage(), ENT_SUBSTITUTE).'</p>';
			echo '<p>'._L('Please check the file %1','<b>userdata/baseconfig/config.inc.php</b>');
			if (is_dir(__DIR__ . '/../../setup')) {
				echo ' '._L('or run <a href="%1">setup</a> again',OIDplus::webpath(null,OIDplus::PATH_RELATIVE).'setup/');
			}
			echo '</p>';
			echo self::getExceptionTechInfo($exception);
			echo '</body></html>';
		} else {
			echo '<!DOCTYPE HTML>';
			echo '<html><head><title>'._L('OIDplus error').'</title></head><body>';
			echo '<h1>'._L('OIDplus error').'</h1>';
			// ENT_SUBSTITUTE because ODBC drivers might return ANSI instead of UTF-8 stuff
			echo '<p>'.htmlentities($exception->getMessage(), ENT_SUBSTITUTE).'</p>';
			echo self::getExceptionTechInfo($exception);
			echo '</body></html>';
		}
	}

	private static function getExceptionTechInfo($exception) {
		$out = '';
		$out .= '<p><b>'._L('Technical information about the problem').':</b></p>';
		$out .= '<pre>';
		$out .= get_class($exception)."\n";
		$out .= _L('at file %1 (line %2)',$exception->getFile(),"".$exception->getLine())."\n\n";
		$out .= _L('Stacktrace').":\n";
		$out .= htmlentities($exception->getTraceAsString());
		$out .= '</pre>';
		return $out;
	}

	public function tabBarStart() {
		return '<ul class="nav nav-tabs" id="myTab" role="tablist">';
	}

	public function tabBarEnd() {
		return '</ul>';
	}

	public function tabBarElement($id, $title, $active) {
		// data-bs-toggle is for Bootstrap 5
		// data-toggle is for Bootstrap 4 (InternetExplorer compatibility)
		return '<li class="nav-item"><a class="nav-link'.($active ? ' active' : '').'" id="'.$id.'-tab" data-bs-toggle="tab" data-toggle="tab" href="#'.$id.'" role="tab" aria-controls="'.$id.'" aria-selected="'.($active ? 'true' : 'false').'">'.$title.'</a></li>';
	}

	public function tabContentStart() {
		return '<div class="tab-content" id="myTabContent">';
	}

	public function tabContentEnd() {
		return '</div>';
	}

	public function tabContentPage($id, $content, $active) {
		return '<div class="tab-pane fade'.($active ? ' show active' : '').'" id="'.$id.'" role="tabpanel" aria-labelledby="'.$id.'-tab">'.$content.'</div>';
	}

}

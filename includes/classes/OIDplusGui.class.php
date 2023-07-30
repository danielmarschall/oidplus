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

class OIDplusGui extends OIDplusBaseClass {

	/**
	 * @param string $id
	 * @return array
	 */
	public function generateContentPage(string $id): array {
		$out = array();

		$handled = false;
		$out['title'] = '';
		$out['icon'] = '';
		$out['text'] = '';

		foreach (OIDplus::getPagePlugins() as $plugin) {
			try {
				$plugin->gui($id, $out, $handled);
			} catch (\Exception $e) {
				$out['title'] = _L('Error');
				$out['icon'] = 'img/error.png';
				$htmlmsg = $e instanceof OIDplusException ? $e->getHtmlMessage() : htmlentities($e->getMessage());
				if (strtolower(substr($htmlmsg, 0, 3)) === '<p ') {
					$out['text'] = $htmlmsg;
				} else {
					$out['text'] = '<p>'.$htmlmsg.'</p>';
				}
				if (isset($_SERVER['SCRIPT_FILENAME']) && (strtolower(basename($_SERVER['SCRIPT_FILENAME'])) !== 'ajax.php')) { // don't send HTTP error codes in ajax.php, because we want a page and not a JavaScript alert box, when someone enters an invalid OID in the GoTo-Box
					if (PHP_SAPI != 'cli') @http_response_code($e instanceof OIDplusException ? $e->getHttpStatus() : 500);
				}
				if (OIDplus::baseConfig()->getValue('DEBUG')) {
					$out['text'] .= self::getExceptionTechInfo($e);
				}
			}
			if ($handled) break;
		}

		if (!$handled) {
			if (isset($_SERVER['SCRIPT_FILENAME']) && (strtolower(basename($_SERVER['SCRIPT_FILENAME'])) !== 'ajax.php')) { // don't send HTTP error codes in ajax.php, because we want a page and not a JavaScript alert box, when someone enters an invalid OID in the GoTo-Box
				if (PHP_SAPI != 'cli') @http_response_code(404);
			}
			$out['title'] = _L('Error');
			$out['icon'] = 'img/error.png';
			$out['text'] = _L('The resource cannot be found.');
		}

		return $out;
	}

	/**
	 * @param string $goto
	 * @param bool $new_window
	 * @return string
	 */
	public function link(string $goto, bool $new_window=false): string {
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

	/**
	 * @param string $goto
	 * @param bool $useJs
	 * @return string
	 * @throws OIDplusConfigInitializationException
	 * @throws OIDplusException
	 */
	public function getLanguageBox(string $goto, bool $useJs): string {
		$out = '<div id="languageBox">';
		$langbox_entries = array();
		$non_default_languages = 0;
		foreach (OIDplus::getAllPluginManifests('language') as $pluginManifest) {
			$flag = $pluginManifest->getLanguageFlag();
			$code = $pluginManifest->getLanguageCode();
			if ($code != OIDplus::getDefaultLang()) $non_default_languages++;
			if ($code == OIDplus::getCurrentLang()) {
				$class = 'lng_flag';
			} else {
				$class = 'lng_flag picture_ghost';
			}
			$add = ($goto != '') ? '&amp;goto='.urlencode($goto) : '';

			$dirs = glob(OIDplus::localpath().'plugins/'.'*'.'/language/'.$code.'/');

			if (count($dirs) > 0) {
				$dir = substr($dirs[0], strlen(OIDplus::localpath()));
				$langbox_entries[$code] = '<span class="lang_flag_bg"><a '.($useJs ? 'onclick="return !setLanguage(\''.$code.'\')" ' : '').'href="?lang='.$code.$add.'"><img src="'.OIDplus::webpath(null,OIDplus::PATH_RELATIVE).$dir.$flag.'" alt="'.$pluginManifest->getName().'" title="'.$pluginManifest->getName().'" class="'.$class.'" id="lng_flag_'.$code.'" height="20"></a></span> ';
			}
		}
		if ($non_default_languages > 0) {
			foreach ($langbox_entries as $ent) {
				$out .= "$ent\n\t\t";
			}
		}
		$out .= '</div>';
		return $out;
	}

	/**
	 * @param \Throwable $exception
	 * @return void
	 * @throws OIDplusException
	 */
	public static function html_exception_handler(\Throwable $exception) {
		// Note: This method must be static, because of its registration as Exception handler

		if ($exception instanceof OIDplusException) {
			$htmlTitle = $exception->gethtmlTitle();
			$htmlMessage = $exception->getHtmlMessage();
			if (isset($_SERVER['SCRIPT_FILENAME']) && (strtolower(basename($_SERVER['SCRIPT_FILENAME'])) !== 'ajax.php')) { // don't send HTTP error codes in ajax.php, because we want a page and not a JavaScript alert box, when someone enters an invalid OID in the GoTo-Box
				if (PHP_SAPI != 'cli') @http_response_code($exception->getHttpStatus());
			}
		} else {
			$htmlTitle = '';
			//$htmlMessage = htmlentities($exception->getMessage());
			$htmlMessage = nl2br(htmlentities(html_to_text($exception->getMessage())));
			if (isset($_SERVER['SCRIPT_FILENAME']) && (strtolower(basename($_SERVER['SCRIPT_FILENAME'])) !== 'ajax.php')) { // don't send HTTP error codes in ajax.php, because we want a page and not a JavaScript alert box, when someone enters an invalid OID in the GoTo-Box
				if (PHP_SAPI != 'cli') @http_response_code(500);
			}
		}
		if (!$htmlTitle) {
			$htmlTitle = _L('OIDplus Error');
		}

		echo '<!DOCTYPE HTML>';
		echo '<html><head><title>'.$htmlTitle.'</title></head><body>';
		echo '<h1>'.$htmlTitle.'</h1>';
		echo $htmlMessage;
		echo self::getExceptionTechInfo($exception);
		echo '</body></html>';
	}

	/**
	 * @param \Throwable $exception
	 * @return string
	 */
	private static function getExceptionTechInfo(\Throwable $exception): string {
		$out  = '<p><b>'.htmlentities(_L('Technical information about the problem')).':</b></p>';
		$out .= '<pre>';
		$out .= get_class($exception)."\n";

		$sourceFile = $exception->getFile();
		$stacktrace = $exception->getTraceAsString();

		// Censor paths
		try {
			$syspath = OIDplus::localpath(NULL);
			$stacktrace = str_replace($syspath, '...'.DIRECTORY_SEPARATOR, $stacktrace); // for security
			$sourceFile = str_replace($syspath, '...'.DIRECTORY_SEPARATOR, $sourceFile); // for security
		} catch (\Throwable $e) {
			// Catch Exception and Error, because this step (censoring) is purely optional and shoult not prevent the stacktrace of being shown
		}

		$out .= _L('at file %1 (line %2)',$sourceFile,"".$exception->getLine())."\n\n";
		$out .= _L('Stacktrace').":\n";
		$out .= htmlentities($stacktrace);

		$out .= '</pre>';
		return $out;
	}

	/**
	 * @return string
	 */
	public function tabBarStart(): string {
		return '<ul class="nav nav-tabs" id="myTab" role="tablist">';
	}

	/**
	 * @return string
	 */
	public function tabBarEnd(): string {
		return '</ul>';
	}

	/**
	 * @param string $id
	 * @param string $title
	 * @param bool $active
	 * @return string
	 */
	public function tabBarElement(string $id, string $title, bool $active): string {
		// data-bs-toggle is for Bootstrap 5
		return '<li class="nav-item"><a class="nav-link'.($active ? ' active' : '').'" id="'.$id.'-tab" data-bs-toggle="tab" href="#'.$id.'" role="tab" aria-controls="'.$id.'" aria-selected="'.($active ? 'true' : 'false').'">'.$title.'</a></li>';
	}

	/**
	 * @return string
	 */
	public function tabContentStart(): string {
		return '<div class="tab-content" id="myTabContent">';
	}

	/**
	 * @return string
	 */
	public function tabContentEnd(): string {
		return '</div>';
	}

	/**
	 * @param string $id
	 * @param string $content
	 * @param bool $active
	 * @return string
	 */
	public function tabContentPage(string $id, string $content, bool $active): string {
		return '<div class="tab-pane fade'.($active ? ' show active' : '').'" id="'.$id.'" role="tabpanel" aria-labelledby="'.$id.'-tab">'.$content.'</div>';
	}

	/**
	 * @param string $systemtitle
	 * @param string $pagetitle
	 * @return string
	 */
	public function combine_systemtitle_and_pagetitle(string $systemtitle, string $pagetitle): string {
		// Please also change the function in oidplus_base.js
		if ($systemtitle == $pagetitle) {
			return $systemtitle;
		} else {
			return $pagetitle . ' - ' . $systemtitle;
		}
	}

	/**
	 * @param string $title
	 * @return string[]
	 * @throws OIDplusException
	 */
	private function getCommonHeadElems(string $title): array {
		// Get theme color (color of title bar)
		$design_plugin = OIDplus::getActiveDesignPlugin();
		$theme_color = is_null($design_plugin) ? '' : $design_plugin->getThemeColor();

		$head_elems = array();
		$head_elems[] = '<meta http-equiv="Content-Type" content="text/html; charset=utf-8">';
		if (OIDplus::baseConfig()->getValue('DATABASE_PLUGIN','') !== '') {
			$head_elems[] = '<meta name="OIDplus-SystemTitle" content="'.htmlentities(OIDplus::config()->getValue('system_title')).'">'; // Do not remove. This meta tag is acessed by oidplus_base.js
		}
		if ($theme_color != '') {
			$head_elems[] = '<meta name="theme-color" content="'.htmlentities($theme_color).'">';
		}
		$head_elems[] = '<meta name="viewport" content="width=device-width, initial-scale=1.0">';
		$head_elems[] = '<title>'.htmlentities($title).'</title>';
		$head_elems[] = '<script src="'.htmlentities(OIDplus::webpath(null, OIDplus::PATH_RELATIVE)).'polyfill.min.js.php"></script>';
		$head_elems[] = '<script src="'.htmlentities(OIDplus::webpath(null, OIDplus::PATH_RELATIVE)).'oidplus.min.js.php?noBaseConfig=1" type="text/javascript"></script>';
		$head_elems[] = '<link rel="stylesheet" href="'.htmlentities(OIDplus::webpath(null, OIDplus::PATH_RELATIVE)).'oidplus.min.css.php?noBaseConfig=1">';
		$head_elems[] = '<link rel="icon" type="image/png" href="'.htmlentities(OIDplus::webpath(null, OIDplus::PATH_RELATIVE)).'favicon.png.php">';
		if (OIDplus::baseConfig()->exists('CANONICAL_SYSTEM_URL')) {
			$head_elems[] = '<link rel="canonical" href="'.htmlentities(OIDplus::canonicalURL().OIDplus::webpath(null, OIDplus::PATH_RELATIVE)).'">';
		}

		return $head_elems;
	}

	/**
	 * @param string $page_title_1
	 * @param string $page_title_2
	 * @param string $static_icon
	 * @param string $static_content
	 * @param array $extra_head_tags
	 * @param string $static_node_id
	 * @return string
	 * @throws OIDplusConfigInitializationException
	 * @throws OIDplusException
	 */
	public function showMainPage(string $page_title_1, string $page_title_2, string $static_icon, string $static_content, array $extra_head_tags=array(), string $static_node_id=''): string {
		$head_elems = $this->getCommonHeadElems($page_title_1);
		$head_elems = array_merge($head_elems, $extra_head_tags);

		$plugins = OIDplus::getAllPlugins();
		foreach ($plugins as $plugin) {
			$plugin->htmlHeaderUpdate($head_elems);
		}

		# ---

		$out  = "<!DOCTYPE html>\n";

		$out .= "<html lang=\"".substr(OIDplus::getCurrentLang(),0,2)."\">\n";
		$out .= "<head>\n";
		$out .= "\t".implode("\n\t",$head_elems)."\n";
		$out .= "</head>\n";

		$out .= "<body>\n";

		$out .= '<div id="loading" style="display:none">Loading&#8230;</div>';

		$out .= '<div id="frames">';
		$out .= '<div id="content_window" class="borderbox">';

		$out .= '<h1 id="real_title">';
		if ($static_icon != '') $out .= '<img src="'.htmlentities($static_icon).'" width="48" height="48" alt=""> ';
		$out .= htmlentities($page_title_2).'</h1>';
		$out .= '<div id="real_content">'.$static_content.'</div>';
		if ((!isset($_SERVER['REQUEST_METHOD'])) || ($_SERVER['REQUEST_METHOD'] == 'GET')) {
			$out .= '<br><p><img src="img/share.png" width="15" height="15" alt="'._L('Share').'"> <a href="'.htmlentities(OIDplus::canonicalUrl($static_node_id)).'" id="static_link" class="gray_footer_font">'._L('Static link to this page').'</a>';
			$out .= '</p>';
		}
		$out .= '<br>';

		$out .= '</div>';

		$out .= '<div id="system_title_bar">';

		$out .= '<div id="system_title_menu" onclick="mobileNavButtonClick(this)" onmouseenter="mobileNavButtonHover(this)" onmouseleave="mobileNavButtonHover(this)">';
		$out .= '	<div id="bar1"></div>';
		$out .= '	<div id="bar2"></div>';
		$out .= '	<div id="bar3"></div>';
		$out .= '</div>';

		$out .= '<div id="system_title_text">';
		$out .= '	<a '.OIDplus::gui()->link('oidplus:system').' id="system_title_a">';
		$out .= '		<span id="system_title_logo"></span>';
		$out .= '		<span id="system_title_1">'.htmlentities(OIDplus::getEditionInfo()['vendor'].' OIDplus 2.0').'</span><br>';
		$out .= '		<span id="system_title_2">'.htmlentities(OIDplus::config()->getValue('system_title')).'</span>';
		$out .= '	</a>';
		$out .= '</div>';

		$out .= '</div>';

		$out .= OIDplus::gui()->getLanguageBox($static_node_id, true);

		$out .= '<div id="gotobox">';
		$out .= '<input type="text" name="goto" id="gotoedit" value="'.htmlentities($static_node_id).'">';
		$out .= '<input type="button" value="'._L('Go').'" onclick="gotoButtonClicked()" id="gotobutton">';
		$out .= '</div>';

		$out .= '<div id="oidtree" class="borderbox">';
		//$out .= '<noscript>';
		//$out .= '<p><b>'._L('Please enable JavaScript to use all features').'</b></p>';
		//$out .= '</noscript>';
		$out .= OIDplus::menuUtils()->nonjs_menu();
		$out .= '</div>';

		$out .= '</div>';

		$out .= "\n</body>\n";
		$out .= "</html>\n";

		# ---

		$plugins = OIDplus::getAllPlugins();
		foreach ($plugins as $plugin) {
			$plugin->htmlPostprocess($out);
		}

		return $out;
	}

	/**
	 * @param string $page_title_1
	 * @param string $page_title_2
	 * @param string $static_icon
	 * @param string $static_content
	 * @param string[] $extra_head_tags
	 * @return string
	 * @throws OIDplusConfigInitializationException
	 * @throws OIDplusException
	 */
	public function showSimplePage(string $page_title_1, string $page_title_2, string $static_icon, string $static_content, array $extra_head_tags=array()): string {
		$head_elems = $this->getCommonHeadElems($page_title_1);
		$head_elems = array_merge($head_elems, $extra_head_tags);

		# ---

		$out  = "<!DOCTYPE html>\n";

		$out .= "<html lang=\"".substr(OIDplus::getCurrentLang(),0,2)."\">\n";
		$out .= "<head>\n";
		$out .= "\t".implode("\n\t",$head_elems)."\n";
		$out .= "</head>\n";

		$out .= "<body>\n";

		$out .= '<div id="loading" style="display:none">Loading&#8230;</div>';

		$out .= '<div id="frames">';
		$out .= '<div id="content_window" class="borderbox">';

		$out .= '<h1 id="real_title">';
		if ($static_icon != '') $out .= '<img src="'.htmlentities($static_icon).'" width="48" height="48" alt=""> ';
		$out .= htmlentities($page_title_2).'</h1>';
		$out .= '<div id="real_content">'.$static_content.'</div>';
		$out .= '<br>';

		$out .= '</div>';

		$out .= '<div id="system_title_bar">';

		$out .= '<div id="system_title_text">';
		$out .= '	<span id="system_title_logo"></span>';
		$out .= '	<span id="system_title_1">'.htmlentities(OIDplus::getEditionInfo()['vendor'].' OIDplus 2.0').'</span><br>';
		$out .= '	<span id="system_title_2">'.htmlentities($page_title_1).'</span>';
		$out .= '</div>';

		$out .= '</div>';

		$out .= OIDplus::gui()->getLanguageBox('', true);

		$out .= '</div>';

		$out .= "\n</body>\n";
		$out .= "</html>\n";

		# ---

		return $out;
	}

}

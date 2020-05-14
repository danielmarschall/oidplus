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

class OIDplusPluginManifest {

	private $name;
	private $author;
	private $version;
	private $htmlDescription;

	private $phpMainClass;
	private $cssFiles = array();
	private $jsFiles = array();

	public function getName(): string {
		return $this->name;
	}

	public function getAuthor(): string {
		return $this->author;
	}

	public function getVersion(): string {
		return $this->version;
	}

	public function getHtmlDescription(): string {
		return $this->htmlDescription;
	}

	public function getPhpMainClass(): string {
		return $this->phpMainClass;
	}

	public function getCSSFiles(): array {
		return $this->cssFiles;
	}

	public function getJSFiles(): array {
		return $this->jsFiles;
	}

	public function loadManifest($filename) {
		$ini = $filename;
		if (!file_exists($ini)) return false;
		$bry = parse_ini_file($ini, true, INI_SCANNER_TYPED); // TODO: in the future, make an XML manifest file
		if (!isset($bry['Plugin'])) return false;

		$this->name = $bry['Plugin']['name'];
		$this->author = $bry['Plugin']['author'];
		$this->version = $bry['Plugin']['version'];
		$this->htmlDescription = $bry['Plugin']['descriptionHTML'];

		$this->phpMainClass = isset($bry['PHP']['pluginclass']) ? $bry['PHP']['pluginclass'] : '';

		if (isset($bry['CSS'])) {
			foreach ($bry['CSS'] as $dry_name => $dry) {
				if ($dry_name != 'file') continue;
				foreach ($dry as $css_file) {
					$this->cssFiles[] = dirname($filename).'/'.$css_file;
				}
			}
		}

		if (isset($bry['JavaScript'])) {
			foreach ($bry['JavaScript'] as $dry_name => $dry) {
				if ($dry_name != 'file') continue;
				foreach ($dry as $js_file) {
					$this->jsFiles[] = dirname($filename).'/'.$js_file;
				}
			}
		}

		return true;
	}

}


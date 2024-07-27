<?php

/*
 * OIDplus 2.0
 * Copyright 2019 - 2024 Daniel Marschall, ViaThinkSoft
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

namespace ViaThinkSoft\OIDplus\Core;

// phpcs:disable PSR1.Files.SideEffects
\defined('INSIDE_OIDPLUS') or die;
// phpcs:enable PSR1.Files.SideEffects

class OIDplusPluginManifest extends OIDplusBaseClass {

	/**
	 * @var string
	 */
	private $manifestFile = null;

	// --- All plugins ---

	/**
	 * @var string
	 */
	private $name = '';

	/**
	 * @var string
	 */
	private $author = '';

	/**
	 * @var string
	 */
	private $license = '';

	/**
	 * @var string
	 */
	private $version = '';

	/**
	 * @var string
	 */
	private $htmlDescription = '';

	/**
	 * @var string
	 */
	private $oid = '';

	/**
	 * @var string
	 */
	private $type = '';

	/**
	 * @var string
	 */
	private $phpMainClass = '';

	// --- Only page or design plugins ---

	/**
	 * @var array
	 */
	private $cssFiles = array();

	// --- Only page plugins ---

	/**
	 * @var array
	 */
	private $jsFiles = array();

	// --- Only database plugins ---

	/**
	 * @var array
	 */
	private $cssFilesSetup = array();

	/**
	 * @var array
	 */
	private $jsFilesSetup = array();

	// --- Only language plugins ---

	/**
	 * @var string
	 */
	private $languageCode = '';

	/**
	 * @var string
	 */
	private $languageFlag = '';

	/**
	 * @var string
	 */
	private $languageMessages = '';

	# -------------

	/**
	 * @return string
	 */
	public function getTypeClass(): string {
		return $this->type;
	}

	/**
	 * @return string
	 */
	public function getName(): string {
		// TODO: Find a way to translate plugin names
		return $this->name;
	}

	/**
	 * @return string
	 */
	public function getAuthor(): string {
		return $this->author;
	}

	/**
	 * @return string
	 */
	public function getLicense(): string {
		return $this->license;
	}

	/**
	 * @return string
	 */
	public function getVersion(): string {
		if (str_starts_with($this->oid,'1.3.6.1.4.1.37476.2.5.2.4.') && ($this->version == '')) {
			$sysver = OIDplus::getVersion();
			if ($sysver == '') {
				//return _L('Part of OIDplus');
				return 'built-in';
			} else {
				//return _L('Part of OIDplus, version %1', $sysver);
				return $sysver ?: 'unknown';
			}
		} else {
			return $this->version;
		}
	}

	/**
	 * @return string
	 */
	public function getHtmlDescription(): string {
		return $this->htmlDescription;
	}

	/**
	 * @return string
	 */
	public function getOid(): string {
		return $this->oid;
	}

	/**
	 * @return string
	 */
	public function getPhpMainClass(): string {
		return $this->phpMainClass;
	}

	/**
	* @return string[]
	*/
	public function getCSSFiles(): array {
		return $this->cssFiles;
	}

	/**
	 * @return string[]
	*/
	public function getJSFiles(): array {
		return $this->jsFiles;
	}

	/**
	 * @return string[]
	*/
	public function getCSSFilesSetup(): array {
		return $this->cssFilesSetup;
	}

	/**
	 * @return string[]
	*/
	public function getJSFilesSetup(): array {
		return $this->jsFilesSetup;
	}

	/**
	 * @return string
	 */
	public function getManifestFile(): string {
		return $this->manifestFile;
	}

	/**
	 * @return string
	 */
	public function getLanguageCode(): string {
		return $this->languageCode;
	}

	/**
	 * @return string
	 */
	public function getLanguageFlag(): string {
		return $this->languageFlag;
	}

	/**
	 * @return string
	 */
	public function getLanguageMessages(): string {
		return $this->languageMessages;
	}

	/**
	 * Lists all files referenced by the manifest files
	 * Not included are other files like menu images or other PHP classes
	 * @return string[]
	 * @throws \ReflectionException
	 */
	public function getManifestLinkedFiles(): array {
		$files = array_merge(
			$this->getJSFiles(),
			$this->getCSSFiles(),
			$this->getJSFilesSetup(),
			$this->getCSSFilesSetup()
		);
		$files[] = $this->getManifestFile();
		$files[] = (new \ReflectionClass($this->getPhpMainClass()))->getFileName();
		sort($files);
		return $files;
	}

	/**
	 * @param string $filename
	 * @return void
	 */
	public function loadManifest(string $filename): void {
		if (!file_exists($filename)) throw new OIDplusException(_L("File %1 does not exist", $filename));;
		$cont = @file_get_contents($filename);
		if ($cont === false) throw new OIDplusException(_L("Cannot read file %1", $filename));
		$data = @json_decode($cont, false, 5, JSON_THROW_ON_ERROR);

		$this->manifestFile = $filename;

		// The following attributes are available for every plugin
		// JSON Schema urn:oid:1.3.6.1.4.1.37476.2.5.2.5.2.1 (page)
		//             urn:oid:1.3.6.1.4.1.37476.2.5.2.5.3.1 (language)
		//             urn:oid:1.3.6.1.4.1.37476.2.5.2.5.5.1 (general)
		$this->type = (string)$data->manifest->type;

		$this->name = (string)$data->manifest->info->name;
		$this->author = (string)$data->manifest->info->author;
		$this->license = (string)$data->manifest->info->license;
		$this->version = (string)$data->manifest->info->version;
		$this->htmlDescription = (string)$data->manifest->info->descriptionHTML;
		$this->oid = (string)$data->manifest->info->oid;

		$this->phpMainClass = $data->manifest->php->mainclass ?? "";

		// The following functionalities are only available for page or design plugins
		// JSON Schema urn:oid:1.3.6.1.4.1.37476.2.5.2.5.2.1
		// JSON Schema urn:oid:1.3.6.1.4.1.37476.2.5.2.5.7.1
		if (!isset($data->manifest->css)) $data->manifest->css = [];
		foreach ((array)$data->manifest->css as $css_file) {
			$file = dirname($filename).DIRECTORY_SEPARATOR.$css_file;
			//if (!file_exists($file)) continue;
			$this->cssFiles[] = $file;
		}

		// The following functionalities are only available for page plugins, captcha plugins, and object type plugins
		// JSON Schema urn:oid:1.3.6.1.4.1.37476.2.5.2.5.2.1
		// JSON Schema urn:oid:1.3.6.1.4.1.37476.2.5.2.5.10.1
		// JSON Schema urn:oid:1.3.6.1.4.1.37476.2.5.2.5.12.1
		if (!isset($data->manifest->js)) $data->manifest->js = [];
		foreach ((array)$data->manifest->js as $js_file) {
			$file = dirname($filename).DIRECTORY_SEPARATOR.$js_file;
			//if (!file_exists($file)) continue;
			$this->jsFiles[] = $file;
		}

		// The following functionalities are only available for database plugins
		// JSON Schema urn:oid:1.3.6.1.4.1.37476.2.5.2.5.2.6
		if (!isset($data->manifest->cssSetup)) $data->manifest->cssSetup = [];
		foreach ((array)$data->manifest->cssSetup as $css_file) {
			$file = dirname($filename).DIRECTORY_SEPARATOR.$css_file;
			//if (!file_exists($file)) continue;
			$this->cssFilesSetup[] = $file;
		}
		if (!isset($data->manifest->jsSetup)) $data->manifest->jsSetup = [];
		foreach ((array)$data->manifest->jsSetup as $js_file) {
			$file = dirname($filename).DIRECTORY_SEPARATOR.$js_file;
			//if (!file_exists($file)) continue;
			$this->jsFilesSetup[] = $file;
		}

		// The following functionalities are only available for language plugins
		// JSON Schema urn:oid:1.3.6.1.4.1.37476.2.5.2.5.3.1
		if (!isset($data->manifest->language)) {
			$data->manifest->language = new \stdClass();
			$data->manifest->language->code = "";
			$data->manifest->language->flag = "";
			$data->manifest->language->messages = "";
		}
		$this->languageCode = (string)$data->manifest->language->code;
		$this->languageFlag = (string)$data->manifest->language->flag;
		$this->languageMessages = (string)$data->manifest->language->messages;
	}

}

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

class OIDplusPluginManifest extends OIDplusBaseClass {

	/**
	 * @var string
	 */
	private $manifestFile = null;

	/**
	 * @var \SimpleXMLElement|null
	 */
	private $rawXML = null;

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
				return $sysver ? $sysver : 'unknown';
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
	 * @return \SimpleXMLElement
	 */
	public function getRawXml(): \SimpleXMLElement {
		return $this->rawXML;
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
	 * @return bool
	 */
	public function loadManifest(string $filename): bool {
		if (!file_exists($filename)) return false;
		$xmldata = @simplexml_load_file($filename);
		if ($xmldata === false) return false; // TODO: rather throw an Exception and let the method return void only

		$this->manifestFile = $filename;
		$this->rawXML = $xmldata;

		// The following attributes are available for every plugin
		// XML Schema urn:oid:1.3.6.1.4.1.37476.2.5.2.5.2.1 (page)
		//            urn:oid:1.3.6.1.4.1.37476.2.5.2.5.3.1 (language)
		//            urn:oid:1.3.6.1.4.1.37476.2.5.2.5.5.1 (general)
		$this->type = (string)$xmldata->type;

		$this->name = (string)$xmldata->info->name;
		$this->author = (string)$xmldata->info->author;
		$this->license = (string)$xmldata->info->license;
		$this->version = (string)$xmldata->info->version;
		$this->htmlDescription = (string)$xmldata->info->descriptionHTML;
		$this->oid = (string)$xmldata->info->oid;

		$this->phpMainClass = (string)$xmldata->php->mainclass;

		// The following functionalities are only available for page or design plugins
		// XML Schema urn:oid:1.3.6.1.4.1.37476.2.5.2.5.2.1
		// XML Schema urn:oid:1.3.6.1.4.1.37476.2.5.2.5.7.1
		foreach ((array)$xmldata->css->file as $css_file) {
			$file = dirname($filename).DIRECTORY_SEPARATOR.$css_file;
			//if (!file_exists($file)) continue;
			$this->cssFiles[] = $file;
		}

		// The following functionalities are only available for page plugins, captcha plugins, and object type plugins
		// XML Schema urn:oid:1.3.6.1.4.1.37476.2.5.2.5.2.1
		// XML Schema urn:oid:1.3.6.1.4.1.37476.2.5.2.5.10.1
		// XML Schema urn:oid:1.3.6.1.4.1.37476.2.5.2.5.12.1
		foreach ((array)$xmldata->js->file as $js_file) {
			$file = dirname($filename).DIRECTORY_SEPARATOR.$js_file;
			//if (!file_exists($file)) continue;
			$this->jsFiles[] = $file;
		}

		// The following functionalities are only available for database plugins
		// XML Schema urn:oid:1.3.6.1.4.1.37476.2.5.2.5.2.6
		foreach ((array)$xmldata->cssSetup->file as $css_file) {
			$file = dirname($filename).DIRECTORY_SEPARATOR.$css_file;
			//if (!file_exists($file)) continue;
			$this->cssFilesSetup[] = $file;
		}
		foreach ((array)$xmldata->jsSetup->file as $js_file) {
			$file = dirname($filename).DIRECTORY_SEPARATOR.$js_file;
			//if (!file_exists($file)) continue;
			$this->jsFilesSetup[] = $file;
		}

		// The following functionalities are only available for language plugins
		// XML Schema urn:oid:1.3.6.1.4.1.37476.2.5.2.5.3.1
		$this->languageCode = (string)$xmldata->language->code;
		$this->languageFlag = (string)$xmldata->language->flag;
		$this->languageMessages = (string)$xmldata->language->messages;

		return true;
	}

}

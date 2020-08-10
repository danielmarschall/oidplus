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

	private $name = '';
	private $author = '';
	private $version = '';
	private $htmlDescription = '';
	private $oid = '';

	private $type = '';
	private $phpMainClass = '';
	private $cssFiles = array();
	private $jsFiles = array();
	private $rawXML = null;

	public function getTypeClass(): string {
		return $this->type;
	}

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

	public function getOid(): string {
		return $this->oid;
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

	public function getRawXml(): object {
		return $this->rawXML;
	}

	public function loadManifest($filename) {
		if (!file_exists($filename)) return false;
		$xmldata = @simplexml_load_file($filename);
		if ($xmldata === false) return false;

		$this->type = (string)$xmldata->type;

		$this->name = (string)$xmldata->info->name;
		$this->author = (string)$xmldata->info->author;
		$this->version = (string)$xmldata->info->version;
		$this->htmlDescription = (string)$xmldata->info->descriptionHTML;
		$this->oid = (string)$xmldata->info->oid;

		$this->phpMainClass = (string)$xmldata->php->mainclass;

		foreach ((array)$xmldata->css->file as $css_file) {
			$file = dirname($filename).'/'.$css_file;
			if (!file_exists($file)) continue;
			$this->cssFiles[] = $file;
		}
		foreach ((array)$xmldata->js->file as $js_file) {
			$file = dirname($filename).'/'.$js_file;
			if (!file_exists($file)) continue;
			$this->jsFiles[] = $file;
		}

		$this->rawXML = $xmldata;

		return true;
	}

}

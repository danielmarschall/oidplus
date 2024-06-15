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

abstract class OIDplusPlugin extends OIDplusBaseClass {

	/**
	 * @return string
	 */
	public final function getPluginDirectory(): string {
		$reflector = new \ReflectionClass(get_called_class());
		return dirname($reflector->getFilename());
	}

	/**
	 * @return OIDplusPluginManifest
	 */
	public function getManifest(): OIDplusPluginManifest {
		$dir = $this->getPluginDirectory();
		$ini = $dir.DIRECTORY_SEPARATOR.'manifest.json';
		$manifest = new OIDplusPluginManifest();
		$manifest->loadManifest($ini);
		return $manifest;
	}

	/**
	 * @param bool $html
	 * @return void
	 */
	public function init(bool $html=true) {}

	/**
	 * @param string $actionID
	 * @param array $params
	 * @return array
	 * @throws OIDplusException
	 */
	public function action(string $actionID, array $params): array {
		throw new OIDplusException(_L('Invalid action ID'));
	}

	/**
	 * override this method if you want that your plugin
	 * can accept ajax.php requests from outside, without CSRF check
	 * @param string $actionID
	 * @return bool
	 */
	public function csrfUnlock(string $actionID): bool {
		return false;
	}

	/**
	 * @param string $request
	 * @return bool Handled?
	 */
	public function handle404(string $request): bool {
		return false;
	}

	/**
	 * @param string $html
	 * @return void
	 */
	public function htmlPostprocess(string &$html) {}

	/**
	 * @param array $head_elems
	 * @return void
	 */
	public function htmlHeaderUpdate(array &$head_elems) {}

	/**
	 * @param string[] $http_headers
	 * @return void
	 */
	public function httpHeaderCheck(array &$http_headers) {}

}

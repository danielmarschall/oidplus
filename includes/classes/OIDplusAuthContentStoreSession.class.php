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

class OIDplusAuthContentStoreSession extends OIDplusAuthContentStore {

	/**
	 * @return OIDplusSessionHandler
	 */
	protected static function getSessionHandler(): OIDplusSessionHandler {
		static $sesHandler = null;
		if (is_null($sesHandler)) {
			$sesHandler = new OIDplusSessionHandler();
		}
		return $sesHandler;
	}

	// Override abstract functions
	# TODO: shouldn't we just include OIDplusSessionHandler in this class?

	/**
	 * @param string $name
	 * @param mixed|null $default
	 * @return mixed|null
	 * @throws OIDplusException
	 */
	public function getValue(string $name, $default = NULL) {
		try {
			return self::getSessionHandler()->getValue($name, $default);
		} catch (\Exception $e) {
			self::getSessionHandler()->destroySession();
			// TODO: For some reason If destroySession() is called, we won't get this Exception?!
			throw new OIDplusException(_L('Internal error with session. Please reload the page and log-in again. %1', $e->getMessage()));
		}
	}

	/**
	 * @param string $name
	 * @param mixed $value
	 * @return void
	 * @throws OIDplusException
	 */
	public function setValue(string $name, $value) {
		self::getSessionHandler()->setValue($name, $value);
	}

	/**
	 * @param string $name
	 * @return bool
	 * @throws OIDplusException
	 */
	public function exists(string $name): bool {
		return self::getSessionHandler()->exists($name);
	}

	/**
	 * @param string $name
	 * @return void
	 * @throws OIDplusException
	 */
	public function delete(string $name) {
		self::getSessionHandler()->delete($name);
	}

	/**
	 * @return void
	 * @throws OIDplusException
	 */
	public function destroySession() {
		self::getSessionHandler()->destroySession();
	}

	/**
	 * @return OIDplusAuthContentStoreSession|null
	 */
	public static function getActiveProvider()/*: ?OIDplusAuthContentStore*/ {
		static $contentProvider = null;

		$rel_url = substr($_SERVER['REQUEST_URI'], strlen(OIDplus::webpath(null, OIDplus::PATH_RELATIVE_TO_ROOT)));
		if (str_starts_with($rel_url, 'rest/')) { // <== TODO: Find a way how to move this into the plugin, since REST does not belong to the core. (Maybe some kind of "stateless mode" that is enabled by the REST plugin)
			// For REST, we must only allow JWT from Bearer and nothing else! So disable cookies if we are accessing the REST plugin
			return null;
		}

		if (!$contentProvider) {
			if (self::getSessionHandler()->isActive()) {
				$contentProvider = new OIDplusAuthContentStoreSession();
			}
		}

		return $contentProvider;
	}

	/**
	 * @param string $email
	 * @param string $loginfo
	 * @return void
	 */
	public function raLoginEx(string $email, string &$loginfo) {
		$this->raLogin($email);
		if (is_null(self::getActiveProvider())) {
			$loginfo = 'into new PHP session';
		} else {
			$loginfo = 'into existing PHP session';
		}
	}

	/**
	 * @param string $loginfo
	 * @return void
	 */
	public function adminLoginEx(string &$loginfo) {
		$this->adminLogin();
		if (is_null(self::getActiveProvider())) {
			$loginfo = 'into new PHP session';
		} else {
			$loginfo = 'into existing PHP session';
		}
	}

	/**
	 * @param string $email
	 * @param string $loginfo
	 * @return void
	 */
	public function raLogoutEx(string $email, string &$loginfo) {
		$this->raLogout($email);
		$loginfo = 'from PHP session';
	}

	/**
	 * @param string $loginfo
	 * @return void
	 */
	public function adminLogoutEx(string &$loginfo) {
		$this->adminLogout();
		$loginfo = 'from PHP session';
	}

	/**
	 * @return void
	 */
	public function activate() {
		# Sessions automatically activate during setValue()
	}

}

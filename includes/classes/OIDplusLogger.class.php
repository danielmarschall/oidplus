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

class OIDplusLogger extends OIDplusBaseClass {

	/**
	 * This method splits a mask code containing multiple components (delimited by '+') into single components
	 * It takes care that '+' inside brackets isn't be used to split the codes
	 * Also, brackets can be escaped.
	 * The severity block (optional, must be standing in front of a component)
	 * is handled too. Inside the severity block, you may only use '/' to split components.
	 * The severity block will be implicitly repeated from the previous components if a component
	 * does not feature one.
	 * @param string $maskcode A maskcode, e.g. [INFO]OID(2.999)
	 * @return array|false An array of [$severity,$target],
	 * where $severity is 'INFO' or [$online,$offline] like ['INFO','INFO']
	 * and $target is like ['A'], ['OID', '2.999'], etc.
	 */
	public static function parse_maskcode(string $maskcode) {
		$out = array();
		$sevs = array(); // Note: The severity block will repeat for the next components if not changed explicitly

		if (!str_starts_with($maskcode,'V2:')) {
			return false;
		} else {
			$maskcode = substr($maskcode, 3);
		}

		if ($maskcode == '') return false;

		// Step 1: Split severities from the rest of the maskcodes
		/*
		 * "[ERR]AAA(BBB)+CCC(DDD)"   ==> array(
		 *                                 array(array("ERR"),"AAA(BBB)"),
		 *                                 array(array("ERR"),"CCC(DDD)")
		 *                              )
		 * "[INFO]AAA(B+BB)+[WARN]CCC(DDD)"  ==> array(
		 *                                 array(array("INFO"),"AAA(B+BB)"),
		 *                                 array(array("WARN"),"CCC(DDD)")
		 *                              )
		 * "[OK/WARN] AAA(B\)BB)+CCC(DDD)" ==> array(
		 *                                 array(array("OK", "WARN"),"AAA(B\)BB)"),
		 *                                 array(array("OK", "WARN"),"CCC(DDD)")
		 *                              )
		 */
		$code = '';
		$sev = '';
		$bracket_level = 0;
		$is_escaping = false;
		$inside_severity_block = false;
		for ($i=0; $i<strlen($maskcode); $i++) {
			$char = $maskcode[$i];

			if ($inside_severity_block) {
				// Severity block (optional)
				// e.g.  [OK/WARN] ==> $sevs = array("OK", "WARN")
				if ($char == '\\') {
					if ($is_escaping) {
						$is_escaping = false;
						$sev .= $char;
					} else {
						$is_escaping = true;
					}
				}
				else if ($char == '[') {
					if ($is_escaping) {
						$is_escaping = false;
					} else {
						$bracket_level++;
					}
					$sev .= $char;
				}
				else if ($char == ']') {
					if ($is_escaping) {
						$is_escaping = false;
						$sev .= $char;
					} else {
						$bracket_level--;
						if ($bracket_level < 0) return false;
						if ($bracket_level == 0) {
							$inside_severity_block = false;
							if ($sev != '') $sevs[] = $sev;
							$sev = '';
						} else {
							$sev .= $char;
						}
					}
				}
				else if ((($char == '/')) && ($bracket_level == 1)) {
					if ($is_escaping) {
						$is_escaping = false;
						$sev .= $char;
					} else {
						if ($sev != '') $sevs[] = $sev;
						$sev = '';
					}
				} else {
					if ($is_escaping) {
						// This would actually be an error, because we cannot escape this
						$is_escaping = false;
						$sev .= '\\' . $char;
					} else {
						$sev .= $char;
					}
				}
			} else {
				// Normal data (after the severity block)
				if (($char == '[') && ($code == '')) {
					$inside_severity_block = true;
					$bracket_level++;
					$sevs = array();
				}
				else if ($char == '\\') {
					if ($is_escaping) {
						$is_escaping = false;
						$code .= $char;
					} else {
						$is_escaping = true;
					}
				}
				else if ($char == '(') {
					if ($is_escaping) {
						$is_escaping = false;
					} else {
						$bracket_level++;
					}
					$code .= $char;
				}
				else if ($char == ')') {
					if ($is_escaping) {
						$is_escaping = false;
					} else {
						$bracket_level--;
						if ($bracket_level < 0) return false;
					}
					$code .= $char;
				}
				else if (($char == '+') && ($bracket_level == 0)) {
					if ($is_escaping) {
						$is_escaping = false;
						$code .= $char;
					} else {
						if ($code != '') $out[] = array($sevs,$code);
						$code = '';
					}
				} else {
					if ($is_escaping) {
						// This would actually be an error, because we cannot escape this
						$is_escaping = false;
						$code .= '\\' . $char;
					} else {
						$code .= $char;
					}
				}
			}
		}
		if ($code != '') $out[] = array($sevs,$code);
		if ($inside_severity_block) return false;
		unset($sevs);

		// Step 2: Process severities (split to online/offline)
		// Allowed:  ['INFO'] or ['INFO', 'INFO']
		// Disallow: ['NONE'] and ['NONE', 'NONE']
		foreach ($out as &$component) {
			$sev_fixed = null;
			$sevs = $component[0];
			if (count($sevs) == 1) {
				if ($sevs[0] == 'NONE') return false; // meaningless component
				try { self::convertSeverity($sevs[0]); } catch (\Exception $e) { return false; } // just checking for valid value
				$sev_fixed = $sevs[0];
			} else if (count($sevs) == 2) {
				$sev_online = $sevs[0];
				$sev_offline = $sevs[1];
				if (($sev_online == 'NONE') && ($sev_offline == 'NONE')) return false; // meaningless component
				try { self::convertSeverity($sev_online); } catch (\Exception $e) { return false; } // just checking for valid value
				try { self::convertSeverity($sev_offline); } catch (\Exception $e) { return false; } // just checking for valid value
				$sev_fixed = [$sev_online, $sev_offline];
			} else {
				return false;
			}
			$component[0] = $sev_fixed;
		}
		unset($component);

		// Step 3: Process target (split to type and value)
		// 'OID(2.999)' becomes ['OID', '2.999']
		// 'A' becomes ['A']
		foreach ($out as &$component) {
			$m = array();
			if (preg_match('@^([^()]+)\((.+)\)$@ismU', $component[1], $m)) {
				$type = $m[1];
				$value = $m[2];
				$component[1] = [$type, $value];
			} else {
				$component[1] = [$component[1]];
			}
		}
		unset($component);

		// Some other checks (it makes it easier to validate the maskcodes with dev tools)
		foreach ($out as list($severity,$target)) {
			if (($target[0] == 'OID') || ($target[0] == 'SUPOID')) {
				if (is_array($severity)) return false; // OID and SUPOID logger mask cannot have online/offline severity
				if (empty($target[1])) return false; /** @phpstan-ignore-line */
			} else if (($target[0] == 'OIDRA') || ($target[0] == 'SUPOIDRA') || ($target[0] == 'RA')) {
				if (empty($target[1])) return false;
			} else if ($target[0] == 'A') {
				if (!empty($target[1])) return false;
			} else {
				return false;
			}
		}

		return $out;
	}

	private $missing_plugin_queue = array();

	/**
	 * @return bool
	 * @throws OIDplusException
	 */
	public function reLogMissing(): bool {
		while (count($this->missing_plugin_queue) > 0) {
			$item = $this->missing_plugin_queue[0];
			if (!$this->log_internal($item[0], $item[1], false)) return false;
			array_shift($this->missing_plugin_queue);
		}
		return true;
	}

	/**
	 * @param string $maskcode A description of the mask-codes can be found in doc/developer_notes/logger_maskcodes.md
	 * @param string $message The message of the event
	 * @param mixed ...$sprintfArgs If used, %1..%n in $maskcode and $message will be replaced, like _L() does.
	 * @return bool
	 * @throws OIDplusException
	 */
	public function log(string $maskcode, string $message, ...$sprintfArgs): bool {
		$this->reLogMissing(); // try to re-log failed requests

		$sprintfArgs_Escaped = array();
		foreach ($sprintfArgs as $arg) {
			// Inside an severity block, e.g. INFO of [INFO], we would need to escape []/\
			// In the value, e.g. 2.999 of OID(2.999), we would need to escape ()+\
			// Since there seems to be no meaningful use-case for parametrized severities, we only escape the value
			$sprintfArgs_Escaped[] = str_replace(array('(',')','+','\\'), array('\\(', '\\)', '\\+', '\\\\'), $arg);
		}

		$maskcode = my_vsprintf($maskcode, $sprintfArgs_Escaped);
		$message = my_vsprintf($message, $sprintfArgs);

		if (strpos(str_replace('%%','',$maskcode),'%') !== false) {
			throw new OIDplusException(_L('Unresolved wildcards in logging maskcode'));
		}

		return $this->log_internal($maskcode, $message, true);
	}

	/**
	 * @param string $sev_name
	 * @return int
	 * @throws OIDplusConfigInitializationException
	 * @throws OIDplusException
	 */
	private static function convertSeverity(string $sev_name): int {
		//$sev_name = strtoupper($sev_name);

		switch ($sev_name) {
			case 'NONE':
				// Do not log anything. Used for online/offline severity pairs
				return -1;

			// [OK]   = Success
			//          Numeric value: 1
			//          Rule of thumb: YOU have done something and it was successful
			case  'OK':
				return 1;

			// [INFO] = Informational
			//          Numeric value: 2
			//          Rule of thumb: Someone else has done something (that affects you) and it was successful
			case 'INFO':
				return 2;

			// [WARN] = Warning
			//          Numeric value: 3
			//          Rule of thumb: Something happened (probably someone did something) and it affects you
			case 'WARN':
				return 3;

			// [ERR]  = Error
			//          Numeric value: 4
			//          Rule of thumb: Something failed (probably someone did something) and it affects you
			case 'ERR':
				return 4;

			// [CRIT] = Critical
			//          Numeric value: 5
			//          Rule of thumb: Something happened (probably someone did something) which is not an error,
			//          but some critical situation (e.g. hardware failure), and it affects you
			case 'CRIT':
				return 5;

			default:
				throw new OIDplusException(_L('Unknown severity "%1" in logger maskcode',$sev_name));
		}
	}

	/**
	 * @param string $maskcode
	 * @param string $message
	 * @param bool $allow_delayed_log
	 * @return bool
	 * @throws OIDplusException
	 */
	private function log_internal(string $maskcode, string $message, bool $allow_delayed_log): bool {
		$loggerPlugins = OIDplus::getLoggerPlugins();
		if (count($loggerPlugins) == 0) {
			// The plugin might not be initialized in OIDplus::init()
			// yet. Remember the log entries for later submission during
			// OIDplus::init();
			if ($allow_delayed_log) $this->missing_plugin_queue[] = array($maskcode, $message);
			return false;
		}

		$logEvent = new OIDplusLogEvent($message);

		$maskcode_ary = self::parse_maskcode($maskcode);
		if ($maskcode_ary === false) {
			throw new OIDplusException(_L('Invalid maskcode "%1" (failed to parse or has invalid data)',$maskcode));
		}
		foreach ($maskcode_ary as list($severity,$target)) {
			if ($target[0] == 'OID') {
				// OID(x)	Save log entry into the logbook of: Object "x"
				$object_id = $target[1];
				assert(!is_array($severity));
				$obj = OIDplusObject::parse($object_id);
				if (!$obj) throw new OIDplusException(_L('OID logger mask: Invalid object %1',$object_id));
				if (($severity_int = self::convertSeverity($severity)) >= 0) {
					$logEvent->addTarget(new OIDplusLogTargetObject($severity_int, $object_id));
				}
			}

			else if ($target[0] == 'SUPOID') {
				// SUPOID(x)	Save log entry into the logbook of: Parent of object "x"
				$object_id = $target[1];
				assert(!is_array($severity));
				$obj = OIDplusObject::parse($object_id);
				if (!$obj) throw new OIDplusException(_L('SUPOID logger mask: Invalid object %1',$object_id));
				if ($objParent = $obj->getParent()) {
					$parent = $objParent->nodeId();
					if (($severity_int = self::convertSeverity($severity)) >= 0) {
						$logEvent->addTarget(new OIDplusLogTargetObject($severity_int, $parent));
					}
				} else {
					//throw new OIDplusException(_L('%1 has no parent',$object_id));
				}
			}

			else if ($target[0] == 'OIDRA') {
				// OIDRA(x)	Save log entry into the logbook of: Logged in RA of object "x"
				$object_id = $target[1];
				$obj = OIDplusObject::parse($object_id);
				if (!$obj) throw new OIDplusException(_L('OIDRA logger mask: Invalid object "%1"', $object_id));
				if (!is_array($severity)) {
					$severity_online = $severity;
					$severity_offline = $severity;
				} else {
					$severity_online = $severity[0];
					$severity_offline = $severity[1];
				}
				foreach (OIDplusRA::getAllRAs() as $ra) {
					if ($obj->userHasWriteRights($ra)) {
						try {
							$tmp = OIDplus::authUtils()->isRaLoggedIn($ra);
						} catch (\Exception $e) {
							$tmp = false; // avoid that logging fails if things like JWT signature verification fails
						}
						if ($tmp) {
							if (($severity_online_int = self::convertSeverity($severity_online)) >= 0) {
								$logEvent->addTarget(new OIDplusLogTargetUser($severity_online_int, $ra->raEmail()));
							}
						} else {
							if (($severity_offline_int = self::convertSeverity($severity_offline)) >= 0) {
								$logEvent->addTarget(new OIDplusLogTargetUser($severity_offline_int, $ra->raEmail()));
							}
						}
					}
				}
			}

			else if ($target[0] == 'SUPOIDRA') {
				// SUPOIDRA(x)	Save log entry into the logbook of: Logged in RA that owns the superior object of "x"
				$object_id = $target[1];
				$obj = OIDplusObject::parse($object_id);
				if (!$obj) throw new OIDplusException(_L('SUPOIDRA logger mask: Invalid object "%1"',$object_id));
				if (!is_array($severity)) {
					$severity_online = $severity;
					$severity_offline = $severity;
				} else {
					$severity_online = $severity[0];
					$severity_offline = $severity[1];
				}
				foreach (OIDplusRA::getAllRAs() as $ra) {
					if ($obj->userHasParentalWriteRights($ra)) {
						try {
							$tmp = OIDplus::authUtils()->isRaLoggedIn($ra);
						} catch (\Exception $e) {
							$tmp = false; // avoid that logging fails if things like JWT signature verification fails
						}
						if ($tmp) {
							if (($severity_online_int = self::convertSeverity($severity_online)) >= 0) {
								$logEvent->addTarget(new OIDplusLogTargetUser($severity_online_int, $ra->raEmail()));
							}
						} else {
							if (($severity_offline_int = self::convertSeverity($severity_offline)) >= 0) {
								$logEvent->addTarget(new OIDplusLogTargetUser($severity_offline_int, $ra->raEmail()));
							}
						}
					}
				}
			}

			else if ($target[0] == 'RA') {
				// RA(x)	Save log entry into the logbook of: Logged in RA "x"
				$ra_email = $target[1];
				if (!is_array($severity)) {
					$severity_online = $severity;
					$severity_offline = $severity;
				} else {
					$severity_online = $severity[0];
					$severity_offline = $severity[1];
				}
				try {
					$tmp = OIDplus::authUtils()->isRaLoggedIn($ra_email);
				} catch (\Exception $e) {
					$tmp = false; // avoid that logging fails if things like JWT signature verification fails
				}
				if ($tmp) {
					if (($severity_online_int = self::convertSeverity($severity_online)) >= 0) {
						$logEvent->addTarget(new OIDplusLogTargetUser($severity_online_int, $ra_email));
					}
				} else {
					if (($severity_offline_int = self::convertSeverity($severity_offline)) >= 0) {
						$logEvent->addTarget(new OIDplusLogTargetUser($severity_offline_int, $ra_email));
					}
				}
			}

			else if ($target[0] == 'A') {
				// A	Save log entry into the logbook of: A logged in admin
				if (!is_array($severity)) {
					$severity_online = $severity;
					$severity_offline = $severity;
				} else {
					$severity_online = $severity[0];
					$severity_offline = $severity[1];
				}
				try {
					$tmp = OIDplus::authUtils()->isAdminLoggedIn();
				} catch (\Exception $e) {
					$tmp = false; // avoid that logging fails if things like JWT signature verification fails
				}
				if ($tmp) {
					if (($severity_online_int = self::convertSeverity($severity_online)) >= 0) {
						$logEvent->addTarget(new OIDplusLogTargetUser($severity_online_int, 'admin'));
					}
				} else {
					if (($severity_offline_int = self::convertSeverity($severity_offline)) >= 0) {
						$logEvent->addTarget(new OIDplusLogTargetUser($severity_offline_int, 'admin'));
					}
				}
			}

			// Unexpected
			else {
				throw new OIDplusException(_L('Unexpected logger component type "%1" in mask code "%2"',$target[0],$maskcode));
			}
		}

		// Now write the log message

		$result = false;

		if (count($logEvent->getTargets()) > 0) { // <-- count(targets)=0 for example of OIDRA(%1) gets notified during delete, but the object has no RA
			foreach ($loggerPlugins as $plugin) {
				$reason = '';
				if ($plugin->available($reason)) {
					$result |= $plugin->log($logEvent);
				}
			}
		}

		return $result;
	}
}

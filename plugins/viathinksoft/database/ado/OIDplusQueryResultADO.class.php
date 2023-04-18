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

class OIDplusQueryResultADO extends OIDplusQueryResult {
	/**
	 * @var bool
	 */
	protected $no_resultset;

	/**
	 * @var mixed
	 */
	protected $res;

	/**
	 * @param mixed $res
	 */
	public function __construct($res) {
		$this->no_resultset = is_bool($res);

		if (!$this->no_resultset) {
			$this->res = $res;
		}
	}

	/**
	 *
	 */
	public function __destruct() {
		if (!is_null($this->res))  $this->res->Close();
	}

	/**
	 * @return bool
	 */
	public function containsResultSet(): bool {
		return !$this->no_resultset;
	}

	/**
	 * @return int
	 * @throws OIDplusException
	 */
	protected function do_num_rows(): int {
		return $this->res->RecordCount;
	}

	/**
	 * @return array|null
	 */
	protected function do_fetch_array()/*: ?array*/ {
		if ($this->res->EOF) return null;

		$ary = array();
		for ($i=0; $i<$this->res->Fields->Count; $i++) {
			$ary[$this->res->Fields->Item($i)->Name] = $this->res->Fields->Item($i)->Value;
		}
		$this->res->MoveNext();
		return $ary;
	}

}

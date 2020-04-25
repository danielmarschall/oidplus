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

class OIDplusQueryResultMySQL extends OIDplusQueryResult {
	protected $no_resultset;
	protected $res;

	public function __construct($res) {
		$this->no_resultset = is_bool($res);

		if (!$this->no_resultset) {
			$this->res = $res;
		}
	}

	public function __destruct() {
		if ($this->res) $this->res->close();
	}

	public function containsResultSet(): bool {
		return !$this->no_resultset;
	}

	public function num_rows(): int {
		if ($this->no_resultset) throw new OIDplusException("The query has returned no result set (i.e. it was not a SELECT query)");
		return $this->res->num_rows;
	}

	public function fetch_array()/*: ?array*/ {
		if ($this->no_resultset) throw new OIDplusException("The query has returned no result set (i.e. it was not a SELECT query)");
		return $this->res->fetch_array(MYSQLI_ASSOC);
	}

	public function fetch_object()/*: ?object*/ {
		if ($this->no_resultset) throw new OIDplusException("The query has returned no result set (i.e. it was not a SELECT query)");
		return $this->res->fetch_object("stdClass");
	}
}

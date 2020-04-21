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

if (!defined('IN_OIDPLUS')) die();

class OIDplusDatabasePluginPDO extends OIDplusDatabasePlugin {
	private $conn = null;
	private $last_error = null; // we need that because PDO divides prepared statement errors and normal query errors, but we have only one "error()" method

	public static function getPluginInformation(): array {
		$out = array();
		$out['name'] = 'PDO';
		$out['author'] = 'ViaThinkSoft';
		$out['version'] = null;
		$out['descriptionHTML'] = null;
		return $out;
	}

	public static function name(): string {
		return "PDO";
	}

	public function doQuery(string $sql, /*?array*/ $prepared_args=null): OIDplusQueryResult {
		$this->last_error = null;
		if (is_null($prepared_args)) {
			$res = $this->conn->query($sql);

			if ($res === false) {
				$this->last_error = $this->conn->errorInfo()[2];
				throw new OIDplusSQLException($sql, $this->error());
			} else {
				return new OIDplusQueryResultPDO($res);
			}
		} else {
			// TEST: Emulate the prepared statement
			/*
			foreach ($prepared_args as $arg) {
				$needle = '?';
				$replace = "'$arg'"; // TODO: types
				$pos = strpos($sql, $needle);
				if ($pos !== false) {
					$sql = substr_replace($sql, $replace, $pos, strlen($needle));
				}
			}
			return OIDplusQueryResultPDO($this->conn->query($sql));
			*/

			if (!is_array($prepared_args)) {
				throw new OIDplusException("'prepared_args' must be either NULL or an ARRAY.");
			}

			foreach ($prepared_args as &$value) {
				// We need to manually convert booleans into strings, because there is a
				// 14 year old bug that hasn't been adressed by the PDO developers:
				// https://bugs.php.net/bug.php?id=57157
				// Note: We are using '1' and '0' instead of 'true' and 'false' because MySQL converts boolean to tinyint(1)
				if (is_bool($value)) $value = $value ? '1' : '0';
			}

			$ps = $this->conn->prepare($sql);
			if (!$ps) {
				$this->last_error = $ps->errorInfo()[2];
				throw new OIDplusSQLException($sql, 'Cannot prepare statement: '.$this->error());
			}
			$this->prepare_cache[$sql] = $ps;

			if (!$ps->execute($prepared_args)) {
				$this->last_error = $ps->errorInfo()[2];
				throw new OIDplusSQLException($sql, $this->error());
			}
			return new OIDplusQueryResultPDO($ps);
		}
	}

	public function insert_id(): int {
		return $this->conn->lastInsertId();
	}

	public function error(): string {
		$err = $this->last_error;
		if ($err == null) $err = '';
		return $err;
	}

	protected function doConnect()/*: void*/ {
		if (!class_exists('PDO')) throw new OIDplusConfigInitializationException('PHP extension "PDO" not installed');

		try {
			$options = [
			#    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
			    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
			    PDO::ATTR_EMULATE_PREPARES   => true,
			];

			// Try connecting to the database
			$dsn      = OIDplus::baseConfig()->getValue('PDO_DSN',      'mysql:host=localhost;dbname=oidplus;CHARSET=UTF8');
			$username = OIDplus::baseConfig()->getValue('PDO_USERNAME', 'root');
			$password = OIDplus::baseConfig()->getValue('PDO_PASSWORD', '');
			$this->conn = new PDO($dsn, $username, $password, $options);
		} catch (PDOException $e) {
			$message = $e->getMessage();
			throw new OIDplusConfigInitializationException('Connection to the database failed! '.$message);
		}

		$this->last_error = null;

		$this->query("SET NAMES 'utf8'");
	}

	protected function doDisconnect()/*: void*/ {
		$this->conn = null; // the connection will be closed by removing the reference
	}

	private $intransaction = false;

	public function transaction_begin()/*: void*/ {
		if ($this->intransaction) throw new OIDplusException("Nested transactions are not supported by this database plugin.");
		$this->conn->beginTransaction();
		$this->intransaction = true;
	}

	public function transaction_commit()/*: void*/ {
		$this->conn->commit();
		$this->intransaction = false;
	}

	public function transaction_rollback()/*: void*/ {
		$this->conn->rollBack();
		$this->intransaction = false;
	}
}

class OIDplusQueryResultPDO extends OIDplusQueryResult {
	protected $no_resultset;
	protected $res;

	public function __construct($res) {
		$this->no_resultset = is_bool($res);

		if (!$this->no_resultset) {
			$this->res = $res;
		}
	}

	public function __destruct() {
		if ($this->res) $this->res->closeCursor();
	}

	public function containsResultSet(): bool {
		return !$this->no_resultset;
	}

	public function num_rows(): int {
		if ($this->no_resultset) throw new OIDplusException("The query has returned no result set (i.e. it was not a SELECT query)");
		return $this->res->rowCount();
	}

	public function fetch_array()/*: ?array*/ {
		if ($this->no_resultset) throw new OIDplusException("The query has returned no result set (i.e. it was not a SELECT query)");
		$ret = $this->res->fetch(PDO::FETCH_ASSOC);
		if ($ret === false) $ret = null;
		return $ret;
	}

	public function fetch_object()/*: ?object*/ {
		if ($this->no_resultset) throw new OIDplusException("The query has returned no result set (i.e. it was not a SELECT query)");
		$ret = $this->res->fetch(PDO::FETCH_OBJ);
		if ($ret === false) $ret = null;
		return $ret;
	}
}

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

class OIDplusDatabasePluginMySQLi extends OIDplusDatabasePlugin {
	private $conn;
	private $prepare_cache = array();

	public static function getPluginInformation(): array {
		$out = array();
		$out['name'] = 'MySQLi';
		$out['author'] = 'ViaThinkSoft';
		$out['version'] = null;
		$out['descriptionHTML'] = null;
		return $out;
	}

	public static function name(): string {
		return "MySQL";
	}

	public function __construct() {
		if (!defined('OIDPLUS_MYSQL_HOST'))     define('OIDPLUS_MYSQL_HOST',     'localhost');
		if (!defined('OIDPLUS_MYSQL_USERNAME')) define('OIDPLUS_MYSQL_USERNAME', 'root');
		if (!defined('OIDPLUS_MYSQL_PASSWORD')) define('OIDPLUS_MYSQL_PASSWORD', ''); // base64 encoded
		if (!defined('OIDPLUS_MYSQL_DATABASE')) define('OIDPLUS_MYSQL_DATABASE', 'oidplus');
	}

	public function query(string $sql, /*?array*/ $prepared_args=null): OIDplusQueryResult {
		if (defined('OIDPLUS_MYSQL_QUERYLOG') && OIDPLUS_MYSQL_QUERYLOG) file_put_contents(__DIR__."/query.log", "$sql <== ".get_calling_function()."\n", FILE_APPEND);
		if (is_null($prepared_args)) {
			$res = $this->conn->query($sql, MYSQLI_STORE_RESULT);

			if ($res === false) {
				throw new OIDplusSQLException($sql, $this->error());
			} else {
				return new OIDplusQueryResultMySQL($res);
			}
		} else {
			if (!is_array($prepared_args)) {
				throw new OIDplusException("'prepared_args' must be either NULL or an ARRAY.");
			}

			foreach ($prepared_args as &$value) {
				// MySQLi has problems converting "true/false" to the data type "tinyint(1)"
				// It seems to be the same issue like in PDO reported 14 years ago at https://bugs.php.net/bug.php?id=57157
				if (is_bool($value)) $value = $value ? '1' : '0';
			}

			if (isset($this->prepare_cache[$sql])) {
				$ps = $this->prepare_cache[$sql];
			} else {
				$ps = $this->conn->prepare($sql);
				if (!$ps) {
					throw new OIDplusSQLException($sql, 'Cannot prepare statement');
				}

				// Caching the prepared is very risky
				// In PDO and ODBC we may not do it, because execute() will
				// destroy the existing cursors.
				// (test this with ./?goto=oid%3A1.3.6.1.4.1.37553.8.32488192274
				// you will see that 2.999 is missing in the tree)
				// But $ps->get_result() seems to "clone" the cursor,
				// so that $ps->execute may be called a second time?!
				// However, it only works with mysqlnd's get_result,
				// not with OIDplusQueryResultMySQLNoNativeDriver
				if (self::nativeDriverAvailable()) {
					$this->prepare_cache[$sql] = $ps;
				}
			}

			self::bind_placeholder_vars($ps,$prepared_args);
			if (!$ps->execute()) {
				throw new OIDplusSQLException($sql, $this->error());
			}

			if (self::nativeDriverAvailable()) {
				return new OIDplusQueryResultMySQL($ps->get_result());
			} else {
				return new OIDplusQueryResultMySQLNoNativeDriver($ps);
			}
		}
	}

	public function insert_id(): int {
		return $this->conn->insert_id;
	}

	public function error(): string {
		return !empty($this->conn->connect_error) ? $this->conn->connect_error : $this->conn->error;
	}

	protected function doConnect(): void {
		if (!function_exists('mysqli_connect')) throw new OIDplusException('PHP extension "MySQLi" not installed');

		if (defined('OIDPLUS_MYSQL_QUERYLOG') && OIDPLUS_MYSQL_QUERYLOG) file_put_contents("query.log", '');

		// Try connecting to the database
		list($hostname,$port) = explode(':', OIDPLUS_MYSQL_HOST.':'.ini_get("mysqli.default_port"));
		$this->conn = @new mysqli($hostname, OIDPLUS_MYSQL_USERNAME, base64_decode(OIDPLUS_MYSQL_PASSWORD), OIDPLUS_MYSQL_DATABASE, $port);
		if (!empty($this->conn->connect_error) || ($this->conn->connect_errno != 0)) {
			$message = $this->error();
			throw new OIDplusConfigInitializationException('Connection to the database failed! '.$message);
		}

		$this->query("SET NAMES 'utf8'");
	}

	protected function doDisconnect(): void {
		$this->conn->close();
		$this->conn = null;
	}

	private $intransaction = false;

	public function transaction_begin(): void {
		if ($this->intransaction) throw new OIDplusException("Nested transactions are not supported by this database plugin.");
		$this->conn->autocommit(true);
		$this->intransaction = true;
	}

	public function transaction_commit(): void {
		$this->conn->commit();
		$this->conn->autocommit(false);
		$this->intransaction = false;
	}

	public function transaction_rollback(): void {
		$this->conn->rollback();
		$this->conn->autocommit(false);
		$this->intransaction = false;
	}

	public static function nativeDriverAvailable(): bool {
		return function_exists('mysqli_fetch_all') && (!(defined('OIDPLUS_MYSQL_FORCE_MYSQLND_SUPPLEMENT') && (OIDPLUS_MYSQL_FORCE_MYSQLND_SUPPLEMENT)));
	}

	private static function bind_placeholder_vars(&$stmt,$params): bool {
		// Credit to: Dave Morgan
		// Code taken from: http://www.devmorgan.com/blog/2009/03/27/dydl-part-3-dynamic-binding-with-mysqli-php/
		//                  https://stackoverflow.com/questions/17219214/how-to-bind-in-mysqli-dynamically
		if ($params != null) {
			$types = '';                        //initial sting with types
			foreach ($params as $param) {        //for each element, determine type and add
				if (is_int($param)) {
					$types .= 'i';              //integer
				} elseif (is_float($param)) {
					$types .= 'd';              //double
				} elseif (is_string($param)) {
					$types .= 's';              //string
				} else {
					$types .= 'b';              //blob and unknown
				}
			}

			$bind_names = array();
			$bind_names[] = $types;             //first param needed is the type string, e.g.: 'issss'

			for ($i=0; $i<count($params);$i++) {    //go through incoming params and added em to array
				$bind_name = 'bind' . $i;       //give them an arbitrary name
				$$bind_name = $params[$i];      //add the parameter to the variable variable
				$bind_names[] = &$$bind_name;   //now associate the variable as an element in an array
			}

			//error_log("better_mysqli has params ".print_r($bind_names, 1));
			//call the function bind_param with dynamic params
			call_user_func_array(array($stmt,'bind_param'),$bind_names);
			return true;
		} else {
			return false;
		}
	}
}

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

class OIDplusQueryResultMySQLNoNativeDriver extends OIDplusQueryResult {
	// Based on https://www.php.net/manual/de/mysqli-stmt.get-result.php#113398

	protected $stmt;
	protected $nCols;
	protected $no_resultset;

	public function __construct($stmt) {
		$metadata = mysqli_stmt_result_metadata($stmt);

		$this->no_resultset = is_bool($metadata);

		if (!$this->no_resultset) {
			$this->nCols = mysqli_num_fields($metadata);
			$this->stmt = $stmt;

			mysqli_free_result($metadata);
		}
	}

	public function containsResultSet(): bool {
		return !$this->no_resultset;
	}

	public function num_rows(): int {
		if ($this->no_resultset) throw new OIDplusException("The query has returned no result set (i.e. it was not a SELECT query)");

		$this->stmt->store_result();
		return $this->stmt->num_rows;
	}

	public function fetch_array()/*: ?array*/ {
		if ($this->no_resultset) throw new OIDplusException("The query has returned no result set (i.e. it was not a SELECT query)");

		// https://stackoverflow.com/questions/10752815/mysqli-get-result-alternative , modified
		$stmt = $this->stmt;
		$stmt->store_result();
		$resultkeys = array();
		$thisName = "";

		if ($stmt->num_rows==0) return null;

		for ($i = 0; $i < $stmt->num_rows; $i++) {
			$metadata = $stmt->result_metadata();
			while ($field = $metadata->fetch_field()) {
				$thisName = $field->name;
				$resultkeys[] = $thisName;
			}
		}

		$ret = array();
		$args = array();
		for ($i=0; $i<$this->nCols; $i++) {
			$ret[$i] = NULL;
			$theValue = $resultkeys[$i];
			$ret[$theValue] = NULL; // will be overwritten by mysqli_stmt_bind_result
			$args[] = &$ret[$theValue];
		}
		if (!mysqli_stmt_bind_result($this->stmt, ...$args)) {
			return null;
		}

		// This should advance the "$stmt" cursor.
		if (!mysqli_stmt_fetch($this->stmt)) {
			return null;
		}

		// Return the array we built.
		return $ret;
	}

	public function fetch_object()/*: ?object*/ {
		if ($this->no_resultset) throw new OIDplusConfigInitializationException("The query has returned no result set (i.e. it was not a SELECT query)");

		$ary = $this->fetch_array();
		if (!$ary) return null;

		$obj = new stdClass;
		foreach ($ary as $name => $val) {
			$obj->$name = $val;
		}
		return $obj;
	}
}

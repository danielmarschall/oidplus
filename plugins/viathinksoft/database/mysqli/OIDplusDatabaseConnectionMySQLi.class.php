<?php

/*
 * OIDplus 2.0
 * Copyright 2019 - 2021 Daniel Marschall, ViaThinkSoft
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

if (!defined('INSIDE_OIDPLUS')) die();

class OIDplusDatabaseConnectionMySQLi extends OIDplusDatabaseConnection {
	private $conn = null; // only with MySQLnd
	private $prepare_cache = array();
	private $last_error = null; // we need that because MySQL divides prepared statement errors and normal query errors, but we have only one "error()" method

	public function doQuery(string $sql, /*?array*/ $prepared_args=null): OIDplusQueryResult {
		$this->last_error = null;
		if (is_null($prepared_args)) {
			$res = $this->conn->query($sql, MYSQLI_STORE_RESULT);

			if ($res === false) {
				$this->last_error = $this->conn->error;
				throw new OIDplusSQLException($sql, $this->error());
			} else {
				return new OIDplusQueryResultMySQL($res);
			}
		} else {
			if (!is_array($prepared_args)) {
				throw new OIDplusException(_L('"prepared_args" must be either NULL or an ARRAY.'));
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
					$this->last_error = $this->conn->error;
					throw new OIDplusSQLException($sql, _L('Cannot prepare statement').': '.$this->error());
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
				$this->last_error = mysqli_stmt_error($ps);
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
		$err = $this->last_error;
		if ($err == null) $err = '';
		return $err;
	}

	protected function doConnect()/*: void*/ {
		if (!function_exists('mysqli_connect')) throw new OIDplusException(_L('PHP extension "%1" not installed','MySQLi'));

		// Try connecting to the database
		$host     = OIDplus::baseConfig()->getValue('MYSQL_HOST',     'localhost');
		$username = OIDplus::baseConfig()->getValue('MYSQL_USERNAME', 'root');
		$password = OIDplus::baseConfig()->getValue('MYSQL_PASSWORD', '');
		$database = OIDplus::baseConfig()->getValue('MYSQL_DATABASE', 'oidplus');
		$socket   = OIDplus::baseConfig()->getValue('MYSQL_SOCKET',   '');
		list($hostname,$port) = explode(':', $host.':'.ini_get("mysqli.default_port"));
		$port = intval($port);
		$this->conn = @new mysqli($hostname, $username, $password, $database, $port, $socket);
		if (!empty($this->conn->connect_error) || ($this->conn->connect_errno != 0)) {
			$message = $this->conn->connect_error;
			throw new OIDplusConfigInitializationException(trim(_L('Connection to the database failed!').' '.$message));
		}

		$this->prepare_cache = array();
		$this->last_error = null;

		$this->query("SET NAMES 'utf8'");
	}

	protected function doDisconnect()/*: void*/ {
		$this->prepare_cache = array();
		if (!is_null($this->conn)) {
			$this->conn->close();
			$this->conn = null;
		}
	}

	private $intransaction = false;

	public function transaction_supported(): bool {
		return true;
	}

	public function transaction_level(): int {
		return $this->intransaction ? 1 : 0;
	}

	public function transaction_begin()/*: void*/ {
		if ($this->intransaction) throw new OIDplusException(_L('Nested transactions are not supported by this database plugin.'));
		$this->conn->autocommit(false);
		$this->conn->begin_transaction();
		$this->intransaction = true;
	}

	public function transaction_commit()/*: void*/ {
		$this->conn->commit();
		$this->conn->autocommit(true);
		$this->intransaction = false;
	}

	public function transaction_rollback()/*: void*/ {
		$this->conn->rollback();
		$this->conn->autocommit(true);
		$this->intransaction = false;
	}

	public function sqlDate(): string {
		return 'now()';
	}

	public static function nativeDriverAvailable(): bool {
		return function_exists('mysqli_fetch_all') && (OIDplus::baseConfig()->getValue('MYSQL_FORCE_MYSQLND_SUPPLEMENT', false) === false);
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

	protected function doGetSlang(bool $mustExist=true)/*: ?OIDplusSqlSlangPlugin*/ {
		$slang = OIDplus::getSqlSlangPlugin('mysql');
		if (is_null($slang)) {
			throw new OIDplusConfigInitializationException(_L('SQL-Slang plugin "%1" is missing. Please check if it exists in the directory "plugin/sqlSlang". If it is not existing, please recover it from an SVN snapshot or OIDplus TAR.GZ file.','mysql'));
		}
		return $slang;
	}
}

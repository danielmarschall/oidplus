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

class OIDplusDataBasePDO extends OIDplusDataBase {
	private $pdo;
	private $last_query;
	private $prepare_cache = array();

	public static function getPluginInformation() {
		$out = array();
		$out['name'] = 'PDO';
		$out['author'] = 'ViaThinkSoft';
		$out['version'] = null;
		$out['descriptionHTML'] = null;
		return $out;
	}

	public static function name() {
		return "PDO";
	}

	public function query($sql, $prepared_args=null) {
		$this->last_query = $sql;
		if (is_null($prepared_args)) {
			return $this->pdo->query($sql);
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
			return $this->pdo->query($sql);
*/


			if (!is_array($prepared_args)) {
				throw new Exception("'prepared_args' must be either NULL or an ARRAY.");
			}
			if (isset($this->prepare_cache[$sql])) {
				$ps = $this->prepare_cache[$sql];
			} else {
				$ps = $this->pdo->prepare($sql);
				if (!$ps) {
					throw new Exception("Cannot prepare statement '$sql'");
				}
				$this->prepare_cache[$sql] = $ps;
			}
			if (!$ps->execute($prepared_args)) {
				// Note: Our plugins prepare the configs by trying to insert stuff, which raises a Primary Key exception. So we cannot throw an Exception.
				return false;
			}
			return $ps;
		}
	}
	public function num_rows($res) {
		if (!is_object($res)) {
			throw new Exception("num_rows called on non object. Last query: ".$this->last_query);
		} else {
			return $res->rowCount();
		}
	}
	public function fetch_array($res) {
		if (!is_object($res)) {
			throw new Exception("fetch_array called on non object. Last query: ".$this->last_query);
		} else {
			return $res->fetch(PDO::FETCH_ASSOC);
		}
	}
	public function fetch_object($res) {
		if (!is_object($res)) {
			throw new Exception("fetch_object called on non object. Last query: ".$this->last_query);
		} else {
			return $res->fetch(PDO::FETCH_OBJ);
		}
	}
	public function insert_id() {
		return $this->pdo->lastInsertId();
	}
	public function error() {
		return $this->pdo->errorInfo()[2];
	}
	public function connect() {
		$html = OIDPLUS_HTML_OUTPUT;

		try {
			$options = [
			#    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
			    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
			    PDO::ATTR_EMULATE_PREPARES   => true,
			];

			// Try connecting to the database
			$this->pdo = new PDO(OIDPLUS_PDO_DSN, OIDPLUS_PDO_USERNAME, base64_decode(OIDPLUS_PDO_PASSWORD), $options);
		} catch (PDOException $e) {
			if ($html) {
				echo "<h1>Error</h1><p>Database connection failed! (".$e->getMessage().")</p>";
				if (is_dir(__DIR__.'/../../../setup')) {
					echo '<p>If you believe that the login credentials are wrong, please run <a href="setup/">setup</a> again.</p>';
				}
			} else {
				echo "Error: Database connection failed! (".$e->getMessage().")";
				if (is_dir(__DIR__.'/../../../setup')) {
					echo ' If you believe that the login credentials are wrong, please run setup again.';
				}
			}
			die();
		}

		$this->query("SET NAMES 'utf8'");
		$this->afterConnect($html);
		$this->connected = true;
	}

	private $intransaction = false;

	public function transaction_begin() {
		if ($this->intransaction) throw new Exception("Nested transactions are not supported by this database plugin.");
		$this->pdo->beginTransaction();
		$this->intransaction = true;
	}

	public function transaction_commit() {
		$this->pdo->commit();
		$this->intransaction = false;
	}

	public function transaction_rollback() {
		$this->pdo->rollBack();
		$this->intransaction = false;
	}
}

OIDplus::registerDatabasePlugin(new OIDplusDataBasePDO());

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

class OIDplusPagePublicSearch extends OIDplusPagePlugin {
	public function type() {
		return 'public';
	}

	public static function getPluginInformation() {
		$out = array();
		$out['name'] = 'Search';
		$out['author'] = 'ViaThinkSoft';
		$out['version'] = null;
		$out['descriptionHTML'] = null;
		return $out;
	}

	public function priority() {
		return 300;
	}

	public function action(&$handled) {
		// Nothing
	}

	public function init($html=true) {
		OIDplus::config()->prepareConfigKey('search_min_term_length', 'Minimum length of a search term', '3', 0, 1);
	}

	public function cfgSetValue($name, $value) {
		if ($name == 'search_min_term_length') {
			if (!is_numeric($value) || ($value < 0)) {
				throw new Exception("Please enter a valid value.");
			}
		}
	}

	public function gui($id, &$out, &$handled) {
		if (explode('$',$id)[0] == 'oidplus:search') {
			$handled = true;

			$out['title'] = 'Search';
			$out['icon'] = file_exists(__DIR__.'/icon_big.png') ? 'plugins/'.basename(dirname(__DIR__)).'/'.basename(__DIR__).'/icon_big.png' : '';

			$out['text'] = '';

			try {
				$search_term = isset($_POST['term']) ? $_POST['term'] : '';
				$ns = isset($_POST['namespace']) ? $_POST['namespace'] : '';

				if (!isset($_POST['search'])) {
					// Default criteria selection
					$_POST['search_title'] = '1';
					$_POST['search_asn1id'] = '1';
					$_POST['search_iri'] = '1';
				}

				// TODO: make it via AJAX? Reloading the whole page is not good. But attention: Also allow NoScript
				$out['text'] .= '<form id="searchForm" action="?goto=oidplus:search" method="POST">
				                 <input type="hidden" name="search" value="1">
				                 Search for: <input type="text" id="term" name="term" value="'.htmlentities($search_term).'"><br><br>
				                 <script>
				                 function searchNsSelect(ns) {
				                     document.getElementById("search_options_oid").style.display = (ns == "oid") ? "block" : "none";
				                     document.getElementById("search_options_object").style.display = (ns == "oidplus:ra") ? "none" : "block";
				                     document.getElementById("search_options_ra").style.display = (ns == "oidplus:ra") ? "block" : "none";
				                 }
				                 $( document ).ready(function() {
				                     searchNsSelect(document.getElementById("namespace").value);
				                 });
				                 </script>
				                 Search in: <select name="namespace" id="namespace" onchange="searchNsSelect(this.value);"><br><br>';

				foreach (OIDplus::getRegisteredObjectTypes() as $ot) {
					$out['text'] .= '<option value="'.htmlentities($ot::ns()).'"'.(($ns == $ot::ns()) ? ' selected' : '').'>'.htmlentities($ot::objectTypeTitle()).'</option>';
				}
				$out['text'] .= '<option value="oidplus:ra"'.(($ns == 'oidplus:ra') ? ' selected' : '').'>Registration Authority</option>
				                 </select><br><br>
				<div id="search_options_ra">
				<!-- TODO: RA specific selection criterias -->
				</div>
				<div id="search_options_object">
				            <input type="checkbox" name="search_title" id="search_title" value="1"'.(isset($_POST["search_title"]) ? ' checked' : '').'> <label for="search_title">Search in field "Title"</label><br>
				            <input type="checkbox" name="search_description" id="search_description" value="1"'.(isset($_POST["search_description"]) ? ' checked' : '').'> <label for="search_description">Search in field "Description"</label><br>
				<div id="search_options_oid">
			            <input type="checkbox" name="search_asn1id" id="search_asn1id" value="1"'.(isset($_POST["search_asn1id"]) ? ' checked' : '').'> <label for="search_asn1id">Search in field "ASN.1 identifier" (only OIDs)</label><br>
			            <input type="checkbox" name="search_iri" id="search_iri" value="1"'.(isset($_POST["search_iri"]) ? ' checked' : '').'> <label for="search_iri">Search in field "Unicode label" (only OIDs)</label><br>
				</div>
				</div>
				 <br>

				<input type="submit" value="Search">
				</form>';

				if (isset($_POST['search'])) {
					// Note: The SQL collation defines if search is case sensitive or case insensitive

					$min_length = OIDplus::config()->getValue('search_min_term_length');

					$search_term = trim($search_term);

					if (strlen($search_term) == 0) {
						$out['text'] .= '<p><font color="red">Error: You must enter a search term.</font></p>';
					} else if (strlen($search_term) < $min_length) {
						$out['text'] .= '<p><font color="red">Error: Search term minimum length is '.$min_length.' characters.</font></p>';
					} else {
						if ($ns == 'oidplus:ra') {
							$out['text'] .= '<h2>Search results for RA "'.htmlentities($search_term).'"</h2>';

							$sql_where = array(); $prep_where = array();
							$sql_where[] = "email like ?";   $prep_where[] = '%'.$search_term.'%';
							$sql_where[] = "ra_name like ?"; $prep_where[] = '%'.$search_term.'%';

							if (count($sql_where) == 0) $sql_where[] = '1=0';
							$res = OIDplus::db()->query("select * from ".OIDPLUS_TABLENAME_PREFIX."ra where (".implode(' or ', $sql_where).")", $prep_where);

							$count = 0;
							while ($row = OIDplus::db()->fetch_object($res)) {
								$email = str_replace('@', '&', $row->email);
								$out['text'] .= '<p><a '.oidplus_link('oidplus:rainfo$'.str_replace('@','&',$email)).'>'.htmlentities($email).'</a>: <b>'.htmlentities($row->ra_name).'</b></p>';
								$count++;
							}
							if ($count == 0) {
								$out['text'] .= '<p>Nothing found</p>';
							}
						} else {
							$out['text'] .= '<h2>Search results for "'.htmlentities($search_term).'" ('.htmlentities($ns).')</h2>';

							$sql_where = array(); $prep_where = array();
							$sql_where[] = "id like ?"; $prep_where[] = '%'.$search_term.'%'; // TODO: should we rather do findFitting(), so we can e.g. find GUIDs with different notation?
							if (isset($_POST["search_title"]))       { $sql_where[] = "title like ?";       $prep_where[] = '%'.$search_term.'%'; }
							if (isset($_POST["search_description"])) { $sql_where[] = "description like ?"; $prep_where[] = '%'.$search_term.'%'; }

							if (isset($_POST["search_asn1id"])) {
								$res = OIDplus::db()->query("select * from ".OIDPLUS_TABLENAME_PREFIX."asn1id where name like ?", array('%'.$search_term.'%'));
								while ($row = OIDplus::db()->fetch_object($res)) {
									$sql_where[] = "id = ?"; $prep_where[] = $row->oid;
								}
							}

							if (isset($_POST["search_iri"])) {
								$res = OIDplus::db()->query("select * from ".OIDPLUS_TABLENAME_PREFIX."iri where name like ?", array('%'.$search_term.'%'));
								while ($row = OIDplus::db()->fetch_object($res)) {
									$sql_where[] = "id = ?"; $prep_where[] = $row->oid;
								}
							}

							if (count($sql_where) == 0) $sql_where[] = '1=0';
							array_unshift($prep_where, $ns.':%');
							
							$res = OIDplus::db()->query("select * from ".OIDPLUS_TABLENAME_PREFIX."objects where id like ? and (".implode(' or ', $sql_where).")", $prep_where);

							$count = 0;
							while ($row = OIDplus::db()->fetch_object($res)) {
								$out['text'] .= '<p><a '.oidplus_link($row->id).'>'.htmlentities($row->id).'</a>: <b>'.htmlentities($row->title).'</b></p>'; // TODO: also show asn1id; highlight search match?
								$count++;
							}
							if ($count == 0) {
								$out['text'] .= '<p>Nothing found</p>';
							}
						}
					}
				}
			} catch (Exception $e) {
				$out['text'] = "Error: ".$e->getMessage();
			}
		}
	}

	public function tree(&$json, $ra_email=null, $nonjs=false, $req_goto='') {
		if (file_exists(__DIR__.'/treeicon.png')) {
			$tree_icon = 'plugins/'.basename(dirname(__DIR__)).'/'.basename(__DIR__).'/treeicon.png';
		} else {
			$tree_icon = null; // default icon (folder)
		}

		$json[] = array(
			'id' => 'oidplus:search',
			'icon' => $tree_icon,
			'text' => 'Search'
		);

		return true;
	}

	public function tree_search($request) {
		return false;
	}
}

OIDplus::registerPagePlugin(new OIDplusPagePublicSearch());

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

class OIDplusPagePublicSearch extends OIDplusPagePluginPublic {

	public function init($html=true) {
		OIDplus::config()->prepareConfigKey('search_min_term_length', 'Minimum length of a search term', '3', OIDplusConfig::PROTECTION_EDITABLE, function($value) {
			if (!is_numeric($value) || ($value < 0)) {
				throw new OIDplusException(_L('Please enter a valid value.'));
			}
		});
	}

	private function prepareSearchParams(&$params, $is_searching) {
		$params['term'] = isset($params['term']) ? trim($params['term']) : '';
		$params['namespace'] = isset($params['namespace']) ? trim($params['namespace']) : '';

		// Default criteria selection:
		if ($is_searching) {
			$params['search_title'] = isset($params['search_title']) && $params['search_title'];
			$params['search_description'] = isset($params['search_description']) && $params['search_description'];
			$params['search_asn1id'] = isset($params['search_asn1id']) && $params['search_asn1id'];
			$params['search_iri'] = isset($params['search_iri']) && $params['search_iri'];
		} else {
			$params['search_title'] = true;
			$params['search_description'] = false;
			$params['search_asn1id'] = true;
			$params['search_iri'] = true;
		}
	}

	private function doSearch($params) {
		$output = '';

		// Note: The SQL collation defines if search is case sensitive or case insensitive

		$min_length = OIDplus::config()->getValue('search_min_term_length');

		$this->prepareSearchParams($params, true);

		if (strlen($params['term']) == 0) {
			$output .= '<p><font color="red">'._L('Error: You must enter a search term.').'</font></p>';
		} else if (strlen($params['term']) < $min_length) {
			$output .= '<p><font color="red">'._L('Error: Search term minimum length is %1 characters.',$min_length).'</font></p>';
		} else {
			// TODO: case insensitive comparison (or should we leave that to the DBMS?)

			if ($params['namespace'] == 'oidplus:ra') {
				$output .= '<h2>'._L('Search results for RA %1',htmlentities($params['term'])).'</h2>';

				$sql_where = array(); $prep_where = array();
				$sql_where[] = "email like ?";   $prep_where[] = '%'.$params['term'].'%';
				$sql_where[] = "ra_name like ?"; $prep_where[] = '%'.$params['term'].'%';

				if (count($sql_where) == 0) $sql_where[] = '1=0';
				$res = OIDplus::db()->query("select * from ###ra where (".implode(' or ', $sql_where).")", $prep_where);

				$count = 0;
				while ($row = $res->fetch_object()) {
					$email = str_replace('@', '&', $row->email);
					$output .= '<p><a '.OIDplus::gui()->link('oidplus:rainfo$'.str_replace('@','&',$email)).'>'.htmlentities($email).'</a>: <b>'.htmlentities($row->ra_name).'</b></p>';
					$count++;
				}
				if ($count == 0) {
					$output .= '<p>'._L('Nothing found').'</p>';
				}
			} else {
				$output .= '<h2>'._L('Search results for %1 (%2)',htmlentities($params['term']),htmlentities($params['namespace'])).'</h2>';

				$sql_where = array(); $prep_where = array();
				$sql_where[] = "id like ?"; $prep_where[] = '%'.$params['term'].'%'; // TODO: should we rather do findFitting(), so we can e.g. find GUIDs with different notation?
				if ($params["search_title"])       { $sql_where[] = "title like ?";       $prep_where[] = '%'.$params['term'].'%'; }
				if ($params["search_description"]) { $sql_where[] = "description like ?"; $prep_where[] = '%'.$params['term'].'%'; }

				if ($params["search_asn1id"]) {
					$res = OIDplus::db()->query("select * from ###asn1id where name like ?", array('%'.$params['term'].'%'));
					while ($row = $res->fetch_object()) {
						$sql_where[] = "id = ?"; $prep_where[] = $row->oid;
					}
				}

				if ($params["search_iri"]) {
					$res = OIDplus::db()->query("select * from ###iri where name like ?", array('%'.$params['term'].'%'));
					while ($row = $res->fetch_object()) {
						$sql_where[] = "id = ?"; $prep_where[] = $row->oid;
					}
				}

				if (count($sql_where) == 0) $sql_where[] = '1=0';
				array_unshift($prep_where, $params['namespace'].':%');

				$res = OIDplus::db()->query("select * from ###objects where id like ? and (".implode(' or ', $sql_where).")", $prep_where);

				$count = 0;
				while ($row = $res->fetch_object()) {
					$output .= '<p><a '.OIDplus::gui()->link($row->id).'>'.htmlentities($row->id).'</a>';

					$asn1ids = array();
					$res2 = OIDplus::db()->query("select name from ###asn1id where oid = ?", array($row->id));
					while ($row2 = $res2->fetch_object()) {
						$asn1ids[] = $row2->name;
					}
					if (count($asn1ids) > 0) {
						$asn1ids = implode(', ', $asn1ids);
						$output .= ' ('.$asn1ids.')';
					}

					if (htmlentities($row->title) != '') $output .= ': <b>'.htmlentities($row->title).'</b></p>';
					$count++;
				}
				if ($count == 0) {
					$output .= '<p>'._L('Nothing found').'</p>';
				}
			}

			// Highlight exact match
			//$output = str_ireplace($params['term'], '<font color="red">'.$params['term'].'</font>', $output);
			$output = preg_replace('@('.preg_quote($params['term'],'@').')@i', '<font color="red">\\1</font>', $output); // this variant keeps the case of the search term
		}

		return $output;
	}

	public function action($actionID, $params) {

		if ($actionID == 'search') {
			// Search with JavaScript/AJAX
			$ret = $this->doSearch($params);
			return array("status" => 0, "output" => $ret);
		}

	}

	public function gui($id, &$out, &$handled) {
		if (explode('$',$id)[0] == 'oidplus:search') {
			$handled = true;

			$out['title'] = _L('Search');
			$out['icon'] = file_exists(__DIR__.'/icon_big.png') ? OIDplus::webpath(__DIR__).'icon_big.png' : '';

			$out['text'] = '';

			try {
				$params = $_POST;

				$this->prepareSearchParams($params, isset($params['search']));

				$out['text'] .= '<form id="searchForm" action="?goto=oidplus:search" method="POST">
				                 <input type="hidden" name="search" value="1">
				                 '._L('Search for').': <input type="text" id="term" name="term" value="'.htmlentities($params['term']).'"><br><br>
				                 <script>
				                 function searchNsSelect(ns) {
				                     $("#search_options_oid")[0].style.display = (ns == "oid") ? "block" : "none";
				                     $("#search_options_object")[0].style.display = (ns == "oidplus:ra") ? "none" : "block";
				                     $("#search_options_ra")[0].style.display = (ns == "oidplus:ra") ? "block" : "none";
				                 }
				                 $( document ).ready(function() {
				                     searchNsSelect($("#namespace")[0].value);
				                 });
				                 </script>
				                 '._L('Search in').': <select name="namespace" id="namespace" onchange="searchNsSelect(this.value);"><br><br>';

				foreach (OIDplus::getEnabledObjectTypes() as $ot) {
					$out['text'] .= '<option value="'.htmlentities($ot::ns()).'"'.(($params['namespace'] == $ot::ns()) ? ' selected' : '').'>'.htmlentities($ot::objectTypeTitle()).'</option>';
				}
				$out['text'] .= '<option value="oidplus:ra"'.(($params['namespace'] == 'oidplus:ra') ? ' selected' : '').'>'._L('Registration Authority').'</option>
				                 </select><br><br>
				<div id="search_options_ra">
				<!-- TODO: RA specific selection criterias -->
				</div>
				<div id="search_options_object">
				            <input type="checkbox" name="search_title" id="search_title" value="1"'.($params["search_title"] ? ' checked' : '').'> <label for="search_title">'._L('Search in field "Title"').'</label><br>
				            <input type="checkbox" name="search_description" id="search_description" value="1"'.($params["search_description"] ? ' checked' : '').'> <label for="search_description">'._L('Search in field "Description"').'</label><br>
				<div id="search_options_oid">
			            <input type="checkbox" name="search_asn1id" id="search_asn1id" value="1"'.($params["search_asn1id"] ? ' checked' : '').'> <label for="search_asn1id">'._L('Search in field "ASN.1 identifier" (only OIDs)').'</label><br>
			            <input type="checkbox" name="search_iri" id="search_iri" value="1"'.($params["search_iri"] ? ' checked' : '').'> <label for="search_iri">'._L('Search in field "Unicode label" (only OIDs)').'</label><br>
				</div>
				</div>
				 <br>

				<input type="submit" value="'._L('Search').'" onclick="return OIDplusPagePublicSearch.search_button_click()">
				</form>';

				$out['text'] .= '<div id="search_output">'; // will be filled with either AJAX or staticly (HTML form submit)
				if (isset($params['search'])) {
					// Search with NoJS/HTML
					$out['text'] .= $this->doSearch($params);
				}
				$out['text'] .= '</div>';
			} catch (Exception $e) {
				$out['text'] = _L('Error: %1',$e->getMessage());
			}
		}
	}

	public function publicSitemap(&$out) {
		$out[] = 'oidplus:search';
	}

	public function tree(&$json, $ra_email=null, $nonjs=false, $req_goto='') {
		if (file_exists(__DIR__.'/treeicon.png')) {
			$tree_icon = OIDplus::webpath(__DIR__).'treeicon.png';
		} else {
			$tree_icon = null; // default icon (folder)
		}

		$json[] = array(
			'id' => 'oidplus:search',
			'icon' => $tree_icon,
			'text' => _L('Search')
		);

		return true;
	}

	public function tree_search($request) {
		return false;
	}
}

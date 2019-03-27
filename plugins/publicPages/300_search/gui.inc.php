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

include_once __DIR__ . '/functions.inc.php';

if (explode('$',$id)[0] == 'oidplus:search') {
	$handled = true;

	$out['title'] = 'Search';
	$out['icon'] = file_exists(__DIR__.'/icon_big.png') ? 'plugins/publicPages/'.basename(__DIR__).'/icon_big.png' : '';

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

		$out['text'] .= '
			  <form id="searchForm" action="?goto=oidplus:search" method="POST">
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

			    Search in: <select name="namespace" id="namespace" onchange="searchNsSelect(this.value);"><br><br>
			';

			foreach (OIDplusObject::$registeredObjectTypes as $ot) {
				$out['text'] .= '<option value="'.htmlentities($ot::ns()).'"'.(($ns == $ot::ns()) ? ' selected' : '').'>'.htmlentities($ot::objectTypeTitle()).'</option>';
			}
			$out['text'] .= '
			<option value="oidplus:ra"'.(($ns == 'oidplus:ra') ? ' selected' : '').'>Registration Authority</option>
			</select><br><br>

				<div id="search_options_ra">
				<!-- TODO: RA specific selection criterias -->
				</div>
				<div id="search_options_object">
		            <input type="checkbox" name="search_title" id="search_title" value="1"'.(isset($_POST["search_title"]) ? ' checked' : '').'> <label for="search_title">Search in field "Title"</label><br>
		            <input type="checkbox" name="search_description" id="search_description" value="1"'.(isset($_POST["search_description"]) ? ' checked' : '').'> <label for="search_description">Search in field "Description"</label><br>
				<div id="search_options_oid">
		            <input type="checkbox" name="search_asn1id" id="search_asn1id" value="1"'.(isset($_POST["search_asn1id"]) ? ' checked' : '').'> <label for="search_asn1id">Search in field "ASN.1 identiier" (only OIDs)</label><br>
		            <input type="checkbox" name="search_iri" id="search_iri" value="1"'.(isset($_POST["search_iri"]) ? ' checked' : '').'> <label for="search_iri">Search in field "Unicode label" (only OIDs)</label><br>
				</div>
				</div>
				 <br>

			    <input type="submit" value="Search">
			  </form>';

		if (!empty($search_term)) {
			// Note: The SQL collation defines if search is case sensitive or case insensitive

			$min_length = 3; // TODO: configurable

			$search_term = trim($search_term);

			if (strlen($search_term) == 0) {
				$out['text'] .= '<p><font color="red">Error: You must enter a search term.</font></p>';
			} else if (strlen($search_term) < $min_length) {
				$out['text'] .= '<p><font color="red">Error: Search term minimum length is '.$min_length.' characters.</font></p>';
			} else {
				if ($ns == 'oidplus:ra') {
					$out['text'] .= '<h2>Search results for RA "'.htmlentities($search_term).'"</h2>';

					$where = array();
					$where[] = "email like '".OIDplus::db()->real_escape_string('%'.$search_term.'%')."'";
					$where[] = "ra_name like '".OIDplus::db()->real_escape_string('%'.$search_term.'%')."'";

					if (count($where) == 0) $where[] = '1=0';
					$res = OIDplus::db()->query("select * from ".OIDPLUS_TABLENAME_PREFIX."ra where (".implode(' or ', $where).")");

					while ($row = OIDplus::db()->fetch_object($res)) {
						// TODO: anti spam!
						$out['text'] .= '<p><a href="?goto=oidplus:rainfo$'.urlencode($row->email).'">'.htmlentities($row->email).'</a>: <b>'.htmlentities($row->ra_name).'</b></p>';
					}
				} else {
					$out['text'] .= '<h2>Search results for "'.htmlentities($search_term).'" ('.htmlentities($ns).')</h2>';

					$where = array();
					$where[] = "id like '".OIDplus::db()->real_escape_string('%'.$search_term.'%')."'"; // TODO: should we rather do findFitting(), so we can e.g. find GUIDs with different notation?
					if (isset($_POST["search_title"])) $where[] = "title like '".OIDplus::db()->real_escape_string('%'.$search_term.'%')."'";
					if (isset($_POST["search_description"])) $where[] = "description like '".OIDplus::db()->real_escape_string('%'.$search_term.'%')."'";

					if (isset($_POST["search_asn1id"])) {
						$res = OIDplus::db()->query("select * from ".OIDPLUS_TABLENAME_PREFIX."asn1id where name like '".OIDplus::db()->real_escape_string('%'.$search_term.'%')."'");
						while ($row = OIDplus::db()->fetch_object($res)) {
							$where[] = "id = '".OIDplus::db()->real_escape_string($row->oid)."'";
						}
					}

					if (isset($_POST["search_iri"])) {
						$res = OIDplus::db()->query("select * from ".OIDPLUS_TABLENAME_PREFIX."iri where name like '".OIDplus::db()->real_escape_string('%'.$search_term.'%')."'");
						while ($row = OIDplus::db()->fetch_object($res)) {
							$where[] = "id = '".OIDplus::db()->real_escape_string($row->oid)."'";
						}
					}

					if (count($where) == 0) $where[] = '1=0';
					$res = OIDplus::db()->query("select * from ".OIDPLUS_TABLENAME_PREFIX."objects where id like '".OIDplus::db()->real_escape_string($ns.':%')."' and (".implode(' or ', $where).")");

					while ($row = OIDplus::db()->fetch_object($res)) {
						$out['text'] .= '<p><a href="?goto='.urlencode($row->id).'">'.htmlentities($row->id).'</a>: <b>'.htmlentities($row->title).'</b></p>'; // TODO: also show asn1id; highlight search match?
					}
				}
			}
		}
	} catch (Exception $e) {
		$out['text'] = "Error: ".$e->getMessage();
	}
}




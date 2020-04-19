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

class OIDplusPageAdminWellKnownOIDs extends OIDplusPagePluginAdmin {

	public static function getPluginInformation() {
		$out = array();
		$out['name'] = 'Well known OIDs';
		$out['author'] = 'ViaThinkSoft';
		$out['version'] = null;
		$out['descriptionHTML'] = null;
		return $out;
	}

	public function priority() {
		return 100;
	}

	public function action(&$handled) {
		// Nothing
	}

	public function init($html=true) {
		// Nothing
	}

	public function gui($id, &$out, &$handled) {
		if ($id === 'oidplus:well_known_oids') {
			$handled = true;
			$out['title'] = 'Well known OIDs';
			$out['icon'] = file_exists(__DIR__.'/icon_big.png') ? OIDplus::webpath(__DIR__).'icon_big.png' : '';

			if (!OIDplus::authUtils()::isAdminLoggedIn()) {
				$out['icon'] = 'img/error_big.png';
				$out['text'] = '<p>You need to <a '.OIDplus::gui()->link('oidplus:login').'>log in</a> as administrator.</p>';
			} else {
				$out['text'] = '<p><abbr title="These ID names can only be edited in the database directly (Tables ###asn1id and ###iri). Usually, there is no need to do this, though.">How to edit these IDs?</abbr></p>';

				$out['text'] .= '<div class="container box"><div id="suboid_table" class="table-responsive">';
				$out['text'] .= '<table class="table table-bordered table-striped">';
				$out['text'] .= '	<tr>';
				$out['text'] .= '	     <th>OID</th>';
				$out['text'] .= '	     <th>ASN.1 identifiers (comma sep.)</th>';
				$out['text'] .= '	     <th>IRI identifiers (comma sep.)</th>';
				$out['text'] .= '	</tr>';

				$res = OIDplus::db()->query("select a.oid from (select oid from ###asn1id where well_known = '1' union select oid from ###iri where well_known = '1') a order by ".OIDplus::db()->natOrder('oid'));
				while ($row = $res->fetch_array()) {
					$asn1ids = array();
					$res2 = OIDplus::db()->query("select name, standardized from ###asn1id where oid = ?", array($row['oid']));
					while ($row2 = $res2->fetch_array()) {
						$asn1ids[] = $row2['name'].($row2['standardized'] ? ' (standardized)' : '');
					}

					$iris = array();
					$res2 = OIDplus::db()->query("select name, longarc from ###iri where oid = ?", array($row['oid']));
					while ($row2 = $res2->fetch_array()) {
						$iris[] = $row2['name'].($row2['longarc'] ? ' (long arc)' : '');
					}

					$out['text'] .= '<tr>';
					$out['text'] .= '     <td>'.htmlentities(explode(':',$row['oid'])[1]).'</td>';
					$out['text'] .= '     <td>'.htmlentities(implode(', ', $asn1ids)).'</td>';
					$out['text'] .= '     <td>'.htmlentities(implode(', ', $iris)).'</td>';
					$out['text'] .= '</tr>';
				}

				$out['text'] .= '</table>';
				$out['text'] .= '</div></div>';
			}
		}
	}

	public function tree(&$json, $ra_email=null, $nonjs=false, $req_goto='') {
		if (file_exists(__DIR__.'/treeicon.png')) {
			$tree_icon = OIDplus::webpath(__DIR__).'treeicon.png';
		} else {
			$tree_icon = null; // default icon (folder)
		}

		$json[] = array(
			'id' => 'oidplus:well_known_oids',
			'icon' => $tree_icon,
			'text' => 'Well known OIDs'
		);

		return true;
	}

	public function tree_search($request) {
		return false;
	}
}

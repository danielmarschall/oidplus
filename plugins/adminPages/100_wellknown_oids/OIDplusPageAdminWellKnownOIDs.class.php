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

class OIDplusPageAdminWellKnownOIDs extends OIDplusPagePluginAdmin {

	public function init($html=true) {
		// Nothing
	}

	public function gui($id, &$out, &$handled) {
		if ($id === 'oidplus:well_known_oids') {
			$handled = true;
			$out['title'] = _L('Well known OIDs');
			$out['icon'] = file_exists(__DIR__.'/icon_big.png') ? OIDplus::webpath(__DIR__).'icon_big.png' : '';

			if (!OIDplus::authUtils()::isAdminLoggedIn()) {
				$out['icon'] = 'img/error_big.png';
				$out['text'] = '<p>'._L('You need to <a %1>log in</a> as administrator.',OIDplus::gui()->link('oidplus:login')).'</p>';
				return;
			}

			$out['text'] = '';

			$out['text'] .= '<p>'._L('Well-known OIDs are OIDs of Registration Authorities which are assigning OIDs to customers, i.e. they are most likely to be used by OIDplus users as their root OID. Well-known OIDs have the following purposes:').'<ol>';
			$out['text'] .= '<li>'._L('When a new OIDplus user creates his root OID into OIDplus, then the ASN.1 identifiers and Unicode labels of the superior OIDs are automatically added.').'</li>';
			$out['text'] .= '<li>'._L('In the automatic oid-info.com publishing, well-known OIDs will not be transmitted (because it is unlikely that RAs of well-known OIDs will be using OIDplus in combination with automatic publishing to oid-info.com). Instead, all children inside these well-known OIDs are most likely to be yours, so these will be reported to oid-info.com instead.').'</li>';
			// $out['text'] .= '<li>'._L('In OID-WHOIS, if a user requests information about an unknown OID which is inside a well-known OID, then OID-WHOIS will output information at which place more information can be retrieved from.').'</li>';
			$out['text'] .= '</ol></p>';

			$out['text'] .= '<p><abbr title="'._L('These ID names can only be edited in the database directly (Tables ###asn1id and ###iri). Usually, there is no need to do this, though.').'">'._L('How to edit these IDs?').'</abbr></p>';

			$out['text'] .= '<div class="container box"><div id="suboid_table" class="table-responsive">';
			$out['text'] .= '<table class="table table-bordered table-striped">';
			$out['text'] .= '	<tr>';
			$out['text'] .= '	     <th>'._L('OID').'</th>';
			$out['text'] .= '	     <th>'._L('ASN.1 identifiers (comma sep.)').'</th>';
			$out['text'] .= '	     <th>'._L('IRI identifiers (comma sep.)').'</th>';
			$out['text'] .= '	</tr>';

			$res = OIDplus::db()->query("select a.oid from (select oid from ###asn1id where well_known = '1' union select oid from ###iri where well_known = '1') a order by ".OIDplus::db()->natOrder('oid'));
			while ($row = $res->fetch_array()) {
				$asn1ids = array();
				$res2 = OIDplus::db()->query("select name, standardized from ###asn1id where oid = ?", array($row['oid']));
				while ($row2 = $res2->fetch_array()) {
					$asn1ids[] = $row2['name'].($row2['standardized'] ? ' ('._L('standardized').')' : '');
				}

				$iris = array();
				$res2 = OIDplus::db()->query("select name, longarc from ###iri where oid = ?", array($row['oid']));
				while ($row2 = $res2->fetch_array()) {
					$iris[] = $row2['name'].($row2['longarc'] ? ' ('._L('long arc').')' : '');
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

	public function tree(&$json, $ra_email=null, $nonjs=false, $req_goto='') {
		if (!OIDplus::authUtils()::isAdminLoggedIn()) return false;

		if (file_exists(__DIR__.'/treeicon.png')) {
			$tree_icon = OIDplus::webpath(__DIR__).'treeicon.png';
		} else {
			$tree_icon = null; // default icon (folder)
		}

		$json[] = array(
			'id' => 'oidplus:well_known_oids',
			'icon' => $tree_icon,
			'text' => _L('Well known OIDs')
		);

		return true;
	}

	public function tree_search($request) {
		return false;
	}
}

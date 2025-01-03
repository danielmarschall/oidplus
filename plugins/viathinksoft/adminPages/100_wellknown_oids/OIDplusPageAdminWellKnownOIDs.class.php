<?php

/*
 * OIDplus 2.0
 * Copyright 2019 - 2023 Daniel Marschall, ViaThinkSoft
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

namespace ViaThinkSoft\OIDplus\Plugins\AdminPages\WellKnownOIDs;

use ViaThinkSoft\OIDplus\Core\OIDplus;
use ViaThinkSoft\OIDplus\Core\OIDplusException;
use ViaThinkSoft\OIDplus\Core\OIDplusHtmlException;
use ViaThinkSoft\OIDplus\Core\OIDplusPagePluginAdmin;

// phpcs:disable PSR1.Files.SideEffects
\defined('INSIDE_OIDPLUS') or die;
// phpcs:enable PSR1.Files.SideEffects

class OIDplusPageAdminWellKnownOIDs extends OIDplusPagePluginAdmin {

	/**
	 * @param bool $html
	 * @return void
	 */
	public function init(bool $html=true): void {
		// Nothing
	}

	/**
	 * @param string $id
	 * @param array $out
	 * @param bool $handled
	 * @return void
	 * @throws OIDplusException
	 */
	public function gui(string $id, array &$out, bool &$handled): void {
		if ($id === 'oidplus:well_known_oids') {
			$handled = true;
			$out['title'] = _L('Well known OIDs');
			$out['icon'] = file_exists(__DIR__.'/img/main_icon.png') ? OIDplus::webpath(__DIR__,OIDplus::PATH_RELATIVE).'img/main_icon.png' : '';

			if (!OIDplus::authUtils()->isAdminLoggedIn()) {
				throw new OIDplusHtmlException(_L('You need to <a %1>log in</a> as administrator.',OIDplus::gui()->link('oidplus:login$admin')), $out['title'], 401);
			}

			$out['text']  = '<p>'._L('Well-known OIDs are OIDs of Registration Authorities which are assigning OIDs to customers, i.e. they are most likely to be used by OIDplus users as their root OID. Well-known OIDs have the following purposes:').'<ol>';
			$out['text'] .= '<li>'._L('When a new OIDplus user creates his root OID into OIDplus, then the ASN.1 identifiers and Unicode labels of the superior OIDs are automatically added.').'</li>';
			if (OIDplus::getEditionInfo()['vendor'] == 'ViaThinkSoft') {
				$out['text'] .= '<li>'._L('In the automatic oid-base.com publishing, well-known OIDs will not be transmitted (because it is unlikely that RAs of well-known OIDs will be using OIDplus in combination with automatic publishing to oid-base.com). Instead, all children inside these well-known OIDs are most likely to be yours, so these will be reported to oid-base.com instead.').'</li>';
			}
			// $out['text'] .= '<li>'._L('In OID-WHOIS, if a user requests information about an unknown OID which is inside a well-known OID, then OID-WHOIS will output information at which place more information can be retrieved from.').'</li>';
			$out['text'] .= '</ol></p>';

			$out['text'] .= '<p><b>'._L('How to edit these IDs?').'</b> '._L('These ID names can only be edited in the database directly (Tables ###asn1id and ###iri). Usually, there is no need to do this, though.').'</p>';
			$out['text'] .= '<div class="container box"><div id="suboid_table" class="table-responsive">';
			$out['text'] .= '<table class="table table-bordered table-striped">';
			$out['text'] .= '<thead>';
			$out['text'] .= '	<tr>';
			$out['text'] .= '	     <th>'._L('OID').'</th>';
			$out['text'] .= '	     <th>'._L('ASN.1 identifiers (comma sep.)').'</th>';
			$out['text'] .= '	     <th>'._L('IRI identifiers (comma sep.)').'</th>';
			$out['text'] .= '	</tr>';
			$out['text'] .= '</thead>';
			$out['text'] .= '<tbody>';

			$asn1ids = array();
			$res = OIDplus::db()->query("select oid, name, standardized from ###asn1id where well_known = ?", array(true));
			while ($row = $res->fetch_array()) {
				$oid = $row['oid'];
				if (!isset($asn1ids[$oid])) $asn1ids[$oid] = array();
				$asn1ids[$oid][] = $row['name'].($row['standardized'] ? ' ('._L('standardized').')' : '');
			}

			$iris = array();
			$res = OIDplus::db()->query("select oid, name, longarc from ###iri where well_known = ?", array(true));
			while ($row = $res->fetch_array()) {
				$oid = $row['oid'];
				if (!isset($iris[$oid])) $iris[$oid] = array();
				$iris[$oid][] = $row['name'].($row['longarc'] ? ' ('._L('long arc').')' : '');
			}

			$oids = array_merge(array_keys($asn1ids), array_keys($iris));
			$oids = array_unique($oids);
			natsort($oids);

			foreach ($oids as $oid) {
				$local_asn1ids = $asn1ids[$oid] ?? array();
				$local_iris = $iris[$oid] ?? array();
				$out['text'] .= '<tr>';
				$out['text'] .= '     <td>'.htmlentities(explode(':', $oid)[1]).'</td>';
				$out['text'] .= '     <td>'.htmlentities(implode(', ', $local_asn1ids)).'</td>';
				$out['text'] .= '     <td>'.htmlentities(implode(', ', $local_iris)).'</td>';
				$out['text'] .= '</tr>';
			}

			$out['text'] .= '</tbody>';
			$out['text'] .= '</table>';
			$out['text'] .= '</div></div>';
		}
	}

	/**
	 * @param array $json
	 * @param string|null $ra_email
	 * @param bool $nonjs
	 * @param string $req_goto
	 * @return bool
	 * @throws OIDplusException
	 */
	public function tree(array &$json, ?string $ra_email=null, bool $nonjs=false, string $req_goto=''): bool {
		if (!OIDplus::authUtils()->isAdminLoggedIn()) return false;

		if (file_exists(__DIR__.'/img/main_icon16.png')) {
			$tree_icon = OIDplus::webpath(__DIR__,OIDplus::PATH_RELATIVE).'img/main_icon16.png';
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

	/**
	 * @param string $request
	 * @return array|false
	 */
	public function tree_search(string $request) {
		return false;
	}
}

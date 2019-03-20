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

if ($id === 'oidplus:well_known_oids') {
	$handled = true;
	$out['title'] = 'Well known OIDs';
	$out['icon'] = file_exists(__DIR__.'/icon_big.png') ? 'plugins/adminPages/'.basename(__DIR__).'/icon_big.png' : '';

	if (!OIDplus::authUtils()::isAdminLoggedIn()) {
		$out['text'] .= '<p>You need to <a href="?goto=oidplus:login">log in</a> as administrator.</p>';
	} else {

		$out['text'] = '<p><abbr title="These ID names can only be edited in the database directly (Tables '.OIDPLUS_TABLENAME_PREFIX.'asn1id and '.OIDPLUS_TABLENAME_PREFIX.'iri). Usually, there is no need to do this, though.">How to edit these IDs?</abbr></p>';

		$out['text'] .= '<div class="container box"><div id="suboid_table" class="table-responsive">';
		$out['text'] .= '<table class="table table-bordered table-striped">';
		$out['text'] .= '	<tr>';
		$out['text'] .= '	     <th>OID</th>';
		$out['text'] .= '	     <th>ASN.1 identifiers (comma sep.)</th>';
		$out['text'] .= '	     <th>IRI identifiers (comma sep.)</th>';
		$out['text'] .= '	</tr>';

		$res = OIDplus::db()->query("select oid from ".OIDPLUS_TABLENAME_PREFIX."asn1id where well_known = 1 union select oid from ".OIDPLUS_TABLENAME_PREFIX."iri where well_known = 1 order by ".OIDplus::db()->natOrder('oid'));
		while ($row = OIDplus::db()->fetch_array($res)) {

			$asn1ids = array();
			$res2 = OIDplus::db()->query("select name from ".OIDPLUS_TABLENAME_PREFIX."asn1id where oid = '".$row['oid']."'");
			while ($row2 = OIDplus::db()->fetch_array($res2)) {
				$asn1ids[] = $row2['name'];
			}

			$iris = array();
			$res2 = OIDplus::db()->query("select name, longarc from ".OIDPLUS_TABLENAME_PREFIX."iri where oid = '".$row['oid']."'");
			while ($row2 = OIDplus::db()->fetch_array($res2)) {
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

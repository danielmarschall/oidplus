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

namespace ViaThinkSoft\OIDplus\Plugins\PublicPages\RaInfo;

use ViaThinkSoft\OIDplus\Core\OIDplus;
use ViaThinkSoft\OIDplus\Core\OIDplusConfigInitializationException;
use ViaThinkSoft\OIDplus\Core\OIDplusException;
use ViaThinkSoft\OIDplus\Core\OIDplusObject;
use ViaThinkSoft\OIDplus\Core\OIDplusPagePluginPublic;

// phpcs:disable PSR1.Files.SideEffects
\defined('INSIDE_OIDPLUS') or die;
// phpcs:enable PSR1.Files.SideEffects

class OIDplusPagePublicRaInfo extends OIDplusPagePluginPublic {

	/**
	 * @param bool $html
	 * @return void
	 */
	public function init(bool $html=true): void {
	}

	/**
	 * @param string $id
	 * @param array $out
	 * @param bool $handled
	 * @return void
	 * @throws OIDplusConfigInitializationException
	 * @throws OIDplusException
	 */
	public function gui(string $id, array &$out, bool &$handled): void {
		if (explode('$',$id)[0] == 'oidplus:rainfo') {
			$handled = true;

			$antispam_email = explode('$',$id.'$')[1];
			$ra_email = str_replace('&', '@', $antispam_email);

			$out['icon'] = OIDplus::webpath(__DIR__,OIDplus::PATH_RELATIVE).'img/rainfo_icon.png';

			if (OIDplus::authUtils()->isAdminLoggedIn()) {
				$listRaPlugin = OIDplus::getPluginByOid('1.3.6.1.4.1.37476.2.5.2.4.3.500'); // OIDplusPageAdminListRAs
				if (!is_null($listRaPlugin)) {
					$out['text'] = '<p><a '.OIDplus::gui()->link('oidplus:list_ra').'><img src="img/arrow_back.png" width="16" alt="'._L('Go back').'"> '._L('Go back to RA listing').'</a></p>';
				}
			}

			if (empty($ra_email)) {
				$out['title'] = _L('Object roots without RA');
				$out['text'] .= '<p>'._L('Following object roots have an undefined Registration Authority').':</p>';
			} else {
				$res = OIDplus::db()->query("select ra_name from ###ra where email = ?", array($ra_email));
				$out['title'] = '';
				if ($row = $res->fetch_array()) {
					$out['title'] = $row['ra_name'];
				}
				if (empty($out['title'])) {
					$out['title'] = $antispam_email;
				}
				$out['text'] .= $this->showRAInfo($ra_email, null);
				$out['text'] .= '<br><br>';
			}

			$ra_roots = OIDplusObject::getRaRoots($ra_email);
			if (count($ra_roots) == 0) {
				if (empty($ra_email)) {
					$out['text'] .= '<p><i>'._L('None').'</i></p>';
				} else {
					$out['text'] .= '<p><i>'._L('This RA has no objects.').'</i></p>';
				}
			} else {
				foreach ($ra_roots as $loc_root) {
					$ico = $loc_root->getIcon();
					$icon = !is_null($ico) ? $ico : OIDplus::webpath(__DIR__,OIDplus::PATH_RELATIVE).'img/link_icon16.png';
					$out['text'] .= '<p><a '.OIDplus::gui()->link($loc_root->nodeId()).'><img src="'.$icon.'"> '._L('Jump to RA root %1',$loc_root->objectTypeTitleShort().' '.$loc_root->crudShowId(OIDplusObject::parse($loc_root::root()))).'</a></p>';
				}
			}

			if (!empty($ra_email)) {
				$res = OIDplus::db()->query("select * from ###ra where email = ?", array($ra_email));
				if ($res->any()) {
					if (OIDplus::authUtils()->isRALoggedIn($ra_email) || OIDplus::authUtils()->isAdminLoggedIn()) {
						$editContactDataPlugin = OIDplus::getPluginByOid('1.3.6.1.4.1.37476.2.5.2.4.2.100'); // OIDplusPageRaEditContactData
						if (!is_null($editContactDataPlugin)) {
							$out['text'] .= '<p><a '.OIDplus::gui()->link('oidplus:edit_ra$'.$ra_email).'>'._L('Edit contact data').'</a></p>';
						}

						$editContactDataPlugin = OIDplus::getPluginByOid('1.3.6.1.4.1.37476.2.5.2.4.2.200'); // OIDplusPageRaLogEvents
						if (!is_null($editContactDataPlugin)) {
							$out['text'] .= '<p><a '.OIDplus::gui()->link('oidplus:ra_log$'.$ra_email).'>'._L('Show log entries').'</a></p>';
						}
					}

					if (OIDplus::authUtils()->isAdminLoggedIn()) {
						$raBasePlugin = OIDplus::getPluginByOid('1.3.6.1.4.1.37476.2.5.2.4.1.1'); // OIDplusPagePublicRaBaseUtils
						if (!is_null($raBasePlugin)) {
							$listRaPlugin = OIDplus::getPluginByOid("1.3.6.1.4.1.37476.2.5.2.4.3.500"); // OIDplusPageAdminListRAs
							if (!is_null($listRaPlugin)) {
								$delete_goback = 'oidplus:list_ra';
							} else {
								$delete_goback = 'oidplus:system';
							}
							$out['text'] .= '<p><a href="#" onclick="return OIDplusPagePublicRaBaseUtils.deleteRa('.js_escape($ra_email).','.js_escape($delete_goback).')">'._L('Delete this RA').'</a></p>';
						}

						$changePasswordPlugin = OIDplus::getPluginByOid('1.3.6.1.4.1.37476.2.5.2.4.2.101'); // OIDplusPageRaChangePassword
						if (!is_null($changePasswordPlugin)) {
							$out['text'] .= '<p><a '.OIDplus::gui()->link('oidplus:change_ra_password$'.$ra_email).'>'._L('Change password of this RA').'</a>';
						}
					}
				}

				if (OIDplus::authUtils()->isRALoggedIn($ra_email) || OIDplus::authUtils()->isAdminLoggedIn()) {
					$res = OIDplus::db()->query("select lo.unix_ts, lo.addr, lo.event from ###log lo ".
					                            "left join ###log_user lu on lu.log_id = lo.id ".
					                            "where lu.username = ? " .
					                            "order by lo.unix_ts desc", array($ra_email));
					$out['text'] .= '<h2>'._L('Log messages for RA %1',htmlentities($ra_email)).'</h2>';
					if ($res->any()) {
						$out['text'] .= '<pre>';
						while ($row = $res->fetch_array()) {
							$addr = empty($row['addr']) ? _L('no address') : $row['addr'];

							$out['text'] .= date('Y-m-d H:i:s', (int)$row['unix_ts']) . ': ' . htmlentities($row["event"])." (" . htmlentities($addr) . ")\n";
						}
						$out['text'] .= '</pre>';

						// TODO: List logs in a table instead of a <pre> text
						// TODO: Load only X events and then re-load new events via AJAX when the user scrolls down
					} else {
						$out['text'] .= '<p>'._L('Currently there are no log entries').'</p>';
					}
				}
			}
		}
	}

	/**
	 * @param array $out
	 * @return void
	 * @throws OIDplusConfigInitializationException
	 * @throws OIDplusException
	 */
	public function publicSitemap(array &$out): void {
		if (OIDplus::db()->getSlang()->id() == 'mysql') {
			$res = OIDplus::db()->query("select distinct BINARY(email) as distinct_email from ###ra"); // "binary" because we want to ensure that 'distinct' is case sensitive
		} else {
			$res = OIDplus::db()->query("select distinct email as distinct_email from ###ra"); // distinct in PGSQL is always case sensitive
		}
		while ($row = $res->fetch_array()) {
			$out[] = 'oidplus:rainfo$'.$row['distinct_email'];
		}
	}

	/**
	 * @param array $json
	 * @param string|null $ra_email
	 * @param bool $nonjs
	 * @param string $req_goto
	 * @return bool
	 */
	public function tree(array &$json, ?string $ra_email=null, bool $nonjs=false, string $req_goto=''): bool {
		return false;
	}

	/**
	 * @param string|null $email
	 * @param OIDplusObject|null $oid
	 * @return string
	 * @throws OIDplusException
	 */
	public static function showRAInfo(?string $email, ?OIDplusObject $oid=null): string {
		$out = '';

		if (empty($email)) {
			return '<p>'._L('The superior RA did not define a RA for this %1.',$oid->objectTypeTitle()).'</p>';
		}

		$res = OIDplus::db()->query("select * from ###ra where email = ?", array($email));
		if (!$res->any()) {
			$out = '<p>'._L('The RA %1 is not registered in the database.','<a href="mailto:'.htmlentities($email).'">'.htmlentities($email).'</a>').'</p>';

			if (OIDplus::authUtils()->isAdminLoggedIn()) {
				$createRAPlugin = OIDplus::getPluginByOid('1.3.6.1.4.1.37476.2.5.2.4.3.130'); // OIDplusPageAdminCreateRa
				if (!is_null($createRAPlugin)) {
					$out .= '<p><a class="btn btn-success" '.OIDplus::gui()->link('oidplus:create_ra$'.$email).'>'._L('Create RA manually').'</a></p>';
				}
				$out .= '<p><a class="btn btn-success" '.OIDplus::gui()->link('oidplus:invite_ra$'.$email).'>'._L('Invite RA to join OIDplus').'</a></p>';
			}  else if (!is_null($oid) && $oid->userHasParentalWriteRights()) {
				$out .= '<p><a class="btn btn-success" '.OIDplus::gui()->link('oidplus:invite_ra$'.$email.'$'.$oid->nodeId(true)).'>'._L('Invite RA to join OIDplus').'</a></p>';
			}

		} else {
			$row = $res->fetch_array();
			$out = '<b>'.htmlentities($row['ra_name']??'').'</b><br>'; // TODO: if you are not already at the page "oidplus:rainfo", then link to it now
			$out .= _L('E-Mail').': <a href="mailto:'.htmlentities($email).'">'.htmlentities($email).'</a><br>';
			if (trim($row['personal_name']??'') !== '') $out .= htmlentities($row['personal_name']).'<br>';
			if (trim($row['organization']??'') !== '') $out .= htmlentities($row['organization']).'<br>';
			if (trim($row['office']??'') !== '') $out .= htmlentities($row['office']).'<br>';
			if ($row['privacy']) {
				// TODO: Only show the message if the user has a street, address, etc.
				// TODO: But the admin and the own user should see it (with a note that the data is not visible to the public)
				$out .= '<p>'._L('The RA does not want to publish their personal information.').'</p>';
			} else {
				if (trim($row['street']??'') !== '') $out .= htmlentities($row['street']).'<br>';
				if (trim($row['zip_town']??'') !== '') $out .= htmlentities($row['zip_town']).'<br>';
				if (trim($row['country']??'') !== '') $out .= htmlentities($row['country']).'<br>';
				$out .= '<br>';
				if (trim($row['phone']??'') !== '') $out .= _L('Phone: %1',htmlentities($row['phone'])).'<br>';
				if (trim($row['fax']??'') !== '') $out .= _L('Fax: %1',htmlentities($row['fax'])).'<br>';
				if (trim($row['mobile']??'') !== '') $out .= _L('Mobile: %1',htmlentities($row['mobile'])).'<br>';
				$out .= '<br>';
			}
		}

		return trim_br($out);
	}

	/**
	 * @param string $request
	 * @return array|false
	 */
	public function tree_search(string $request) {
		return false;
	}
}

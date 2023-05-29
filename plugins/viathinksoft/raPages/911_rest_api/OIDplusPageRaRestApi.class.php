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

// ATTENTION: If you change something, please make sure that the changes
//            are synchronous with OIDplusPageAdminRestApi

namespace ViaThinkSoft\OIDplus;

// phpcs:disable PSR1.Files.SideEffects
\defined('INSIDE_OIDPLUS') or die;
// phpcs:enable PSR1.Files.SideEffects

class OIDplusPageRaRestApi extends OIDplusPagePluginRa {

	/**
	 * @param array $params
	 * @return array
	 * @throws OIDplusException
	 */
	private function action_Blacklist(array $params): array {
		if (!OIDplus::baseConfig()->getValue('JWT_ALLOW_REST_USER', true)) {
			throw new OIDplusException(_L('The administrator has disabled this feature. (Base configuration setting %1).','JWT_ALLOW_REST_USER'));
		}

		_CheckParamExists($params, 'user');
		$ra_email = $params['user'];

		if (!OIDplus::authUtils()->isRaLoggedIn($ra_email) && !OIDplus::authUtils()->isAdminLoggedIn()) {
			throw new OIDplusHtmlException(_L('You need to <a %1>log in</a> as the requested RA %2 or as admin.',OIDplus::gui()->link('oidplus:login$ra$'.$ra_email),'<b>'.htmlentities($ra_email).'</b>'), null, 401);
		}

		$gen = OIDplusAuthContentStoreJWT::JWT_GENERATOR_REST;
		$sub = $ra_email;

		OIDplusAuthContentStoreJWT::jwtBlacklist($gen, $sub);

		return array("status" => 0);
	}

	/**
	 * @param string $actionID
	 * @param array $params
	 * @return array
	 * @throws OIDplusException
	 */
	public function action(string $actionID, array $params): array {
		if ($actionID == 'blacklistJWT') {
			return $this->action_Blacklist($params);
		} else {
			return parent::action($actionID, $params);
		}
	}

	/**
	 * @param string $id
	 * @param array $out
	 * @param bool $handled
	 * @return void
	 * @throws OIDplusException
	 */
	public function gui(string $id, array &$out, bool &$handled) {
		$parts = explode('$',$id,3);
		$ra_email = $parts[1] ?? '';
		$subpage = $parts[2] ?? '';

		if (empty($ra_email)) return;

		if ($parts[0] == 'oidplus:rest_api_information_ra') {
			$handled = true;

			if (str_starts_with($subpage, 'endpoints:')) {
				// Note: This page can be accessed WITHOUT login!
				$plugin = OIDplus::getPluginByOid(explode(':',$subpage)[1]);
				if (!$plugin || !($plugin instanceof INTF_OID_1_3_6_1_4_1_37476_2_5_2_3_9)) throw new OIDplusException(_L("No endpoints for this plugin found"), null, 404);
				$out['title'] = _L('REST API').' - '.$plugin->getManifest()->getName() . ' ' . _L('Endpoints');
				$out['icon'] = file_exists(__DIR__.'/img/endpoints_icon.png') ? OIDplus::webpath(__DIR__,OIDplus::PATH_RELATIVE).'img/endpoints_icon.png' : '';
				$out['text'] = '';
				if (OIDplus::authUtils()->isRaLoggedIn($ra_email) || OIDplus::authUtils()->isAdminLoggedIn()) {
					$out['text'] .= '<p><a '.OIDplus::gui()->link('oidplus:rest_api_information_ra$'.$ra_email).'><img src="img/arrow_back.png" width="16" alt="'._L('Go back').'"> '._L('Go back').'</a></p>';
				}
				$out['text'] .= $plugin->restApiInfo('html');
			} else {
				$out['title'] = _L('REST API');
				$out['icon'] = file_exists(__DIR__.'/img/main_icon.png') ? OIDplus::webpath(__DIR__,OIDplus::PATH_RELATIVE).'img/main_icon.png' : '';

				if (!OIDplus::authUtils()->isRaLoggedIn($ra_email) && !OIDplus::authUtils()->isAdminLoggedIn()) {
					throw new OIDplusHtmlException(_L('You need to <a %1>log in</a> as the requested RA %2 or as admin.',OIDplus::gui()->link('oidplus:login$ra$'.$ra_email),'<b>'.htmlentities($ra_email).'</b>'), $out['title'], 401);
				}

				if (!OIDplus::baseConfig()->getValue('JWT_ALLOW_REST_USER', true)) {
					throw new OIDplusException(_L('The administrator has disabled this feature. (Base configuration setting %1).','JWT_ALLOW_REST_USER'), $out['title']);
				}

				if (is_null(OIDplus::getPluginByOid("1.3.6.1.4.1.37476.2.5.2.4.1.2"))) { // OIDplusPagePublicRestApi
					throw new OIDplusException(_L('The administrator has disabled this feature. (Plugin %1).','OIDplusPagePublicRestApi'), $out['title']);
				}

				$gen = OIDplusAuthContentStoreJWT::JWT_GENERATOR_REST;
				$sub = $ra_email;

				$authSimulation = new OIDplusAuthContentStoreJWT();
				$authSimulation->raLogin($ra_email);
				$authSimulation->setValue('oidplus_generator', $gen);
				$token = $authSimulation->getJWTToken();

				$out['text'] .= '<p>'._L('You can make automated calls to your OIDplus account by calling an REST API.').'</p>';
				$out['text'] .= '<h2>'._L('Endpoints').'</h2>';
				$endpoints = '';
				foreach (OIDplus::getAllPlugins() as $plugin) {
					if ($plugin instanceof INTF_OID_1_3_6_1_4_1_37476_2_5_2_3_9) {
						$link = 'oidplus:rest_api_information_ra$'.$ra_email.'$endpoints:'.$plugin->getManifest()->getOid();
						$endpoints .= '<li><a '.OIDplus::gui()->link($link).'>'.htmlentities($plugin->getManifest()->getName()).'</a></li>';
					}
				}
				if ($endpoints) {
					$out['text'] .= '<p>'._L('The following installed plugins are offering REST endpoints:').'</p>';
					$out['text'] .= '<p><ul>'.$endpoints.'</ul></p>';
				} else {
					$out['text'] .= '<p>'._L('No installed plugin offers a REST functionality').'</p>';
				}
				$out['text'] .= '<h2>'._L('Authentication').'</h2>';
				$out['text'] .= '<p>'._L('The authentication is done via the following HTTP header:').'</p>';
				$out['text'] .= '<p><pre id="oidplus_auth_jwt">';
				$out['text'] .= 'Authentication: Bearer '.htmlentities($token)."\n";
				$out['text'] .= '</pre></p>';
				$out['text'] .= '<p><input type="button" value="'._L('Copy to clipboard').'" onClick="copyToClipboard(oidplus_auth_jwt)"></p>';
				$out['text'] .= '<p>'._L('Please keep this information confidential!').'</p>';
				$out['text'] .= '<p>'._L('The JWT-token (secret!) will automatically perform a one-time-login to fulfill the request. The other fields are the normal fields which are called during the usual operation of OIDplus.').'</p>';

				$out['text'] .= '<h2>'._L('Blacklisted tokens').'</h2>';
				$bl_time = OIDplusAuthContentStoreJWT::jwtGetBlacklistTime($gen, $sub);
				if ($bl_time == 0) {
					$out['text'] .= '<p>'._L('None of the previously generated JWT tokens have been blacklisted.').'</p>';
				} else {
					$out['text'] .= '<p>'._L('All tokens generated before %1 have been blacklisted.',date('d F Y, H:i:s',$bl_time+1)).'</p>';
				}
				$out['text'] .= '<button type="button" name="btn_blacklist_jwt" id="btn_blacklist_jwt" class="btn btn-danger btn-xs" onclick="OIDplusPageRaRestApi.blacklistJWT('.js_escape($ra_email).')">'._L('Blacklist all previously generated tokens').'</button>';
			}
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
	public function tree(array &$json, string $ra_email=null, bool $nonjs=false, string $req_goto=''): bool {
		if (!$ra_email) return false;
		if (!OIDplus::authUtils()->isRaLoggedIn($ra_email) && !OIDplus::authUtils()->isAdminLoggedIn()) return false;

		if (file_exists(__DIR__.'/img/main_icon16.png')) {
			$tree_icon = OIDplus::webpath(__DIR__,OIDplus::PATH_RELATIVE).'img/main_icon16.png';
		} else {
			$tree_icon = null; // default icon (folder)
		}

		if (file_exists(__DIR__.'/img/endpoints_icon16.png')) {
			$tree_icon_endpoints = OIDplus::webpath(__DIR__,OIDplus::PATH_RELATIVE).'img/endpoints_icon16.png';
		} else {
			$tree_icon_endpoints = null; // default icon (folder)
		}

		$submenu = array();
		foreach (OIDplus::getAllPlugins() as $plugin) {
			if ($plugin instanceof INTF_OID_1_3_6_1_4_1_37476_2_5_2_3_9) {
				$submenu[] = [
					'id' => 'oidplus:rest_api_information_ra$'.$ra_email.'$endpoints:'.$plugin->getManifest()->getOid(),
					'icon' => $tree_icon_endpoints,
					'text' => $plugin->getManifest()->getName() . ' ' . _L('Endpoints')
				];
			}
		}

		$json[] = array(
			'id' => 'oidplus:rest_api_information_ra$'.$ra_email,
			'icon' => $tree_icon,
			'text' => _L('REST API'),
			'children' => $submenu
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

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

class OIDplusPagePublicLoginLdap extends OIDplusPagePluginPublic {

	protected function ldapLogin($username, $password) {
		$cfg_ldap_server      = OIDplus::baseConfig()->getValue('LDAP_SERVER');
		$cfg_ldap_port        = OIDplus::baseConfig()->getValue('LDAP_PORT', 389);
		$cfg_ldap_base_dn     = OIDplus::baseConfig()->getValue('LDAP_BASE_DN');

		// Connect to the server
		if (!empty($cfg_ldap_port)) {
			if (!($ldapconn = @ldap_connect($cfg_ldap_server, $cfg_ldap_port))) throw new OIDplusException(_L('Cannot connect to LDAP server'));
		} else {
			if (!($ldapconn = @ldap_connect($cfg_ldap_server))) throw new OIDplusException(_L('Cannot connect to LDAP server'));
		}
		ldap_set_option($ldapconn, LDAP_OPT_PROTOCOL_VERSION, 3);
		ldap_set_option($ldapconn, LDAP_OPT_REFERRALS, 0);

		if (!strstr($username,'@')) throw new OIDplusException('Please use the username schema "username@domainname.local" (userPrincipalName).');

		// Login as the new user in order to check the credentials
		if ($ldapbind = @ldap_bind($ldapconn, $username, $password)) {

			// Search the user using the email address

			$cfg_ldap_user_filter = "(&(objectClass=user)(objectCategory=person)(userPrincipalName=".ldap_escape($username, '', LDAP_ESCAPE_FILTER)."))";

			if (!($result = @ldap_search($ldapconn,$cfg_ldap_base_dn, $cfg_ldap_user_filter))) throw new OIDplusException(_L('Error in search query: %1', ldap_error($ldapconn)));
			$data = ldap_get_entries($ldapconn, $result);
			$ldap_userinfo = array();

			for ($i=0; $i<$data['count']; $i++) {
				foreach ($data[$i] as $x => $y) {
					if (is_int($x)) continue;
					if (!is_array($y)) continue;
					$ldap_userinfo[$x] = $y[0];
				}
			}

			//ldap_unbind($ldapconn); // commented out because ldap_unbind() kills the link descriptor
			ldap_close($ldapconn);

			// empty($ldap_userinfo) can happen if the user did not log-in using their correct userPrincipalName (e.g. "username@domainname" instead of "username@domainname.local")
			return empty($ldap_userinfo) ? false : $ldap_userinfo;
		} else {
			return false;
		}
	}

	private function registerRA($ra, $ldap_userinfo) {
		$email = $ra->raEmail();

		$ra->register_ra(null); // create a user account without password

		/*
		OIDplus DB Field          ActiveDirectory field
		------------------------------------------------
		ra_name                   cn
		personal_name             displayname (or: givenname + " " + sn)
		organization              company
		office                    physicaldeliveryofficename or department
		street                    streetaddress
		zip_town                  postalcode + " " + l
		country                   co (human-readable) or c (ISO country code)
		phone                     telephonenumber or homephone
		mobile                    mobile
		fax                       facsimiletelephonenumber
		(none)                    wwwhomepage
		*/

		if (!isset($ldap_userinfo['cn']))                         $ldap_userinfo['cn'] = '';
		if (!isset($ldap_userinfo['displayname']))                $ldap_userinfo['displayname'] = '';
		if (!isset($ldap_userinfo['givenname']))                  $ldap_userinfo['givenname'] = '';
		if (!isset($ldap_userinfo['sn']))                         $ldap_userinfo['sn'] = '';
		if (!isset($ldap_userinfo['company']))                    $ldap_userinfo['company'] = '';
		if (!isset($ldap_userinfo['physicaldeliveryofficename'])) $ldap_userinfo['physicaldeliveryofficename'] = '';
		if (!isset($ldap_userinfo['department']))                 $ldap_userinfo['department'] = '';
		if (!isset($ldap_userinfo['streetaddress']))              $ldap_userinfo['streetaddress'] = '';
		if (!isset($ldap_userinfo['postalcode']))                 $ldap_userinfo['postalcode'] = '';
		if (!isset($ldap_userinfo['l']))                          $ldap_userinfo['l'] = '';
		if (!isset($ldap_userinfo['co']))                         $ldap_userinfo['co'] = '';
		if (!isset($ldap_userinfo['c']))                          $ldap_userinfo['c'] = '';
		if (!isset($ldap_userinfo['telephonenumber']))            $ldap_userinfo['telephonenumber'] = '';
		if (!isset($ldap_userinfo['homephone']))                  $ldap_userinfo['homephone'] = '';
		if (!isset($ldap_userinfo['mobile']))                     $ldap_userinfo['mobile'] = '';
		if (!isset($ldap_userinfo['facsimiletelephonenumber']))   $ldap_userinfo['facsimiletelephonenumber'] = '';
		//if (!isset($ldap_userinfo['wwwhomepage']))                $ldap_userinfo['wwwhomepage'] = '';
		if (!isset($ldap_userinfo['admincount']))                 $ldap_userinfo['admincount'] = '0';

		//file_put_contents('d:/ldap_info.txt',print_r($ldap_userinfo,true));


		$opuserdata = array();
		$opuserdata['ra_name'] = $ldap_userinfo['cn'];
		if (!empty($ldap_userinfo['displayname'])) {
			$opuserdata['personal_name'] = $ldap_userinfo['displayname'];
		} else {
			$opuserdata['personal_name'] = trim($ldap_userinfo['givenname'].' '.$ldap_userinfo['sn']);
		}
		$opuserdata['organization'] = $ldap_userinfo['company'];
		if (!empty($ldap_userinfo['physicaldeliveryofficename'])) {
			$opuserdata['office'] = $ldap_userinfo['physicaldeliveryofficename'];
		} else {
			$opuserdata['office'] = $ldap_userinfo['department'];
		}
		$opuserdata['street'] = $ldap_userinfo['streetaddress'];
		$opuserdata['zip_town'] = trim($ldap_userinfo['postalcode'].' '.$ldap_userinfo['l']);
		$opuserdata['country'] = $ldap_userinfo['co']; // ISO country code: $ldap_userinfo['c']
		$opuserdata['phone'] = $ldap_userinfo['telephonenumber']; // homephone for private phone number
		$opuserdata['mobile'] = $ldap_userinfo['mobile'];
		$opuserdata['fax'] = $ldap_userinfo['facsimiletelephonenumber'];

		foreach ($opuserdata as $dbfield => $val) {
			if (!empty($val)) {
				OIDplus::db()->query("update ###ra set ".$dbfield." = ? where email = ?", array($val, $email));
			}
		}
	}

	private function doLoginRA($remember_me, $email, $ldap_userinfo) {
		$ra = new OIDplusRA($email);
		if (!$ra->existing()) {
			$this->registerRA($ra, $ldap_userinfo);
			OIDplus::logger()->log("[INFO]RA($email)!", "RA '$email' was created because of successful LDAP login");
		}

		OIDplus::authUtils()->raLoginEx($email, $remember_me, 'LDAP');

		OIDplus::db()->query("UPDATE ###ra set last_login = ".OIDplus::db()->sqlDate()." where email = ?", array($email));
	}

	public function action($actionID, $params) {
		if ($actionID == 'ra_login_ldap') {
			if (!OIDplus::baseConfig()->getValue('LDAP_ENABLED', false)) {
				throw new OIDplusException(_L('LDAP authentication is disabled on this system.'));
			}

			if (!function_exists('ldap_connect')) throw new OIDplusConfigInitializationException(_L('PHP extension "%1" not installed','LDAP'));

			if (OIDplus::baseConfig()->getValue('RECAPTCHA_ENABLED', false)) {
				$secret=OIDplus::baseConfig()->getValue('RECAPTCHA_PRIVATE', '');
				_CheckParamExists($params, 'captcha');
				$response=$params["captcha"];
				$verify=file_get_contents("https://www.google.com/recaptcha/api/siteverify?secret={$secret}&response={$response}");
				$captcha_success=json_decode($verify);
				if ($captcha_success->success==false) {
					throw new OIDplusException(_L('CAPTCHA not successfully verified'));
				}
			}

			_CheckParamExists($params, 'email');
			_CheckParamExists($params, 'password');

			$email = $params['email'];
			$password = $params['password'];

			if (empty($email)) {
				throw new OIDplusException(_L('Please enter a valid username'));
			}

			if (!($ldap_userinfo = $this->ldapLogin($email, $password))) {
				if (OIDplus::config()->getValue('log_failed_ra_logins', false)) {
					OIDplus::logger()->log("[WARN]A!", "Failed login to RA account '$email' using LDAP");
				}
				throw new OIDplusException(_L('Wrong password or user not registered'));
			}

			if (!OIDplus::baseConfig()->getValue('LDAP_AUTHENTICATE_UPN',true) &&
			    !OIDplus::baseConfig()->getValue('LDAP_AUTHENTICATE_EMAIL',false)) {
			    throw new OIDplusException(_L('Error in base config: %1 and %2 cannot be both disabled','LDAP_AUTHENTICATE_UPN','LDAP_AUTHENTICATE_EMAIL'));
			}

			if (OIDplus::baseConfig()->getValue('LDAP_AUTHENTICATE_UPN',true)) {
				$remember_me = isset($params['remember_me']) && ($params['remember_me']);
				$this->doLoginRA($remember_me, $email, $ldap_userinfo);
			}

			if (OIDplus::baseConfig()->getValue('LDAP_AUTHENTICATE_EMAIL',false)) {
				if (isset($ldap_userinfo['mail']) && (!empty($ldap_userinfo['mail']))) {
					$remember_me = isset($params['remember_me']) && ($params['remember_me']);
					$this->doLoginRA($remember_me, $ldap_userinfo['mail'], $ldap_userinfo);
				}
			}

			if (OIDplus::baseConfig()->getValue('LDAP_ADMIN_IS_OIDPLUS_ADMIN',false)) {
				if ($ldap_userinfo['admincount'] == 1) {
					$remember_me = isset($params['remember_me']) && ($params['remember_me']);
					OIDplus::authUtils()->adminLoginEx($remember_me, 'LDAP login (admincount)');
				}
			}

			return array("status" => 0);
		} else {
			throw new OIDplusException(_L('Unknown action ID'));
		}
	}

	public function init($html=true) {
		// Nothing
	}

	public function gui($id, &$out, &$handled) {
		if ($id === 'oidplus:login_ldap') {
			$handled = true;
			$out['title'] = _L('Login using LDAP / ActiveDirectory');
			$out['icon']  = OIDplus::webpath(__DIR__).'icon_big.png';

			if (!OIDplus::baseConfig()->getValue('LDAP_ENABLED', false)) {
				$out['icon'] = 'img/error_big.png';
				$out['text'] = _L('LDAP authentication is disabled on this system.');
				return;
			}

			if (!function_exists('ldap_connect')) {
				$out['icon'] = 'img/error_big.png';
				$out['text'] = _L('PHP extension "%1" not installed','LDAP');
				return;
			}

			$out['text'] = '';

			$out['text'] .= '<noscript>';
			$out['text'] .= '<p>'._L('You need to enable JavaScript to use the login area.').'</p>';
			$out['text'] .= '</noscript>';

			$out['text'] .= '<div id="loginLdapArea" style="visibility: hidden">';
			$out['text'] .= (OIDplus::baseConfig()->getValue('RECAPTCHA_ENABLED', false) ?
			                '<p>'._L('Before logging in, please solve the following CAPTCHA').'</p>'.
			                '<div id="g-recaptcha" class="g-recaptcha" data-sitekey="'.OIDplus::baseConfig()->getValue('RECAPTCHA_PUBLIC', '').'"></div>'.
			                '<script> grecaptcha.render($("#g-recaptcha")[0], { "sitekey" : "'.OIDplus::baseConfig()->getValue('RECAPTCHA_PUBLIC', '').'" }); </script>' : '');
			$out['text'] .= '<br>';

			$out['text'] .= '<p><a '.OIDplus::gui()->link('oidplus:login').'><img src="img/arrow_back.png" width="16" alt="'._L('Go back').'"> '._L('Regular login method').'</a></p>';

			$out['text'] .= '<h2>'._L('Login as RA').'</h2>';

			$login_list = OIDplus::authUtils()->loggedInRaList();
			if (count($login_list) > 0) {
				foreach ($login_list as $x) {
					$out['text'] .= '<p>'._L('You are logged in as %1','<b>'.$x->raEmail().'</b>').' (<a href="#" onclick="return OIDplusPagePublicLogin.raLogout('.js_escape($x->raEmail()).');">'._L('Logout').'</a>)</p>';
				}
				$out['text'] .= '<p>'._L('If you have more accounts, you can log in with another account here.').'</p>';
			} else {
				$out['text'] .= '<p>'._L('Enter your domain username (e.g. <b>username@contoso.local</b>) and your password to log in as Registration Authority.').'</p>';
			}
			$out['text'] .= '<form onsubmit="return OIDplusPagePublicLoginLDAP.raLoginLdapOnSubmit(this);">';
			$out['text'] .= '<div><label class="padding_label">'._L('Username').':</label><input type="text" name="email" value="" id="raLoginLdapEMail"></div>';
			$out['text'] .= '<div><label class="padding_label">'._L('Password').':</label><input type="password" name="password" value="" id="raLoginLdapPassword"></div>';
			if (OIDplus::baseConfig()->getValue('JWT_ALLOW_LOGIN_USER', true)) {
				if ((OIDplus::authUtils()->getAuthMethod() === OIDplusAuthContentStoreJWT::class)) {
					if (OIDplus::authUtils()->getExtendedAttribute('oidplus_generator',-1) === OIDplusAuthContentStoreJWT::JWT_GENERATOR_LOGIN) {
						$att = 'disabled checked';
					} else {
						$att = 'disabled';
					}
				} else if ((OIDplus::authUtils()->getAuthMethod() === OIDplusAuthContentStoreSession::class)) {
					$att = 'disabled';
				} else {
					$att = '';
				}
				$out['text'] .= '<div><input '.$att.' type="checkbox" value="1" id="remember_me_ldap" name="remember_me_ldap"> <label for="remember_me_ldap">'._L('Remember me').'</label></div>';
			}
			$out['text'] .= '<br><input type="submit" value="'._L('Login').'"><br><br>';
			$out['text'] .= '</form>';

			$invitePlugin = OIDplus::getPluginByOid('1.3.6.1.4.1.37476.2.5.2.4.2.92'); // OIDplusPageRaInvite
			$out['text'] .= '<p><abbr title="'._L('You don\'t need to register. Just enter your Windows/Company credentials.').'">'._L('How to register?').'</abbr></p>';

			$mins = ceil(OIDplus::baseConfig()->getValue('SESSION_LIFETIME', 30*60)/60);
			$out['text'] .= '<p><font size="-1">'._L('<i>Privacy information</i>: By using the login functionality, you are accepting that a "session cookie" is temporarily stored in your browser. The session cookie is a small text file that is sent to this website every time you visit it, to identify you as an already logged in user. It does not track any of your online activities outside OIDplus. The cookie will be destroyed when you log out or after an inactivity of %1 minutes.', $mins);
			$privacy_document_file = 'OIDplus/privacy_documentation.html';
			$resourcePlugin = OIDplus::getPluginByOid('1.3.6.1.4.1.37476.2.5.2.4.1.500'); // OIDplusPagePublicResources
			if (!is_null($resourcePlugin) && file_exists(OIDplus::localpath().'res/'.$privacy_document_file)) {
				$out['text'] .= ' <a '.OIDplus::gui()->link('oidplus:resources$'.$privacy_document_file.'#cookies').'>'._L('More information about the cookies used').'</a>';
			}
			$out['text'] .= '</font></p></div>';

			$out['text'] .= '<script>$("#loginLdapArea")[0].style.visibility = "visible";</script>';
		}
	}

	public function publicSitemap(&$out) {
		$out[] = 'oidplus:login_ldap';
	}

	public function tree(&$json, $ra_email=null, $nonjs=false, $req_goto='') {
		return true;
	}

	public function tree_search($request) {
		return false;
	}

	public function implementsFeature($id) {
		if (strtolower($id) == '1.3.6.1.4.1.37476.2.5.2.3.5') return true; // alternativeLoginMethods
		return false;
	}

	public function alternativeLoginMethods() {
		$logins = array();
		if (OIDplus::baseConfig()->getValue('LDAP_ENABLED', false)) {
			$logins[] = array(
				'oidplus:login_ldap',
				_L('Login using LDAP / ActiveDirectory'),
				OIDplus::webpath(__DIR__).'treeicon.png'
			);
		}
		return $logins;
	}
}

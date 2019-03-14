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

class OIDplusGui {

	private static $crudCounter = 0;

	protected static function objDescription($html) {
		// We allow HTML, but no hacking
		// TODO: disallow more html tags https://www.experts-exchange.com/questions/22664900/Extensive-list-of-all-dangerous-HTML-tags-and-attributes-anti-XSS.html
		$forbidden_tags = array('script');
		$html = str_ireplace('<script', '<xxx', $html);
		$html = str_ireplace('</script>', '</xxx>', $html);
		return $html;
	}

	protected static function showRAInfo($email) {
		$out = '';

		$res = OIDplus::db()->query("select * from ".OIDPLUS_TABLENAME_PREFIX."ra where email = '".OIDplus::db()->real_escape_string($email)."'");
		if (OIDplus::db()->num_rows($res) === 0) {
			$out = '<p>The RA <a href="mailto:'.htmlentities($email).'">'.htmlentities($email).'</a> is not registered in the database.</p>';

		} else {
			$row = OIDplus::db()->fetch_array($res);
			$out = '<b>'.htmlentities($row['ra_name']).'</b><br>';
			$out .= 'E-Mail: <a href="mailto:'.htmlentities($email).'">'.htmlentities($email).'</a><br>';
			if (trim($row['personal_name']) !== '') $out .= htmlentities($row['personal_name']).'<br>';
			if (trim($row['organization']) !== '') $out .= htmlentities($row['organization']).'<br>';
			if (trim($row['office']) !== '') $out .= htmlentities($row['office']).'<br>';
			if ($row['privacy']) {
				// TODO: meldung nur anzeigen, wenn benutzer überhaupt straße, adresse etc hat
				// TODO: aber der admin soll es sehen, und der user selbst (mit anmerkung, dass es privat ist)
				$out .= '<p>The RA does not want to publish their personal information.</p>';
			} else {
				if (trim($row['street']) !== '') $out .= htmlentities($row['street']).'<br>';
				if (trim($row['zip_town']) !== '') $out .= htmlentities($row['zip_town']).'<br>';
				if (trim($row['country']) !== '') $out .= htmlentities($row['country']).'<br>';
				$out .= '<br>';
				if (trim($row['phone']) !== '') $out .= htmlentities($row['phone']).'<br>';
				if (trim($row['fax']) !== '') $out .= htmlentities($row['fax']).'<br>';
				if (trim($row['mobile']) !== '') $out .= htmlentities($row['mobile']).'<br>';
				$out .= '<br>';
			}
		}

		return $out;
	}

	protected static function showCrud($parent='oid:') {
		$items_total = 0;
		$items_hidden = 0;

		$objParent = OIDplusObject::parse($parent);

		$output = '';
		if (!$objParent->userHasWriteRights()) {
			// TODO: wir sollten eigentlich bei noscript die buttons und edits ausblenden
			$output .= '<noscript><b>Please enable JavaScript to edit the subsequent OIDs.</b></noscript>';
		}
		$output .= '<div class="container box"><div id="suboid_table" class="table-responsive">';
		$output .= '<table class="table table-bordered table-striped">';
		$output .= '	<tr>';
		$output .= '	     <th>ID'.(($objParent::ns() == 'gs1') ? ' (without check digit)' : '').'</th>';
		if ($objParent::ns() == 'oid') $output .= '	     <th>ASN.1 IDs (comma sep.)</th>';
		if ($objParent::ns() == 'oid') $output .= '	     <th>IRI IDs (comma sep.)</th>';
		$output .= '	     <th>RA</th>';
		if ($objParent->userHasWriteRights()) {
			$output .= '	     <th>Hide</th>';
			$output .= '	     <th>Update</th>';
			$output .= '	     <th>Delete</th>';
		}
		$output .= '	     <th>Created</th>';
		$output .= '	     <th>Updated</th>';
		$output .= '	</tr>';

		$result = OIDplus::db()->query("select o.*, r.ra_name from ".OIDPLUS_TABLENAME_PREFIX."objects o left join ".OIDPLUS_TABLENAME_PREFIX."ra r on r.email = o.ra_email where parent = '".OIDplus::db()->real_escape_string($parent)."' order by ".OIDplus::db()->natOrder('id'));
		while ($row = OIDplus::db()->fetch_object($result)) {
			$obj = OIDplusObject::parse($row->id);

			$items_total++;
			if (!$obj->userHasReadRights()) {
				$items_hidden++;
				continue;
			}

			$show_id = $obj->crudShowId($objParent);

			$asn1ids = array();
			$res2 = OIDplus::db()->query("select name from ".OIDPLUS_TABLENAME_PREFIX."asn1id where oid = '".OIDplus::db()->real_escape_string($row->id)."' order by lfd");
			while ($row2 = OIDplus::db()->fetch_array($res2)) {
				$asn1ids[] = $row2['name'];
			}

			$iris = array();
			$res2 = OIDplus::db()->query("select name from ".OIDPLUS_TABLENAME_PREFIX."iri where oid = '".OIDplus::db()->real_escape_string($row->id)."' order by lfd");
			while ($row2 = OIDplus::db()->fetch_array($res2)) {
				$iris[] = $row2['name'];
			}

			$output .= '<tr>';
			// TODO: if no scripts are allowed, we cannot open this link using openAndSelectNode()
			$output .= '     <td><a href="#" onclick="return openAndSelectNode('.js_escape($row->id).', '.js_escape($parent).')">'.htmlentities($show_id).'</a></td>';
			if ($objParent->userHasWriteRights()) {
				if ($obj::ns() == 'oid') {
					$output .= '     <td><input type="text" id="asn1ids_'.$row->id.'" value="'.implode(', ', $asn1ids).'"></td>';
					$output .= '     <td><input type="text" id="iris_'.$row->id.'" value="'.implode(', ', $iris).'"></td>';
				}
				$output .= '     <td><input type="text" id="ra_email_'.$row->id.'" value="'.$row->ra_email.'"></td>';
				$output .= '     <td><input type="checkbox" id="hide_'.$row->id.'" '.($row->confidential ? 'checked' : '').'></td>';
				$output .= '     <td><button type="button" name="update_'.$row->id.'" id="update_'.$row->id.'" class="btn btn-success btn-xs update" onclick="javascript:crudActionUpdate('.js_escape($row->id).', '.js_escape($parent).')">Update</button></td>';
				$output .= '     <td><button type="button" name="delete_'.$row->id.'" id="delete_'.$row->id.'" class="btn btn-danger btn-xs delete" onclick="javascript:crudActionDelete('.js_escape($row->id).', '.js_escape($parent).')">Delete</button></td>';
				$output .= '     <td>'.oiddb_formatdate($row->created).'</td>';
				$output .= '     <td>'.oiddb_formatdate($row->updated).'</td>';
			} else {
				if ($asn1ids == '') $asn1ids = '<i>(none)</i>';
				if ($iris == '') $iris = '<i>(none)</i>';
				if ($obj::ns() == 'oid') {
					$asn1ids_ext = array();
					foreach ($asn1ids as $asn1id) {
						// TODO: if no scripts are allowed, we cannot open the rainfo: pages using openOidInPanel()
						$asn1ids_ext[] = '<a href="#" onclick="return openAndSelectNode('.js_escape($row->id).', '.js_escape($parent).')">'.$asn1id.'</a>';
					}
					$output .= '     <td>'.implode(', ', $asn1ids_ext).'</td>';
					$output .= '     <td>'.implode(', ', $iris).'</td>';
				}
				// TODO: if no scripts are allowed, we cannot open the rainfo: pages using openOidInPanel()
				$output .= '     <td><a href="#" onclick="return openOidInPanel('.js_escape('oidplus:rainfo$'.str_replace('@', "'+'@'+'", $row->ra_email)).', true)">'.htmlentities(empty($row->ra_name) ? str_replace('@','&',$row->ra_email) : $row->ra_name).'</a></td>';
				$output .= '     <td>'.oiddb_formatdate($row->created).'</td>';
				$output .= '     <td>'.oiddb_formatdate($row->updated).'</td>';
			}
			$output .= '</tr>';
		}

		$result = OIDplus::db()->query("select * from ".OIDPLUS_TABLENAME_PREFIX."objects where id = '".OIDplus::db()->real_escape_string($parent)."'");
		$parent_ra_email = OIDplus::db()->num_rows($result) > 0 ? OIDplus::db()->fetch_object($result)->ra_email : '';

		if ($objParent->userHasWriteRights()) {
			$output .= '<tr>';
			$prefix = is_null($objParent) ? '' : $objParent->crudInsertPrefix();
			if ($objParent::ns() == 'oid') {
				$output .= '     <td>'.$prefix.' <input type="text" id="id" value="" style="width:100%;min-width:50px"></td>'; // TODO: idee classname vergeben, z.B. "OID" und dann mit einem oid-spezifischen css die breite einstellbar machen, somit hat das plugin mehr kontrolle über das aussehen und die mindestbreiten
			} else {
				$output .= '     <td>'.$prefix.' <input type="text" id="id" value=""></td>';
			}
			if ($objParent::ns() == 'oid') $output .= '     <td><input type="text" id="asn1ids" value=""></td>';
			if ($objParent::ns() == 'oid') $output .= '     <td><input type="text" id="iris" value=""></td>';
			$output .= '     <td><input type="text" id="ra_email" value="'.htmlentities($parent_ra_email).'"></td>';
			$output .= '     <td><input type="checkbox" id="hide"></td>';
			$output .= '     <td><button type="button" name="insert" id="insert" class="btn btn-success btn-xs update" onclick="javascript:crudActionInsert('.js_escape($parent).')">Insert</button></td>';
			$output .= '     <td></td>';
			$output .= '     <td></td>';
			$output .= '     <td></td>';
			$output .= '</tr>';
		} else {
			if ($items_total-$items_hidden == 0) {
				$cols = ($objParent::ns() == 'oid') ? 7 : 5;
				$output .= '<tr><td colspan="'.$cols.'">No items available</td></tr>';
			}
		}

		$output .= '</table>';
		$output .= '</div></div>';

		if ($items_hidden == 1) {
			$output .= '<p>'.$items_hidden.' item is hidden. Please <a href="?goto=oidplus:login">log in</a> to see it.</p>';
		} else if ($items_hidden > 1) {
			$output .= '<p>'.$items_hidden.' items are hidden. Please <a href="?goto=oidplus:login">log in</a> to see them.</p>';
		}

		return $output;
	}

	public static $exclude_tinymce_plugins = array('fullpage', 'bbcode');

	protected static function showMCE($name, $content) {
		$mce_plugins = array();
		foreach (glob(__DIR__ . '/../../3p/tinymce/plugins/*') as $m) { // */
			$mce_plugins[] = basename($m);
		}

		foreach (self::$exclude_tinymce_plugins as $exclude) {
			$index = array_search($exclude, $mce_plugins);
			if ($index !== false) unset($mce_plugins[$index]);
		}

		$out = '<script>
				tinymce.remove("#'.$name.'");
				tinymce.init({
					selector: "#'.$name.'",
					height: 200,
					statusbar: false,
//					menubar:false,
//					toolbar: "undo redo | styleselect | bold italic | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | table | fontsizeselect",
					toolbar: "undo redo | styleselect | bold italic underline forecolor | bullist numlist | outdent indent | table | fontsizeselect",
					plugins: "'.implode(' ', $mce_plugins).'"
				});
			</script>';

		$out .= '<textarea name="'.htmlentities($name).'" id="'.htmlentities($name).'">'.trim($content).'</textarea><br>';

		return $out;
	}

	public static function getInvitationText($email) {
		$res = OIDplus::db()->query("select * from ".OIDPLUS_TABLENAME_PREFIX."ra where email = '".OIDplus::db()->real_escape_string($email)."'");
		if (OIDplus::db()->num_rows($res) > 0) {
			throw new Exception("This RA is already registered and does not need to be invited.");
		}

		if (!OIDplus::authUtils()::isAdminLoggedIn()) {
			// Check if the RA may invite the user (i.e. the they are the parent of an OID of that person)
			$ok = false;
			$res = OIDplus::db()->query("select parent from ".OIDPLUS_TABLENAME_PREFIX."objects where ra_email = '".OIDplus::db()->real_escape_string($email)."'");
			while ($row = OIDplus::db()->fetch_array($res)) {
				$objParent = OIDplusObject::parse($row['parent']);
				if (is_null($objParent)) throw new Exception("Type of ".$row['parent']." unknown");
				if ($objParent->userHasWriteRights()) {
					$ok = true;
				}
			}
			if (!$ok) {
				throw new Exception('You may not invite this RA. Maybe you need to log in again.');
			}
		}

		$list_of_oids = array();
		$res = OIDplus::db()->query("select id from ".OIDPLUS_TABLENAME_PREFIX."objects where ra_email = '".OIDplus::db()->real_escape_string($email)."'");
		while ($row = OIDplus::db()->fetch_array($res)) {
			$list_of_oids[] = $row['id'];
		}

		$message = file_get_contents(__DIR__ . '/../invite_msg.tpl');

		// Resolve stuff
		$message = str_replace('{{SYSTEM_URL}}', OIDplus::system_url(), $message);
		$message = str_replace('{{OID_LIST}}', implode("\n", $list_of_oids), $message);
		$message = str_replace('{{ADMIN_EMAIL}}', OIDPLUS_ADMIN_EMAIL, $message);
		$message = str_replace('{{PARTY}}', OIDplus::authUtils()::isAdminLoggedIn() ? 'the system administrator' : 'a superior Registration Authority', $message);

		// {{ACTIVATE_URL}} will be resolved in action.php

		return $message;
	}

	public static function getForgotPasswordText($email) {
		$res = OIDplus::db()->query("select * from ".OIDPLUS_TABLENAME_PREFIX."ra where email = '".OIDplus::db()->real_escape_string($email)."'");
		if (OIDplus::db()->num_rows($res) == 0) {
			throw new Exception("This RA does not exist.");
		}

		$message = file_get_contents(__DIR__ . '/../forgot_password.tpl');

		// Resolve stuff
		$message = str_replace('{{SYSTEM_URL}}', OIDplus::system_url(), $message);
		$message = str_replace('{{ADMIN_EMAIL}}', OIDPLUS_ADMIN_EMAIL, $message);

		// {{ACTIVATE_URL}} will be resolved in action.php

		return $message;
	}

	public static function generateContentPage($id) {
		$out = array();

		$handled = false;
		$out['title'] = '';
		$out['text'] = '';

		// === System ===

		if ($id === 'oidplus:system') {
			$handled = true;

			$out['title'] = OIDplus::config()->systemTitle(); // 'Object Database of ' . $_SERVER['SERVER_NAME'];
			$out['text'] = file_get_contents('welcome.html');
			return $out;

		// === Generic stuff ===

		} else if (explode('$',$id)[0] == 'oidplus:rainfo') {
			$handled = true;

			$ra_email = explode('$',$id)[1];

			$out['title'] = 'Registration Authority Information'; // TODO: email addresse reinschreiben? aber wie vor anti spam schützen?

			if (empty($ra_email)) {
				$out['text'] = '<p>Following object roots have an undefined Registration Authority:</p>';
			} else {
				$out['text'] = self::showRAInfo($ra_email);
			}

			foreach (OIDplusObject::getRaRoots($ra_email) as $loc_root) {
				$ico = $loc_root->getIcon();
				$icon = !is_null($ico) ? $ico : 'img/link.png';
				$out['text'] .= '<p><a href="?goto='.$loc_root->nodeId().'"><img src="'.$icon.'"> Jump to RA root '.$loc_root->objectTypeTitleShort().' '.$loc_root->crudShowId(OIDplusObject::parse($loc_root::root())).'</a></p>';
			}


			if (OIDplus::authUtils()::isAdminLoggedIn()) {
				$out['text'] .= '<p><a href="#" onclick="return deleteRa('.js_escape($ra_email).',null)">Delete this RA</a></p>';
			}

		// === Forgot password ===

		} else if (explode('$',$id)[0] == 'oidplus:forgot_password') {
			$handled = true;

			$out['title'] = 'Forgot password';

			try {
				$out['text'] .= '<p>Please enter the email address of your account, and information about the password reset will be sent to you.</p>
				  <form id="forgotPasswordForm" onsubmit="return forgotPasswordFormOnSubmit();">
				    E-Mail: <input type="text" id="email" value=""/><br><br>'.
				 (RECAPTCHA_ENABLED ? '<script> grecaptcha.render(document.getElementById("g-recaptcha"), { "sitekey" : "'.RECAPTCHA_PUBLIC.'" }); </script>'.
				                   '<div id="g-recaptcha" class="g-recaptcha" data-sitekey="'.RECAPTCHA_PUBLIC.'"></div>' : '').
				' <br>
				    <input type="submit" value="Send recovery information">
				  </form>';

			} catch (Exception $e) {

				$out['text'] = "Error: ".$e->getMessage();

			}
		} else if (explode('$',$id)[0] == 'oidplus:reset_password') {
			$handled = true;

			$email = explode('$',$id)[1];
			$timestamp = explode('$',$id)[2];
			$auth = explode('$',$id)[3];

			$out['title'] = 'Reset password';

			if (!OIDplus::authUtils()::validateAuthKey('reset_password;'.$email.';'.$timestamp, $auth)) {
				$out['text'] = 'Invalid authorization. Is the URL OK?';
			} else {
				$out['text'] = '<p>E-Mail-Adress: <b>'.$email.'</b></p>

				  <form id="resetPasswordForm" onsubmit="return resetPasswordFormOnSubmit();">
				    <input type="hidden" id="email" value="'.htmlentities($email).'"/>
				    <input type="hidden" id="timestamp" value="'.htmlentities($timestamp).'"/>
				    <input type="hidden" id="auth" value="'.htmlentities($auth).'"/>
				    New password: <input type="password" id="password1" value=""/><br><br>
				    Again: <input type="password" id="password2" value=""/><br><br>
				    <input type="submit" value="Change password">
				  </form>';
			}


		// === Invite ===

		} else if (explode('$',$id)[0] == 'oidplus:invite_ra') {
			$handled = true;

			$email = explode('$',$id)[1];
			$origin = explode('$',$id)[2];

			$out['title'] = 'Invite a Registration Authority';

			try {
				$cont = self::getInvitationText($email);

				$out['text'] .= '<p>You have chosen to invite <b>'.$email.'</b> as an Registration Authority. If you click "Send", the following email will be sent to '.$email.':</p><p><i>'.nl2br(htmlentities($cont)).'</i></p>
				  <form id="inviteForm" onsubmit="return inviteFormOnSubmit();">
				    <input type="hidden" id="email" value="'.htmlentities($email).'"/>
				    <input type="hidden" id="origin" value="'.htmlentities($origin).'"/>'.
				 (RECAPTCHA_ENABLED ? '<script> grecaptcha.render(document.getElementById("g-recaptcha"), { "sitekey" : "'.RECAPTCHA_PUBLIC.'" }); </script>'.
				                   '<div id="g-recaptcha" class="g-recaptcha" data-sitekey="'.RECAPTCHA_PUBLIC.'"></div>' : '').
				' <br>
				    <input type="submit" value="Send invitation">
				  </form>';

			} catch (Exception $e) {

				$out['text'] = "Error: ".$e->getMessage();

			}
		} else if (explode('$',$id)[0] == 'oidplus:activate_ra') {
			$handled = true;

			$email = explode('$',$id)[1];
			$timestamp = explode('$',$id)[2];
			$auth = explode('$',$id)[3];

			$out['title'] = 'Register as Registration Authority';

			$res = OIDplus::db()->query("select * from ".OIDPLUS_TABLENAME_PREFIX."ra where email = '".OIDplus::db()->real_escape_string($email)."'");
			if (OIDplus::db()->num_rows($res) > 0) {
				$out['text'] = 'This RA is already registered and does not need to be invited.';
			} else {
				if (!OIDplus::authUtils()::validateAuthKey('activate_ra;'.$email.';'.$timestamp, $auth)) {
					$out['text'] = 'Invalid authorization. Is the URL OK?';
				} else {
					// TODO: like in the FreeOID plugin, we could ask here at least for a name for the RA
					$out['text'] = '<p>E-Mail-Adress: <b>'.$email.'</b></p>

					  <form id="activateRaForm" onsubmit="return activateRaFormOnSubmit();">
					    <input type="hidden" id="email" value="'.htmlentities($email).'"/>
					    <input type="hidden" id="timestamp" value="'.htmlentities($timestamp).'"/>
					    <input type="hidden" id="auth" value="'.htmlentities($auth).'"/>
					    New password: <input type="password" id="password1" value=""/><br><br>
					    Again: <input type="password" id="password2" value=""/><br><br>
					    <input type="submit" value="Register">
					  </form>';
				}
			}

		// === Login ===

		} else if ($id === 'oidplus:login') {
			$handled = true;
			$out['title'] = 'Login';

			$out['text'] .= '<script>function raLoginOnSubmit() {';
			$out['text'] .= '	raLogin(document.getElementById("raLoginEMail").value, document.getElementById("raLoginPassword").value);';
			$out['text'] .= '	return false;';
			$out['text'] .= '}</script>';

			$out['text'] .= (RECAPTCHA_ENABLED ? '<script> grecaptcha.render(document.getElementById("g-recaptcha"), { "sitekey" : "'.RECAPTCHA_PUBLIC.'" }); </script>'.
			                                  '<p>Before logging in, please solve the following CAPTCHA</p><div id="g-recaptcha" class="g-recaptcha" data-sitekey="'.RECAPTCHA_PUBLIC.'"></div>' : '');


			$out['text'] .= '<br>';
			$out['text'] .= '<br>';

			$out['text'] .= '<div id="loginTab" class="container" style="width:100%">';
			$out['text'] .= '<ul  class="nav nav-pills">';
			$out['text'] .= '			<li class="active">';
			$out['text'] .= '        <a  href="#1a" data-toggle="tab">Login as RA</a>';
			$out['text'] .= '			</li>';
			$out['text'] .= '			<li><a href="#2a" data-toggle="tab">Login as Administrator</a>';
			$out['text'] .= '			</li>';
			$out['text'] .= '		</ul>';
			$out['text'] .= '			<div class="tab-content clearfix">';
			$out['text'] .= '			  <div class="tab-pane active" id="1a">';

			$out['text'] .= '<h2>Login as RA</h2>';

			$login_list = OIDplus::authUtils()->loggedInRaList();
			if (count($login_list) > 0) {
				foreach (OIDplus::authUtils()->loggedInRaList() as $x) {
					$out['text'] .= '<p>You are logged in as <b>'.$x.'</b> (<a href="#" onclick="return raLogout('.js_escape($x).');">Logout</a>)</p>';
				}
				$out['text'] .= '<p>If you have more accounts, you can log in with a new account:</p>';
			} else {
				$out['text'] .= '<p>Enter your email address and your password to log in as Registration Authority.</p>';
			}
			$out['text'] .= '<form action="action.php" method="POST" onsubmit="return raLoginOnSubmit(this);">';
			$out['text'] .= '<input type="hidden" name="action" value="ra_login">';
			$out['text'] .= 'E-Mail: <input type="text" name="email" value="" id="raLoginEMail"><br>';
			$out['text'] .= 'Password: <input type="password" name="password" value="" id="raLoginPassword"><br><br>';
			$out['text'] .= '<input type="submit" value="Login"><br><br>';
			$out['text'] .= '</form>';
			$out['text'] .= '<p><a href="?goto=oidplus:forgot_password">Forgot password?</a><br>';
			$out['text'] .= '<abbr title="To receive login data, the superior RA needs to send you an invitation. After creating or updating your OID, the system will ask them if they want to send you an invitation. If they accept, you will receive an email with an activation link.">How to register?</abbr></p>';
			$out['text'] .= '<script>function adminLoginOnSubmit() {';
			$out['text'] .= '	adminLogin(document.getElementById("adminLoginPassword").value);';
			$out['text'] .= '	return false;';
			$out['text'] .= '}</script>';

			$out['text'] .= '				</div>';
			$out['text'] .= '				<div class="tab-pane" id="2a">';

			if (OIDplus::authUtils()::isAdminLoggedIn()) {
				$out['text'] .= '<h2>Admin login</h2>';
				$out['text'] .= '<p>You are logged in as administrator.</p>';
				$out['text'] .= '<a href="#" onclick="return adminLogout();">Logout</a>';
			} else {
				$out['text'] .= '<h2>Login as Administrator</h2>';
				$out['text'] .= '<form action="action.php" method="POST" onsubmit="return adminLoginOnSubmit(this);">';
				$out['text'] .= '<input type="hidden" name="action" value="admin_login">';
				$out['text'] .= 'Password: <input type="password" name="password" value="" id="adminLoginPassword"><br><br>';
				$out['text'] .= '<input type="submit" value="Login"><br><br>';
				$out['text'] .= '</form>';
				$out['text'] .= '<p><abbr title="Delete the file includes/config.inc.php and reload the page to start Setup again">Forgot password?</abbr></p>';
			}

			$out['text'] .= '				</div>';
			$out['text'] .= '			</div>';
			$out['text'] .= '  </div>';






		}

		// === Plugins ===

		$ary = glob(__DIR__ . '/../../plugins/publicPages/'.'*'.'/gui.inc.php');
		sort($ary);
		foreach ($ary as $a) include $a;

		$ary = glob(__DIR__ . '/../../plugins/adminPages/'.'*'.'/gui.inc.php');
		sort($ary);
		foreach ($ary as $a) include $a;

		$ary = glob(__DIR__ . '/../../plugins/raPages/'.'*'.'/gui.inc.php');
		sort($ary);
		foreach ($ary as $a) include $a;

		// === Everything else (objects) ===

		if (!$handled) {
			$obj = OIDplusObject::parse($id);

			if ((!is_null($obj)) && (!$obj->userHasReadRights())) {
				$out['title'] = 'Access denied';
				$out['text'] = '<p>Please <a href="?goto=oidplus:login">log in</a> to receive information about this object.</p>';
				return $out;
			}

			$res = OIDplus::db()->query("select * from ".OIDPLUS_TABLENAME_PREFIX."objects where id = '".OIDplus::db()->real_escape_string($id)."'");
			$row = OIDplus::db()->fetch_array($res);

			if (empty($row['title'])) {
				$out['title'] = is_null($obj) ? $id : $obj->defaultTitle();
			} else {
				$out['title'] = $row['title'];
			}

			if (isset($row['description'])) {
				$desc = empty($row['description']) ? '<p><i>No description for this object available</i></p>' : OIDplusGui::objDescription($row['description']);
				if ($obj->userHasWriteRights()) {
					$rand = ++self::$crudCounter;
					$desc = '<noscript><p><b>You need to enable JavaScript to edit title or description of this object.</b></p>'.$desc.'</noscript>';
					$desc .= '<div class="container box" style="display:none" id="descbox_'.$rand.'">';
					$desc .= 'Title: <input type="text" name="title" id="titleedit" value="'.htmlentities($row['title']).'"><br><br>Description:<br>';
					$desc .= self::showMCE('description', $row['description']);
					$desc .= '<button type="button" name="update_desc" id="update_desc" class="btn btn-success btn-xs update" onclick="javascript:updateDesc()">Update description</button>';
					$desc .= '</div>';
					$desc .= '<script>document.getElementById("descbox_'.$rand.'").style.display = "block";</script>';
				}
			} else {
				$desc = '';
			}

			$matches_any_registered_type = false;
			foreach (OIDplusObject::$registeredObjectTypes as $ot) {
				if ($obj = $ot::parse($id)) {
					$matches_any_registered_type = true;
					if ((OIDplus::db()->num_rows($res) == 0) && !$obj->isRoot()){
						http_response_code(404);
						$out['title'] = 'Object not found';
						$out['text'] = 'The object <code>'.htmlentities($id).'</code> was not found in this database.';
						return $out;
					} else {
						$obj->getContentPage($out['title'], $out['text']);
					}
				}
			}
			if (!$matches_any_registered_type) {
				http_response_code(404);
				$out['title'] = 'Object not found';
				$out['text'] = 'The object <code>'.htmlentities($id).'</code> was not found in this database.';
				return $out;
			}

			if (strpos($out['text'], '%%DESC%%') !== false)
				$out['text'] = str_replace('%%DESC%%',    $desc,                              $out['text']);
			if (strpos($out['text'], '%%CRUD%%') !== false)
				$out['text'] = str_replace('%%CRUD%%',    self::showCrud($id),                $out['text']);
			if (strpos($out['text'], '%%RA_INFO%%') !== false)
				$out['text'] = str_replace('%%RA_INFO%%', self::showRaInfo($row['ra_email']), $out['text']);
		}

		return $out;
	}

}

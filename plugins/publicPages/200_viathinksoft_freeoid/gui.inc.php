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

if (isset($_SERVER['SERVER_NAME']) && (($_SERVER['SERVER_NAME'] == 'oidplus.viathinksoft.com'))) {

	if (explode('$',$id)[0] == 'oidplus:com.viathinksoft.freeoid') {
		$handled = true;

		$out['title'] = 'Register a free OID';
		$out['icon'] = file_exists(__DIR__.'/icon_big.png') ? 'plugins/publicPages/'.basename(__DIR__).'/icon_big.png' : '';

		$highest_id = freeoid_max_id();

		$out['text'] .= '<p>Currently <a href="?goto=oid:1.3.6.1.4.1.37476.9000">'.$highest_id.' free OIDs have been</a> registered. Please enter your email below to receive a free OID.</p>';

		try {
			$out['text'] .= '
				  <form id="freeOIDForm" onsubmit="return freeOIDFormOnSubmit();">
				    E-Mail: <input type="text" id="email" value=""/><br><br>'.
				 (RECAPTCHA_ENABLED ? '<script> grecaptcha.render(document.getElementById("g-recaptcha"), { "sitekey" : "'.RECAPTCHA_PUBLIC.'" }); </script>'.
				                   '<div id="g-recaptcha" class="g-recaptcha" data-sitekey="'.RECAPTCHA_PUBLIC.'"></div>' : '').
				' <br>
				    <input type="submit" value="Request free OID">
				  </form>




<h3>Terms of Service</h3>

<ul>
	<li>This service is mainly intended to give <b>private persons, small workgroups and software developers of freeware, shareware or open-source software</b> the possibility of obtaining a free Object Identifier (OID) with minimum bureaucratic overhead.</li>
	<li>You simply need to enter a valid email address to which an activation link will be sent. To get the OID assigned, you need to click the link in the automatically generated email and then enter your personal name, which is the only mandatory field. No human intervention from our side is performed during the whole process and the OID will be immediately assigned.</li>
	<li>Only OIDs which are registered and shown in this registry web site are legal assigned.</li>
	<li><b>You may only register one OID per person</b>. You can assign an infinite amount of sub-OIDs to yourself using your root OID.</li>	<li>Instead of using your new generated OID directly for a specific purpose, we recommend you to delegate a sub-OID for each purpose. Following this strategy, you will only require one root OID assigned to you.</li>
	<li>Please only register an OID if you are really sure that you need an OID namespace.</li>
	<li>To ensure that you get an appropriate OID namespace for your needs, please read the following points carefully:<ul>
		<li><b>If you are representing a company, you should register an OID in arc <code>1.3.6.1.4.1</code> instead (<a href="http://pen.iana.org" target="_blank">more information</a>).</b></li>
		<li>If you only need an OID for an example in a document, please use the OID <code>2.999</code> (no registration required).</li>
		<li>If you are sure that your target application does allow OIDs with 128-bit arcs (which does not apply to most ASN.1 implementations), you can register a globally unique OID (UUID OID) in arc <code>2.25</code> (see <a href="http://www.itu.int/ITU-T/asn1/uuid.html#UUID%20Generation%20&%20Registration" target="_blank">generate OID</a> and <a href="http://www.oid-info.com/faq.htm#size-limitations" target="_blank">more information about known limitations</a>). However, you can still request a ViaThinkSoft OID since it is shorter and easier to remember.</li>
		<li>If you want to extend the Active Directory schema, please consider obtaining an OID from Microsoft instead (<a href="http://msdn.microsoft.com/en-us/library/ms677620.aspx" target="_blank">more information</a>).</li>
		<li>If you are representing a country, you should consider registering an OID under the arc <code>1.2</code> (deprecated) or <code>2.16</code> (recommended) instead (<a href="http://www.oid-info.com/faq.htm#11" target="_blank">more information</a>).</li>
		<li>If you are representing a telecom operator, you should consider registering an OID in arc <code>0.2</code> instead (<a href="http://www.oid-info.com/get/0.2" target="_blank">more information</a>).</li>
		<li>If you are representing a network operator, you should consider registering an OID in arc <code>0.3</code> instead (<a href="http://www.oid-info.com/get/0.3" target="_blank">more information</a>).</li>
	</ul></li>
	<li>A registered OID can never be unregistered or reassigned. It will always stay visible to the public.</li>
	<li><b>You agree that your OID including your email address will be published in this public registry as well as in <a href="http://oid-info.com/get/1.3.6.1.4.1.37476.9000" target="_blank">oid-info.com</a> and in other public services.</b></li>
	<li>We encourage you to submit all your delegations to <a href="http://oid-info.com/get/1.3.6.1.4.1.37476.9000" target="_blank">oid-info.com</a>.</li>	<li>The data you entered can be changed at any time with a randomly generated password for your OID during the registration procedure (which can be changed and recovered at any time).</li>
	<li>You cannot choose a favorite arc number for your OID.</li>
	<li>ViaThinkSoft does not assign alphanumeric identifiers or Internationalized Resource Identifier (IRI) names since this system is fully automated and only the email address will be verified.</li>
	<li>Your new OID will be registered in arc <code>{iso(1) identified-organization(3) dod(6) internet(1) private(4) enterprise(1) 37476 freeoid(9000)}</code><br>IRI notation: <code>/ISO/Identified-Organization/6/1/4/1/37476/FreeOID</code></li>
	<li>If you have any questions or if you encounter bugs or problems, please contact <a href="mailto:'.htmlentities(OIDPLUS_ADMIN_EMAIL).'">'.htmlentities(OIDPLUS_ADMIN_EMAIL).'</a> (in English or German).</li>
</ul>



';

		} catch (Exception $e) {

			$out['text'] = "Error: ".$e->getMessage();

		}
	} else if (explode('$',$id)[0] == 'oidplus:com.viathinksoft.freeoid.activate_freeoid') {
		$handled = true;

		$email = explode('$',$id)[1];
		$timestamp = explode('$',$id)[2];
		$auth = explode('$',$id)[3];

		$out['title'] = 'Activate Free OID';
		$out['icon'] = file_exists(__DIR__.'/icon_big.png') ? 'plugins/publicPages/'.basename(__DIR__).'/icon_big.png' : '';

		$res = OIDplus::db()->query("select * from ".OIDPLUS_TABLENAME_PREFIX."ra where email = '".OIDplus::db()->real_escape_string($email)."'");
		if (OIDplus::db()->num_rows($res) > 0) {
			$out['text'] = 'This RA is already registered.'; // TODO: actually, the person might have something else (like a DOI) and want to have a FreeOID
		} else {
			if (!OIDplus::authUtils()::validateAuthKey('com.viathinksoft.freeoid.activate_freeoid;'.$email.';'.$timestamp, $auth)) {
				$out['text'] = 'Invalid authorization. Is the URL OK?';
			} else {
				$out['text'] = '<p>E-Mail-Adress: <b>'.$email.'</b></p>

				  <form id="activateFreeOIDForm" onsubmit="return activateFreeOIDFormOnSubmit();">
				    <input type="hidden" id="email" value="'.htmlentities($email).'"/>
				    <input type="hidden" id="timestamp" value="'.htmlentities($timestamp).'"/>
				    <input type="hidden" id="auth" value="'.htmlentities($auth).'"/>

				    Your personal name or the name of your group:<br><input type="text" id="ra_name" value=""/><br><br><!-- TODO: disable autocomplete -->
				    Title of your OID (usually equal to your name, optional):<br><input type="text" id="title" value=""/><br><br>
				    URL for more information about your project(s) (optional):<br><input type="text" id="url" value=""/><br><br>

				    Password: <input type="password" id="password1" value=""/><br><br>
				    Again: <input type="password" id="password2" value=""/><br><br>
				    <input type="submit" value="Register">
				  </form>';
			}
		}
	}

}


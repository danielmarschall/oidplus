/*
 * OIDplus 2.0
 * Copyright 2019 - 2022 Daniel Marschall, ViaThinkSoft
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

var OIDplusCaptchaPluginHCaptcha = {

	oid: "1.3.6.1.4.1.37476.2.5.2.4.11.4",

	sitekey: null,

	captchaResponse: function() {
		return $("#h-captcha").length > 0 ? hcaptcha.getResponse() : null;
	},

	captchaReset: function() {
		if ($("#h-captcha").length > 0) hcaptcha.reset();
	},

	captchaShow_internal: function() {
		if (typeof hcaptcha === 'undefined') {
			setTimeout(OIDplusCaptchaPluginHCaptcha.captchaShow_internal, 250);
		} else {
			hcaptcha.render('h-captcha', {
				'sitekey': OIDplusCaptchaPluginHCaptcha.sitekey
			});
		}
	},

	captchaShow: function(sitekey) {
		OIDplusCaptchaPluginHCaptcha.sitekey = sitekey;
		/*var*/ oidplus_captcha_response = function() {
		    return OIDplusCaptchaPluginHCaptcha.captchaResponse();
		};
		/*var*/ oidplus_captcha_reset = function() {
		    return OIDplusCaptchaPluginHCaptcha.captchaReset();
		};
		OIDplusCaptchaPluginHCaptcha.captchaShow_internal();
	}

};

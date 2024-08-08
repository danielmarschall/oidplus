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

// TODO: We can't change $("meta[name='theme-color']").attr("content", ...) here,
//       because this property (and the color manipulation) can only be accessed in PHP.

var OIDplusPageAdminColors = {

	oid: "1.3.6.1.4.1.37476.2.5.2.4.3.700",

	g_hue_shift: null,
	g_sat_shift: null,
	g_val_shift: null,
	g_invcolors: null,
	g_activetheme: null,

	g_hue_shift_saved: null,
	g_sat_shift_saved: null,
	g_val_shift_saved: null,
	g_invcolors_saved: null,
	g_activetheme_saved: null,

	color_reset_sliders_factory: function() {
		$("#hshift").val(OIDplusPageAdminColors.hue_shift = 0);
		$("#sshift").val(OIDplusPageAdminColors.sat_shift = 0);
		$("#vshift").val(OIDplusPageAdminColors.val_shift = 0);
		$("#icolor").attr('checked',OIDplusPageAdminColors.invcolors = 0 ? true : false);
		$("#theme").val(OIDplusPageAdminColors.activetheme = "default");
		$("#slider-hshift").slider("option", "value", OIDplusPageAdminColors.hue_shift);
		$("#slider-sshift").slider("option", "value", OIDplusPageAdminColors.sat_shift);
		$("#slider-vshift").slider("option", "value", OIDplusPageAdminColors.val_shift);
		OIDplusPageAdminColors.test_color_theme();
	},

	color_reset_sliders_cfg: function() {
		$("#hshift").val(OIDplusPageAdminColors.hue_shift = OIDplusPageAdminColors.hue_shift_saved);
		$("#sshift").val(OIDplusPageAdminColors.sat_shift = OIDplusPageAdminColors.sat_shift_saved);
		$("#vshift").val(OIDplusPageAdminColors.val_shift = OIDplusPageAdminColors.val_shift_saved);
		$("#icolor").attr('checked',OIDplusPageAdminColors.invcolors_saved ? true : false);
		$("#theme").val(OIDplusPageAdminColors.activetheme = OIDplusPageAdminColors.activetheme_saved);
		$("#slider-hshift").slider("option", "value", OIDplusPageAdminColors.hue_shift);
		$("#slider-sshift").slider("option", "value", OIDplusPageAdminColors.sat_shift);
		$("#slider-vshift").slider("option", "value", OIDplusPageAdminColors.val_shift);
		OIDplusPageAdminColors.test_color_theme();
	},

	setup_color_sliders: function() {
		$("#slider-hshift").slider({
			value: OIDplusPageAdminColors.hue_shift,
			min:   -360,
			max:   360,
			slide: function(event, ui) {
				$("#hshift").val(ui.value);
			}
		});
		$("#hshift").val($("#slider-hshift").slider("value"));

		$("#slider-sshift").slider({
			value: OIDplusPageAdminColors.sat_shift,
			min:   -100,
			max:   100,
			slide: function(event, ui) {
				$("#sshift").val(ui.value);
			}
		});
		$("#sshift").val($("#slider-sshift").slider("value"));

		$("#slider-vshift").slider({
			value: OIDplusPageAdminColors.val_shift,
			min:   -100,
			max:   100,
			slide: function(event, ui) {
				$("#vshift").val(ui.value);
			}
		});
		$("#vshift").val($("#slider-vshift").slider("value"));

		$("#icolor").attr('checked',OIDplusPageAdminColors.invcolors ? true : false);
	},

	url_change_param(url, desired_key, desired_value) {
		var baseUrl = url.split('?')[0];
		var queryString = url.split('?')[1];

		var params = new URLSearchParams(queryString);

		var output = {};

		params.forEach((value, key) => {
			if (key == desired_key) {
				output[key] = desired_value;
			} else {
				output[key] = value;
			}
		});

		if (!output.hasOwnProperty(desired_key)) {
			output[desired_key] = desired_value
		}

		var params = new URLSearchParams(output);

		var finalUrl = baseUrl;
		if (params.toString() != "") finalUrl += "?" + params.toString();

		return finalUrl;
	},

	test_color_theme: function() {
		OIDplusPageAdminColors.hue_shift = $("#hshift").val();
		OIDplusPageAdminColors.sat_shift = $("#sshift").val();
		OIDplusPageAdminColors.val_shift = $("#vshift").val();
		OIDplusPageAdminColors.invcolors = $("#icolor").is(':checked') ? 1 : 0;
		OIDplusPageAdminColors.activetheme = $("#theme").val();

		var links = $("head link");

		for (i=0; i<links.length; i++) {
			if (links[i].href.includes('oidplus.min.css.php')) {

				var url = links[i].href;

				console.log("Prev: " + url);

				url = OIDplusPageAdminColors.url_change_param(url, "theme", $("#theme").val());
				url = OIDplusPageAdminColors.url_change_param(url, "invert", $("#icolor").is(':checked') ? 1 : 0);
				url = OIDplusPageAdminColors.url_change_param(url, "h_shift", $("#hshift").val()/360);
				url = OIDplusPageAdminColors.url_change_param(url, "s_shift", $("#sshift").val()/100);
				url = OIDplusPageAdminColors.url_change_param(url, "v_shift", $("#vshift").val()/100);

				console.log("New: " + url);

				$("head link")[i].href = url;
			}
		}
	},

	crudActionColorUpdate: function(name) {
		if(!window.confirm(_L("Are you sure that you want to permanently change the color (for all users)? Please make sure you have tested the colors first, because if the contrast is too extreme, you might not be able to see the controls anymore, in order to correct the colors."))) return false;

		$.ajax({
			url:"ajax.php",
			method:"POST",
			beforeSend: function(jqXHR, settings) {
				$.xhrPool.abortAll();
				$.xhrPool.add(jqXHR);
			},
			complete: function(jqXHR, text) {
				$.xhrPool.remove(jqXHR);
			},
			data: {
				csrf_token: csrf_token,
				plugin: OIDplusPageAdminColors.oid,
				action: "color_update",
				hue_shift: $("#hshift")[0].value,
				sat_shift: $("#sshift")[0].value,
				val_shift: $("#vshift")[0].value,
				invcolors: $("#icolor").is(':checked') ? 1 : 0,
				theme: $("#theme")[0].value,
			},
			error: oidplus_ajax_error,
			success: function (data) {
				oidplus_ajax_success(data, function (data) {
					OIDplusPageAdminColors.hue_shift_saved = OIDplusPageAdminColors.hue_shift;
					OIDplusPageAdminColors.sat_shift_saved = OIDplusPageAdminColors.sat_shift;
					OIDplusPageAdminColors.val_shift_saved = OIDplusPageAdminColors.val_shift;
					OIDplusPageAdminColors.invcolors_saved = OIDplusPageAdminColors.invcolors;
					OIDplusPageAdminColors.test_color_theme(); // apply visually
					alertSuccess(_L("Update OK"));
				});
			}
		});
	}

};

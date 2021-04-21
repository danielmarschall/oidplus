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

var OIDplusPageAdminColors = {

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
		$("#icolor").val(OIDplusPageAdminColors.invcolors = 0);
		$("#theme").val(OIDplusPageAdminColors.activetheme = "default");
		$("#slider-hshift").slider("option", "value", OIDplusPageAdminColors.hue_shift);
		$("#slider-sshift").slider("option", "value", OIDplusPageAdminColors.sat_shift);
		$("#slider-vshift").slider("option", "value", OIDplusPageAdminColors.val_shift);
		$("#slider-icolor").slider("option", "value", OIDplusPageAdminColors.invcolors);
		$("#slider-icolor").slider("option", "value", OIDplusPageAdminColors.invcolors);
		$("#slider-icolor").slider("option", "value", OIDplusPageAdminColors.invcolors);
		OIDplusPageAdminColors.test_color_theme();
	},

	color_reset_sliders_cfg: function() {
		$("#hshift").val(OIDplusPageAdminColors.hue_shift = OIDplusPageAdminColors.hue_shift_saved);
		$("#sshift").val(OIDplusPageAdminColors.sat_shift = OIDplusPageAdminColors.sat_shift_saved);
		$("#vshift").val(OIDplusPageAdminColors.val_shift = OIDplusPageAdminColors.val_shift_saved);
		$("#icolor").val(OIDplusPageAdminColors.invcolors = OIDplusPageAdminColors.invcolors_saved);
		$("#theme").val(OIDplusPageAdminColors.activetheme = OIDplusPageAdminColors.activetheme_saved);
		$("#slider-hshift").slider("option", "value", OIDplusPageAdminColors.hue_shift);
		$("#slider-sshift").slider("option", "value", OIDplusPageAdminColors.sat_shift);
		$("#slider-vshift").slider("option", "value", OIDplusPageAdminColors.val_shift);
		$("#slider-icolor").slider("option", "value", OIDplusPageAdminColors.invcolors);
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

		/* ToDo: Checkbox instead */
		$("#slider-icolor").slider({
			value: OIDplusPageAdminColors.invcolors,
			min:   0,
			max:   1,
			slide: function(event, ui) {
				$("#icolor").val(ui.value);
			}
		});
		$("#icolor").val($("#slider-icolor").slider("value"));
	},

	test_color_theme: function() {
		OIDplusPageAdminColors.hue_shift = $("#hshift").val();
		OIDplusPageAdminColors.sat_shift = $("#sshift").val();
		OIDplusPageAdminColors.val_shift = $("#vshift").val();
		OIDplusPageAdminColors.invcolors = $("#icolor").val();
		OIDplusPageAdminColors.activetheme = $("#theme").val();
		OIDplusPageAdminColors.changeCSS('oidplus.min.css.php'+
		                                 '?theme='+encodeURIComponent($("#theme").val())+
		                                 '&invert='+encodeURIComponent($("#icolor").val())+
		                                 '&h_shift='+encodeURIComponent($("#hshift").val()/360)+
		                                 '&s_shift='+encodeURIComponent($("#sshift" ).val()/100)+
		                                 '&v_shift='+encodeURIComponent($("#vshift" ).val()/100),
		                                 OIDplusPageAdminColors.findLinkIndex('oidplus.min.css.php'));
	},

	findLinkIndex: function(searchString) {
		var links = document.getElementsByTagName("head").item(0).getElementsByTagName("link");

		for (i=0; i<links.length; i++) {
			if (links.item(i).href.includes(searchString)) return i;
		}

		return -1;
	},

	changeCSS: function(cssFile, cssLinkIndex) {
		var oldlink = document.getElementsByTagName("head").item(0).getElementsByTagName("link").item(cssLinkIndex);

		var newlink = document.createElement("link");
		newlink.setAttribute("rel", "stylesheet");
		newlink.setAttribute("type", "text/css");
		newlink.setAttribute("href", cssFile);

		document.getElementsByTagName("head").item(0).replaceChild(newlink, oldlink);
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
				csrf_token:csrf_token,
				plugin:"1.3.6.1.4.1.37476.2.5.2.4.3.700",
				action:"color_update",
				hue_shift:document.getElementById('hshift').value,
				sat_shift:document.getElementById('sshift').value,
				val_shift:document.getElementById('vshift').value,
				invcolors:document.getElementById('icolor').value,
				theme:document.getElementById('theme').value,
			},
			error:function(jqXHR, textStatus, errorThrown) {
				if (errorThrown == "abort") return;
				alert(_L("Error: %1",errorThrown));
			},
			success:function(data) {
				if ("error" in data) {
					alert(_L("Error: %1",data.error));
				} else if (data.status >= 0) {
					OIDplusPageAdminColors.hue_shift_saved = OIDplusPageAdminColors.hue_shift;
					OIDplusPageAdminColors.sat_shift_saved = OIDplusPageAdminColors.sat_shift;
					OIDplusPageAdminColors.val_shift_saved = OIDplusPageAdminColors.val_shift;
					OIDplusPageAdminColors.invcolors_saved = OIDplusPageAdminColors.invcolors;
					OIDplusPageAdminColors.test_color_theme(); // apply visually
					alert(_L("Update OK"));
				} else {
					alert(_L("Error: %1",data));
				}
			}
		});
	}

};

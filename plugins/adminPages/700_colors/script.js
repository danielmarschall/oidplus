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

g_hue_shift = null;
g_sat_shift = null;
g_val_shift = null;
g_invcolors = null;
g_hue_shift_saved = null;
g_sat_shift_saved = null;
g_val_shift_saved = null;
g_invcolors_saved = null;

function color_reset_sliders_factory() {
	$("#hshift").val(g_hue_shift = 0);
	$("#sshift").val(g_sat_shift = 0);
	$("#vshift").val(g_val_shift = 0);
	$("#icolor").val(g_invcolors = 0);
	$("#slider-hshift").slider("option", "value", g_hue_shift);
	$("#slider-sshift").slider("option", "value", g_sat_shift);
	$("#slider-vshift").slider("option", "value", g_val_shift);
	$("#slider-icolor").slider("option", "value", g_invcolors);
	test_color_theme();
}

function color_reset_sliders_cfg() {
	$("#hshift").val(g_hue_shift = g_hue_shift_saved);
	$("#sshift").val(g_sat_shift = g_sat_shift_saved);
	$("#vshift").val(g_val_shift = g_val_shift_saved);
	$("#icolor").val(g_invcolors = g_invcolors_saved);
	$("#slider-hshift").slider("option", "value", g_hue_shift);
	$("#slider-sshift").slider("option", "value", g_sat_shift);
	$("#slider-vshift").slider("option", "value", g_val_shift);
	$("#slider-icolor").slider("option", "value", g_invcolors);
	test_color_theme();
}

function setup_color_sliders() {
	$("#slider-hshift").slider({
		value: g_hue_shift,
		min:   -360,
		max:   360,
		slide: function(event, ui) {
			$("#hshift").val(ui.value);
		}
	});
	$("#hshift").val($("#slider-hshift").slider("value"));

	$("#slider-sshift").slider({
		value: g_sat_shift,
		min:   -100,
		max:   100,
		slide: function(event, ui) {
			$("#sshift").val(ui.value);
		}
	});
	$("#sshift").val($("#slider-sshift").slider("value"));

	$("#slider-vshift").slider({
		value: g_val_shift,
		min:   -100,
		max:   100,
		slide: function(event, ui) {
			$("#vshift").val(ui.value);
		}
	});
	$("#vshift").val($("#slider-vshift").slider("value"));

	/* ToDo: Checkbox instead */
	$("#slider-icolor").slider({
		value: g_invcolors,
		min:   0,
		max:   1,
		slide: function(event, ui) {
			$("#icolor").val(ui.value);
		}
	});
	$("#icolor").val($("#slider-icolor").slider("value"));
}

function test_color_theme() {
	g_hue_shift = $("#hshift").val();
	g_sat_shift = $("#sshift").val();
	g_val_shift = $("#vshift").val();
	g_invcolors = $("#icolor").val();
	changeCSS('oidplus.min.css.php?invert='+$("#icolor").val()+'&h_shift='+$("#hshift").val()/360+'&s_shift='+$("#sshift" ).val()/100+'&v_shift='+$("#vshift" ).val()/100, 0);
}

function changeCSS(cssFile, cssLinkIndex) {
	var oldlink = document.getElementsByTagName("link").item(cssLinkIndex);

	var newlink = document.createElement("link");
	newlink.setAttribute("rel", "stylesheet");
	newlink.setAttribute("type", "text/css");
	newlink.setAttribute("href", cssFile);

	document.getElementsByTagName("head").item(0).replaceChild(newlink, oldlink);
}

function crudActionColorUpdate(name) {
	if(!window.confirm(_L("Are you sure that you want to permanently change the color (for all users)? Please make sure you have tested the colors first, because if the contrast is too extreme, you might not be able to see the controls anymore, in order to correct the colors."))) return false;

	$.ajax({
		url:"ajax.php",
		method:"POST",
		data: {
			plugin:"1.3.6.1.4.1.37476.2.5.2.4.3.700",
			action:"color_update",
			hue_shift:document.getElementById('hshift').value,
			sat_shift:document.getElementById('sshift').value,
			val_shift:document.getElementById('vshift').value,
			invcolors:document.getElementById('icolor').value,
		},
		error:function(jqXHR, textStatus, errorThrown) {
			alert(_L("Error: %1",errorThrown));
		},
		success:function(data) {
			if ("error" in data) {
				alert(_L("Error: %1",data.error));
			} else if (data.status >= 0) {
				g_hue_shift_saved = g_hue_shift;
				g_sat_shift_saved = g_sat_shift;
				g_val_shift_saved = g_val_shift;
				g_invcolors_saved = g_invcolors;
				test_color_theme(); // apply visually
				alert(_L("Update OK"));
			} else {
				alert(_L("Error: %1",data));
			}
		}
	});
}
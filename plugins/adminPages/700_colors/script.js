g_hue_shift = null;
g_sat_shift = null;
g_val_shift = null;
g_hue_shift_saved = null;
g_sat_shift_saved = null;
g_val_shift_saved = null;

function color_reset_sliders_factory() {
	$("#hshift").val(g_hue_shift = 0);
	$("#sshift").val(g_sat_shift = 0);
	$("#vshift").val(g_val_shift = 0);
	$("#slider-hshift").slider("option", "value", g_hue_shift);
	$("#slider-sshift").slider("option", "value", g_sat_shift);
	$("#slider-vshift").slider("option", "value", g_val_shift);
	test_color_theme();
}

function color_reset_sliders_cfg() {
	$("#hshift").val(g_hue_shift = g_hue_shift_saved);
	$("#sshift").val(g_sat_shift = g_sat_shift_saved);
	$("#vshift").val(g_val_shift = g_val_shift_saved);
	$("#slider-hshift").slider("option", "value", g_hue_shift);
	$("#slider-sshift").slider("option", "value", g_sat_shift);
	$("#slider-vshift").slider("option", "value", g_val_shift);
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
}

function test_color_theme() {
	g_hue_shift = $("#hshift").val();
	g_sat_shift = $("#sshift").val();
	g_val_shift = $("#vshift").val();
	changeCSS('oidplus.min.css.php?h_shift='+$("#hshift").val()/360+'&s_shift='+$("#sshift" ).val()/100+'&v_shift='+$("#vshift" ).val()/100, 0);
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
	$.ajax({
		url:"ajax.php",
		method:"POST",
		data: {
			action:"color_update",
			hue_shift:document.getElementById('hshift').value,
			sat_shift:document.getElementById('sshift').value,
			val_shift:document.getElementById('vshift').value,
		},
		error:function(jqXHR, textStatus, errorThrown) {
			alert("Error: " + errorThrown);
		},
		success:function(data) {
			if ("error" in data) {
				alert("Error: " + data.error);
			} else if (data.status == 0) {
				g_hue_shift_saved = g_hue_shift;
				g_sat_shift_saved = g_sat_shift;
				g_val_shift_saved = g_val_shift;
				test_color_theme(); // apply visually
				alert("Update OK");
			} else {
				alert("Error: " + data);
			}
		}
	});
}


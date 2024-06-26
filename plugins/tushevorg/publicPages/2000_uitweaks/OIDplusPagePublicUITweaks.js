/*
* MIT License
*
* Copyright (c) 2022 Simon Tushev
*
* Permission is hereby granted, free of charge, to any person obtaining a copy
* of this software and associated documentation files (the "Software"), to deal
* in the Software without restriction, including without limitation the rights
* to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
* copies of the Software, and to permit persons to whom the Software is
* furnished to do so, subject to the following conditions:
*
* The above copyright notice and this permission notice shall be included in all
* copies or substantial portions of the Software.
*
* THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
* IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
* FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
* AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
* LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
* OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
* SOFTWARE.
*/

oidplus_menu_width_uservalue = isNull(localStorage.getItem('menu_width'), oidplus_menu_width);

// This function will fire upon any page reload (both document.ready and AJAX page change)
pageLoadedCallbacks.anyPageLoad.push(function() {
	if (typeof uitweaks == "undefined") return; // is undefined for Setup
	if (uitweaks.prefer_admin_login_tab) {
		$('#loginTab #myTab a[href="#admin"]').tab('show'); // Select tab by link
	}
});

// This function will fire upon document.ready event only, after the OIDplus base code
pageLoadedCallbacks.documentReadyAfter.push(function () {
	if (typeof uitweaks == "undefined") return; // is undefined for Setup

	var tree = $('#oidtree');
	tree.on('ready.jstree', function (e, data) {
		var o  = isNull(uitweaks.expand_objects_tree , false);
		var l  = isNull(uitweaks.collapse_login_tree , false);
		var r  = isNull(uitweaks.collapse_res_tree   , false);

		if (o) tree.jstree('open_all',  data.instance.get_node('oidplus:system'));
		if (l) tree.jstree('close_all', data.instance.get_node('oidplus:login'));
		if (r) tree.jstree('close_all', data.instance.get_node('oidplus:resources'));		
	});

	var menu_remember_width = isNull(uitweaks.menu_remember_width, false);
	if (menu_remember_width) {
		handle_glayout_onresize_start = function () {
			var new_width = parseInt($("#oidtree")[0].style.width, 10);
			localStorage.setItem('menu_width', new_width);
		}
		handle_glayout_onresize_start(); // to make sure that current value will be saved even if the user will not drag the bar
	} else {
		localStorage.removeItem('menu_width');
		oidplus_menu_width_uservalue = oidplus_menu_width;
	}

});

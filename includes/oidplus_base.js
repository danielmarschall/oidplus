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

/*jshint esversion: 6 */

// $('#html').jstree();

var bs5Utils = undefined;

var current_node = "";
var popstate_running = false;
// DEFAULT_LANGUAGE will be set by oidplus.min.js.php
// language_messages will be set by oidplus.min.js.php
// language_tblprefix will be set by oidplus.min.js.php
// csrf_token will be set by oidplus.min.js.php
// samesite_policy will bet set by oidplus.min.js.php

var pageChangeCallbacks = [];
var pageChangeRequestCallbacks = [];

var pageLoadedCallbacks= {
	"anyPageLoad":         [], // this is processed inside both AJAX successful reload and document.ready (at the very end)
	"ajaxPageLoad":        [], // inside AJAX successful reload only
	"documentReadyBefore": [], // inside document.ready, in the very beginning of the function
	"documentReadyAfter":  []  // inside document.ready, in the very end of the function
};

var oidplus_menu_width = 450; // In pixels. You can change this at runtime because of the glayoutWorkaroundB() workaround

function executeAllCallbacks(functionsArray) {
	functionsArray.forEach(
		function(fel) {
			if (typeof fel == 'function') fel();
		}
	);
}

function getOidPlusSystemTitle() {
	return getMeta('OIDplus-SystemTitle'); // do not translate
}

function combine_systemtitle_and_pagetitle(systemtitle, pagetitle) {
	// Please also change the function in index.php
	if (systemtitle == pagetitle) {
		return systemtitle;
	} else {
		return pagetitle + ' - ' + systemtitle;
	}
}

function getSystemUrl(relative) {
	relative = (typeof relative === 'undefined') ? false : relative; // do not translate
	var url = new URL(window.location.href);
	var res = relative ? url.pathname : url.href.substr(0, url.href.length-url.search.length);
	if (res.endsWith("index.php")) res = res.substring(0, res.lastIndexOf('/')) + "/";
	return res;
}

function getTreeLoadURL() {
	var url = new URL(window.location.href);
	var goto = url.searchParams.get("goto");
	return (goto != null) ? "ajax.php?csrf_token="+encodeURIComponent(csrf_token)+"&action=tree_load&anticache="+Date.now()+"&goto="+encodeURIComponent(goto)
	                      : "ajax.php?csrf_token="+encodeURIComponent(csrf_token)+"&action=tree_load&anticache="+Date.now();
}

function reloadMenu() {
	if(!$('#oidtree').jstree(true).get_node(current_node)) {
		// Avoid that a language change at "oidplus:srvreg_status" won't redirect the user to "oidplus:srv_registration" because of the reselection during refresh
		$('#oidtree').jstree("deselect_all");
	}
	$('#oidtree').jstree("refresh");
}

function reloadContent() {
	// window.location.href = "?goto="+encodeURIComponent(current_node);
	if (openOidInPanel(current_node, false)) {
		reloadMenu();
	}
}

function x_rec(x_data, i) {
	$('#oidtree').jstree('open_node', x_data[i], function(e, data) {
		if (i+1 < x_data.length) {
			x_rec(x_data, i+1);
		} else {
			popstate_running = true; // don't call openOidInPanel again
			try {
				$('#oidtree').jstree('select_node', x_data[i]);
			} catch (err) {
				popstate_running = false;
			} finally {
				popstate_running = false;
			}
		}
	});
}

function performCloseQueryCB() {
	for (var i=0; i<pageChangeRequestCallbacks.length; i++) {
		if (!pageChangeRequestCallbacks[i][0](pageChangeRequestCallbacks[i][1])) return false;
	}
	pageChangeRequestCallbacks = [];
	return true; // may close
}

function performCloseCB() {
	for (var i=0; i<pageChangeCallbacks.length; i++) {
		pageChangeCallbacks[i][0](pageChangeCallbacks[i][1]);
	}
	pageChangeCallbacks = [];
}

function openOidInPanel(id, reselect/*=false*/, anchor/*=''*/, force/*=false*/) {
	reselect = (typeof reselect === 'undefined') ? false : reselect; // do not translate
	anchor = (typeof anchor === 'undefined') ? '' : anchor; // do not translate
	force = (typeof force === 'undefined') ? false : force; // do not translate

	var mayClose = performCloseQueryCB();
	if (!force && !mayClose) return false;

	performCloseCB();

	$.xhrPool.abortAll();

	if (reselect) {
		$('#oidtree').jstree('deselect_all');

		popstate_running = true; // don't call openOidInPanel during tree selection
		try {
			// If the node is already loaded in the tree, select it
			if (!$('#oidtree').jstree('select_node', id)) {
				// If the node is not loaded, then we try to search it.
				// If it can be found, then open all parent nodes and select the node
				$.ajax({
					url:"ajax.php",
					method:"POST",
					beforeSend: function(jqXHR, settings) {
						//$.xhrPool.abortAll();
						$.xhrPool.add(jqXHR);
					},
					complete: function(jqXHR, text) {
						$.xhrPool.remove(jqXHR);
					},
					data:{
						csrf_token:csrf_token,
						action:"tree_search",
						search:id,
						anticache:Date.now()
					},
					error:function(jqXHR, textStatus, errorThrown) {
						if (errorThrown == "abort") return;
						console.error("Tree search failed");
						console.error(_L("Error: %1",errorThrown));
					},
					success:function(data) {
						if (typeof data === "object" && "error" in data) {
							console.error("Tree search failed");
							console.error(data);
						} else if ((data instanceof Array) && (data.length > 0)) {
							x_rec(data, 0);
						} else {
							console.error("Tree search failed");
							console.error(data);
						}
					}
				});
			}
		} catch (err) {
			popstate_running = false;
		} finally {
			popstate_running = false;
		}
	}

	// This loads the actual content

	// document.title = ""; // <-- we may not do this, otherwise Firefox won't
	//                            show titles in the browser history (right-click
	//                            on back-button), although document.title() is
	//                            set inside the AJAX-callback [Firefox bug?!]

	$('#real_title').html("&nbsp;");
	$('#real_content').html(_L("Loading..."));
	$('#static_link').attr("href", oidplus_webpath_absolute_canonical+"?goto="+encodeURIComponent(id));
	$("#gotoedit").val(id);

	// Normal opening of a description
	$.ajax({
		url:"ajax.php",
		method:"GET",
		beforeSend: function(jqXHR, settings) {
			//$.xhrPool.abortAll();
			$.xhrPool.add(jqXHR);
		},
		complete: function(jqXHR, text) {
			$.xhrPool.remove(jqXHR);
		},
		data:{
			csrf_token:csrf_token,
			action:"get_description",
			id:id,
			anticache:Date.now()
		},
		error:function(jqXHR, textStatus, errorThrown) {
			if (errorThrown == "abort") return;
			alertError(_L("Failed to load content: %1",errorThrown));
			console.error(_L("Error: %1",errorThrown));
		},
		success:function(data) {
			// TODO: Use oidplus_ajax_success(), since this checks the existance of "error" in data, and checks if status>=0
			if (typeof data === "object" && "error" in data) {
				console.error(data.error);
				alertError(_L("Failed to load content: %1",data.error));
			} else if (typeof data === "object" && "status" in data && data.status >= 0) {
				if (!("id" in data)) data.id = id;

				var state = {
					"node_id":/*data.*/id,
					"titleHTML":(data.icon ? '<img src="'+data.icon+'" width="48" height="48" alt="'+data.title.htmlentities()+'"> ' : '') + data.title.htmlentities(),
					"textHTML":data.text,
					"staticlinkHREF":oidplus_webpath_absolute_canonical+"?goto="+encodeURIComponent(/*data.*/id),
				};
				if (current_node != /*data.*/id) {
					window.history.pushState(state, data.title, "?goto="+encodeURIComponent(/*data.*/id));
				} else {
					window.history.replaceState(state, data.title, "?goto="+encodeURIComponent(/*data.*/id));
				}

				document.title = combine_systemtitle_and_pagetitle(getOidPlusSystemTitle(), data.title);

				if (data.icon) {
					$('#real_title').html('<img src="'+data.icon+'" width="48" height="48" alt="'+data.title.htmlentities()+'"> ' + data.title.htmlentities());
				} else {
					$('#real_title').html(data.title.htmlentities());
				}
				$('#real_content').html(data.text);
				document.title = combine_systemtitle_and_pagetitle(getOidPlusSystemTitle(), data.title);
				current_node = /*data.*/id;

				executeAllCallbacks(pageLoadedCallbacks.anyPageLoad);
				executeAllCallbacks(pageLoadedCallbacks.ajaxPageLoad);

				if (anchor != '') {
					jumpToAnchor(anchor);
				}
			} else if (typeof data === "object" && "status" in data && data.status < 0) {
				console.error(data);
				alertError(_L("Failed to load content: %1",data.status));
			} else {
				console.error(data);
				alertError(_L("Failed to load content: %1",data));
			}
		}
	});

	return true;
}

// This function opens the "parentID" node, and then selects the "childID" node (which should be beneath the parent node)
function openAndSelectNode(childID, parentID) {
	if ($('#oidtree').jstree(true).get_node(parentID)) {
		$('#oidtree').jstree('open_node', parentID, function(e, data) { // open parent node
			if ($('#oidtree').jstree(true).get_node(childID)) { // is the child there?
				$('#oidtree').jstree('deselect_all').jstree('select_node', childID); // select it
			} else {
				// This can happen if the content page contains brand new items which are not in the treeview yet
				$("#gotoedit").val(childID);
				window.location.href = "?goto="+encodeURIComponent(childID);
			}
		}, true);
	} else {
		// This should usually not happen
		$("#gotoedit").val(childID);
		window.location.href = "?goto="+encodeURIComponent(childID);
	}
}

$(window).on("popstate", function(e) {
	if (!performCloseQueryCB()) {
		// TODO: does not work!!! The "back/forward" action will be cancelled, but the browser still thinks it was successful,
		// so if you do it again, you will then jump 2 pages back, etc!
		// This does also not help:
		//window.history.pushState(e.originalEvent.state, e.originalEvent.title, e.originalEvent.url);
		//window.history.forward();
		return;
	}

	popstate_running = true;
	try {
		var data = e.originalEvent.state;

		current_node = data.node_id;
		$("#gotoedit").val(current_node);
		$('#oidtree').jstree('deselect_all').jstree('select_node', data.node_id);
		$('#real_title').html(data.titleHTML);
		$('#real_content').html(data.textHTML);
		$('#static_link').attr("href", data.staticlinkHREF);
		document.title = combine_systemtitle_and_pagetitle(getOidPlusSystemTitle(), data.titleHTML.html_entity_decode());
	} catch (err) {
		popstate_running = false;
	} finally {
		popstate_running = false;
	}
});

$(document).ready(function () {

	executeAllCallbacks(pageLoadedCallbacks.documentReadyBefore);

	/*
	window.onbeforeunload = function(e) {
		// TODO: This won't be called because TinyMCE overrides it??
		// TODO: when the user accepted the query in performCloseQueryCB(), then the message will be shown again by the browser!
		if (!performCloseQueryCB()) {
			// Cancel the event
			e.preventDefault(); // If you prevent default behavior in Mozilla Firefox prompt will always be shown
			// Chrome requires returnValue to be set
			e.returnValue = '';
		} else {
			// the absence of a returnValue property on the event will guarantee the browser unload happens
			delete e['returnValue'];
		}
	};
	*/

	if (typeof oidplus_menu_width_uservalue !== 'undefined') {
		oidplus_menu_width = oidplus_menu_width_uservalue;
	}

	// --- JsTree

	if ($('#oidtree').length > 0) $('#oidtree')
	.jstree({
		plugins: ['massload','search','conditionalselect'],
		'core' : {
			'data' : {
				"url" : getTreeLoadURL(),
				"data" : function (node) {
					return { "id" : node.id };
				}
			},
			"multiple": false /* do not allow multiple selections */
		},
		'conditionalselect' : function (node) {
			if (node.original.conditionalselect !== undefined) {
				return eval(node.original.conditionalselect);
			} else {
				return performCloseQueryCB();
			}
		},
	})
	.on('ready.jstree', function (e, data) {
		var url = new URL(window.location.href);
		var goto = url.searchParams.get("goto");
		if (goto == null) goto = "oidplus:system"; // the page was not called with ?goto=...
		if ($('#gotoedit').length > 0) $("#gotoedit").val(goto);

		// By setting current_node, select_node() will not cause ajax.php?action=get_description to load (since we already loaded the first static content via PHP, for search engines mainly)
		// But then we need to set the history state manually
		current_node = goto;
		window.history.replaceState({
			"node_id":goto,
			"titleHTML":$('#real_title').html(),
			"textHTML":$('#real_content').html(),
			"staticlinkHREF":oidplus_webpath_absolute_canonical+"?goto="+encodeURIComponent(goto),
		}, $('#real_title').html(), "?goto="+encodeURIComponent(goto));

		if (goto != null) data.instance.select_node([goto]);

		setTimeout(glayoutWorkaroundAC, 100);
		setTimeout(glayoutWorkaroundB, 100);
	})
	.on('select_node.jstree', function (node, selected, event) {
		mobileNavClose();

		var id = selected.node.id;
		if ((!popstate_running) && (current_node != id)) {
			// 4th argument: we force the reload (because in the
			// conditional select above, we already asked if
			// tinyMCE needs to be saved)
			openOidInPanel(id, false, '', true);
		}
	});

	// --- Layout

	if ($('#system_title_menu').length > 0) $("#system_title_menu")[0].style.display = "block";

	var tmpObjectTree = _L("OBJECT TREE").replace(/(.{1})/g,"$1<br>");
	tmpObjectTree = tmpObjectTree.substring(0, tmpObjectTree.length-"<br>".length);

	if ($('#oidtree').length > 0) $('#oidtree').addClass('ui-layout-west');
	if ($('#content_window').length > 0) $('#content_window').addClass('ui-layout-center');
	if ($('#system_title_bar').length > 0) $('#system_title_bar').addClass('ui-layout-north');
	if ($('#frames').length > 0) glayout = $('#frames').layout({
		north__size:                  40,
		north__slidable:              false,
		north__closable:              false,
		north__resizable:             false,
		west__size:                   oidplus_menu_width,
		west__spacing_closed:         20,
		west__togglerLength_closed:   230,
		west__togglerAlign_closed:    "center",
		west__togglerContent_closed:  tmpObjectTree,
		west__togglerTip_closed:      _L("Open & Pin Menu"),
		west__sliderTip:              _L("Slide Open Menu"),
		west__slideTrigger_open:      "mouseover",
		west__enableCursorHotkey:     false, // disable Ctrl+Shift+LeftArrow hotkey ( see https://github.com/danielmarschall/oidplus/issues/28 )
		center__maskContents:         true, // IMPORTANT - enable iframe masking
		onresize_start:               function() { if (typeof handle_glayout_onresize_start == 'function') handle_glayout_onresize_start(); }
	});

	if ($('#gotobox').length == 0) $("#languageBox").css('right', '20px'); // Language Box to the right if there is no goto-box

	if ($('#gotobox').length > 0) $("#gotobox").addClass("mobilehidden");
	if ($('#languageBox').length > 0) $("#languageBox").addClass("mobilehidden");
	if ($('#gotobox').length > 0) $("#gotobox")[0].style.display = "block";
	if ($('#gotoedit').length > 0) $('#gotoedit').keypress(function(event) {
		var keycode = (event.keyCode ? event.keyCode : event.which);
		if (keycode == '13') {
			gotoButtonClicked();
		}
	});

	if (typeof Bs5Utils !== "undefined") {
		Bs5Utils.defaults.toasts.position = 'top-center';
		Bs5Utils.defaults.toasts.stacking = true;
		bs5Utils = new Bs5Utils();
	}

	executeAllCallbacks(pageLoadedCallbacks.anyPageLoad);
	executeAllCallbacks(pageLoadedCallbacks.documentReadyAfter);
});

// can be overridden if necessary
var handle_glayout_onresize_start = undefined;

function glayoutWorkaroundAC() {
	// "Bug A": Sometimes, the design is completely destroyed after reloading the page. It does not help when glayout.resizeAll()
	//          is called at the beginning (e.g. during the ready function), and it does not help if we wait 500ms.
	//          So we do it all the time. It has probably something to do with slow loading times, since the error
	//          does only appear when the page is "blank" for a short while while it is loading.
	glayout.resizeAll();

	// "Bug C": With Firefox (And sometimes with Chrome), there is a gap between the content-window (including scroll bars)
	//          and the right corner of the screen. Removing the explicit width solves this problem.
	$("#content_window")[0].style.removeProperty("width");

	setTimeout(glayoutWorkaroundAC, 100);
}

function glayoutWorkaroundB() {
	// "Bug B": Sometimes, after reload, weird space between oidtree and content window, because oidtree has size of 438px
	$("#oidtree")[0].style.width = oidplus_menu_width + "px";
}

function mobileNavClose() {
	if ($("#system_title_menu").is(":hidden")) {
		return;
	}

	$("#oidtree").slideUp("medium").promise().done(function() {
		$("#oidtree").addClass("ui-layout-west");
		$("#oidtree").show();
//		$("#gotobox").hide();
//		$("#languageBox").hide();
		$("#gotobox").addClass("mobilehidden");
		$("#languageBox").addClass("mobilehidden");
	});
	$("#system_title_menu").removeClass("active");
}

function mobileNavOpen() {
	$("#oidtree").hide();
	$("#oidtree").removeClass("ui-layout-west");
	$("#oidtree").slideDown("medium");
//	$("#gotobox").show();
//	$("#languageBox").show();
	$("#gotobox").removeClass("mobilehidden");
	$("#languageBox").removeClass("mobilehidden");
	$("#system_title_menu").addClass("active");
}

function mobileNavButtonClick(sender) {
	if ($("#oidtree").hasClass("ui-layout-west")) {
		mobileNavOpen();
	} else {
		mobileNavClose();
	}
}

function mobileNavButtonHover(sender) {
	sender.classList.toggle("hover");
}

function gotoButtonClicked() {
	openOidInPanel($("#gotoedit").val(), true);
}

function setLanguage(lngid) {
	setCookie('LANGUAGE', lngid, 0/*Until browser closes*/, oidplus_webpath_relative);

	if (current_node == "") return false; // Happens for Setup. Open URL instead.

	$(".lng_flag").each(function(){
		$(this).addClass("picture_ghost");
	});
	$("#lng_flag_"+$.escapeSelector(lngid)).removeClass("picture_ghost");

	// TODO: Small detail: The "Go" button also needs to be re-translated
	reloadContent();
	mobileNavClose();

	return true; // we have handled it. Do not follow href=""
}

function show_waiting_anim() {
	$("#loading").show();
}

function hide_waiting_anim() {
	$("#loading").hide();
}

/* Mini-framework to abort all AJAX requests if a new request is made */

$.xhrPool = [];
$.xhrPool.add = function(jqXHR) {
	$.xhrPool.push(jqXHR);
}
$.xhrPool.remove = function(jqXHR) {
	var index = $.xhrPool.indexOf(jqXHR);
	if (index > -1) {
		$.xhrPool.splice(index, 1);
	}
};
$.xhrPool.abortAll = function() {
	var calls = Array.from($.xhrPool);
	$.each(calls, function(key, value) {
		value.abort();
	});
}

/* Individual alert types */
/* TODO: alert() blocks program flow, but alertSuccess() not! Will the mass change have negative effects (e.g. a redirect without the user seeing the toast)? */

function alertSuccess(txt) {
	if (typeof bs5Utils !== "undefined") {
		bs5Utils.Snack.show('success', _L(txt), delay = 5000, dismissible = true);
	} else {
		alert(txt);
	}
}

function alertWarning(txt) {
	// TODO: as toast?
	alert(txt);
}

function alertError(txt) {
	// TODO: as toast?
	alert(txt);
}

/* AJAX success/error-handling */

function oidplus_ajax_error(jqXHR, textStatus, errorThrown) {
	if (errorThrown == "abort") return;
	console.error(errorThrown, jqXHR);
	alertError(_L("Error: %1", errorThrown));
}

function oidplus_ajax_success(data, cb) {
	if (typeof data === "object" && "error" in data) {
		console.error(data);
		alertError(_L("Error: %1", data.error));
	} else if (typeof data === "object" && "status" in data && data.status >= 0) {
		cb(data);
	} else if (typeof data === "object" && "status" in data && data.status < 0) {
		console.error(data);
		alertError(_L("Error: %1", data.status));
	} else {
		console.error(data);
		alertError(_L("Error: %1", data));
	}
}

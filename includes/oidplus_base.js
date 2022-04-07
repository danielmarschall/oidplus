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

var current_node = "";
var popstate_running = false;
// DEFAULT_LANGUAGE will be set by oidplus.min.js.php
// language_messages will be set by oidplus.min.js.php
// language_tblprefix will be set by oidplus.min.js.php
// csrf_token will be set by oidplus.min.js.php
// samesite_policy will bet set by oidplus.min.js.php

var pageChangeCallbacks = [];
var pageChangeRequestCallbacks = [];

var oidplus_menu_width = 450; // In pixels. You can change this at runtime because of the glayoutWorkaroundB() workaround

function isInternetExplorer() {
	// see also includes/functions.inc.php
	var ua = window.navigator.userAgent;
	return ((ua.indexOf("MSIE ") > 0) || (ua.indexOf("Trident/") > 0));
}

String.prototype.explode = function (separator, limit) {
	// https://stackoverflow.com/questions/4514323/javascript-equivalent-to-php-explode
	const array = this.split(separator);
	if (limit !== undefined && array.length >= limit) {
		array.push(array.splice(limit - 1).join(separator));
	}
	return array;
};

String.prototype.htmlentities = function () {
	return this.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');//"
};

String.prototype.html_entity_decode = function () {
	return $('<textarea />').html(this).text();
};

if (!String.prototype.replaceAll) {
	/**
	 * String.prototype.replaceAll() polyfill
	 * https://gomakethings.com/how-to-replace-a-section-of-a-string-with-another-one-with-vanilla-js/
	 * @author Chris Ferdinandi
	 * @license MIT
	 */
	String.prototype.replaceAll = function(str, newStr){
		// If a regex pattern
		if (Object.prototype.toString.call(str).toLowerCase() === '[object regexp]') {
			return this.replace(str, newStr);
		}
		// If a string
		return this.replace(new RegExp(str, 'g'), newStr);
	};
}

if (!String.prototype.startsWith) {
	// https://developer.mozilla.org/en-US/docs/Web/JavaScript/Reference/Global_Objects/String/startsWith#polyfill
	Object.defineProperty(String.prototype, 'startsWith', {
		value: function(search, rawPos) {
			var pos = rawPos > 0 ? rawPos|0 : 0;
			return this.substring(pos, pos + search.length) === search;
		}
	});
}

function getMeta(metaName) {
	const metas = $('meta[name='+metaName+']');
	return (metas.length == 0) ? '' : metas[0].content;
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
	var url = new URL(window.location.href);
	if (relative) {
		return url.pathname;
	} else {
		return url.href.substr(0, url.href.length-url.search.length);
	}
}

function getTreeLoadURL() {
	var url = new URL(window.location.href);
	var goto = url.searchParams.get("goto");
	return (goto != null) ? "ajax.php?csrf_token="+encodeURIComponent(csrf_token)+"&action=tree_load&anticache="+Date.now()+"&goto="+encodeURIComponent(goto)
	                      : "ajax.php?csrf_token="+encodeURIComponent(csrf_token)+"&action=tree_load&anticache="+Date.now();
}

function reloadContent() {
	// window.location.href = "?goto="+encodeURIComponent(current_node);
	if (openOidInPanel(current_node, false)) {
		if(!$('#oidtree').jstree(true).get_node(current_node)) {
			// Avoid that a language change at "oidplus:srvreg_status" won't redirect the user to "oidplus:srv_registration" because of the reselection during refresh
			$('#oidtree').jstree("deselect_all");
		}

		$('#oidtree').jstree("refresh");
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
						if ("error" in data) {
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
	$('#static_link').attr("href", "index.php?goto="+encodeURIComponent(id));
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
			alert(_L("Failed to load content: %1",errorThrown));
			console.error(_L("Error: %1",errorThrown));
		},
		success:function(data) {
			if ("error" in data) {
				alert(_L("Failed to load content: %1",data.error));
				console.error(data.error);
			} else if (data.status >= 0) {
				data.id = id;

				var state = {
					"node_id":id,
					"titleHTML":(data.icon ? '<img src="'+data.icon+'" width="48" height="48" alt="'+data.title.htmlentities()+'"> ' : '') + data.title.htmlentities(),
					"textHTML":data.text,
					"staticlinkHREF":"index.php?goto="+encodeURIComponent(id),
				};
				if (current_node != id) {
					window.history.pushState(state, data.title, "?goto="+encodeURIComponent(id));
				} else {
					window.history.replaceState(state, data.title, "?goto="+encodeURIComponent(id));
				}

				document.title = combine_systemtitle_and_pagetitle(getOidPlusSystemTitle(), data.title);

				if (data.icon) {
					$('#real_title').html('<img src="'+data.icon+'" width="48" height="48" alt="'+data.title.htmlentities()+'"> ' + data.title.htmlentities());
				} else {
					$('#real_title').html(data.title.htmlentities());
				}
				$('#real_content').html(data.text);
				document.title = combine_systemtitle_and_pagetitle(getOidPlusSystemTitle(), data.title);
				current_node = id;

				if (anchor != '') {
					jumpToAnchor(anchor);
				}
			} else {
				alert(_L("Failed to load content: %1",data.status));
				console.error(data);
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

	// --- JsTree

	$('#oidtree')
	.jstree({
		plugins: ['massload','search','conditionalselect'],
		'core' : {
			'data' : {
				"url" : getTreeLoadURL(),
				"data" : function (node) {
					return { "id" : node.id };
				}
			},
			"multiple": false
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
		$("#gotoedit").val(goto);

		// By setting current_node, select_node() will not cause ajax.php?action=get_description to load (since we already loaded the first static content via PHP, for search engines mainly)
		// But then we need to set the history state manually
		current_node = goto;
		window.history.replaceState({
			"node_id":goto,
			"titleHTML":$('#real_title').html(),
			"textHTML":$('#real_content').html(),
			"staticlinkHREF":"index.php?goto="+encodeURIComponent(goto),
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

	$("#system_title_menu")[0].style.display = "block";

	var tmpObjectTree = _L("OBJECT TREE").replace(/(.{1})/g,"$1<br>");
	tmpObjectTree = tmpObjectTree.substring(0, tmpObjectTree.length-"<br>".length);

	$('#oidtree').addClass('ui-layout-west');
	$('#content_window').addClass('ui-layout-center');
	$('#system_title_bar').addClass('ui-layout-north');
	glayout = $('#frames').layout({
		north__size:                  40,
		north__slidable:              false,
		north__closable:              false,
		north__resizable:             false,
		west__size:                   oidplus_menu_width,
		west__spacing_closed:         20,
		west__togglerLength_closed:   230,
		west__togglerAlign_closed:    "center", // TODO: does not work! The text "OBJECT TREE" is still at the top and nearly cut off by the title bar!!!
		west__togglerContent_closed:  tmpObjectTree,
		west__togglerTip_closed:      _L("Open & Pin Menu"),
		west__sliderTip:              _L("Slide Open Menu"),
		west__slideTrigger_open:      "mouseover",
		center__maskContents:         true // IMPORTANT - enable iframe masking
	});

	$("#gotobox").addClass("mobilehidden");
	$("#languageBox").addClass("mobilehidden");
	$("#gotobox")[0].style.display = "block";
	$('#gotoedit').keypress(function(event) {
		var keycode = (event.keyCode ? event.keyCode : event.which);
		if (keycode == '13') {
			gotoButtonClicked();
		}
	});
});

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

function jumpToAnchor(anchor) {
	window.location.href = "#" + anchor;
}

function getCookie(cname) {
	// Source: https://www.w3schools.com/js/js_cookies.asp
	var name = cname + "=";
	var decodedCookie = decodeURIComponent(document.cookie);
	var ca = decodedCookie.split(';');
	for(var i = 0; i <ca.length; i++) {
		var c = ca[i];
		while (c.charAt(0) == ' ') {
			c = c.substring(1);
		}
		if (c.indexOf(name) == 0) {
			return c.substring(name.length, c.length);
		}
	}
	return undefined;
}

function setCookie(cname, cvalue, exdays, path) {
	var d = new Date();
	d.setTime(d.getTime() + (exdays*24*60*60*1000));
	var expires = exdays == 0 ? "" : "; expires="+d.toUTCString();
	document.cookie = cname + "=" + cvalue + expires + ";path=" + path + ";SameSite=" + samesite_policy;
}

function setLanguage(lngid) {
	setCookie('LANGUAGE', lngid, 0/*Until browser closes*/, location.pathname);

	$(".lng_flag").each(function(){
		$(this).addClass("picture_ghost");
	});
	$("#lng_flag_"+$.escapeSelector(lngid)).removeClass("picture_ghost");

	if (isInternetExplorer()) {
		// Internet Explorer has problems with sending new cookies to new AJAX requests, so we reload the page completely
		window.location.reload();
	} else {
		// TODO: Small detail: The "Go" button also needs to be re-translated
		reloadContent();
		mobileNavClose();
	}
}

function getCurrentLang() {
	// Note: If the argument "?lang=" is used, PHP will automatically set a Cookie, so it is OK when we only check for the cookie
	var lang = getCookie('LANGUAGE'); // do not translate
	return (typeof lang != 'undefined') ? lang : DEFAULT_LANGUAGE; // do not translate
}

function _L() {
	var args = Array.prototype.slice.call(arguments);
	var str = args.shift().trim();

	var tmp = "";
	if (typeof language_messages[getCurrentLang()] == 'undefined') { // do not translate
		tmp = str;
	} else {
		var msg = language_messages[getCurrentLang()][str];
		if (typeof msg != 'undefined') { // do not translate
			tmp = msg;
		} else {
			tmp = str;
		}
	}

	tmp = tmp.replace('###', language_tblprefix);

	var n = 1;
	while (args.length > 0) {
		var val = args.shift();
		tmp = tmp.replace("%"+n, val);
		n++;
	}

	tmp = tmp.replace("%%", "%");

	return tmp;
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

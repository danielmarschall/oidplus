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

/*jshint esversion: 6 */

// $('#html').jstree();

current_node = "";
popstate_running = false;

system_title = "";

String.prototype.explode = function (separator, limit) {
	// https://stackoverflow.com/questions/4514323/javascript-equivalent-to-php-explode
	const array = this.split(separator);
	if (limit !== undefined && array.length >= limit) {
		array.push(array.splice(limit - 1).join(separator));
	}
	return array;
};

String.prototype.htmlentities = function () {
	return this.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
};

String.prototype.html_entity_decode = function () {
	return $('<textarea />').html(this).text();
};

function combine_systemtitle_and_pagetitle(systemtitle, pagetitle) {
	if (systemtitle == pagetitle) {
		return systemtitle;
	} else {
		return systemtitle + ' - ' + pagetitle;
	}
}

function getTreeLoadURL() {
	var url = new URL(window.location.href);
	var goto = url.searchParams.get("goto");
	return (goto != null) ? "treeload.php?goto="+encodeURIComponent(goto) : "treeload.php";
}

function reloadContent() {
	// document.location = '?goto='+encodeURIComponent(current_node);
	openOidInPanel(current_node);
	$('#oidtree').jstree("refresh");
}

function openOidInPanel(id, unselect=false) {
	console.log("openOidInPanel("+id+", "+unselect+")");

	if (unselect) {
		$('#oidtree').jstree('deselect_all');
		$('#oidtree').jstree('select_node', id);
	}

	// $('#content_window').hide();
	document.title = "";
	$('#real_title').html("&nbsp;");
	$('#real_content').html("Loading...");
	$('#static_link').attr("href", "?goto="+encodeURIComponent(id));
	$('#static_link_desktop').attr("href", "index_desktop.php?goto="+encodeURIComponent(id));
	$('#static_link_mobile').attr("href", "index_mobile.php?goto="+encodeURIComponent(id));

	if (popstate_running) return; // To avoid that the jstree selection during popstate() won't trigger another page load

	// Normal opening of a description
	fetch('get_description.php?id='+encodeURIComponent(id))
	.then(function(response) {
		response.json()
		.then(function(data) {
			data.id = id;

			document.title = combine_systemtitle_and_pagetitle(system_title, data.title);
			var state = {
				"node_id":encodeURIComponent(id),
				"titleHTML":(data.icon ? '<img src="'+data.icon+'" width="48" height="48" alt="'+data.title.htmlentities()+'"> ' : '') +data.title.htmlentities(),
				"textHTML":data.text,
				"staticlinkHREF":"?goto="+encodeURIComponent(id),
				"staticlinkHREF_Desktop":"index_desktop.php?goto="+encodeURIComponent(id),
				"staticlinkHREF_Mobile":"index_mobile.php?goto="+encodeURIComponent(id)
			};
			if (current_node != id) {
				window.history.pushState(state, data.title, "?goto="+encodeURIComponent(id));
			} else {
				window.history.replaceState(state, data.title, "?goto="+encodeURIComponent(id));
			}

			if (data.icon) {
				$('#real_title').html('<img src="'+data.icon+'" width="48" height="48" alt="'+data.title.htmlentities()+'"> ' + data.title.htmlentities());
			} else {
				$('#real_title').html(data.title.htmlentities());
			}
			$('#real_content').html(data.text);
			document.title = combine_systemtitle_and_pagetitle(system_title, data.title);
			current_node = id;
		})
		.catch(function(error) {
			console.error(error);
		});
	})
	.catch(function(error) {
		console.error(error);
	});

	// $('#content_window').show();
}

function updateDesc() {
	$.ajax({
		url:"action.php",
		method:"POST",
		data: {
			action:"Update2",
			id:current_node,
			title:(document.getElementById('titleedit') ? document.getElementById('titleedit').value : null),
			//description:(document.getElementById('description') ? document.getElementById('description').value : null)
			description:tinyMCE.get('description').getContent()
		},
		success:function(data) {
			if (data != "OK") {
				alert("Error: " + data);
			} else {
				alert("Update OK");
				//reloadContent();
				$('#oidtree').jstree("refresh");
				var h1s = document.getElementsByTagName("h1");
				for (var i = 0; i < h1s.length; i++) {
					var h1 = h1s[i];
					h1.innerHTML = document.getElementById('titleedit').value.htmlentities();
				}
				document.title = combine_systemtitle_and_pagetitle(system_title, document.getElementById('titleedit').value);

				var mce = tinymce.get('description');
				if (mce != null) mce.isNotDirty = 1;
			}
		}
	});
}

function crudActionSendInvitation(origin, email) {
	// document.location = '?goto=oidplus:invite_ra$'+email+'$'+origin;

	openOidInPanel('oidplus:invite_ra$'+email+'$'+origin);

}

function crudActionInsert(parent) {
	$.ajax({
		url:"action.php",
		method:"POST",
		data:{
			action:"Insert",
			id:document.getElementById('id').value,
			ra_email:document.getElementById('ra_email').value,
			asn1ids:(document.getElementById('asn1ids') ? document.getElementById('asn1ids').value : null),
			iris:(document.getElementById('iris') ? document.getElementById('iris').value : null),
			confidential:(document.getElementById('hide') ? document.getElementById('hide').checked : null),
			parent:parent
		},
		success:function(data) {
			if (data == "OK") {
				//alert("Insert OK");
				reloadContent();
				// TODO: auf reloadContent() verzichten. stattdessen nur tree links aktualisieren, und rechts eine neue zeile zur tabelle hinzufügen
			} else if (data == "OK (RaNotInDatabase)") {
				if (confirm("Update OK. However, the email address you have entered ("+document.getElementById('ra_email').value+") is not in our system. Do you want to send an invitation, so that the RA can register an account to manage their OIDs?")) {
					crudActionSendInvitation(parent, document.getElementById('ra_email').value);
				} else {
					reloadContent();
					// TODO: auf reloadContent() verzichten. stattdessen nur tree links aktualisieren, und rechts eine neue zeile zur tabelle hinzufügen
				}
			} else {
				alert("Error: " + data);
			}
		}
	});
}

function crudActionUpdate(id, parent) {
	$.ajax({
		url:"action.php",
		method:"POST",
		data: {
			action:"Update",
			id:encodeURIComponent(id),
			ra_email:document.getElementById('ra_email_'+id).value,
			asn1ids:(document.getElementById('asn1ids_'+id) ? document.getElementById('asn1ids_'+id).value : null),
			iris:(document.getElementById('iris_'+id) ? document.getElementById('iris_'+id).value : null),
			confidential:(document.getElementById('hide_'+id) ? document.getElementById('hide_'+id).checked : null),
			parent:parent
		},
		success:function(data) {
			if (data == "OK") {
				alert("Update OK");
				// reloadContent();
				$('#oidtree').jstree("refresh");
			} else if (data == "OK (RaNotInDatabase)") {
				if (confirm("Update OK. However, the email address you have entered ("+document.getElementById('ra_email_'+id).value+") is not in our system. Do you want to send an invitation, so that the RA can register an account to manage their OIDs?")) {
					crudActionSendInvitation(parent, document.getElementById('ra_email_'+id).value);
				} else {
					// reloadContent();
					$('#oidtree').jstree("refresh");
				}
			} else {
				alert("Error: " + data);
			}
		}
	});
}

function crudActionDelete(id, parent) {
	if(!window.confirm("Are you sure that you want to delete "+id+"?")) return false;

	$.ajax({
		url:"action.php",
		method:"POST",
		data: {
			action:"Delete",
			id:encodeURIComponent(id),
			parent:parent
		},
		success:function(data) {
			if (data != "OK") {
				alert("Error: " + data);
			} else {
				reloadContent();
				// TODO: auf reloadContent() verzichten. stattdessen nur tree links aktualisieren, und rechts die zeile aus der tabelle löschen
			}
		}
	});
}

function deleteRa(email, goto) {
	if(!window.confirm("Are you really sure that you want to delete "+email+"? (The OIDs stay active)")) return false;

	$.ajax({
		url:"action.php",
		method:"POST",
		data: {
			action:"delete_ra",
			email:email,
		},
		success:function(data) {
			if (data != "OK") {
				alert("Error: " + data);
			} else {
				alert("Done.");
				if (goto != null) document.location = '?goto=' + goto;
				// reloadContent();
			}
		}
	});
}

function openAndSelectNode(childID, parentID) {
	if ($('#oidtree').jstree(true).get_node(parentID)) {
		$('#oidtree').jstree('open_node', '#'+parentID, function(e, data) { // open parent node
			if ($('#oidtree').jstree(true).get_node(childID)) { // is the child there?
				$('#oidtree').jstree('deselect_all').jstree('select_node', '#'+childID); // select it
			} else {
				// This can happen if the content page contains brand new items which are not in the treeview yet
				document.location = "?goto="+encodeURIComponent(childID);
			}
		}, true);
	} else {
		// This should usually not happen
		document.location = "?goto="+encodeURIComponent(childID);
	}
}

$(window).on("popstate", function(e) {
	popstate_running = true;
	try {
		var data = e.originalEvent.state;

		current_node = data.node_id;
		$('#oidtree').jstree('deselect_all').jstree('select_node', data.node_id); // TODO: search and open the nodes
		$('#real_title').html(data.titleHTML);
		$('#real_content').html(data.textHTML);
		$('#static_link').attr("href", data.staticlinkHREF);
		$('#static_link_desktop').attr("href", data.staticlinkHREF_Desktop);
		$('#static_link_mobile').attr("href", data.staticlinkHREF_Mobile);
		document.title = combine_systemtitle_and_pagetitle(system_title, data.titleHTML.html_entity_decode());
	} catch (err) {
		popstate_running = false;
	} finally {
		popstate_running = false;
	}
});

$(document).ready(function () {
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
				return true; // allow select
			}
		},
	})
	.on('ready.jstree', function (e, data) {
		var url = new URL(window.location.href);
		var goto = url.searchParams.get("goto");
		if (goto == null) goto = "oidplus:system"; // the page was not called with ?goto=...

		// By setting current_node, select_node() will not cause get_description.php to load (since we already loaded the first static content via PHP, for search engines mainly)
		// But then we need to set the history state manually
		current_node = goto;
		window.history.replaceState({
			"node_id":encodeURIComponent(goto),
			"titleHTML":$('#real_title').html(),
			"textHTML":$('#real_content').html(),
			"staticlinkHREF":"?goto="+encodeURIComponent(goto),
			"staticlinkHREF_Desktop":"index_desktop.php?goto="+encodeURIComponent(goto),
			"staticlinkHREF_Mobile":"index_mobile.php?goto="+encodeURIComponent(goto)
		}, $('#real_title').html(), '?goto='+encodeURIComponent(goto));

		if (goto != null) data.instance.select_node([goto]);
	})
	.on('select_node.jstree', function (node, selected, event) {
		if (oidplusMobile()) {
			document.getElementById("oidtree").style.display = "none";
			document.getElementById("system_title_menu").classList.remove("active");
		}

		var id = selected.node.id;
		if (current_node != id) {
			openOidInPanel(id);
		}
	});

	// --- Layout

	if (oidplusMobile()) {
		document.getElementById('oidtree').style.display = "none";
		document.getElementById('system_title_menu').style.visibility = "visible";
	} else {
		$('body').layout({
			north__size: 40,
			north__slidable: false,
			north__closable: false,
			north__resizable: false,
			west__size:			450,
			west__spacing_closed:		20,
			west__togglerLength_closed:	230,
			west__togglerAlign_closed:	"top",
			west__togglerContent_closed:"O<br>B<br>J<br>E<br>C<br>T<br><br>T<BR>R<BR>E<BR>E",
			west__togglerTip_closed:	"Open & Pin Menu",
			west__sliderTip:		"Slide Open Menu",
			west__slideTrigger_open:	"mouseover",
			center__maskContents:		true // IMPORTANT - enable iframe masking
		});
	}
});

function oidplusMobile() {
	return document.getElementsByClassName("ui-layout-center").length == 0;
}

function mobileNavButtonClick() {
	var x = document.getElementById("oidtree");
	if (x.style.display === "block") {
		x.style.display = "none";
		document.getElementById("system_title_menu").classList.remove("active");
	} else {
		x.style.display = "block";
		document.getElementById("system_title_menu").classList.add("active");
	}
}

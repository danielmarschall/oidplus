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

function getMeta(metaName) {
	const metas = document.getElementsByTagName('meta');

	for (let i = 0; i < metas.length; i++) {
		if (metas[i].getAttribute('name') === metaName) {
			return metas[i].getAttribute('content');
		}
	}

	return '';
}

function getOidPlusSystemTitle() {
	return getMeta('OIDplus-SystemTitle')
}

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
	return (goto != null) ? "ajax.php?action=tree_load&goto="+encodeURIComponent(goto)
	                      : "ajax.php?action=tree_load";
}

function reloadContent() {
	// document.location = "?goto="+encodeURIComponent(current_node);
	openOidInPanel(current_node, false);
	$('#oidtree').jstree("refresh");
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

function openOidInPanel(id, reselect=false) {
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
					data:{
						action:"tree_search",
						search:id
					},
					error:function(jqXHR, textStatus, errorThrown) {
						console.error("Error: " + errorThrown);
					},
					success:function(data) {
						if ("error" in data) {
							console.error(data);
						} else if ((data instanceof Array) && (data.length > 0)) {
							x_rec(data, 0);
						} else {
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

	document.title = "";
	$('#real_title').html("&nbsp;");
	$('#real_content').html("Loading...");
	$('#static_link').attr("href", "index.php?goto="+encodeURIComponent(id));
	$('#static_link_desktop').attr("href", "index_desktop.php?goto="+encodeURIComponent(id));
	$('#static_link_mobile').attr("href", "index_mobile.php?goto="+encodeURIComponent(id));

	// Normal opening of a description
	fetch('ajax.php?action=get_description&id='+encodeURIComponent(id))
	.then(function(response) {
		response.json()
		.then(function(data) {
			if ("error" in data) {
				alert("Failed to load content: " + data.error);
				console.error(data.error);
				return;
			}

			data.id = id;

			document.title = combine_systemtitle_and_pagetitle(getOidPlusSystemTitle(), data.title);
			var state = {
				"node_id":id,
				"titleHTML":(data.icon ? '<img src="'+data.icon+'" width="48" height="48" alt="'+data.title.htmlentities()+'"> ' : '') + data.title.htmlentities(),
				"textHTML":data.text,
				"staticlinkHREF":"index.php?goto="+encodeURIComponent(id),
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
			document.title = combine_systemtitle_and_pagetitle(getOidPlusSystemTitle(), data.title);
			current_node = id;
		})
		.catch(function(error) {
			alert("Failed to load content: " + error);
			console.error(error);
		});
	})
	.catch(function(error) {
		alert("Failed to load content: " + error);
		console.error(error);
	});
}

function updateDesc() {
	$.ajax({
		url:"ajax.php",
		method:"POST",
		data: {
			action:"Update2",
			id:current_node,
			title:(document.getElementById('titleedit') ? document.getElementById('titleedit').value : null),
			//description:(document.getElementById('description') ? document.getElementById('description').value : null)
			description:tinyMCE.get('description').getContent()
		},
		error:function(jqXHR, textStatus, errorThrown) {
			alert("Error: " + errorThrown);
		},
		success:function(data) {
			if ("error" in data) {
				alert("Error: " + data.error);
			} else if (data.status == 0) {
				alert("Update OK");
				//reloadContent();
				$('#oidtree').jstree("refresh");
				var h1s = document.getElementsByTagName("h1");
				for (var i = 0; i < h1s.length; i++) {
					var h1 = h1s[i];
					h1.innerHTML = document.getElementById('titleedit').value.htmlentities();
				}
				document.title = combine_systemtitle_and_pagetitle(getOidPlusSystemTitle(), document.getElementById('titleedit').value);

				var mce = tinymce.get('description');
				if (mce != null) mce.isNotDirty = 1;
			} else {
				alert("Error: " + data.error);
			}
		}
	});
}

function crudActionSendInvitation(origin, email) {
	// document.location = "?goto=oidplus:invite_ra$"+encodeURIComponent(email)+"$"+encodeURIComponent(origin);
	openOidInPanel('oidplus:invite_ra$'+email+'$'+origin, false);
}

function crudActionInsert(parent) {
	$.ajax({
		url:"ajax.php",
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
		error:function(jqXHR, textStatus, errorThrown) {
			alert("Error: " + errorThrown);
		},
		success:function(data) {
			if ("error" in data) {
				alert("Error: " + data.error);
			} else if (data.status == 0) {
				//alert("Insert OK");
				reloadContent();
				// TODO: auf reloadContent() verzichten. stattdessen nur tree links aktualisieren, und rechts eine neue zeile zur tabelle hinzufügen
			} else if (data.status == 1) {
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
		url:"ajax.php",
		method:"POST",
		data: {
			action:"Update",
			id:id,
			ra_email:document.getElementById('ra_email_'+id).value,
			asn1ids:(document.getElementById('asn1ids_'+id) ? document.getElementById('asn1ids_'+id).value : null),
			iris:(document.getElementById('iris_'+id) ? document.getElementById('iris_'+id).value : null),
			confidential:(document.getElementById('hide_'+id) ? document.getElementById('hide_'+id).checked : null),
			parent:parent
		},
		error:function(jqXHR, textStatus, errorThrown) {
			alert("Error: " + errorThrown);
		},
		success:function(data) {
			if ("error" in data) {
				alert("Error: " + data.error);
			} else if (data.status == 0) {
				alert("Update OK");
				// reloadContent();
				$('#oidtree').jstree("refresh");
			} else if (data.status == 1) {
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
		url:"ajax.php",
		method:"POST",
		data: {
			action:"Delete",
			id:id,
			parent:parent
		},
		error:function(jqXHR, textStatus, errorThrown) {
			alert("Error: " + errorThrown);
		},
		success:function(data) {
			if ("error" in data) {
				alert("Error: " + data.error);
			} else if (data.status == 0) {
				reloadContent();
				// TODO: auf reloadContent() verzichten. stattdessen nur tree links aktualisieren, und rechts die zeile aus der tabelle löschen
			} else {
				alert("Error: " + data.error);
			}
		}
	});
}

function deleteRa(email, goto) {
	if(!window.confirm("Are you really sure that you want to delete "+email+"? (The OIDs stay active)")) return false;

	$.ajax({
		url:"ajax.php",
		method:"POST",
		data: {
			action:"delete_ra",
			email:email,
		},
		error:function(jqXHR, textStatus, errorThrown) {
			alert("Error: " + errorThrown);
		},
		success:function(data) {
			if ("error" in data) {
				alert("Error: " + data.error);
			} else if (data.status == 0) {
				alert("Done.");
				if (goto != null) document.location = "?goto="+encodeURIComponent(goto);
				// reloadContent();
			} else {
				alert("Error: " + data.error);
			}
		}
	});
}

// This function opens the "parentID" node, and then selects the "childID" node (which should be beneath the parent node)
function openAndSelectNode(childID, parentID) {
	if ($('#oidtree').jstree(true).get_node(parentID)) {
		$('#oidtree').jstree('open_node', parentID, function(e, data) { // open parent node
			if ($('#oidtree').jstree(true).get_node(childID)) { // is the child there?
				$('#oidtree').jstree('deselect_all').jstree('select_node', childID); // select it
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
		$('#oidtree').jstree('deselect_all').jstree('select_node', data.node_id);
		$('#real_title').html(data.titleHTML);
		$('#real_content').html(data.textHTML);
		$('#static_link').attr("href", data.staticlinkHREF);
		$('#static_link_desktop').attr("href", data.staticlinkHREF_Desktop);
		$('#static_link_mobile').attr("href", data.staticlinkHREF_Mobile);
		document.title = combine_systemtitle_and_pagetitle(getOidPlusSystemTitle(), data.titleHTML.html_entity_decode());
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

		// By setting current_node, select_node() will not cause ajax.php?action=get_description to load (since we already loaded the first static content via PHP, for search engines mainly)
		// But then we need to set the history state manually
		current_node = goto;
		window.history.replaceState({
			"node_id":goto,
			"titleHTML":$('#real_title').html(),
			"textHTML":$('#real_content').html(),
			"staticlinkHREF":"index.php?goto="+encodeURIComponent(goto),
			"staticlinkHREF_Desktop":"index_desktop.php?goto="+encodeURIComponent(goto),
			"staticlinkHREF_Mobile":"index_mobile.php?goto="+encodeURIComponent(goto)
		}, $('#real_title').html(), "?goto="+encodeURIComponent(goto));

		if (goto != null) data.instance.select_node([goto]);
	})
	.on('select_node.jstree', function (node, selected, event) {
		if (oidplusMobile()) {
			$("#oidtree").hide("slow");
			$("#system_title_menu").removeClass("active");
		}

		var id = selected.node.id;
		if ((!popstate_running) && (current_node != id)) {
			openOidInPanel(id, false);
		}
	});

	// --- Layout

	if (oidplusMobile()) {
		document.getElementById('oidtree').style.display = "none";
		document.getElementById('system_title_menu').style.visibility = "visible";
	} else {
		$('body').layout({
			north__size:                  40,
			north__slidable:              false,
			north__closable:              false,
			north__resizable:             false,
			west__size:                   450,
			west__spacing_closed:         20,
			west__togglerLength_closed:   230,
			west__togglerAlign_closed:    "top",
			west__togglerContent_closed:  "O<br>B<br>J<br>E<br>C<br>T<br><br>T<BR>R<BR>E<BR>E",
			west__togglerTip_closed:      "Open & Pin Menu",
			west__sliderTip:              "Slide Open Menu",
			west__slideTrigger_open:      "mouseover",
			center__maskContents:         true // IMPORTANT - enable iframe masking
		});
	}
});

function oidplusMobile() {
	return document.getElementsByClassName("ui-layout-center").length == 0;
}

function mobileNavButtonClick(sender) {
	if ($("#oidtree").is(":visible")) {
		$("#oidtree").hide("slow");
		$("#system_title_menu").removeClass("active");
	} else {
		$("#oidtree").show("slow");
		$("#system_title_menu").addClass("active");
	}
}

function mobileNavButtonHover(sender) {
	sender.classList.toggle("hover");
}

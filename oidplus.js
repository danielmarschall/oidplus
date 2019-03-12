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
}

String.prototype.html_entity_decode = function () {
	return $('<textarea />').html(this).text();
}

function getTreeLoadURL() {
	var url = new URL(window.location.href);
	var goto = url.searchParams.get("goto");
	return (goto != null) ? "treeload.php?goto="+encodeURI(goto) : "treeload.php";
}

function reloadContent() {
	// document.location = '?goto='+encodeURI(current_node);
	openOidInPanel(current_node);
	$('#oidtree').jstree("refresh");
}

function openOidInPanel(id, unselect=false) {
	console.log("openOidInPanel("+id+", "+unselect+")");

	if (unselect) {
		$('#oidtree').jstree('deselect_all');
	}

	// $('#content_window').hide();
	document.title = "";
	$('#real_title').html("&nbsp;");
	$('#real_content').html("Loading...");
	$('#static_link').attr("href", "?goto="+encodeURI(id));

	if (popstate_running) return; // To avoid that the jstree selection during popstate() won't trigger another page load

	// Normal opening of a description
	fetch('get_description.php?id='+id)
	.then(function(response) {
		response.json()
		.then(function(data) {
			data.id = id;

			document.title = system_title + data.title;
			var state = {
				"node_id":id,
				"titleHTML":data.title.htmlentities(),
				"textHTML":data.text,
				"staticlinkHREF":"?goto="+encodeURI(id)
			};
			if (current_node != id) {
				window.history.pushState(state, data.title, "?goto="+encodeURI(id));
			} else {
				window.history.replaceState(state, data.title, "?goto="+encodeURI(id));
			}

			$('#real_title').html(data.title.htmlentities());
			$('#real_content').html(data.text);
			document.title = system_title + data.title;
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
				document.title = system_title + document.getElementById('titleedit').value;

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
			id:id,
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
			id:id,
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

function raLogout(email) {
	if(!window.confirm("Are you sure that you want to logout?")) return false;

	$.ajax({
		url:"action.php",
		method:"POST",
		data: {
			action:"ra_logout",
			email:email,
		},
		success:function(data) {
			if (data != "OK") {
				alert("Error: " + data);
			} else {
				document.location = '?goto=oidplus:system';
				// reloadContent();
			}
		}
	});
}

function raLogin(email, password) {
	$.ajax({
		url:"action.php",
		method:"POST",
		data: {
			action:"ra_login",
			email:email,
			password:password,
			captcha: document.getElementsByClassName('g-recaptcha').length > 0 ? grecaptcha.getResponse() : null
		},
		success:function(data) {
			if (data != "OK") {
				alert("Error: " + data);
				grecaptcha.reset();
			} else {
				document.location = '?goto=oidplus:system';
				// reloadContent();
			}
		}
	});
}

function adminLogin(password) {
	$.ajax({
		url:"action.php",
		method:"POST",
		data: {
			action:"admin_login",
			password:password,
			captcha: document.getElementsByClassName('g-recaptcha').length > 0 ? grecaptcha.getResponse() : null
		},
		success:function(data) {
			if (data != "OK") {
				alert("Error: " + data);
				grecaptcha.reset();
			} else {
				document.location = '?goto=oidplus:system';
				// reloadContent();
			}
		}
	});
}

function adminLogout() {
	if(!window.confirm("Are you sure that you want to logout?")) return false;

	$.ajax({
		url:"action.php",
		method:"POST",
		data: {
			action:"admin_logout",
		},
		success:function(data) {
			if (data != "OK") {
				alert("Error: " + data);
			} else {
				document.location = '?goto=oidplus:system';
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
				document.location = "?goto="+encodeURI(childID);
			}
		}, true);
	} else {
		// This should usually not happen
		document.location = "?goto="+encodeURI(childID);
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
		document.title = system_title + data.titleHTML.html_entity_decode();
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
			var id = node.id;

			if (id.explode('$', 2)[0] == 'oidplus:dummy') {
				// Nothing to do here
				// node can either bei "ra:<emailAddress" or "ra:admin"
				// TODO: it would be much better if the mouse cursor would not show a hand
			} else if (id.explode('$', 2)[0] == 'oidplus:logout') {
				var email = id.explode('$', 2)[1];
				if (email == 'admin') {
					adminLogout();
				} else {
					raLogout(email);
				}
				return false;
			} else if (id.explode('$', 2)[0] == 'oidplus:raroot') {
				// Function "Jump to RA root"
				$('#content_window').html('');
				document.location = '?goto='+encodeURI(id.explode('$', 2)[1]);
			} else {
				return true;
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
		window.history.replaceState({"node_id":goto, "titleHTML":$('#real_title').html(), "textHTML":$('#real_content').html(), "staticlinkHREF":"?goto="+encodeURI(goto)}, $('#real_title').html(), '?goto='+encodeURI(goto));

		if (goto != null) data.instance.select_node([goto]);
	})
	.on('select_node.jstree', function (node, selected, event) {
		var id = selected.node.id
		if (current_node != id) {
			openOidInPanel(id);
		}
	});

	// --- Layout

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
});

function inviteFormOnSubmit() {
    $.ajax({
      url: "action.php",
      type: "POST",
      data: {
        action: "invite_ra",
        email: $("#email").val(),
        captcha: document.getElementsByClassName('g-recaptcha').length > 0 ? grecaptcha.getResponse() : null
      },
      success: function(data) {
                        if (data != "OK") {
                                alert("Error: " + data);
				grecaptcha.reset();
                        } else {
				alert("The RA has been invited via email.");
                                document.location = '?goto='+$("#origin").val();
                                //reloadContent();
                        }
      }
  });
  return false;
}

function activateRaFormOnSubmit() {
    $.ajax({
      url: "action.php",
      type: "POST",
      data: {
        action: "activate_ra",
        email: $("#email").val(),
        auth: $("#auth").val(),
        password1: $("#password1").val(),
        password2: $("#password2").val(),
        timestamp: $("#timestamp").val()
      },
      success: function(data) {
                        if (data != "OK") {
                                alert("Error: " + data);
                        } else {
				alert("Registration successful! You can now log in.");
                                document.location = '?goto=oidplus:login';
                                //reloadContent();
                        }
      }
  });
  return false;
}



function forgotPasswordFormOnSubmit() {
    $.ajax({
      url: "action.php",
      type: "POST",
      data: {
        action: "forgot_password",
        email: $("#email").val(),
        captcha: document.getElementsByClassName('g-recaptcha').length > 0 ? grecaptcha.getResponse() : null
      },
      success: function(data) {
                        if (data != "OK") {
                                alert("Error: " + data);
				grecaptcha.reset();
                        } else {
				alert("E-Mail sent.");
                                document.location = '?goto=oidplus:login';
                                //reloadContent();
                        }
      }
  });
  return false;
}

function resetPasswordFormOnSubmit() {
    $.ajax({
      url: "action.php",
      type: "POST",
      data: {
        action: "reset_password",
        email: $("#email").val(),
        auth: $("#auth").val(),
        password1: $("#password1").val(),
        password2: $("#password2").val(),
        timestamp: $("#timestamp").val()
      },
      success: function(data) {
                        if (data != "OK") {
                                alert("Error: " + data);
                        } else {
				alert("Password sucessfully changed. You can now log in.");
                                document.location = '?goto=oidplus:login';
                                //reloadContent();
                        }
      }
  });
  return false;
}


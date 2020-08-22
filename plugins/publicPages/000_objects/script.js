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

function crudActionInsert(parent) {
	$.ajax({
		url:"ajax.php",
		method:"POST",
		data:{
			plugin:"1.3.6.1.4.1.37476.2.5.2.4.1.0",
			action:"Insert",
			id:document.getElementById('id').value,
			ra_email:document.getElementById('ra_email').value,
			comment:document.getElementById('comment').value,
			asn1ids:(document.getElementById('asn1ids') ? document.getElementById('asn1ids').value : null),
			iris:(document.getElementById('iris') ? document.getElementById('iris').value : null),
			confidential:(document.getElementById('hide') ? document.getElementById('hide').checked : null),
			weid:(document.getElementById('weid') ? document.getElementById('weid').checked : null),
			parent:parent
		},
		error:function(jqXHR, textStatus, errorThrown) {
			alert(_L("Error: %1",errorThrown));
		},
		success:function(data) {
			if ("error" in data) {
				alert(_L("Error: %1",data.error));
			} else if (data.status == 0/*OK*/) {
				//alert(_L("Insert OK"));
				reloadContent();
				// TODO: auf reloadContent() verzichten. stattdessen nur tree links aktualisieren, und rechts eine neue zeile zur tabelle hinzufügen
			} else if (data.status == 1/*RaNotExisting*/) {
				if (confirm(_L("Update OK. However, the email address you have entered (%1) is not in our system. Do you want to send an invitation, so that the RA can register an account to manage their OIDs?",document.getElementById('ra_email').value))) {
					crudActionSendInvitation(parent, document.getElementById('ra_email').value);
				} else {
					reloadContent();
					// TODO: auf reloadContent() verzichten. stattdessen nur tree links aktualisieren, und rechts eine neue zeile zur tabelle hinzufügen
				}
			} else if (data.status == 2/*RaNotExistingNoInvitation*/) {
				//alert(_L("Insert OK"));
				reloadContent();
				// TODO: auf reloadContent() verzichten. stattdessen nur tree links aktualisieren, und rechts eine neue zeile zur tabelle hinzufügen
			} else {
				alert(_L("Error: %1",data));
			}
		}
	});
}

function crudActionUpdate(id, parent) {
	$.ajax({
		url:"ajax.php",
		method:"POST",
		data: {
			plugin:"1.3.6.1.4.1.37476.2.5.2.4.1.0",
			action:"Update",
			id:id,
			ra_email:document.getElementById('ra_email_'+id).value,
			comment:document.getElementById('comment_'+id).value,
			asn1ids:(document.getElementById('asn1ids_'+id) ? document.getElementById('asn1ids_'+id).value : null),
			iris:(document.getElementById('iris_'+id) ? document.getElementById('iris_'+id).value : null),
			confidential:(document.getElementById('hide_'+id) ? document.getElementById('hide_'+id).checked : null),
			parent:parent
		},
		error:function(jqXHR, textStatus, errorThrown) {
			alert(_L("Error: %1",errorThrown));
		},
		success:function(data) {
			if ("error" in data) {
				alert(_L("Error: %1",data.error));
			} else if (data.status == 0/*OK*/) {
				alert(_L("Update OK"));
				// reloadContent();
				$('#oidtree').jstree("refresh");
			} else if (data.status == 1/*RaNotExisting*/) {
				if (confirm(_L("Update OK. However, the email address you have entered (%1) is not in our system. Do you want to send an invitation, so that the RA can register an account to manage their OIDs?",document.getElementById('ra_email_'+id).value))) {
					crudActionSendInvitation(parent, document.getElementById('ra_email_'+id).value);
				} else {
					// reloadContent();
					$('#oidtree').jstree("refresh");
				}
			} else if (data.status == 2/*RaNotExistingNoInvitation*/) {
				alert(_L("Update OK"));
				// reloadContent();
				$('#oidtree').jstree("refresh");
			} else {
				alert(_L("Error: %1",data));
			}
		}
	});
}

function crudActionDelete(id, parent) {
	if(!window.confirm(_L("Are you sure that you want to delete %1?",id))) return false;

	$.ajax({
		url:"ajax.php",
		method:"POST",
		data: {
			plugin:"1.3.6.1.4.1.37476.2.5.2.4.1.0",
			action:"Delete",
			id:id,
			parent:parent
		},
		error:function(jqXHR, textStatus, errorThrown) {
			alert(_L("Error: %1",errorThrown));
		},
		success:function(data) {
			if ("error" in data) {
				alert(_L("Error: %1",data.error));
			} else if (data.status == 0) {
				reloadContent();
				// TODO: auf reloadContent() verzichten. stattdessen nur tree links aktualisieren, und rechts die zeile aus der tabelle löschen
			} else {
				alert(_L("Error: %1",data.error));
			}
		}
	});
}

function updateDesc() {
	$.ajax({
		url:"ajax.php",
		method:"POST",
		data: {
			plugin:"1.3.6.1.4.1.37476.2.5.2.4.1.0",
			action:"Update2",
			id:current_node,
			title:(document.getElementById('titleedit') ? document.getElementById('titleedit').value : null),
			//description:(document.getElementById('description') ? document.getElementById('description').value : null)
			description:tinyMCE.get('description').getContent()
		},
		error:function(jqXHR, textStatus, errorThrown) {
			alert(_L("Error: %1",errorThrown));
		},
		success:function(data) {
			if ("error" in data) {
				alert(_L("Error: %1",data.error));
			} else if (data.status == 0) {
				alert(_L("Update OK"));
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
				alert(_L("Error: %1",data.error));
			}
		}
	});
}

function crudActionSendInvitation(origin, email) {
	// window.location.href = "?goto=oidplus:invite_ra$"+encodeURIComponent(email)+"$"+encodeURIComponent(origin);
	openOidInPanel('oidplus:invite_ra$'+email+'$'+origin, false);
}

function frdl_weid_change() {
	var from_base = 36;
	var from_control = "#weid";
	var to_base = 10;
	var to_control = "#id";

	var inp = $(from_control).val().trim();
	if (inp == "") {
		$(to_control).val("");
	} else {
		var x = BigNumber(inp, from_base);
		if (isNaN(x)) {
			$(to_control).val("");
		} else {
			$(to_control).val(x.toString(to_base));
		}
	}
}

function frdl_oidid_change() {
	var from_base = 10;
	var from_control = "#id";
	var to_base = 36;
	var to_control = "#weid";

	var inp = $(from_control).val().trim();
	if (inp == "") {
		$(to_control).val("");
	} else {
		var x = BigNumber(inp, from_base);
		if (isNaN(x)) {
			$(to_control).val("");
		} else {
			$(to_control).val(x.toString(to_base));
		}
	}
}
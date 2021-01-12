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

function cbRemoveTinyMCE(selector) {
	if ((typeof tinymce == "undefined") || (tinymce == null)) {
		// This should not happen
		console.error("cbRemoveTinyMCE(): TinyMCE is missing?!");
		return;
	}
	tinymce.remove(selector); // here, we need a "#" (selector contains "#")
}

function cbQueryTinyMCE(selector) {
	// tinymce.get() does NOT allow a "#" prefix (but selector contains "#"). So we remove it
	selector = selector.replace('#', '');

	if ((typeof tinymce == "undefined") || (tinymce == null) || (tinymce.get(selector) == null)) {
		// This should not happen
		console.error("cbQueryTinyMCE(): TinyMCE is missing?!");
		return true;
	}
	if (tinymce.get(selector).isDirty()) {
		return confirm(_L("Attention: Do you want to continue without saving?"));
	} else {
		return true;
	}
}

function checkMissingOrDoubleASN1(oid) {
	var suffix = (oid == '') ? '' : '_'+oid;

	//var curinput = $('#asn1ids'+suffix').value;
	var curinput = $('input[id="asn1ids'+suffix+'"]')[0];

	if (curinput.value == '') {
		// TODO: maybe we should only warn if ASN.1, IRI and Comment are all null, not just ASN.1?
		if (!confirm(_L("Attention: You did not enter an ASN.1 identifier. Are you sure that you want to continue?"))) return false;
	}

	var ary = curinput.value.split(',');

	for (var i=0; i<ary.length; i++) {
		var toCheck = ary[i];
		var bry = $('input[id^="asn1ids_"]');
		for (var j=0; j<bry.length; j++) {
			if (bry[j].id != 'asn1ids'+suffix) {
				var cry = bry[j].value.split(',');
				for (var k=0; k<cry.length; k++) {
					var candidate = cry[k];
					if ((toCheck != "") && (candidate != "") && (toCheck == candidate)) {
						if (!confirm(_L("Warning! ASN.1 ID %1 is already used in another OID. Continue?", candidate))) return false;
					}
				}
			}
		}
	}

	return true;
}

function crudActionInsert(parent) {
	if (parent.startsWith('oid:') && !checkMissingOrDoubleASN1('')) return;

	$.ajax({
		url:"ajax.php",
		method:"POST",
		data:{
			csrf_token:csrf_token,
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
			} else if (data.status >= 0) {
				if (data.status == 0/*OK*/) {
					if (confirm(_L("Insert OK")+".\n\n"+_L("Do you want to open the newly created object now?"))) {
						openAndSelectNode(data.inserted_id, parent);
						return;
					}
				}

				if ((data.status & 1) == 1/*RaNotExisting*/) {
					if (confirm(_L("Insert OK. However, the email address you have entered (%1) is not in our system. Do you want to send an invitation, so that the RA can register an account to manage their OIDs?",document.getElementById('ra_email').value))) {
						crudActionSendInvitation(parent, document.getElementById('ra_email_'+id).value);
						return;
					} else {
						if (confirm(_L("Do you want to open the newly created object now?"))) {
							openAndSelectNode(data.inserted_id, parent);
							return;
						}
					}
				}

				if ((data.status & 2) == 2/*RaNotExistingNoInvitation*/) {
					if (confirm(_L("Insert OK.")+"\n\n"+_L("Do you want to open the newly created object now?"))) {
						openAndSelectNode(data.inserted_id, parent);
						return;
					}
				}

				if ((data.status & 4) == 4/*IsWellKnownOID*/) {
					if (confirm(_L("Insert OK. However, the RA and the ASN.1 and IRI identifiers were overwritten, because this OID is a well-known OID.")+"\n\n"+L("Do you want to open the newly created object now?"))) {
						openAndSelectNode(data.inserted_id, parent);
						return;
					}
				}

				// TODO: Don't use reloadContent(); instead add a node at the tree at the left add at the right add a new row to the table
				reloadContent();
			} else {
				alert(_L("Error: %1",data));
			}
		}
	});
}

function crudActionUpdate(id, parent) {
	if (id.startsWith('oid:') && !checkMissingOrDoubleASN1(id)) return;

	$.ajax({
		url:"ajax.php",
		method:"POST",
		data: {
			csrf_token:csrf_token,
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
			} else if (data.status >= 0) {
				if (data.status == 0/*OK*/) {
					alert(_L("Update OK"));
				}

				if ((data.status & 1) == 1/*RaNotExisting*/) {
					if (confirm(_L("Update OK. However, the email address you have entered (%1) is not in our system. Do you want to send an invitation, so that the RA can register an account to manage their OIDs?",document.getElementById('ra_email_'+id).value))) {
						crudActionSendInvitation(parent, document.getElementById('ra_email_'+id).value);
						return;
					}
				}

				if ((data.status & 2) == 2/*RaNotExistingNoInvitation*/) {
					alert(_L("Update OK"));
				}

				if ((data.status & 4) == 4/*IsWellKnownOID*/) {
					alert(_L("Update OK. However, the RA and the ASN.1 and IRI identifiers were overwritten, because this OID is a well-known OID."));
				}

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
			csrf_token:csrf_token,
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
			} else if (data.status >= 0) {
				reloadContent();
				// TODO: Don't use reloadContent(); instead delete node at the left tree and remove the row at the right table
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
			csrf_token:csrf_token,
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
			} else if (data.status >= 0) {
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
				if (mce != null) mce.setDirty(false);
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
			$(to_control).val(x.toString(to_base).toUpperCase());
		}
	}
}

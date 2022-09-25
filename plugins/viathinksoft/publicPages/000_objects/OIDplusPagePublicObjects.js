/*
 * OIDplus 2.0
 * Copyright 2019 - 2021 Daniel Marschall, ViaThinkSoft
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

var OIDplusPagePublicObjects = {

	oid: "1.3.6.1.4.1.37476.2.5.2.4.1.0",

	cbRemoveTinyMCE: function(selector) {
		if ((typeof tinymce == "undefined") || (tinymce == null)) {
			// This should not happen
			console.error("OIDplusPagePublicObjects.cbRemoveTinyMCE(): TinyMCE is missing?!");
			return;
		}
		tinymce.remove(selector); // here, we need a "#" (selector contains "#")
	},

	cbQueryTinyMCE: function(selector) {
		// tinymce.get() does NOT allow a "#" prefix (but selector contains "#"). So we remove it
		selector = selector.replace('#', '');

		if ((typeof tinymce == "undefined") || (tinymce == null) || (tinymce.get(selector) == null)) {
			// This should not happen
			console.error("OIDplusPagePublicObjects.cbQueryTinyMCE(): TinyMCE is missing?!");
			return true;
		}
		if (tinymce.get(selector).isDirty()) {
			return confirm(_L("Attention: Do you want to continue without saving?"));
		} else {
			return true;
		}
	},

	checkMissingOrDoubleASN1: function(oid) {
		var suffix = (oid == '') ? '' : '_'+oid;

		//var curinput = $('#asn1ids'+suffix').value;
		var curinput = $('input[id="asn1ids'+suffix+'"]')[0];

		if (typeof curinput == "undefined") return true;

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
	},

	crudActionInsert: function(parent) {
		if (parent.startsWith('oid:') && !OIDplusPagePublicObjects.checkMissingOrDoubleASN1('')) return;

		$.ajax({
			url:"ajax.php",
			method:"POST",
			beforeSend: function(jqXHR, settings) {
				$.xhrPool.abortAll();
				$.xhrPool.add(jqXHR);
			},
			complete: function(jqXHR, text) {
				$.xhrPool.remove(jqXHR);
			},
			data: {
				csrf_token:csrf_token,
				plugin:OIDplusPagePublicObjects.oid,
				action:"Insert",
				id:$("#id")[0].value,
				ra_email:$("#ra_email")[0].value,
				comment:$("#comment")[0].value,
				asn1ids:($("#asn1ids")[0] ? $("#asn1ids")[0].value : null),
				iris:($("#iris")[0] ? $("#iris")[0].value : null),
				confidential:($("#hide")[0] ? $("#hide")[0].checked : null),
				weid:($("#weid")[0] ? $("#weid")[0].checked : null),
				parent:parent
			},
			error:function(jqXHR, textStatus, errorThrown) {
				if (errorThrown == "abort") return;
				alertError(_L("Error: %1",errorThrown));
			},
			success:function(data) {
				if ("error" in data) {
					alertError(_L("Error: %1",data.error));
				} else if (data.status >= 0) {
					if (data.status == 0/*OK*/) {
						if (confirm(_L("Insert OK.")+"\n\n"+_L("Do you want to open the newly created object now?"))) {
							openAndSelectNode(data.inserted_id, parent);
							return;
						}
					}

					if ((data.status & 1) == 1/*RaNotExisting*/) {
						if (confirm(_L("Insert OK. However, the email address you have entered (%1) is not in our system. Do you want to send an invitation, so that the RA can register an account to manage their OIDs?",$("#ra_email")[0].value))) {
							OIDplusPagePublicObjects.crudActionSendInvitation(parent, $("#ra_email_"+$.escapeSelector(id))[0].value);
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
						if (confirm(_L("Insert OK. However, the RA and the ASN.1 and IRI identifiers were overwritten, because this OID is a well-known OID.")+"\n\n"+_L("Do you want to open the newly created object now?"))) {
							openAndSelectNode(data.inserted_id, parent);
							return;
						}
					}

					// TODO: Don't use reloadContent(); instead add a node at the tree at the left add at the right add a new row to the table
					reloadContent();
				} else {
					alertError(_L("Error: %1",data));
				}
			}
		});
	},

	crudActionUpdate: function(id, parent) {
		if (id.startsWith('oid:') && !OIDplusPagePublicObjects.checkMissingOrDoubleASN1(id)) return;

		$.ajax({
			url:"ajax.php",
			method:"POST",
			beforeSend: function(jqXHR, settings) {
				$.xhrPool.abortAll();
				$.xhrPool.add(jqXHR);
			},
			complete: function(jqXHR, text) {
				$.xhrPool.remove(jqXHR);
			},
			data: {
				csrf_token:csrf_token,
				plugin:OIDplusPagePublicObjects.oid,
				action:"Update",
				id:id,
				ra_email:$("#ra_email_"+$.escapeSelector(id))[0].value,
				comment:$("#comment_"+$.escapeSelector(id))[0].value,
				asn1ids:($("#asn1ids_"+$.escapeSelector(id))[0] ? $("#asn1ids_"+$.escapeSelector(id))[0].value : null),
				iris:($("#iris_"+$.escapeSelector(id))[0] ? $("#iris_"+$.escapeSelector(id))[0].value : null),
				confidential:($("#hide_"+$.escapeSelector(id))[0] ? $("#hide_"+$.escapeSelector(id))[0].checked : null),
				parent:parent
			},
			error:function(jqXHR, textStatus, errorThrown) {
				if (errorThrown == "abort") return;
				alertError(_L("Error: %1",errorThrown));
			},
			success:function(data) {
				if ("error" in data) {
					alertError(_L("Error: %1",data.error));
				} else if (data.status >= 0) {
					if (data.status == 0/*OK*/) {
						alertSuccess(_L("Update OK"));
					}

					if ((data.status & 1) == 1/*RaNotExisting*/) {
						if (confirm(_L("Update OK. However, the email address you have entered (%1) is not in our system. Do you want to send an invitation, so that the RA can register an account to manage their OIDs?",$("#ra_email_"+$.escapeSelector(id))[0].value))) {
							OIDplusPagePublicObjects.crudActionSendInvitation(parent, $("#ra_email_"+$.escapeSelector(id))[0].value);
							return;
						}
					}

					if ((data.status & 2) == 2/*RaNotExistingNoInvitation*/) {
						alertSuccess(_L("Update OK"));
					}

					if ((data.status & 4) == 4/*IsWellKnownOID*/) {
						alertWarning(_L("Update OK. However, the RA and the ASN.1 and IRI identifiers were overwritten, because this OID is a well-known OID."));
					}

					// reloadContent();
					$('#oidtree').jstree("refresh");
				} else {
					alertError(_L("Error: %1",data));
				}
			}
		});
	},

	crudActionDelete: function(id, parent) {
		if(!window.confirm(_L("Are you sure that you want to delete %1?",id))) return false;

		$.ajax({
			url:"ajax.php",
			method:"POST",
			beforeSend: function(jqXHR, settings) {
				$.xhrPool.abortAll();
				$.xhrPool.add(jqXHR);
			},
			complete: function(jqXHR, text) {
				$.xhrPool.remove(jqXHR);
			},
			data: {
				csrf_token:csrf_token,
				plugin:OIDplusPagePublicObjects.oid,
				action:"Delete",
				id:id,
				parent:parent
			},
			error:function(jqXHR, textStatus, errorThrown) {
				if (errorThrown == "abort") return;
				alertError(_L("Error: %1",errorThrown));
			},
			success:function(data) {
				if ("error" in data) {
					alertError(_L("Error: %1",data.error));
				} else if (data.status >= 0) {
					reloadContent();
					// TODO: Don't use reloadContent(); instead delete node at the left tree and remove the row at the right table
				} else {
					alertError(_L("Error: %1",data.error));
				}
			}
		});
	},

	updateDesc: function() {
		$.ajax({
			url:"ajax.php",
			method:"POST",
			beforeSend: function(jqXHR, settings) {
				$.xhrPool.abortAll();
				$.xhrPool.add(jqXHR);
			},
			complete: function(jqXHR, text) {
				$.xhrPool.remove(jqXHR);
			},
			data: {
				csrf_token:csrf_token,
				plugin:OIDplusPagePublicObjects.oid,
				action:"Update2",
				id:current_node,
				title:($("#titleedit")[0] ? $("#titleedit")[0].value : null),
				//description:($("#description")[0] ? $("#description")[0].value : null)
				description:tinyMCE.get('description').getContent()
			},
			error:function(jqXHR, textStatus, errorThrown) {
				if (errorThrown == "abort") return;
				alertError(_L("Error: %1",errorThrown));
			},
			success:function(data) {
				if ("error" in data) {
					alertError(_L("Error: %1",data.error));
				} else if (data.status >= 0) {
					alertSuccess(_L("Update OK"));
					//reloadContent();
					$('#oidtree').jstree("refresh");
					var h1s = $("h1");
					for (var i = 0; i < h1s.length; i++) {
						var h1 = h1s[i];
						h1.innerHTML = $("#titleedit")[0].value.htmlentities();
					}
					document.title = combine_systemtitle_and_pagetitle(getOidPlusSystemTitle(), $("#titleedit")[0].value);

					var mce = tinymce.get('description');
					if (mce != null) mce.setDirty(false);
				} else {
					alertError(_L("Error: %1",data.error));
				}
			}
		});
	},

	crudActionSendInvitation: function(origin, email) {
		// window.location.href = "?goto=oidplus%3Ainvite_ra%24"+encodeURIComponent(email)+"%24"+encodeURIComponent(origin);
		openOidInPanel('oidplus:invite_ra$'+email+'$'+origin, false);
	},

	frdl_weid_change: function() {
		var from_base = 36;
		var from_control = "#weid";
		var to_base = 10;
		var to_control = "#id";

		var inp = $(from_control).val().toUpperCase().trim();
		if (inp == "") {
			$(to_control).val("");
		} else {
			var x = WeidOidConverter.base_convert_bigint(inp, from_base, to_base);
			if (x == false) {
				$(to_control).val("");
			} else {
				$(to_control).val(x);
			}
		}
	},

	frdl_oidid_change: function() {
		var from_base = 10;
		var from_control = "#id";
		var to_base = 36;
		var to_control = "#weid";

		var inp = $(from_control).val().trim();
		if (inp == "") {
			$(to_control).val("");
		} else {
			var x = WeidOidConverter.base_convert_bigint(inp, from_base, to_base);
			if (x == false) {
				$(to_control).val("");
			} else {
				$(to_control).val(x.toUpperCase());
			}
		}
	}

};

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

var OIDplusPageAdminSoftwareUpdate = {

	oid: "1.3.6.1.4.1.37476.2.5.2.4.3.900",

	doUpdateOIDplus: function(rev, max) {
		$("#update_versioninfo").hide();
		$("#update_infobox").html(_L("Started update from %1 to %2",rev,max)+"\n");
		OIDplusPageAdminSoftwareUpdate._doUpdateOIDplus(rev, max);
	},

	_doUpdateOIDplus: function(rev, max) {
		$("#update_header").text(_L("Updating to Revision %1 ...",rev)+"\n");
		//show_waiting_anim();
		$.ajax({
			url: "ajax.php",
			type: "POST",
			beforeSend: function(jqXHR, settings) {
				$.xhrPool.abortAll();
				$.xhrPool.add(jqXHR);
			},
			complete: function(jqXHR, text) {
				$.xhrPool.remove(jqXHR);
			},
			data: {
				csrf_token:csrf_token,
				plugin: OIDplusPageAdminSoftwareUpdate.oid,
				rev: rev,
				action: "update_now",
			},
			error:function(jqXHR, textStatus, errorThrown) {
				//hide_waiting_anim();
				if (errorThrown == "abort") {
					$("#update_header").text(_L("Update aborted"));
					$("#update_infobox").html($("#update_infobox").html() + '\n\n<input type="button" onclick="location.reload()" value="'+_L('Reload page')+'">');
					return;
				} else {
					$("#update_header").text(_L("Update failed"));
					//alert(_L("Error: %1",errorThrown));
					$("#update_infobox").html($("#update_infobox").html() + "\n\n" + '<span class="severity_4"><strong>' + _L('FATAL ERROR') + ':</strong></span> ' + errorThrown + "\n\n");
					$("#update_infobox").html($("#update_infobox").html() + '\n\n<input type="button" onclick="location.reload()" value="'+_L('Reload page')+'">');
				}
			},
			success: function(data) {
				//hide_waiting_anim();
				if ("error" in data) {
					$("#update_header").text(_L("Update failed"));
					//alert(_L("Error: %1",data.error));
					if ("content" in data) {
						$("#update_infobox").html($("#update_infobox").html() + "\n\n" + data.content + "\n\n" + '<span class="severity_4"><strong>' + _L('FATAL ERROR') + ':</strong></span> ' + data.error + "\n\n");
					} else {
						$("#update_infobox").html($("#update_infobox").html() + "\n\n" + '<span class="severity_4"><strong>' + _L('FATAL ERROR') + ':</strong></span> ' + data.error + "\n\n");
					}
					$("#update_infobox").html($("#update_infobox").html() + '\n\n<input type="button" onclick="location.reload()" value="'+_L('Reload page')+'">');
				} else if (data.status >= 0) {
					output = data.content.trim();
					output = output.replace(/INFO:/g, '<span class="severity_2"><strong>' + _L('INFO') + ':</strong></span>');
					output = output.replace(/WARNING:/g, '<span class="severity_3"><strong>' + _L('WARNING') + ':</strong></span>');
					output = output.replace(/FATAL ERROR:/g, '<span class="severity_4"><strong>' + _L('FATAL ERROR') + ':</strong></span>');
					$("#update_infobox").html($("#update_infobox").html() + output + "\n");
					rev = data.rev=="HEAD" ? max : data.rev;
					if (rev >= max) {
						$("#update_header").text(_L("Update finished"));
						$("#update_infobox").html($("#update_infobox").html() + "\n\n" + '<span class="severity_1"><strong> ' + _L('UPDATE FINISHED') + ':</strong></span> ' + _L('You are now at SVN revision %1', rev));
						$("#update_infobox").html($("#update_infobox").html() + '\n\n<input type="button" onclick="location.reload()" value="'+_L('Reload page')+'">');
					} else {
						if (output.includes("FATAL ERROR:")) {
							$("#update_header").text(_L("Update failed"));
							$("#update_infobox").html($("#update_infobox").html() + '\n\n<input type="button" onclick="location.reload()" value="'+_L('Reload page')+'">');
						} else {
							OIDplusPageAdminSoftwareUpdate._doUpdateOIDplus(parseInt(rev)+1, max);
						}
					}
					return;
				} else {
					$("#update_header").text(_L("Update failed"));
					//alert(_L("Error: %1",data));
					if ("content" in data) {
						$("#update_infobox").html($("#update_infobox").html() + "\n\n" + data.content + "\n\n" + '<span class="severity_4"><strong>' + _L('FATAL ERROR') + ':</strong></span> ' + data + "\n\n");
					} else {
						$("#update_infobox").html($("#update_infobox").html() + "\n\n" + '<span class="severity_4"><strong>' + _L('FATAL ERROR') + ':</strong></span> ' + data + "\n\n");
					}
					$("#update_infobox").html($("#update_infobox").html() + '\n\n<input type="button" onclick="location.reload()" value="'+_L('Reload page')+'">');
				}
			},
			timeout:0 // infinite
		});
		return false;
	}

};

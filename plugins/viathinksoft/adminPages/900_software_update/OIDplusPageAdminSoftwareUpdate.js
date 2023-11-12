/*
 * OIDplus 2.0
 * Copyright 2019 - 2023 Daniel Marschall, ViaThinkSoft
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

	doUpdateOIDplus: function(next_rev, max_rev) {
		$("#update_versioninfo").hide();
		OIDplusPageAdminSoftwareUpdate._downloadUpdate(next_rev, max_rev);
	},

	_downloadUpdate: function(next_rev, max_rev) {
		var msg = _L("Downloading update beginning from version %1 up to %2...",next_rev,max_rev);
		$("#update_infobox").html(/*$("#update_infobox").html() +*/ '<span class="severity_2"><strong>' + _L('INFO') + ":</strong></span> "+msg+"\n");

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
				next_version: next_rev,
				max_version: max_rev,
				update_version: 3,
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
					//alertError(_L("Error: %1",errorThrown));
					$("#update_infobox").html($("#update_infobox").html() + "\n\n" + '<span class="severity_4"><strong>' + _L('FATAL ERROR') + ':</strong></span> ' + errorThrown + "\n\n");
					$("#update_infobox").html($("#update_infobox").html() + '\n\n<input type="button" onclick="location.reload()" value="'+_L('Reload page')+'">');
				}
			},
			success: function(data) {
				// TODO: Use oidplus_ajax_success(), since this checks the existance of "error" in data, and checks if status>=0
				//hide_waiting_anim();
				if (typeof data === "object" && "error" in data) {
					$("#update_header").text(_L("Update failed"));
					//alertError(_L("Error: %1",data.error));
					if (typeof data === "object" && "content" in data) {
						$("#update_infobox").html($("#update_infobox").html() + "\n\n" + data.content + "\n\n" + '<span class="severity_4"><strong>' + _L('FATAL ERROR') + ':</strong></span> ' + data.error + "\n\n");
					} else {
						$("#update_infobox").html($("#update_infobox").html() + "\n\n" + '<span class="severity_4"><strong>' + _L('FATAL ERROR') + ':</strong></span> ' + data.error + "\n\n");
					}
					$("#update_infobox").html($("#update_infobox").html() + '\n\n<input type="button" onclick="location.reload()" value="'+_L('Reload page')+'">');
				} else if (typeof data === "object" && data.status >= 0) {

					if (!(typeof data === "object" && "update_files" in data)) {
						// This code is usual for svn-wc and git-wc update
						var output = data.content.trim();
						output = output.replace(/INFO:/g, '<span class="severity_2"><strong>' + _L('INFO') + ':</strong></span>');
						output = output.replace(/WARNING:/g, '<span class="severity_3"><strong>' + _L('WARNING') + ':</strong></span>');
						output = output.replace(/FATAL ERROR:/g, '<span class="severity_4"><strong>' + _L('FATAL ERROR') + ':</strong></span>');
						$("#update_infobox").html($("#update_infobox").html() + output + "\n");
						var cur_rev = data.rev=="HEAD" ? max_rev : data.rev;
						if (cur_rev >= max_rev) {
							$("#update_header").text(_L("Update finished"));
							$("#update_infobox").html($("#update_infobox").html() + "\n\n" + '<span class="severity_1"><strong> ' + _L('UPDATE FINISHED') + ':</strong></span> ' + _L('You are now at version %1', cur_rev));
							$("#update_infobox").html($("#update_infobox").html() + '\n\n<input type="button" onclick="location.reload()" value="'+_L('Reload page')+'">');
						} else {
							if (output.includes("FATAL ERROR:")) {
								$("#update_header").text(_L("Update failed"));
								$("#update_infobox").html($("#update_infobox").html() + '\n\n<input type="button" onclick="location.reload()" value="'+_L('Reload page')+'">');
							} else {
								// This is an undefined state! (TODO)
							}
						}
						return;
					} else {
						// This code is usual for version 3 "manual" update
						OIDplusPageAdminSoftwareUpdate._applyChangescripts(data.update_files);
					}
					return;
				} else {
					$("#update_header").text(_L("Update failed"));
					//alertError(_L("Error: %1",data));
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
	},

	_applyChangescripts: function(leftscripts) {

		if (leftscripts.length == 0) {
			$("#update_header").text(_L("Update successful"));
			$("#update_infobox").html($("#update_infobox").html() + "\n\n" + '<span class="severity_1"><strong> ' + _L('UPDATE FINISHED') + '</strong></span>');
			$("#update_infobox").html($("#update_infobox").html() + '\n\n<input type="button" onclick="location.reload()" value="'+_L('Reload page')+'">');
			return;
		}

		var tmp = leftscripts.shift();
		var version = tmp[0];
		var scripturl = tmp[1];

		console.log("Execute update file " + scripturl);
		$("#update_header").text(_L("Updating to version %1 ...",version)+"\n");
		var msg = _L("Update to OIDplus version %1 is running...",version);
		$("#update_infobox").html($("#update_infobox").html() + '<span class="severity_2"><strong>' + _L('INFO') + ":</strong></span> "+msg+"\n");

		$.ajax({
			url: scripturl,
			type: "GET",
			beforeSend: function(jqXHR, settings) {
				$.xhrPool.abortAll();
				$.xhrPool.add(jqXHR);
			},
			complete: function(jqXHR, text) {
				$.xhrPool.remove(jqXHR);
			},
			data: {
			},
			error:function(jqXHR, textStatus, errorThrown) {
				//hide_waiting_anim();
				if (errorThrown == "abort") {
					$("#update_header").text(_L("Update aborted"));
					$("#update_infobox").html($("#update_infobox").html() + '\n\n<input type="button" onclick="location.reload()" value="'+_L('Reload page')+'">');
					return;
				} else {
					$("#update_header").text(_L("Update failed"));
					//alertError(_L("Error: %1",errorThrown));
					$("#update_infobox").html($("#update_infobox").html() + "\n\n" + '<span class="severity_4"><strong>' + _L('FATAL ERROR') + ':</strong></span> ' + errorThrown + "\n\n");
					$("#update_infobox").html($("#update_infobox").html() + '\n\n<input type="button" onclick="location.reload()" value="'+_L('Reload page')+'">');
				}
			},
			success: function(data2) {
				//hide_waiting_anim();
				var output2 = data2.trim();
				output2 = output2.replace(/INFO:/g, '<span class="severity_2"><strong>' + _L('INFO') + ':</strong></span>');
				output2 = output2.replace(/WARNING:/g, '<span class="severity_3"><strong>' + _L('WARNING') + ':</strong></span>');
				output2 = output2.replace(/FATAL ERROR:/g, '<span class="severity_4"><strong>' + _L('FATAL ERROR') + ':</strong></span>');

				if (output2 == "DONE") { // DO NOT TRANSLATE
					var msg = _L("Update to OIDplus version %1 was successful!",version);
					$("#update_infobox").html($("#update_infobox").html() + '<span class="severity_2"><strong>' + _L('INFO') + ":</strong></span> "+msg+"\n");
				} else {
					$("#update_infobox").html($("#update_infobox").html() + output2 + "\n");
				}

				if (output2.includes("FATAL ERROR:")) {
					$("#update_header").text(_L("Update failed"));
					$("#update_infobox").html($("#update_infobox").html() + '\n\n<input type="button" onclick="location.reload()" value="'+_L('Reload page')+'">');
				} else {
					OIDplusPageAdminSoftwareUpdate._applyChangescripts(leftscripts);
				}
				return;
			},
			timeout:0 // infinite
		});

	}

};

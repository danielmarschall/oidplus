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

var OIDplusPagePublicAttachments = {

	oid: "1.3.6.1.4.1.37476.2.5.2.4.1.95",

	downloadAttachment: function(webpath, id, file) {

		//OIDplus::webpath(__DIR__).'download.php?id='.urlencode($id).'&filename='.urlencode(basename($file)).'

		window.open(webpath + 'download.php?id='+encodeURI(id)+'&filename='+encodeURI(file), '_blank');

	},

	deleteAttachment: function(id, file) {
		if(!window.confirm(_L("Are you sure that you want to delete %1?",file))) return false;

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
				plugin:OIDplusPagePublicAttachments.oid,
				action:"deleteAttachment",
				id:id,
				filename:file,
			},
			error:function(jqXHR, textStatus, errorThrown) {
				if (errorThrown == "abort") return;
				alertError(_L("Error: %1",errorThrown));
			},
			success:function(data) {
				if ("error" in data) {
					alertError(_L("Error: %1",data.error));
				} else if (data.status >= 0) {
					alertSuccess(_L("OK"));
					reloadContent();
				} else {
					alertError(_L("Error: %1",data));
				}
			}
		});
	},

	uploadAttachment: function(id, file) {
		var file_data = $('#fileAttachment').prop('files')[0];

		var form_data = new FormData();
		form_data.append('csrf_token', csrf_token);
		form_data.append('userfile', file_data);
		form_data.append('plugin', OIDplusPagePublicAttachments.oid);
		form_data.append('action', "uploadAttachment");
		form_data.append('id', id);

		$.ajax({
			url:"ajax.php",
			method:"POST",
			processData:false,
			contentType:false,
			beforeSend: function(jqXHR, settings) {
				$.xhrPool.abortAll();
				$.xhrPool.add(jqXHR);
			},
			complete: function(jqXHR, text) {
				$.xhrPool.remove(jqXHR);
			},
			data: form_data,
			error:function(jqXHR, textStatus, errorThrown) {
				if (errorThrown == "abort") return;
				alertError(_L("Error: %1",errorThrown));
			},
			success:function(data) {
				if ("error" in data) {
					alertError(_L("Error: %1",data.error));
				} else if (data.status >= 0) {
					alertSuccess(_L("OK"));
					$('#fileAttachment').val('');
					reloadContent();
				} else {
					alertError(_L("Error: %1",data));
				}
			}
		});
	},

	uploadAttachmentOnSubmit: function() {
		try {
			OIDplusPagePublicAttachments.uploadAttachment(current_node, $("#fileAttachment")[0].value);
		} finally {
			return false;
		}
	}

};

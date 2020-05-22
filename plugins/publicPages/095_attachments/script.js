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

function downloadAttachment(webpath, id, file) {

	//OIDplus::webpath(__DIR__).'download.php?id='.urlencode($id).'&filename='.urlencode(basename($file)).'

	window.open(webpath + 'download.php?id='+encodeURI(id)+'&filename='+encodeURI(file), '_blank');

}

function deleteAttachment(id, file) {
	if(!window.confirm("Are you sure that you want to delete "+file+"?")) return false;

	$.ajax({
		url:"ajax.php",
		method:"POST",
		data: {
			plugin:"1.3.6.1.4.1.37476.2.5.2.4.1.95",
			action:"deleteAttachment",
			id:id,
			filename:file,
		},
		error:function(jqXHR, textStatus, errorThrown) {
			alert("Error: " + errorThrown);
		},
		success:function(data) {
			if ("error" in data) {
				alert("Error: " + data.error);
			} else if (data.status == 0) {
				alert("OK");
				reloadContent();
			} else {
				alert("Error: " + data);
			}
		}
	});
}

function uploadAttachment(id, file) {
	var file_data = $('#fileAttachment').prop('files')[0];

	var form_data = new FormData();
	form_data.append('userfile', file_data);
	form_data.append('plugin', "1.3.6.1.4.1.37476.2.5.2.4.1.95");
	form_data.append('action', "uploadAttachment");
	form_data.append('id', id);

	$.ajax({
		url:"ajax.php",
		method:"POST",
		processData:false,
		contentType:false,
		data: form_data,
		error:function(jqXHR, textStatus, errorThrown) {
			alert("Error: " + errorThrown);
		},
		success:function(data) {
			if ("error" in data) {
				alert("Error: " + data.error);
			} else if (data.status == 0) {
				alert("OK");
				$('#fileAttachment').val('');
				reloadContent();
			} else {
				alert("Error: " + data);
			}
		}
	});
}

function uploadAttachmentOnSubmit() {
	try {
		uploadAttachment(current_node, document.getElementById("fileAttachment").value);
	} finally {
		return false;
	}
}

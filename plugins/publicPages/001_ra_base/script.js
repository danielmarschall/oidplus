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

// Is currently used by plugin public-093 (rainfo) and ra-100 (edit-contact-data)!
function deleteRa(email, goto) {
	if(!window.confirm("Are you really sure that you want to delete "+email+"? (The OIDs stay active)")) return false;

	$.ajax({
		url:"ajax.php",
		method:"POST",
		data: {
			plugin:"1.3.6.1.4.1.37476.2.5.2.4.1.1",
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
				if (goto != null) {
					$("#gotoedit").val(goto);
					window.location.href = "?goto="+encodeURIComponent(goto);
				}
				// reloadContent();
			} else {
				alert("Error: " + data.error);
			}
		}
	});
}

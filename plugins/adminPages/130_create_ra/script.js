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

function adminCreateRaFormOnSubmit() {
	$.ajax({
		url: "ajax.php",
		type: "POST",
		data: {
			action: "create_ra",
			email: $("#email").val(),
			password1: $("#password1").val(),
			password2: $("#password2").val()
		},
		error:function(jqXHR, textStatus, errorThrown) {
			alert("Error: " + errorThrown);
		},
		success: function(data) {
			if ("error" in data) {
				alert("Error: " + data.error);
			} else if (data.status == 0) {
				alert("Account created");
				//openOidInPanel('oidplus:rainfo$'+$("#email").val(),true);
				// We need to reload the whole page, because the tree at the left contains a "List RA" list with the RAs
				document.location.href = '?goto='+encodeURIComponent('oidplus:rainfo$'+$("#email").val());
			} else {
				alert("Error: " + data);
			}
		}
	});
	return false;
}

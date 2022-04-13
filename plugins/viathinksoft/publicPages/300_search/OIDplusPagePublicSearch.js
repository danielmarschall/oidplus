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

var OIDplusPagePublicSearch = {

	oid: "1.3.6.1.4.1.37476.2.5.2.4.1.300",

	search_button_click: function() {
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
				plugin: OIDplusPagePublicSearch.oid,
				action:"search",
				namespace: $("#namespace").val(),
				term: $("#term").val(),
				search_title: $("#search_title:checked").length,
				search_description: $("#search_description:checked").length,
				search_asn1id: $("#search_asn1id:checked").length,
				search_iri: $("#search_iri:checked").length
			},
			error:function(jqXHR, textStatus, errorThrown) {
				if (errorThrown == "abort") return;
				alertError(_L("Error: %1",errorThrown));
			},
			success:function(data) {
				if ("error" in data) {
					alertError(_L("Error: %1",data.error));
				} else if (data.status >= 0) {
					$("#search_output").html(data.output)
				} else {
					alertError(_L("Error: %1",data));
				}
			}
		});

		return false; // Don't call the normal form submit (it is for Non-JS only)
	}

};

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

var OIDplusPageAdminOIDInfoExport = {

	oid: "1.3.6.1.4.1.37476.2.5.2.4.3.400",

	removeMissingOid: function(oid) {
		$('#missing_oid_'+oid.replace(/\./g,'_')).remove();
	},

	importMissingOid: function(oid) {
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
				csrf_token: csrf_token,
				plugin: OIDplusPageAdminOIDInfoExport.oid,
				action: "import_oidinfo_oid",
				oid: oid
			},
			error: oidplus_ajax_error,
			success: function (data) {
				oidplus_ajax_success(data, function (data) {
					console.log(_L("Imported OID %1", oid));
					OIDplusPageAdminOIDInfoExport.removeMissingOid(oid);
				});
			}
		});
	},

	uploadXmlFile: function(file) {
		var file_data = $('#userfile').prop('files')[0];

		var form_data = new FormData();
		form_data.append('userfile', file_data);
		form_data.append('plugin', OIDplusPageAdminOIDInfoExport.oid),
		form_data.append('action', "import_xml_file");
		form_data.append('csrf_token', csrf_token);

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
			error: oidplus_ajax_error,
			success:function(data) {
				// TODO XXX: (Future feature) If the user decides that existing OIDs shall be overwritten, then we may not print "Ignored OIDs because they are already existing"
				// TODO: Use oidplus_ajax_success(), since this checks the existance of "error" in data, and checks if status>=0
				if (typeof data === "object" && "error" in data) {
					if (typeof data === "object" && "count_imported_oids" in data) {
						// TODO: Device if alertSuccess, alertWarning oder alertError is shown
						alertSuccess(_L("Successfully imported OIDs: %1",data.count_imported_oids)+"\n"+
							  _L("Ignored OIDs because they are already existing: %1",data.count_already_existing)+"\n"+
							  _L("Not imported because of errors: %1",data.count_errors)+"\n"+
							  _L("Warnings: %1",data.count_warnings)+"\n"+
							  "\n"+
							  _L("Warnings / Error messages:")+"\n"+
							  "\n"+
							  data.error);
					} else {
						alertError(_L("Error: %1",data.error));
					}
				} else if (typeof data === "object" && data.status >= 0) {
					// TODO: Device if alertSuccess, alertWarning oder alertError is shown
					alertSuccess(_L("Successfully imported OIDs: %1",data.count_imported_oids)+"\n"+
						  _L("Ignored OIDs because they are already existing: %1",data.count_already_existing)+"\n"+
						  _L("Not imported because of errors: %1",data.count_errors)+"\n"+
						  _L("Warnings: %1",data.count_warnings));
					$('#userfile').val('');
				} else {
					if (typeof data === "object" && "count_imported_oids" in data) {
						// TODO: Device if alertSuccess, alertWarning oder alertError is shown
						alertSuccess(_L("Successfully imported OIDs: %1",data.count_imported_oids)+"\n"+
							  _L("Ignored OIDs because they are already existing: %1",data.count_already_existing)+"\n"+
							  _L("Not imported because of errors: %1",data.count_errors)+"\n"+
							  _L("Warnings: %1",data.count_warnings)+"\n"+
							  "\n"+
							  _L("Warnings / Error messages:")+"\n"+
							  "\n"+
							  data/*sic*/);
					} else {
						alertError(_L("Error: %1",data));
					}
				}
			}
		});
	},

	uploadXmlFileOnSubmit: function() {
		try {
			OIDplusPageAdminOIDInfoExport.uploadXmlFile($("#userfile")[0].value);
		} finally {
			return false;
		}
	}

};

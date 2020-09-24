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

function removeMissingOid(oid) {
	$('#missing_oid_'+oid.replace(/\./g,'_')).remove();
}

function importMissingOid(oid) {
	$.ajax({
		url:"ajax.php",
		method:"POST",
		data: {
			plugin:"1.3.6.1.4.1.37476.2.5.2.4.3.400",
			action:"import_oidinfo_oid",
			oid:oid
		},
		error:function(jqXHR, textStatus, errorThrown) {
			alert(_L("Error: %1",errorThrown));
		},
		success:function(data) {
			if ("error" in data) {
				alert(_L("Error: %1",data.error));
			} else if (data.status >= 0) {
				console.log(_L("Imported OID %1",oid));
				removeMissingOid(oid);
			} else {
				alert(_L("Error: %1",data));
			}
		}
	});
}

function uploadXmlFile(file) {
	var file_data = $('#userfile').prop('files')[0];

	var form_data = new FormData();
	form_data.append('userfile', file_data);
	form_data.append('plugin', "1.3.6.1.4.1.37476.2.5.2.4.3.400");
	form_data.append('action', "import_xml_file");

	$.ajax({
		url:"ajax.php",
		method:"POST",
		processData:false,
		contentType:false,
		data: form_data,
		error:function(jqXHR, textStatus, errorThrown) {
			alert(_L("Error: %1",errorThrown));
		},
		success:function(data) {
			// TODO XXX: (Future feature) If the user decides that existing OIDs shall be overwritten, then we may not print "Ignored OIDs because they are already existing"
			if ("error" in data) {
				if ("count_imported_oids" in data) {
					alert(_L("Successfully imported OIDs: %1",data.count_imported_oids)+"\n"+
					      _L("Ignored OIDs because they are already existing: %1",data.count_already_existing)+"\n"+
					      _L("Not imported because of errors: %1",data.count_errors)+"\n"+
					      _L("Warnings: %1",data.count_warnings)+"\n"+
					      "\n"+
					      _L("Warnings / Error messages:")+"\n"+
					      "\n"+
					      data.error);
				} else {
					alert(_L("Error: %1",data.error));
				}
			} else if (data.status >= 0) {
				alert(_L("Successfully imported OIDs: %1",data.count_imported_oids)+"\n"+
					  _L("Ignored OIDs because they are already existing: %1",data.count_already_existing)+"\n"+
					  _L("Not imported because of errors: %1",data.count_errors)+"\n"+
					  _L("Warnings: %1",data.count_warnings));
				$('#userfile').val('');
			} else {
				if ("count_imported_oids" in data) {
					alert(_L("Successfully imported OIDs: %1",data.count_imported_oids)+"\n"+
					      _L("Ignored OIDs because they are already existing: %1",data.count_already_existing)+"\n"+
					      _L("Not imported because of errors: %1",data.count_errors)+"\n"+
					      _L("Warnings: %1",data.count_warnings)+"\n"+
					      "\n"+
					      _L("Warnings / Error messages:")+"\n"+
					      "\n"+
					      data/*sic*/);
				} else {
					alert(_L("Error: %1",data));
				}
			}
		}
	});
}

function uploadXmlFileOnSubmit() {
	try {
		uploadXmlFile(document.getElementById("userfile").value);
	} finally {
		return false;
	}
}
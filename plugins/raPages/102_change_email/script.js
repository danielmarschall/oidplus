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

function changeRaEmailFormOnSubmit() {
    $.ajax({
      url: "action.php",
      type: "POST",
      data: {
        action: "change_ra_email",
        old_email: $("#old_email").val(),
        new_email: $("#new_email").val(),
      },
      success: function(data) {
                        if (data != "OK") {
                                alert("Error: " + data);
                        } else {
				alert("eMail was sent");
                                //document.location = '?goto=oidplus:system';
                                //reloadContent();
                        }
      }
  });
  return false;
}

function activateNewRaEmailFormOnSubmit() {
    $.ajax({
      url: "action.php",
      type: "POST",
      data: {
        action: "activate_new_ra_email",
        password: $("#password").val(),
        old_email: $("#old_email").val(),
        new_email: $("#new_email").val(),
        auth: $("#auth").val(),
        timestamp: $("#timestamp").val()
      },
      success: function(data) {
                        if (data != "OK") {
                                alert("Error: " + data);
                        } else {
				alert("Done");
                                document.location = '?goto=oidplus:system';
                                //reloadContent();
                        }
      }
  });
  return false;
}

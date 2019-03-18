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

function freeOIDFormOnSubmit() {
    $.ajax({
      url: "action.php",
      type: "POST",
      data: {
        action: "com.viathinksoft.freeoid.request_freeoid",
        email: $("#email").val(),
        captcha: document.getElementsByClassName('g-recaptcha').length > 0 ? grecaptcha.getResponse() : null
      },
      success: function(data) {
                        if (data != "OK") {
                                alert("Error: " + data);
				grecaptcha.reset();
                        } else {
				alert("Instructions have been sent via email.");
                                document.location = '?goto=oidplus:system';
                                //reloadContent();
                        }
      }
  });
  return false;
}

function activateFreeOIDFormOnSubmit() {
    $.ajax({
      url: "action.php",
      type: "POST",
      data: {
        action: "com.viathinksoft.freeoid.activate_freeoid",
        email: $("#email").val(),

        ra_name: $("#ra_name").val(),
        title: $("#title").val(),
        url: $("#url").val(),

        auth: $("#auth").val(),
        password1: $("#password1").val(),
        password2: $("#password2").val(),
        timestamp: $("#timestamp").val()
      },
      success: function(data) {
                        if (data != "OK") {
                                alert("Error: " + data);
                        } else {
				alert("Registration successful! You can now log in.");
                                document.location = '?goto=oidplus:login';
                                //reloadContent();
                        }
      }
  });
  return false;
}


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


function raChangePasswordFormOnSubmit() {
    $.ajax({
      url: "action.php",
      type: "POST",
      data: {
        action: "change_ra_password",
        email: $("#email").val(),
        old_password: $("#old_password").val(),
        new_password1: $("#new_password1").val(),
        new_password2: $("#new_password2").val()
      },
      success: function(data) {
                        if (data != "OK") {
                                alert("Error: " + data);
                        } else {
				alert("Done");
                                //document.location = '?goto=oidplus:system';
                                //reloadContent();
                        }
      }
  });
  return false;
}

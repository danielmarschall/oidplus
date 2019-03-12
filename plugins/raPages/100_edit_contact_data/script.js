function raChangeContactDataFormOnSubmit() {
    $.ajax({
      url: "action.php",
      type: "POST",
      data: {
        action: "change_ra_data",
        email: $("#email").val(),
        ra_name: $("#ra_name").val(),
        organization: $("#organization").val(),
        office: $("#office").val(),
        personal_name: $("#personal_name").val(),
        privacy: $("#privacy").is(":checked") ? 1 : 0,
        street: $("#street").val(),
        zip_town: $("#zip_town").val(),
        country: $("#country").val(),
        phone: $("#phone").val(),
        mobile: $("#mobile").val(),
        fax: $("#fax").val()
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

(function($) {
  $(document).ready(function() {

    var settings = { 
      async: true, 
      crossDomain: true, 
      url: wu_dm_settings.url, 
      method: "POST", 
      headers: { 
        "content-type": "application/x-www-form-urlencoded", 
        accept: "application/json", 
        authorization: "Bearer " + wu_dm_settings.token,
      }, 
      data: wu_dm_settings.data,
    };

    $.ajax(settings).done(function(response) {
      // console.log(response);
    });

  }); 

})(jQuery);

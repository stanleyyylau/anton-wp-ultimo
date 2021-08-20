

(function($) {
  $(document).ready(function() {

    console.log(wu_coupon_edit.applies_to_setup_fee);

    app_wu_coupon_edit = new Vue({
      el: "#postbox-container-1",
      data: {
        applies_to_setup_fee: wu_coupon_edit.applies_to_setup_fee,
      },
    }); // end Vue;

  });

})(jQuery);

/**
 * Change Plans & Add Coupon code
 */
(function ($) {
  $(document).ready(function () {
    // /**
    //  * Coupon Code Selector
    //  */
    // $('#has_coupon_code').on('change', function() {
    //   $('#coupon-code-field').slideToggle();
    // });

    // (function() {
    //    var is_check = $('#has_coupon_code').attr('checked');
    //    // console.log(is_check);
    //    if (is_check) {
    //     $('#coupon-code-field').slideToggle();
    //    }
    // })();

    /**
     * Select the default pricing option
     */
    setTimeout(function() {
      $('[data-frequency-selector="'+ wpu.default_pricing_option +'"]').click();
    }, 100);

    /**
     * Change Plans
     */
    $(".wu-plans-frequency-selector").on("click", "li > a", function (event) {

      event.preventDefault();

      var $selector = $(this);
      var $plans = $(".wu-plan");
      var current = parseInt($(".wu-plans-frequency-selector li > a.active").data("frequency-selector"), 10);

      if ($selector.hasClass("active") && !$selector.hasClass("first")) {
        return;
      }

      $(".wu-plans-frequency-selector li > a").removeClass("active").removeClass('first');
      $selector.addClass("active");

      var freq = $selector.data("frequency-selector");
      // console.log(freq);

      // CHnage the frequency value  
      $("#wu_plan_freq").val(freq);

      $plans.each(function () {

        var $this = $(this);
        var text = $(this).data("price-" + freq);

        $(this).find(".plan-price").text(text);

        // Hide all tables  
        if (current !== 1) {
          $this.find(".total-price").slideUp("fast", function () {
            $this.find(".total-price-" + freq).slideDown("fast");
          });
        } else {
          $this.find(".total-price-" + freq).slideDown("fast");
        }

      });
    });
  });
})(jQuery);
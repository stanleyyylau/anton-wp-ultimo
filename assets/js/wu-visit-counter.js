/**
 * Handles Visit Counting.
 * 
 * This scripts triggers a call to the counter 5 seconds after the page load is complete 
 * OR when the user clicks to leave the window.
 * Passes security code to prevent DDoS.
 * 
 */
(function($) {

  /**
   * Defines the jqHRX variable holder and the done controller.
   */
  var wu_sync_visits_count_call,
      done = false;

  /**
   * Sends the count visit request to the Server.
   */
  var wu_sync_visits_count = function() {

    console.log('Counting Visit...');

    return $.ajax({
      type: 'GET',
      url: wu_visit_counter.ajaxurl,
      data: {
        action: 'wu_count_visits',
        code: wu_visit_counter.code, 
      }
    }).done(function() {

      /**
       * When done, we set the controller to true to prevent recounting...
       */
      done = true;

      console.log('Visit registered.');

    });

  }; // end wu_sync_visits_count;

  /**
   * Triggers when the user navigates away after 3 seconds or more.
   */
  setTimeout(function() {

    console.log('Listening for unloads...');

    $(window).on("unload", function () {

      /**
       * Abort ongoing call
       */
      if (typeof wu_sync_visits_count_call == 'null') {

        if (!done) {

          wu_sync_visits_count_call = wu_sync_visits_count();

        } // end if;

      } // end if;
      
    });
    
  }, 3000);

  /**
   * Triggers when the document is ready and 5 seconds have passed.
   */
  $(document).ready(function() {

    setTimeout(function() {
      
      wu_sync_visits_count_call = wu_sync_visits_count();

    }, 10000);

  });

})(jQuery);
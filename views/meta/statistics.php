<?php
$screen = get_current_screen();
wp_enqueue_script('jquery-ui-datepicker');
wp_enqueue_style('jquery-ui');
?>

<div id="wp-ultimo-wrap" class="wrap">
  
  <h1><?php _e('Statistics - WP Ultimo', 'wp-ultimo'); ?></h1>
  <p class="description"><?php _e('Here are some cool statistics about your network usage and growth!', 'wp-ultimo'); ?></p>

  <div class="wp-filter-wu-stats">
    
    <div class="row">

      <div class="wu-col-sm-6">
        <div class="wu-label">
          <?php // _e('Filter Options', 'wp-ultimo'); ?>
        </div>
      </div>

      <div class="wu-col-sm-6">
        <form class="wu-statistics-data-range">
          <span class="dashicons dashicons-calendar-alt"></span>
          <input class="" type="text" id="from" name="from" placeholder="<?php _e('From', 'wp-ultimo'); ?>">
          <input class="" type="text" id="to" name="to" placeholder="<?php _e('To', 'wp-ultimo'); ?>">
          <button class="button" type="submit"><?php _e('Filter', 'wp-ultimo'); ?></button>
        </form>
      </div>

    </div>

  </div>

  <div id="dashboard-widgets-wrap">

    <div id="dashboard-widgets" class="metabox-holder">

      <!-- <div id="postbox-container-0" class="postbox-container postbox-container-full">
          <?php do_meta_boxes('wu-statistics', 'full', '' ); ?>
      </div> -->

      <div id="postbox-container-1" class="postbox-container">
        <?php do_meta_boxes($screen->id, 'normal', ''); ?>
      </div>

      <div id="postbox-container-2" class="postbox-container">
        <?php do_meta_boxes($screen->id, 'side', ''); ?>
      </div>

    </div>

  </div>
  
</div>

<?php wp_nonce_field( 'meta-box-order', 'meta-box-order-nonce', false ); ?>
<?php wp_nonce_field('closedpostboxes', 'closedpostboxesnonce', false); ?>

<script>
  (function($) {
    var dateFormat = "mm/dd/yy",

      from = $( "#from" ).datepicker({
          defaultDate: "+1w",
          changeMonth: true,
          numberOfMonths: 3,
          dateFormat: dateFormat,
        });
        $( "#from" ).on( "change", function() {
          $( "#to" ).datepicker( "option", "minDate", getDate( this ) );
        }),

      to = $( "#to" ).datepicker({
        defaultDate: "+1w",
        changeMonth: true,
        numberOfMonths: 3,
        dateFormat: dateFormat,
      });

      $( "#to" ).on( "change", function() {
        $( "#from" ).datepicker( "option", "maxDate", getDate( this ) );
      });
 
    function getDate( element ) {
      var date;
      try {
        date = $.datepicker.parseDate( dateFormat, element.value );
      } catch( error ) {
        date = null;
      }
      // console.log(date);
      return date;
    }
  })(jQuery);
  </script>
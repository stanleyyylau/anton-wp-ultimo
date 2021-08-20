<?php

wp_enqueue_script('jquery-ui-datepicker');
wp_enqueue_style('jquery-ui');
wp_enqueue_script('jquery-blockui');

if ($subscription->get_trial()) {

  $status  = 'trialing';
  $message = __('Trialing', 'wp-ultimo');

} else if ($subscription->is_on_hold()) { // @since 1.2.0

  $status  = 'on-hold';
  $message = __('On Hold', 'wp-ultimo');

} else {

  $status  = $subscription->is_active() ? 'active' : 'inactive';
  $message = $subscription->is_active() ? __('Active', 'wp-ultimo') : __('Inactive', 'wp-ultimo');

}

$active_until = new DateTime($subscription->active_until);
$created_at   = new DateTime($subscription->created_at);

?>

<div class="wu-subscription-details-actions">
  <div class="row">

    <div class="wu-col-sm-6 wu-subscription-status">
      <span class="status-label"><?php _e('Subscription Status:', 'wp-ultimo'); ?></span> <span class="status <?php echo $status; ?>"><?php echo $message; ?></span>
    </div>

    <div class="wu-col-sm-6">
      <span class="remaining"><?php echo $subscription->get_active_until_string(); ?></span>
    </div>

  </div>
</div>

<div class="">

   <div class="wu-col-sm-3 wu-subscription-owner">

     <div class="wu-subscription-details">

       <label for="active_until"><?php _e('Active Until', 'wp-ultimo'); ?></label>

       <span class="wu-tooltip active_until_value" title="<?php _e('Click to edit', 'wp-ultimo'); ?>"><?php echo $subscription->get_date('active_until', get_blog_option(1, 'date_format') . ' @ H:i' ); ?></span>

       <input type="text" id="active_until" name="active_until" value="<?php echo $active_until->format('Y-m-d H:i:S'); ?>" data-format="Y-m-d H:i:S" class="wu-datepicker" placeholder="<?php _e('Click to edit', 'wp-ultimo'); ?>">

     </div>

   </div>

   <div class="wu-col-sm-3 wu-subscription-owner">

     <div class="wu-subscription-details">

      <label for="created_at"><?php _e('Created At', 'wp-ultimo'); ?></label>

      <span class="wu-tooltip" title="<?php _e('Click to edit', 'wp-ultimo'); ?>"><?php echo $subscription->get_date('created_at', get_blog_option(1, 'date_format') . ' @ H:i' ); ?></span>

      <input type="text" id="created_at" name="created_at" value="<?php echo $created_at->format('Y-m-d H:i:S'); ?>" data-format="Y-m-d H:i:S" class="wu-datepicker" placeholder="<?php _e('Click to edit', 'wp-ultimo'); ?>">

     </div>

   </div>

   <div class="wu-col-sm-3 wu-subscription-owner">

     <div class="wu-subscription-details">

      <label for="trial"><?php _e('Trial Days Left', 'wp-ultimo'); ?></label>

      <span class="wu-tooltip" title="<?php _e('Click to edit', 'wp-ultimo'); ?>"><?php printf(__('%s day(s)', 'wp-ultimo'), $subscription->get_trial()); ?></span>

      <input type="number" id="trial" name="trial" value="<?php echo $subscription->get_trial(); ?>" placeholder="<?php _e('Number in days', 'wp-ultimo'); ?>">

     </div>

   </div>

   <div class="wu-col-sm-3 wu-subscription-owner">

    <div class="wu-subscription-details">

      <?php $plan = $subscription->get_plan(); ?>

      <label for="plan_id"><?php _e('Subscription Plan', 'wp-ultimo'); ?></label>

      <span class="wu-tooltip" title="<?php _e('Click to edit', 'wp-ultimo'); ?>"><?php echo $plan->title ? $plan->title : '--' ?></span>
      
      <select id="plan_id" name="plan_id">
        
      <?php 

      $plans = WU_Plans::get_plans(); 

      foreach ($plans as $plan) :

      ?>

        <option <?php echo selected($plan->id, $subscription->plan_id); ?> value="<?php echo $plan->id; ?>"><?php echo $plan->title; ?></option>

      <?php endforeach; ?>

      </select>

    </div>

   </div>

   <div class="clear"></div>

</div>

<?php if ($subscription->price && $subscription->freq) : ?>
<div class="wu-subscription-details-actions wu-subscription-details-actions-last">
  <div class="row">

    <div class="wu-col-sm-12">
      
    <?php
    /**
     * @since  1.1.3 Shortcode buttons to allow fast changes to the subscription status
     */
    ?>

    <a href="#" class="wu-subscription-add button"><?php printf(__('Add %s month(s)', 'wp-ultimo'), $subscription->freq); ?></a>
    <a href="#" class="wu-subscription-remove button"><?php printf(__('Remove %s month(s)', 'wp-ultimo'), $subscription->freq); ?></a>

    </div>

  </div>
</div>
<?php endif; ?>

<div class="clear"></div>

<script type="text/javascript">
  
  (function($) {

    /**
     * @since  1.1.3 Call to extend
     */
    $(document).on('click', '.wu-subscription-add, .wu-subscription-remove', function(e) {

      e.preventDefault();

      var _this          = $(this);
      var _parent        = $('#wu-mb-subscriptions-details');
      var original_label = _this.html();
      var type           = _this.is('.wu-subscription-add') ? 'add' : 'remove';

      _this.attr('disabled', 'disabled').html('<?php _e('Loading...', 'wp-ultimo'); ?>');

      $.ajax({
        url: ajaxurl,
        method: 'post',
        data: {
          type: type,
          action: 'wu_extend_subscription',
          nonce: '<?php echo wp_create_nonce('wu-subscription-add-remove'); ?>',
          user_id: <?php echo esc_js($subscription->user_id); ?>
        },
        dataType: 'json',
        success: function(data) {

          $('#wu-mb-subscriptions-details').block({
            message: null,
            overlayCSS: {
              background: '#F1F1F1',
              opacity: 0.4
            }
          });

          _this.html(data.message);

          setTimeout(function() {

            // Change elements
            _parent.find('.remaining').html(data.remaining_string);
            _parent.find('.status').removeClass('active').removeClass('on-hold').removeClass('inactive').addClass(data.status).html(data.status_label);
            
            _parent.find('.active_until_value').html(data.active_until);
            _parent.find('#active_until').val(data.active_until);

            _this.html(original_label).removeAttr('disabled');

            $('#wu-mb-subscriptions-details').unblock();

          }, 2000);

        }
      })

    });

    /**
     * Display the fields
     */
    $('.wu-subscription-details').on('click', function(e) {

      var $this = $(this);

      $this.find('span').hide();
      $this.find('input, select').show().focus();

    });

    /**
     * Alert when values don't match
     */
    $('#publish').on('click', function(e) {

      var $form = $('#form-wu-edit-subscription');

      e.preventDefault();

      wuswal({
        title: "<?php _e('Are you sure?', 'wp-ultimo'); ?>",
        text: "<?php _e('Changing some elements of a subscription - <strong>like the price and billing frequency</strong> - will incur in the removal of the integration with the selected payment gateway. Please be aware of that fact before confirming those changes.', 'wp-ultimo'); ?>",
        type: "warning",
        showCancelButton: true,
        // confirmButtonColor: "#DD6B55",
        confirmButtonText: "<?php _e('Yes, I\'m sure', 'wp-ultimo'); ?>",
        cancelButtonText: "<?php _e('Cancel', 'wp-ultimo'); ?>",
        closeOnConfirm: false,
        closeOnCancel: true,
        showLoaderOnConfirm: true,
        html: true,
      },
      function(isConfirm) {
        if (isConfirm) {
          $form.submit();
        }
      });

    });

  })(jQuery);

</script>
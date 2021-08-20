<?php

/**
 * Get the subscription
 */
$subscription = wu_get_current_site()->get_subscription();

/**
 * Current Plan
 */
$plan = wu_get_current_site()->get_plan();

/**
 * Coupon Codes
 */
$coupon_code = $subscription->get_coupon_code();
$have_coupon = $coupon_code ?: false;

 
?>

<ul class="wu_status_list">
  
  <li class="current-plan-status">
    <p>
      <?php if ($subscription->is_free()): ?>

        <strong><?php printf(__('Plan %s', 'wp-ultimo'), $plan->title); ?></strong>: <?php _e('Free!', 'wp-ultimo'); ?>

      <?php else: ?>


        <?php if ($coupon_code): ?>
          <?php if ($subscription->is_free()): ?>
            <strong><?php printf(__('Plan %s', 'wp-ultimo'), $plan->title); ?></strong>: <?php printf(__("<span style='text-decoration: line-through;'>%s every %s month(s).</span> ", 'wp-ultimo'), wu_format_currency($subscription->price), $subscription->freq) . _e('Free!', 'wp-ultimo'); ?>
          <?php else: ?>
            <strong><?php printf(__('Plan %s', 'wp-ultimo'), $plan->title); ?></strong>: <?php printf(__("<span style='text-decoration: line-through;'>%s every %s month(s).</span> ", 'wp-ultimo'), wu_format_currency($subscription->price), $subscription->freq) . printf(__('%s every %s month(s).', 'wp-ultimo'), wu_format_currency($subscription->get_price_after_coupon_code()), $subscription->freq); ?>
          <?php endif; ?>
        <?php else: ?>
          <strong><?php printf(__('Plan %s', 'wp-ultimo'), $plan->title); ?></strong>: <?php printf(__('%s every %s month(s).', 'wp-ultimo'), wu_format_currency($subscription->price), $subscription->freq); ?>
        <?php endif; ?>

      <?php endif; ?>
    </p>


    <p id="coupon-code-field" <?php if (!$coupon_code) echo 'style="display: none;"' ?>>
      
      <?php echo $subscription->get_coupon_code_string(); ?>

    </p>

  </li>

  <li class="account-status">
    
    <?php
    /**
     * Trial Status
     */
    $site_trial = $subscription->get_trial();
    if ($site_trial && !$subscription->is_free()) :
    ?>
    <p>
      <strong><?php _e('Trial Period:', 'wp-ultimo'); ?></strong> 
      <?php printf(_n('You still have %s day left in your trial period. It will end on %s.', 'You still have %s days left in your trial period. It will end on %s.', $site_trial, 'wp-ultimo'), $site_trial, $subscription->get_date('trial_end')); ?>
    </p>
    <?php endif; ?>
    
    <?php
    /**
     * Display the integration
     */
    if (!$subscription->is_free()) :

    if ($subscription->integration_status) :
      
    ?>
    
    <p>
      <strong><?php _e('Payment Method:', 'wp-ultimo'); ?></strong> 
      
      <?php 
      /**
       * Get Gateway title
       */
      $gateway = wu_get_gateway($subscription->gateway);

      echo apply_filters('wu_account_integrated_method_title', $gateway ? $gateway->get_title() : ucfirst($subscription->gateway), $gateway, $subscription);
      
      /**
       * Allow plugin developers to add payment integration info
       * @since 1.7.0
       * @param WU_Gateway|false Gateway integrated
       * @param WU_SUbscription  Current user subscription
       */
      do_action('wu_account_integrated_method_actions_before', $gateway, $subscription);

      ?>
      
      <?php if (apply_filters('wu_account_display_cancel_integration_link', true)) : ?>
        - 
        <span class="plugins">
          <a href="<?php echo wu_get_active_gateway()->get_url('remove-integration'); ?>" class="delete"><?php _e('Cancel Payment Method', 'wp-ultimo'); ?></a>
        </span>
      <?php endif; ?>

      <?php
      
      /**
       * Allow plugin developers to add payment integration info
       * @since 1.7.0
       * @param WU_Gateway|false Gateway integrated
       * @param WU_SUbscription  Current user subscription
       */
      do_action('wu_account_integrated_method_actions_after', $gateway, $subscription);

      ?>

    </p>
    
    <?php else: ?>
    
      <p>
        <strong><?php _e('Payment Method:', 'wp-ultimo'); ?></strong> 
        <?php _e('No Payment Method integrated yet.', 'wp-ultimo'); ?>
      </p>
    
    <?php endif; endif; ?>
    
    <?php
    /**
     * Billing Starts
     */
    if (!$subscription->is_free()) :
    ?>
    <p>
      <strong><?php _e('Account valid until:', 'wp-ultimo'); ?></strong> 
      <?php echo $subscription->created_at == $subscription->active_until ? $subscription->get_date('trial_end') : $subscription->get_date('active_until'); ?>
    </p>
    <?php endif; ?>
    
  </li>

  <?php do_action('wu_account_integration_meta_box', $subscription, $plan); ?>
  
</ul>

<?php if (wu_get_current_site()->is_user_owner() && !$subscription->integration_status && !$subscription->is_free()) : ?>
<ul class="wu-button-upgrade-account">
  <li class="upgrade-account">

    <?php
    /**
     * Allow plugin developers to hide the integration buttons when certain things happen
     * @since 1.9.0
     */
    if (apply_filters('wu_display_payment_integration_buttons', true, $subscription)) : 
    
      $active_gateways = is_array(WU_Settings::get_setting('active_gateway')) ? WU_Settings::get_setting('active_gateway') : array();

      /**
       * @since  1.1.0 displays all possible gateways
       */
      foreach (wu_get_gateways() as $gateway) : $gateway = $gateway['gateway'];

        if (!in_array($gateway->id, array_keys($active_gateways))) continue;

        $content = $gateway->get_button_label();

      ?>

        <?php $class = !$subscription->integration_status ? 'button-primary' : '' ?>

        <?php ob_start(); ?>
        <a class="button <?php echo $class; ?> button-streched button-gateway" href="<?php echo $gateway->get_url('process-integration'); ?>">
        <strong><?php echo $content; ?></strong>
        </a>
        <?php $button = ob_get_clean(); ?>
        
        <?php echo apply_filters("wu_gateway_integration_button_$gateway->id", $button, $content); ?>

      <?php endforeach; ?>
    
    <?php endif; // end if; ?>

  </li>
</ul>
<?php endif; ?>

<script type="text/javascript">
  (function($) {
    $(document).ready(function() {

      /**
      * Alert when trying to cancel the account integration
      * @since 1.7.3
      */
      $('#wp-ultimo-status .account-status .delete').on('click', function (e) {

        e.preventDefault();

        var button = $(this);

        wuswal({
          title: wpu.delete_plan_title,
          text: <?php echo json_encode(apply_filters('wu_cancel_integration_text', __('Are you sure you want to cancel your current payment integration?', 'wp-ultimo'))); ?>,
          type: "warning",
          showCancelButton: true,
          // confirmButtonColor: "#DD6B55",
          confirmButtonText: wpu.delete_plan_confirm,
          cancelButtonText: wpu.delete_plan_cancel,
          closeOnConfirm: false,
          closeOnCancel: true,
          showLoaderOnConfirm: true,
          html: true,
        },
          function (isConfirm) {
            if (isConfirm) {
              window.location.href = button.attr('href');
            }
          });
      }); // end plan-delete;

    });
  })(jQuery);
</script>
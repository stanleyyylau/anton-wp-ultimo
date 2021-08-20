<ul>

  <li>
    <label>
      <?php _e('Integration Status', 'wp-ultimo'); ?>
    </label>
    <span><?php echo $subscription->integration_status ? __('Integrated', 'wp-ultimo') : __('Not Integrated', 'wp-ultimo') ; ?></span>
  </li>

  <?php if ($subscription->gateway) : ?>
  <li>
    <label>
      <?php _e('Gateway', 'wp-ultimo'); ?>
    </label>
    <span>
      <?php 
      
      /**
       * Display the Gateway name, captalized
       */
      echo ucfirst($subscription->gateway); 
      
      /**
       * Allow plugin developers to display links useful to this particular payment integration
       * 
       * @since 1.7.0
       * @param WU_Subscription The current subscription
       */
      do_action('wu_integration_status_widget_actions', $subscription);

      ?>
    </span>
  </li>
  <?php endif; ?>

  <?php if ($subscription->integration_status) : ?>

    <li>
      <label>
        <?php _e('Integration Key', 'wp-ultimo'); ?>
      </label>
      <span><?php echo $subscription->integration_key; ?></span>
    </li>

  <?php endif; ?>

  <?php do_action('wu_edit_subscription_integration_meta_box', $subscription); ?>

  <?php
  /**
   * Displays the remove integration Link
   * @since 1.7.0
   */

   $gateway = wu_get_gateway($subscription->gateway);
  
   $url = $gateway ? $gateway->get_admin_cancel_integration_url($subscription->user_id) : false;

  ?>

  <?php if ($subscription->integration_status && $url) : ?>

    <li>
      <a href="<?php echo $url; ?>" class="button button-streched"><?php _e('Cancel Subscription', 'wp-ultimo'); ?> <?php echo WU_Util::tooltip(__('This will remove the payment integration and cancel the subscription on the remote payment gateway, if that is supported', 'wp-ultimo')); ?></a>
    </li>

  <?php endif; ?>

</ul>

<ul>

  <li>
    <span><?php echo $subscription->get_coupon_code_string(); ?></span>
    <?php if ($subscription->get_coupon_code()) : ?>
      <a href="<?php echo wp_nonce_url(add_query_arg('action', 'remove_coupon'), 'remove_coupon'); ?>" class="delete red"><?php _e('Remove', 'wp-ultimo'); ?></a>
    <?php endif; ?>
  </li>

  <li>
    <input title="<?php if ($subscription->integration_status) echo __('You can only apply coupon codes to subscriptions that have not integrated a payment method yet.', 'wp-ultimo'); ?>"  name="coupon" class="regular-text <?php if ($subscription->integration_status) echo 'wu-tooltip'; ?>" placeholder="<?php _e('Enter a coupon code', 'wp-ultimo'); ?>">
    
    <button <?php disabled($subscription->integration_status); ?> class="button button-streched" name="apply-coupon-code" value="1"><?php _e('Apply Coupon Code', 'wp-ultimo'); ?></button>
  </li>

  <?php do_action('wu_edit_subscription_coupon_code_meta_box', $subscription); ?>

</ul>

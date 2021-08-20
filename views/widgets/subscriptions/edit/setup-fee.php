<?php
/**
 * Setup Fee Metabox
 * @since 1.7.0
 */
$has_plan = $subscription->has_plan();
$plan     = $subscription->get_plan();

?>

<label>
  <input <?php checked( ! $subscription->has_paid_setup_fee() ); ?> type="checkbox" name="should_charge_setup_fee"> <?php _e('Should charge Setup Fee?', 'wp-ultimo'); ?>
</label>

<br>
<br>

<?php if ($has_plan && $plan->has_setup_fee()) : ?>
  
  <p class="description"><?php printf(__('Setup Fee for %s: %s'), $plan->title, wu_format_currency($plan->get_setup_fee())); ?></p>

<?php else : ?>

  <p class="description"><?php printf(__('Setup Fee: %s', 'wp-ultimo'), wu_format_currency(0)); ?></p>

<?php endif; ?>

<p class="description">
  <?php _e('Checking this box will add the setup fee of this subscription plan to the total value the next time this user adds a payment option. This box gets automatically unchecked again when a setup fee payment is received.', 'wp-ultimo'); ?>
</p>

<?php do_action('wu_edit_subscription_setup_fee_meta_box', $subscription); ?>
<?php
/**
 * Gets the plans
 * @since 1.5.4
 */
$plans = WU_Plans::get_plans();

$frequencies = array(1 => __('Monthly', 'wp-ultimo'));
// Check if pricings 3 and 12 are allowed
if (WU_Settings::get_setting('enable_price_3'))  $frequencies[3]  = __('Quarterly', 'wp-ultimo');
if (WU_Settings::get_setting('enable_price_12')) $frequencies[12] = __('Yearly', 'wp-ultimo');


 ?>
<div class="input-text-wrap">
  <label for="user"><?php _e('Target User', 'wp-ultimo'); ?></label>
  <input type="text" name="user_id" id="user" class="regular-text" placeholder="<?php _e('Select the User you want to create a subscription for', 'wp-ultimo'); ?>">
</div>

<div class="input-text-wrap">
  <label for="user"><?php _e('Subscription Plan', 'wp-ultimo'); ?></label>
  <select name="plan_id" id="plan" placeholder="<?php _e('Select a Plan to assign to newly created subscription', 'wp-ultimo'); ?>">
    <?php foreach($plans as $plan) : ?>
        <option value="<?php echo $plan->id; ?>"><?php echo $plan->title; ?></option>
    <?php endforeach; ?>
  </select>
</div>

<div class="input-text-wrap">
  <label for="user"><?php _e('Subscription Frequency', 'wp-ultimo'); ?></label>
        <select id="freq" name="freq">

          <?php foreach($frequencies as $freq => $freq_label) : ?>

            <option value="<?php echo $freq; ?>"><?php echo $freq_label; ?></option>

          <?php endforeach; ?>

        </select>
</div>

<style>
.input-text-wrap {
    padding: 6px 0;
}

.input-text-wrap  label{
    display: block;
    font-weight: 700;
    text-transform: uppercase;
    font-size: 11px;
    padding: 3px 0;
}

.select2-container {
    width: 100%;
}
</style>
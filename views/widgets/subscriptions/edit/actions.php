<div class="submitbox" id="submitpost">

  <div class="wpultimo-subscription-avatar-block">

    <div class="wpultimo-subscription-avatar">

      <?php echo get_avatar($user_id, 90, 'identicon', '', array(
        'force_display' => true,
      )); ?>

    </div>

    <div class="wpultimo-subscription-user-info">

      <strong><?php echo $user->display_name; ?> <small><?php printf(__('(ID: %s)', 'wp-ultimo'), $user_id); ?></small></strong>

      <span><a href="mailto:<?php echo $user->user_email; ?>"><?php echo $user->user_email; ?></a></span>

      <br>

      <a href="<?php echo get_edit_user_link($user_id); ?>" target="_blank" class="button button-streched">

        <?php _e('Visit Profile &rarr;', 'wp-ultimo'); ?>

      </a>

    </div>

  </div>

  <div id="minor-publishing">

    <!-- .misc-pub-section -->

    <div class="misc-pub-section curtime misc-pub-curtime">

      <p class="wpultimo-price-text">

        <label for="free-plan">

          <input v-model="is_free" type="checkbox" <?php checked($subscription->price == 0); ?> name="free" id="free-plan">

          <?php _e('Mark as Free', 'wp-ultimo'); ?>

        </label>

      </p>

      <p class="wpultimo-price-text">

        <?php _e('<strong>Important</strong>: Any changes made to this subscription price and frequency will remove the current integration.', 'wp-ultimo'); ?>

      </p>

      <div v-show="!is_free" class="wpultimo-price wpultimo-price-first" style="margin-top: 10px;">

        <label for="price">

          <?php _e('Subscription Price', 'wp-ultimo'); ?> (

          <?php echo get_wu_currency_symbol(); ?>)

        </label>

        <input placeholder="<?php echo wu_format_currency(0); ?>" id="price" name="price" class="wpultimo-money" value="<?php echo $subscription->price ?>">

      </div>

      <div v-show="!is_free" class="wpultimo-price wpultimo-price-first">

        <label for="freq">

          <?php _e('Subscription Frequency', 'wp-ultimo'); ?>

        </label>

        <?php

        $frequencies = array(1 => __('Monthly', 'wp-ultimo'));

        // Check if pricings 3 and 12 are allowed
        if (WU_Settings::get_setting('enable_price_3'))  $frequencies[3]  = __('Quarterly', 'wp-ultimo');
        if (WU_Settings::get_setting('enable_price_12')) $frequencies[12] = __('Yearly', 'wp-ultimo');

        ?>

        <select name="freq">

          <?php foreach($frequencies as $freq => $freq_label) : ?>

            <option <?php selected($freq, $subscription->freq); ?> value="<?php echo $freq; ?>"><?php echo $freq_label; ?></option>

          <?php endforeach; ?>

        </select>

      </div>

      <div class="clear"></div>

    </div>

    <div class="clear"></div>

  </div>

  <div id="major-publishing-actions">

    <input name="save_subscription" type="hidden" value="save_subscription">

    <input type="submit" id="publish" class="button button-primary button-large button-streched" value="<?php _e('Update Subscription', 'wp-ultimo'); ?>">

    <div class="clear"></div>

  </div>

</div>

<script type="text/javascript">

  var price_toggle = new Vue({
    el: "#wu-mb-actions",
    data: {
      is_free: <?php echo json_encode($subscription->price == 0); ?>,
    },
  });

</script>
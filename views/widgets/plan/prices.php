<div class="submitbox" id="submitpost">
  
  <div id="minor-publishing">
      
    <!-- .misc-pub-section -->
    <div class="misc-pub-section curtime misc-pub-curtime">
      
      <p class="wpultimo-price-text">
        <?php _e('Use this block to define the prices of each time interval.', 'wp-ultimo'); ?>
      </p>
      
      <p class="wpultimo-price-text" v-show="!is_contact_us">
        <label for="free-plan">
          <input v-model="is_free" type="checkbox" <?php checked($plan->free); ?> name="free" id="free-plan">
          <?php _e('Is this a Free Plan?', 'wp-ultimo'); ?>
        </label>
      </p>

      <p class="wpultimo-price-text" v-show="!is_free">
        <label for="is_contact_us">
          <input v-model="is_contact_us" type="checkbox" <?php checked($plan->is_contact_us); ?> name="is_contact_us" id="is_contact_us">
          <?php _e('Is this a <em>Contact Us</em> plan?', 'wp-ultimo'); ?>
        </label>
      </p>

      <div class="wrapper-prices" v-show="!is_free && !is_contact_us" style="overflow: hidden; border-bottom: 1px solid rgb(236, 236, 236); padding-bottom: 20px;">
      
        <?php
        
        $prices = array(
          1  => __('Monthly Price', 'wp-ultimo'), 
          3  => __('3 mo Price', 'wp-ultimo'), 
          12 => __('Yearly Price', 'wp-ultimo'), 
        );
        
        foreach ($prices as $price => $label) : 

          // Check Status
          $blocked = !WU_Settings::get_setting("enable_price_$price");

          // HTML elements
          if ($blocked) {

            $message = sprintf(__('%s is disabled on the WP Ultimo\'s settings.', 'wp-ultimo'), $label);

            $title = sprintf('title="%s"', $message);

            $disabled = 'disabled="disabled"';

            $class = 'wu-tooltip';

          } else {

            $title = '';

            $disabled = '';

            $class = '';

          } // end if;
          
          ?>
        <div <?php echo $title; ?> class="wpultimo-price <?php echo $class; ?> <?php echo $price == 1 ? 'wpultimo-price-first' : ''; ?>">

          <label for="price_<?php echo $price; ?>">
            <?php echo $label ?> (<?php echo get_wu_currency_symbol(); ?>)
          </label>

          <input <?php echo $disabled; ?> placeholder="<?php echo wu_format_currency(0); ?>" id="price_<?php echo $price; ?>" name="price_<?php echo $price; ?>" class="wpultimo-money" value="<?php echo $plan->{"price_$price"}; ?>">

        </div>
        
        <?php endforeach; ?>

      </div>

      <div class="wrapper-prices" v-show="is_contact_us" style="overflow: hidden; border-bottom: 1px solid rgb(236, 236, 236); padding-bottom: 20px;">
      
        <div title="<?php _e('This will be used on the pricing table button for this plan.', 'wp-ultimo'); ?>" class="wpultimo-price wu-tooltip wpultimo-price-first">

          <label for="contact_us_label">
            <?php _e('Button Label', 'wp-ultimo'); ?>
          </label>

          <input placeholder="<?php _e('Contact Us', 'wp-ultimo'); ?>" id="contact_us_label" name="contact_us_label" class="wpultimo-money" value="<?php echo $plan->get_contact_us_label(); ?>">

        </div>

        <div title="<?php _e('The user will be redirected to this URL when he clicks on the CTA button.', 'wp-ultimo'); ?>" class="wpultimo-price wu-tooltip wpultimo-price-first">

          <label for="contact_us_link">
            <?php _e('Contact Page Link', 'wp-ultimo'); ?>
          </label>

          <input placeholder="e.g. <?php echo home_url('/contact'); ?>" id="contact_us_link" name="contact_us_link" class="wpultimo-money" value="<?php echo $plan->contact_us_link; ?>">

        </div>

      </div>

      <div title="<?php _e('Leave blank or 0 for no setup fee.', 'wp-ultimo'); ?>" class="wpultimo-price wu-tooltip wpultimo-price-first" style="margin-top: 10px;">

        <label for="setup_fee">
          <?php _e('Setup Fee', 'wp-ultimo'); ?> (<?php echo get_wu_currency_symbol(); ?>)
        </label>

        <input placeholder="<?php echo wu_format_currency(0); ?>" id="setup_fee" name="setup_fee" class="wpultimo-money" value="<?php echo $plan->setup_fee; ?>">

      </div>

      <div class="clear"></div>

    </div>
    
    <div class="clear"></div>

  </div>
  
  <div id="major-publishing-actions">

    <input name="original_publish" type="hidden" id="original_publish" value="Publish">
    <input type="submit" name="save_plan" id="publish" class="button button-primary button-large button-streched" value="<?php echo $edit ? __('Update Plan', 'wp-ultimo') : __('Create Plan', 'wp-ultimo'); ?>">
    
    <div class="clear"></div>
  </div>
  
</div>

<script type="text/javascript">

  var price_toggle = new Vue({
    el: "#submitdiv",
    data: {
      is_free: <?php echo json_encode($plan->free); ?>,
      is_contact_us: <?php echo json_encode($plan->is_contact_us); ?>,
    },
  });

</script>
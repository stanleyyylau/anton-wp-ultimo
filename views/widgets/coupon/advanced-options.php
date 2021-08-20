<div class="panel-wrap product_data">
  
  <ul class="product_data_tabs wc-tabs" style="">

    <?php 

    /**
     * Tabs
     * @since 1.5.5 Added the Plans and Billing Frequency Limitations
     */
    $advanced_options_tabs = apply_filters('wu_coupons_advanced_options_tabs', array(
      'general' => __('General', 'wp-ultimo'),
      'plans'   => __('Plans & Frequencies', 'wp-ultimo'),
    ));

    foreach ($advanced_options_tabs as $tab => $tab_label) : ?>

      <li class="<?php echo $tab; ?>_options <?php echo $tab; ?>_tab">
        <a href="#wu_<?php echo $tab; ?>"><?php echo $tab_label; ?></a>
      </li>

    <?php endforeach; ?>
    
  </ul>

  <div id="wu_general" class="panel wu_options_panel">
    
    <div class="options_group">
      <p class="form-field allowed_uses_field">
        <label for="allowed_uses">
          <?php _e('Allowed Uses', 'wp-ultimo'); ?>
          <?php echo WU_Util::tooltip(__('How many times will this coupon be available? Leave 0 for unlimited uses.', 'wp-ultimo')); ?>
        </label>
        <input type="number" class="short" style="" name="allowed_uses" id="allowed_uses" value="<?php echo $coupon->allowed_uses ? $coupon->allowed_uses : '0' ?>" placeholder="0">
      </p>
    </div>

    <?php // if ($coupon->id > 0) : ?>
    <div class="options_group">
      <p class="form-field">
        <label class="form-field-full">
          <?php printf(__('This coupon was used %d time(s) so far.', 'wp-ultimo'), $coupon->uses); ?>
        </label>
      </p>
    </div>
    <?php // endif; ?>

    <div class="options_group">
      <p class="form-field expiring_date_field">
        <label for="expiring_date">
          <?php _e('Expiring Date', 'wp-ultimo'); ?>
          <?php echo WU_Util::tooltip(__('For how long should this coupon be accepted?', 'wp-ultimo')); ?>
        </label>
        <input type="text" data-format="Y-m-d H:i:S" class="short wu-datepicker" data-allow-time="true" style="" name="expiring_date" id="expiring_date" value="<?php echo $coupon->expiring_date ? $coupon->expiring_date : '' ?>" placeholder="<?php _e('Click to edit', 'wp-ultimo'); ?>">
        <button onclick="event.preventDefault(); jQuery('#expiring_date').val('')" style="margin-left: 5px" class="button"><?php _e('Clear Date', 'wp-ultimo'); ?></button>
      </p>
    </div>

    <div class="options_group">
      <p class="form-field cycles_field">
        <label for="cycles">
          <?php _e('Billing Cycles', 'wp-ultimo'); ?>
          <?php echo WU_Util::tooltip(__('For how many billing cycles should this discount apply? Leave 0 for unlimited cycles.', 'wp-ultimo')); ?>
        </label>
        <input type="number" class="short" style="" name="cycles" id="cycles" value="<?php echo $coupon->cycles ? $coupon->cycles : '' ?>" placeholder="0">
      </p>
    </div>

    <?php

    /**
     * @since  1.2.0 Hook to allow the inclusion of extra fields from add-ons
     */
    do_action('wp_ultimo_coupon_advanced_options', $coupon);

    ?>

  </div>

  <div id="wu_plans" class="panel wu_options_panel">

    <?php
    /**
     * @since 1.5.5
     */
    ?>
    <div class="options_group">
      <p class="form-field">
        <label class="form-field-full">
          <?php printf(__('Plan & Frequency Limitation Options', 'wp-ultimo'), $coupon->uses); ?>
        </label>
      </p>
    </div>

    <div class="options_group">
      <?php 
      /**
       * Loop the plan types to allow users to check
       * @since 1.5.5
       */
      $plans = WU_Plans::get_plans(); 
      
      foreach($plans as $plan) :
      ?>
      
        <p class="form-field">
          <label for="<?php echo $plan->get_id(); ?>">
            <?php echo $plan->title; ?><br>
          </label>
          <input <?php checked($coupon->is_plan_allowed($plan->get_id())); ?> type="checkbox" class="checkbox" style="" name="allowed_plans[]" id="<?php echo $plan->get_id(); ?>" value="<?php echo $plan->get_id(); ?>"> 
          <span class="description"><?php printf(__('Allow the Coupon to be used with this Plan', 'wp-ultimo')); ?></span>
        </p>
      <?php endforeach; ?>
      </div>
      
      <div class="options_group">
      <?php 
      /**
       * Loop the plan types to allow users to check
       * @since 1.5.5
       */
      $freqs = array(
        1  => __('Monthly', 'wp-ultimo'), 
        3  => __('Quarterly', 'wp-ultimo'), 
        12 => __('Yearly', 'wp-ultimo'), 
      );
      
      foreach($freqs as $freq => $freq_name) :
      ?>
      
        <p class="form-field">
          <label for="<?php echo $freq; ?>">
            <?php echo $freq_name; ?><br>
          </label>
          <input <?php checked($coupon->is_freq_allowed($freq)); ?> type="checkbox" class="checkbox" style="" name="allowed_freqs[]" id="<?php echo $freq; ?>" value="<?php echo $freq; ?>"> 
          <span class="description"><?php printf(__('Allow the Coupon to be used with this billing frequency', 'wp-ultimo')); ?></span>
        </p>
      <?php endforeach; ?>
      </div>
        
    </div>

    <?php 

    /**
     * Displays the extra option panels for added Tabs
     */
    
    do_action('wu_coupons_advanced_options_after_panels', $coupon);

    ?>
  
  <div class="clear"></div>
</div>
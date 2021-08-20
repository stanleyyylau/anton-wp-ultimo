<?php 
$current_plan = wu_get_current_site()->get_plan();
$plans        = WU_Plans::get_plans(true, $current_plan->id);
$subscription = wu_get_current_site()->get_subscription();
$coupon_code = $subscription->get_coupon_code();
?>

<div class="wu-plan-change">

<?php if (empty($plans)) : ?>

<div class="wu-setup-content-error">
  <p><?php _e('There is no plans created in the platform.', 'wp-ultimo'); ?></p>
</div>

<?php else: ?>

<?php $count = count($plans); $percent = 100 / $count; ?>

  <?php wp_nonce_field('signup_form_1', '_signup_form'); ?>
  
  <div class="wu-plan-selector">
    <?php foreach($plans as $plan) : ?>

      <?php
      $plan_attrs = '';
      foreach(array(1, 3, 12) as $type) {
        $price = $plan->free ? __('Free!', 'wp-ultimo') : str_replace(get_wu_currency_symbol(), '', wu_format_currency( round( ( (int) $plan->{"price_".$type})  / $type )));
        $plan_attrs .= " data-price-$type='$price'";
      }

      $class = $current_plan->id == $plan->id ? 'plan-active' : '';

      ?>

      <div data-plan="<?php echo $plan->id; ?>" <?php echo $plan_attrs; ?> class="<?php echo $class; ?> wu-plan-selector-plan wu-plan">

        <!-- Price -->
        <?php if ($plan->free) : ?>
          <span class="plan-price"><?php _e('Free!', 'wp-ultimo'); ?></span>
        <?php else : ?>
          <span class="plan-price">
            <span class="superscript"></span>
            
            <?php 
            if ($current_plan && $plan->id == $current_plan->id) {

              if ($coupon_code && $subscription->get_price_after_coupon_code() != $subscription->price) {
                echo "<span style='text-decoration: line-through;'>";

                echo str_replace(get_wu_currency_symbol(), '', wu_format_currency($subscription->price));


                if     ($subscription->freq == 1)  echo __('/mo', 'wp-ultimo');
                elseif ($subscription->freq == 3)  echo __(' every 3 months', 'wp-ultimo');
                elseif ($subscription->freq == 12) echo __('/year', 'wp-ultimo');

                echo '</span> ';
                echo '<br />';

                // echo $subscription->get_price_after_coupon_code();
                $price = $subscription->get_price_after_coupon_code() ? wu_format_currency($subscription->get_price_after_coupon_code()) : __('Free!', 'wp-ultimo');

                // new

                echo $price;

              } else {

                echo wu_format_currency($subscription->price); 

              }// end if;

            } else {
              echo wu_format_currency($plan->{"price_$subscription->freq"}); 
            }
            ?>
          
            <?php 
            if (is_int($price)) {
              if     ($subscription->freq == 1)  echo __('/mo', 'wp-ultimo');
              elseif ($subscription->freq == 3)  echo __(' every 3 months', 'wp-ultimo');
              elseif ($subscription->freq == 12) echo __('/year', 'wp-ultimo');
            }
            ?>
          </span>
        <?php endif; ?>
        <!-- end Price -->

        <strong><?php echo strtoupper($plan->title); ?></strong>
        <?php echo $current_plan && $plan->id == $current_plan->id ? '<code>'.__('current', 'wp-ultimo').'</code>' : '' ?>
        
        <?php if ($plan->description) : ?>
          <br><small><?php echo $plan->description; ?>&nbsp;</small><br>
        <?php endif; ?>

      </div>


    <?php endforeach; ?>

    <div style="clear: both"></div>
  </div>

  <?php if (wu_get_current_site()->is_user_owner()) : ?>
  <div class="sub wu-change-button">
    <a href="<?php echo wu_get_active_gateway()->get_url('change-plan'); ?>" class="button button-primary button-streched"><?php _e('Change Plan', 'wp-ultimo'); ?></a>
  </div>
  <?php endif; ?>

</div>
  
<?php endif; ?>
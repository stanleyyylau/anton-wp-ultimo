
<?php 

$subscription = wu_get_subscription($user->ID); 

// create subscription in case of an inexsistent one
if (!$subscription) {

  $subscription = new WU_Subscription((object) array(
    'user_id'      => $user->ID,
    'created_at'   => date('Y-m-d H:i:s'),
    'active_until' => date('Y-m-d H:i:s', strtotime('+1 day')),
  ));

  $subscription = $subscription->save();

} // end if create subs;

?>

<h2 id="wp-ultimo-options"><?php _e('WP Ultimo Options', 'wp-ultimo'); ?></h2>

<table class="form-table">
  <tbody>

    <!-- WP Ultimo: Site Plan -->
    <tr class="form-field form-required">

      <th scope="row">
        <label for="site_list"><?php _e('Sites List', 'wp-ultimo'); ?></label>
      </th>

      <td>

        <?php foreach($subscription->get_sites() as $site) : 

          $site = wu_get_site($site->site_id); 

          if (!$site->site) continue; ?>

          <strong><?php echo $site->site->blogname; ?></strong>: <a href="<?php echo get_admin_url($site->ID); ?>"><?php _e('Dashboard', 'wp-ultimo'); ?></a> - <a href="<?php echo get_site_url($site->ID); ?>"><?php _e('Visit Site', 'wp-ultimo'); ?></a><br>

        <?php endforeach; ?>

      </td>

    </tr>

    <!-- WP Ultimo: Site Plan -->
    <!-- <tr class="form-field form-required">

      <th scope="row">
        <label for="subscription_plan"><?php _e('Subscription Plan', 'wp-ultimo'); ?></label>
      </th>

      <td>

        <select id="subscription_plan" name="subscription_plan">
          <option value=''><?php _e('Select Plan', 'wp-ultimo'); ?></option>
          <?php

          // Get plans and make the dropdown
          $plans = WU_Plans::get_plans();
          
          // Loop them
          foreach ($plans as $plan) :

          ?>

          <option <?php selected($plan->id, $subscription->plan_id); ?> value="<?php echo $plan->id; ?>"><?php echo $plan->title; ?></option>

          <?php endforeach; ?>
        </select>

        <br><br>

        <?php if ($subscription->plan_id) : ?>

          <span class="description"><?php _e('Select a new plan to be applied to this user. By default, this option will only change the plan of the user (his quotas and limitations) and not the price on his subscription (if the user pays $19.99 per month, for example, that value and his payment integration (PayPal or Stripe) will continue the same. To also apply the price of the new plan to the user <strong>you need to mark the checkbox below</strong>. Marking the checkbox below will cancel the current subscription of the user (Paypal, Stripe or other gateway).', 'wp-ultimo'); ?></span>

        <?php else: ?>

          <span class="description"><?php _e('Select a new plan to be applied to this user.', 'wp-ultimo'); ?></span>

        <?php endif; ?>

      </td>

    </tr> -->

    <?php if (false) : // ($subscription->plan_id) : ?>

    <!-- WP Ultimo: Site Template -->
    <tr class="form-field form-required">

      <th scope="row">
        <label for="change_price"><?php _e('Change Price', 'wp-ultimo'); ?></label>
      </th>

      <td>

        <label for="change_price">
          <input name="change_price" type="checkbox" id="change_price" value="1">
          <?php _e('Change prices on the user subscription and cancel the current gateway integration.', 'wp-ultimo'); ?>
          <?php echo WU_Util::tooltip( __('', 'wp-ultimo') ); ?>
        </label>

      </td>

    </tr>

    <?php endif; ?>

  </tbody>
</table>
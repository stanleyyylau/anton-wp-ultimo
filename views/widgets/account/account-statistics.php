  <?php
  /**
   * Trial Status
   */
  $site_trial = wu_get_current_site()->get_subscription()->get_trial();
  ?>
<ul class="wu_status_list">
  
  <?php
  /**
   * Current Plan 
   */
  $plan = wu_get_current_site()->get_subscription()->get_plan();
  ?>
  <li class="current-plan" style="<?php echo $site_trial ? 'width: 50%' : ''; ?>">
    <div>
      <strong><?php echo $plan->title; ?></strong> <?php _e('your current plan', 'wp-ultimo'); ?>				
    </div>
  </li>

  <?php
  /**
   * Trial Status
   */
  if ($site_trial) :
  ?>
  <li class="trial" style="<?php echo $site_trial ? 'width: 50%' : ''; ?>">
    <div>
    <strong><?php printf(_n('%s day', '%s days', $site_trial, 'wp-ultimo'), $site_trial); ?></strong> <?php _e('remaining time in trial', 'wp-ultimo'); ?></div>
  </li>
  <?php endif; ?>
  
  <?php
  /**
   * Spaced Used
   */
  $space_used      = get_space_used();
  $space_allowed   = get_space_allowed() ?: 1;
  $percentage      = ceil($space_used / $space_allowed * 100);
  $unlimited_space = get_site_option( 'upload_space_check_disabled' ); 
  $message = $unlimited_space ? '%s' : '%s / %s (%s%s)';

  ?>
  <li class="space-used">
    <div>
      <strong><?php printf($message, WU_Util::format_megabytes($space_used), WU_Util::format_megabytes($space_allowed), $percentage, '%'); ?></strong> <?php _e('space used', 'wp-ultimo'); ?>
    </div>
  </li>
  
  <?php
  /**
   * Users
   */
  $users_quota = $plan->get_quota('users') + 1;
  $users       = wu_get_current_site()->get_user_count();
  $url         = admin_url('users.php');

  $unlimited = $plan->should_allow_unlimited_extra_users();

  ?>
  <li class="total-users">
    <a href="<?php echo $url ?>">
    <strong><?php printf(__('%s / %s', 'wp-ultimo'), $users, $unlimited ? __('Unlimited', 'wp-ultimo') : $users_quota); ?></strong> <?php _e('users', 'wp-ultimo'); ?></a>
  </li>
  
</ul>

<?php if (wu_get_current_site()->is_user_owner()) : ?>
<ul class="wu-button-upgrade-account">
  <li class="upgrade-account">
    <p>
    <a class="button button-primary button-streched" href="<?php echo admin_url('admin.php?page=wu-my-account'); ?>">
    <strong><?php _e('See Account Summary', 'wp-ultimo'); ?></strong></a>
    </p>
  </li>
</ul>
<?php endif; ?>
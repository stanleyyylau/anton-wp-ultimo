<ul id="wu-account-actions">
  
  <?php do_action('wu_button_subscription_on_site'); ?>

  <?php if ($subscription && $subscription->get_site_count() > 1) : ?>
    <li>
      <a href="<?php echo admin_url('ms-delete-site.php'); ?>">
        <?php _e('Remove Site', 'wp-ultimo'); ?>
      </a>
    </li>
  <?php endif; ?>

  <li>
    <a href="<?php echo admin_url('admin.php?page=wu-remove-account'); ?>">
      <?php _e('Delete Account', 'wp-ultimo'); ?>
    </a>
  </li>
  
</ul>
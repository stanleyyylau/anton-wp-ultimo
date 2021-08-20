<?php

$old_version = defined('WPULTIMO_SUNRISE_VERSION') ? WPULTIMO_SUNRISE_VERSION : '0.0.1';
$new_version = WP_Ultimo()->sunrise_version;

// '<a href="'. network_admin_url('admin.php?page=wu-setup&step=checks') .'">'

?>

<div class="error">
  <p><?php printf(__('You are using an old version of WP Ultimo\'s sunrise.php (%s) on your install. The recomended version is %s. To update your sunrise.php, copy the sunrise.php file from the WP Ultimo plugin folder and paste in the %s folder, replacing the old file.', 'wp-ultimo'), "<code>$old_version</code>", "<code>$new_version</code>", "<code>". WP_CONTENT_DIR ."</code>", '</a>'); ?></p>
  <p>
    <button class="wu-ajax-button button" data-restore="false" data-url="<?php echo admin_url('admin-ajax.php?action=wu_upgrade_sunrise'); ?>"><?php _e('Upgrade Automatically', 'wp-ultimo'); ?></button>
  </p>
</div>
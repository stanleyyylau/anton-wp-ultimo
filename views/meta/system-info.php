<?php global $wp_filesystem, $wpdb;
$pad_spaces = 45;
?>

<div id="wp-ultimo-wrap" class="wrap">
  
  <h1><?php _e('System Info - WP Ultimo', 'wp-ultimo'); ?></h1>

  <?php
  /**
   * @since  1.2.2 Logs directory message
   */
  if (!is_writable( WU_Logger::get_logs_folder() )) : ?>

    <div class="notice notice-warning is-dismissible">
      <p><?php printf(__('Your <strong>%s</strong> directory does not seem to be writable. WP Ultimo will not be able to log important information about payment gateways and cron jobs. To fix that problem, change the permissions of the uploads folder to 755. You can check the status of the directory on the box below.', 'wp-ultimo'), WU_Logger::get_logs_folder()); ?></p>
    </div>

  <?php endif; ?>

  <p class="description"><?php _e('Check your system running WP Ultimo.', 'wp-ultimo'); ?></p>

  <h2 class="nav-tab-wrapper woo-nav-tab-wrapper" style="margin: 20px 0 40px 0;">
    
    <?php 
    /**
     * Get all sections to loop them as hooks
     */
    $tabs = apply_filters('wu_system_info_tabs', array(
      'summary' => array('title' => __('System Summary', 'wp-ultimo')),
      'logs'    => array('title' => __('Logs', 'wp-ultimo')),
    ));

    if (!isset($_GET['wu-tab'])) {

      $_GET['wu-tab'] = 'summary';

    } // end if;

    ?>
    
    <?php foreach($tabs as $tab_slug => $tab) : ?>
    
    <a href="<?php echo network_admin_url('admin.php?page=wp-ultimo-system-info&wu-tab=') . $tab_slug; ?>" class="nav-tab <?php echo esc_attr(isset($_GET['wu-tab']) && $_GET['wu-tab'] == $tab_slug ? "nav-tab-active" : ""); ?>"><?php echo $tab['title']; ?></a>
    
    <?php endforeach; ?>
    
  </h2>

  <?php

  /**
   * TAB SUMMARY
   */
  if ($_GET['wu-tab'] == 'summary') {

      $theme                  = wp_get_theme();
      $browser                = $system_info->get_browser();

      $plugins                   = $system_info->get_all_plugins();
      $active_plugins            = $system_info->get_active_plugins();
      $active_plugins_main_site  = $system_info->get_active_plugins_on_main_site();

      $memory_limit           = (int) str_replace('M', '', ini_get('memory_limit'));
      $memory_usage           = $system_info->get_memory_usage();

      $max_execution_time     = sprintf(__('%s seconds', 'wp-ultimo'), ini_get('max_execution_time'));

      $all_options            = $system_info->get_all_options();
      $all_options_serialized = serialize($all_options);
      $all_options_bytes      = round(mb_strlen($all_options_serialized, '8bit') / 1024, 2);
      $all_options_transients = $system_info->get_transients_in_options($all_options);

      ?>

    <div id="summary" data-type="heading">
      <h3><?php _e('System Summary', 'wp-ultimo'); ?></h3>
      <p><?php _e('A detailed summary of your system settings.', 'wp-ultimo'); ?></p>
    </div>

    <textarea  onclick="this.focus();this.select()" readonly="readonly" wrap="off" style="width: 100%; height: 600px; font-family: monospace;">
`
-----------------------------------------------------------------
------------------ WORDPRESS & SYSTEM SETTINGS ------------------
-----------------------------------------------------------------

<?php echo str_pad(__('WP Ultimo Version', 'wp-ultimo') . ":", $pad_spaces); ?><?php echo WP_Ultimo()->version . "\n"; ?>

<?php echo str_pad(__('WordPress Version', 'wp-ultimo') . ":", $pad_spaces); ?><?php echo get_bloginfo('version') . "\n"; ?>
<?php echo str_pad(__('PHP Version', 'wp-ultimo') . ":", $pad_spaces); ?><?php echo PHP_VERSION . "\n"; ?>
<?php echo str_pad(__('MySQL Version', 'wp-ultimo') . ":", $pad_spaces); ?><?php echo $wpdb->db_version() . "\n"; ?>
<?php echo str_pad(__('Web Server', 'wp-ultimo') . ":", $pad_spaces); ?><?php echo $_SERVER['SERVER_SOFTWARE'] . "\n"; ?>

<?php echo str_pad(__('Multi-Site Active', 'wp-ultimo') . ":", $pad_spaces); ?><?php echo is_multisite() ? _e('Yes', 'sysinfo') . "\n" : _e('No', 'sysinfo') . "\n" ?>
<?php echo str_pad(__('Subdomain Install', 'wp-ultimo') . ":", $pad_spaces); ?><?php echo is_subdomain_install() ? _e('Yes', 'sysinfo') . "\n" : _e('No', 'sysinfo') . "\n" ?>

<?php echo str_pad(__('WordPress URL', 'wp-ultimo') . ":", $pad_spaces); ?><?php echo get_bloginfo('wpurl') . "\n"; ?>
<?php echo str_pad(__('Home URL', 'wp-ultimo') . ":", $pad_spaces); ?><?php echo get_bloginfo('url') . "\n"; ?>

<?php echo str_pad(__('Content Directory', 'wp-ultimo') . ":", $pad_spaces); ?><?php echo WP_CONTENT_DIR . "\n"; ?>
<?php echo str_pad(__('Content URL', 'wp-ultimo') . ":", $pad_spaces); ?><?php echo WP_CONTENT_URL . "\n"; ?>
<?php echo str_pad(__('Plugins Directory', 'wp-ultimo') . ":", $pad_spaces); ?><?php echo WP_PLUGIN_DIR . "\n"; ?>
<?php echo str_pad(__('Plugins URL', 'wp-ultimo') . ":", $pad_spaces); ?><?php echo WP_PLUGIN_URL . "\n"; ?>
<?php echo str_pad(__('Uploads Directory', 'wp-ultimo') . ":", $pad_spaces); ?><?php echo (defined('UPLOADS') ? UPLOADS : WP_CONTENT_DIR . '/uploads') . "\n"; ?>

<?php echo str_pad(__('Cookie Domain', 'wp-ultimo') . ":", $pad_spaces); ?><?php echo defined('COOKIE_DOMAIN') ? COOKIE_DOMAIN ? COOKIE_DOMAIN . "\n" : _e('Disabled', 'sysinfo') . "\n" : _e('Not set', 'sysinfo') . "\n" ?>

<?php

$database_time = $wpdb->get_row('SELECT NOW() as time;');
$db_time = date('Y-m-d H:i:s', strtotime($database_time->time));

?>
<?php echo str_pad(__('PHP Current Time', 'wp-ultimo') . ":", $pad_spaces); ?><?php echo WU_Transactions::get_current_time() . "\n"; ?>
<?php echo str_pad(__('Database Current Time', 'wp-ultimo') . ":", $pad_spaces); ?><?php echo $db_time . "\n"; ?>

<?php echo str_pad(__('PHP cURL Support', 'wp-ultimo') . ":", $pad_spaces); ?><?php echo (function_exists('curl_init')) ? _e('Yes', 'sysinfo') . "\n" : _e('No', 'sysinfo') . "\n"; ?>
<?php echo str_pad(__('PHP GD Support', 'wp-ultimo') . ":", $pad_spaces); ?><?php echo (function_exists('gd_info')) ? _e('Yes', 'sysinfo') . "\n" : _e('No', 'sysinfo') . "\n"; ?>
<?php echo str_pad(__('PHP Memory Limit', 'wp-ultimo') . ":", $pad_spaces); ?><?php echo $memory_limit . "M \n"; ?>
<?php echo str_pad(__('PHP Memory Usage', 'wp-ultimo') . ":", $pad_spaces); ?><?php echo $memory_usage . "M (" . round($memory_usage / $memory_limit * $pad_spaces, 0) . "%)\n"; ?>
<?php echo str_pad(__('PHP Post Max Size', 'wp-ultimo') . ":", $pad_spaces); ?><?php echo ini_get('post_max_size') . "\n"; ?>
<?php echo str_pad(__('PHP Upload Max Size', 'wp-ultimo') . ":", $pad_spaces); ?><?php echo ini_get('upload_max_filesize') . "\n"; ?>
<?php echo str_pad(__('PHP Max Execution Time', 'wp-ultimo') . ":", $pad_spaces); ?><?php echo $max_execution_time . "\n"; ?>
<?php echo str_pad(__('PHP Allow URL Fopen', 'wp-ultimo') . ":", $pad_spaces); ?><?php echo ini_get('allow_url_fopen') . "\n"; ?>
<?php echo str_pad(__('PHP Max File Uploads', 'wp-ultimo') . ":", $pad_spaces); ?><?php echo ini_get('max_file_uploads') . "\n"; ?>

<?php echo str_pad(__('WP Options Count', 'wp-ultimo') . ":", $pad_spaces); ?><?php echo count($all_options) . "\n"; ?>
<?php echo str_pad(__('WP Options Size', 'wp-ultimo') . ":", $pad_spaces); ?><?php echo $all_options_bytes . "kb\n" ?>
<?php echo str_pad(__('WP Options Transients', 'wp-ultimo') . ":", $pad_spaces); ?><?php echo count($all_options_transients) . "\n"; ?>

<?php echo str_pad(__('WP_DEBUG', 'wp-ultimo') . ":", $pad_spaces); ?><?php echo defined('WP_DEBUG') ? WP_DEBUG ? _e('Enabled', 'sysinfo') . "\n" : _e('Disabled', 'sysinfo') . "\n" : _e('Not set', 'sysinfo') . "\n" ?>
<?php echo str_pad(__('SCRIPT_DEBUG', 'wp-ultimo') . ":", $pad_spaces); ?><?php echo defined('SCRIPT_DEBUG') ? SCRIPT_DEBUG ? _e('Enabled', 'sysinfo') . "\n" : _e('Disabled', 'sysinfo') . "\n" : _e('Not set', 'sysinfo') . "\n" ?>
<?php echo str_pad(__('SAVEQUERIES', 'wp-ultimo') . ":", $pad_spaces); ?><?php echo defined('SAVEQUERIES') ? SAVEQUERIES ? _e('Enabled', 'sysinfo') . "\n" : _e('Disabled', 'sysinfo') . "\n" : _e('Not set', 'sysinfo') . "\n" ?>
<?php echo str_pad(__('AUTOSAVE_INTERVAL', 'wp-ultimo') . ":", $pad_spaces); ?><?php echo defined('AUTOSAVE_INTERVAL') ? AUTOSAVE_INTERVAL ? AUTOSAVE_INTERVAL . "\n" : _e('Disabled', 'sysinfo') . "\n" : _e('Not set', 'sysinfo') . "\n" ?>
<?php echo str_pad(__('WP_POST_REVISIONS', 'wp-ultimo') . ":", $pad_spaces); ?><?php echo defined('WP_POST_REVISIONS') ? WP_POST_REVISIONS ? WP_POST_REVISIONS . "\n" : _e('Disabled', 'sysinfo') . "\n" : _e('Not set', 'sysinfo') . "\n" ?>

<?php echo str_pad(__('DISABLE_WP_CRON', 'wp-ultimo') . ":", $pad_spaces); ?><?php echo defined('DISABLE_WP_CRON') ? DISABLE_WP_CRON ? DISABLE_WP_CRON . "\n" : _e('Yes', 'sysinfo') . "\n" : _e('Not set', 'sysinfo') . "\n" ?>
<?php echo str_pad(__('WPLANG', 'wp-ultimo') . ":", $pad_spaces); ?><?php echo defined('WPLANG') ? WPLANG ? WPLANG . "\n" : _e('Yes', 'sysinfo') . "\n" : _e('Not set', 'sysinfo') . "\n" ?>

<?php echo str_pad(__('WP_MEMORY_LIMIT', 'wp-ultimo') . ":", $pad_spaces); ?><?php echo defined('WP_MEMORY_LIMIT') ? WP_MEMORY_LIMIT ? WP_MEMORY_LIMIT . "\n" : _e('Yes', 'sysinfo') . "\n" : _e('Not set', 'sysinfo') . "\n" ?>
<?php echo str_pad(__('WP_MAX_MEMORY_LIMIT', 'wp-ultimo') . ":", $pad_spaces); ?><?php echo defined('WP_MAX_MEMORY_LIMIT') ? WP_MAX_MEMORY_LIMIT ? WP_MAX_MEMORY_LIMIT . "\n" : _e('Yes', 'sysinfo') . "\n" : _e('Not set', 'sysinfo') . "\n" ?>

<?php echo str_pad(__('Operating System', 'wp-ultimo') . ":", $pad_spaces); ?><?php echo $browser['platform'] . "\n"; ?>
<?php echo str_pad(__('Browser', 'wp-ultimo') . ":", $pad_spaces); ?><?php echo $browser['name'] . ' ' . $browser['version'] . "\n"; ?>
<?php echo str_pad(__('User Agent', 'wp-ultimo') . ":", $pad_spaces); ?><?php echo $browser['user_agent'] . "\n"; ?>

-----------------------------------------------------------------
----------------------- THEME AND PLUGINS -----------------------
-----------------------------------------------------------------

<?php echo str_pad(__('Active Theme - Main Site', 'wp-ultimo') . ":", $pad_spaces); ?>- <?php echo $theme->get('Name') ?> - <?php echo $theme->get('Version') . ""; ?> (<?php echo $theme->get('ThemeURI'); ?>)

<?php echo str_pad(__('Active Plugins Network-Wide', 'wp-ultimo') . ":", $pad_spaces); ?>
<?php
  $first = true; foreach ($plugins as $plugin_path => $plugin) {

    // Only show active plugins
    if (in_array($plugin_path, array_keys($active_plugins))) {

        if (!$first) echo str_pad(' ', $pad_spaces);
        $first = false;

        echo '- ' . $plugin['Name'] . ' - ' . $plugin['Version'];

        if (isset($plugin['PluginURI'])) {
            echo ' (' . $plugin['PluginURI'] . ")";
        }

        echo "\n";
    }
  }
  ?>

<?php echo str_pad(__('Active Plugins on Main Site', 'wp-ultimo') . ":", $pad_spaces); ?>
<?php
  $first = true; foreach ($plugins as $plugin_path => $plugin) {

    // Only show active plugins
    if (in_array($plugin_path, $active_plugins_main_site)) {

        if (!$first) echo str_pad(' ', $pad_spaces);
        $first = false;

        echo '- ' . $plugin['Name'] . ' - ' . $plugin['Version'];

        if (isset($plugin['PluginURI'])) {
            echo ' (' . $plugin['PluginURI'] . ")";
        }

        echo "\n";
    }
  }
  ?>

-----------------------------------------------------------------
------------------ WP ULTIMO - DATABASE STATUS ------------------
-----------------------------------------------------------------

<?php foreach (array(
  'wu_subscription_db_version' => 'Subscriptions',
  'wu_site_owner_db_version'   => 'Site Owner',
  'wu_transactions_db_version' => 'Transactions',
) as $table => $table_name) : ?>
<?php echo str_pad(sprintf(__('%s - Table Version', 'wp-ultimo'), $table_name) . ":", $pad_spaces); ?><?php echo get_network_option(null, $table, 1) . "\n"; ?>
<?php endforeach; ?>

-----------------------------------------------------------------
-------------------- WP ULTIMO CORE SETTINGS --------------------
-----------------------------------------------------------------

<?php echo str_pad(__('Logs Directory', 'wp-ultimo') . ":", $pad_spaces); ?><?php echo is_writable( WU_Logger::get_logs_folder() ) ? 'Writable' : 'Not Writable' . "\n"; ?>


<?php foreach ($system_info->get_all_wp_ultimo_settings() as $setting => $value) {

  if (is_array($value)) continue;

  echo str_pad( ucwords(str_replace(array('_', '-'), ' ', $setting)) . ":", $pad_spaces) . $value . "\n";

}?>
`
  </textarea>

  <?php } else if ($_GET['wu-tab'] == 'logs') { ?>

  <!-- <div id="logs" data-type="heading">
    <h3><?php _e('Log Viewer', 'wp-ultimo'); ?></h3>
    <p><?php _e('Use the viewer below to access the contents of the logs generated by WP Ultimo.', 'wp-ultimo'); ?></p>
  </div> -->

  <form method="get" action="<?php echo network_admin_url('admin.php?page=wp-ultimo-system-info&wu-tab=logs')?>">

  <?php foreach($_GET as $name => $value) {

    $name = htmlspecialchars($name);

    $value = htmlspecialchars($value);

    echo '<input type="hidden" name="'. $name .'" value="'. $value .'">';

  } ?>

  <div style="margin-left: -12px; width: 100%;">

    <div style="clear: both;"> </div>

  </div>

  <div id="log-viewer-select" style="padding: 10px 0 8px; line-height: 28px;">

		<div class="alignleft">
			<h2>
        <?php echo $file_name; ?>
          <button class="page-title-action" type="submit" name="action" value="delete"><?php _e('Delete Log', 'wp-ultimo'); ?></button>
          <button class="page-title-action" type="submit" name="action" value="download"><?php _e('Download Log', 'wp-ultimo'); ?></button>
			</h2>
    </div>
    
		<div class="alignright">

			<select name="file" style="">

        <option><?php _e('Select a Log File', 'wp-ultimo'); ?></option>

        <?php foreach($logs_list as $file_path) : ?>
          <option value="<?php echo $file_path; ?>" <?php selected($file == $file_path); ?>><?php echo $file_path; ?></option>
        <?php endforeach; ?>

      </select>

      <button class="button-primary" name="action" value="see" type="submit"><?php _e('See Log File', 'wp-ultimo'); ?></button>

		</div>
		<div class="clear"></div>
	</div>

    <br>

    <textarea  onclick="this.focus();this.select()" readonly="readonly" wrap="off" style="width: 100%; height: 600px; font-family: monospace;"><?php echo $contents; ?></textarea>

  </form>

  <?php } else {

    do_action('wu_system_info_extra_tabs');

  } // end else extra tabs; ?>

</div>
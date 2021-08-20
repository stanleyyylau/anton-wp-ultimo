<?php

if (!defined('WP_UNINSTALL_PLUGIN')) {
  exit;
}

global $wpdb;

/**
 * @since  1.3.3 Only delete if the option is set
 */

$settings = get_network_option(null, 'wp-ultimo_settings');

if (isset($settings['uninstall_wipe_tables']) && $settings['uninstall_wipe_tables'] === true) {

  /**
   * Remove our tables - Nooooo!
   */

  // Tables to remove
  $tables = array('wu_transactions', 'wu_site_owner', 'wu_subscriptions');

  foreach($tables as $table) {
    $wpdb->query("DROP TABLE IF EXISTS $wpdb->prefix$table");
  };

  // Remove settings
  delete_network_option(null, 'wp-ultimo_settings');
  delete_network_option(null, '_wpultimo_activation_redirect');
  delete_network_option(null, 'wu_transactions_db_version');
  delete_network_option(null, 'wu_site_owner_db_version');
  delete_network_option(null, 'wu_subscription_db_version');

  /**
   * @since  1.1.2 Remove the activation flag
   */
  delete_network_option(null, 'wp_ultimo_activated');

  // Get all plans and coupons
  $sql = "delete p, pm FROM {$wpdb->prefix}posts p LEFT JOIN {$wpdb->prefix}postmeta pm ON pm.post_id = p.id WHERE p.post_type = 'wpultimo_plan' OR p.post_type = 'wpultimo_coupon'";

  // Run the query
  $wpdb->query($sql);

} // end if;

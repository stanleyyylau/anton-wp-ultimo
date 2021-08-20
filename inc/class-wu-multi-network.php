<?php
/**
 * Handles Multi-Network Support, in case we are in a multi-network environment
 *
 * @author      Arindo Duque
 * @category    Admin
 * @package     WP_Ultimo/Multi-Network
 * @version     1.3.0
*/

if (!defined('ABSPATH')) {
  exit;
}

class WU_Multi_Network {

  /**
   * Attach filters to what are usually considered "global" values, and modify
   * them based on the currently loaded WordPress network of sites.
   *
   * - Global cache key
   * - Table prefix
   * - User meta key
   */
  public function __construct() {

    add_filter('wu_core_get_table_prefix', array($this, 'filter_table_prefix'));
    //add_filter('wu_get_user_meta_key', array($this, 'filter_user_meta_key'));

  }

  /**
   * Filter the DB table prefix and maybe add a network ID
   *
   * @param  string $prefix
   * @return string
   */
  public function filter_table_prefix($prefix = '') {

    // Override prefix if not main network and there is a prefix match
    if (true === self::is_base_db_prefix($prefix)) {

      $prefix = self::get_network_db_prefix();

    }

    // Use this network's prefix
    return $prefix;

  }

  /**
   * Whether or not the current DB query is from the main network.
   *
   * The main network is typically ID 1 (and does not have a modified prefix)
   * but we also need to check for the PRIMARY_NETWORK_ID constant introduced
   * in WordPress 3.7 for more sophisticated installations.
   *
   * @return boolean
   */
  private static function is_main_network() {

    return (bool) (self::get_network_id() === (int) self::get_wpdb()->siteid);

  }

  /**
   * Compare a given DB table prefix with the base DB prefix.
   *
   * @param  string $prefix
   * @return string
   */
  private static function is_base_db_prefix($prefix = '') {

    return (bool) (self::get_wpdb()->base_prefix === $prefix);

  }

  /**
   * Return the DB table prefix for the current network
   *
   * Note that we're using the prefix for the root-blog, and not the network
   * ID itself. This is because BuddyPress stores much of its data in the
   * root-blog options table VS the sitemeta table.
   *
   * @return string
   */
  private static function get_network_db_prefix() {

    return self::get_wpdb()->get_blog_prefix(self::get_site_id());

  }

  /**
   * Get the root blog ID for the current network
   *
   * @global int $blog_id
   * @return int
   */
  private static function get_site_id() {

    return (int) function_exists('get_current_site')
      ? get_current_site()->blog_id
      : $GLOBALS['blog_id'];

  }

  /**
   * Use primary network ID if defined
   *
   * @return int
   */
  private static function get_network_id() {

    return (int) defined('PRIMARY_NETWORK_ID')
      ? PRIMARY_NETWORK_ID
      : 1;

  }

  /**
   * Return the global $wpdb object
   *
   * @return object
   */
  private static function get_wpdb() {

    return isset($GLOBALS['wpdb'])
      ? $GLOBALS['wpdb']
      : false;

  }

} // end class WU_Multi_Network;

new WU_Multi_Network;
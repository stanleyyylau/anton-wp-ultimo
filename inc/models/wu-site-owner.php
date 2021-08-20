<?php
/**
 * WP Ultimo Site Owner
 *
 * Create the site owner table
 *
 * @author      Arindo Duque
 * @category    Admin
 * @package     WP_Ultimo/Site_Owners
 * @version     0.0.1
*/

if (!defined('ABSPATH')) {
  exit;
}

class WU_Site_Owner {

  /**
   * Returns the table name for use
   * @return string Transactions table name
   */
  public static function get_table_name() {
    global $wpdb;
    return apply_filters('wu_core_get_table_prefix', $wpdb->base_prefix) . 'wu_site_owner';
  }

  /**
   * Creates the database table for our transactions
   * @return boolean The saving status of database version
   */
  public static function create_table() {
  
    global $wpdb;

    $table_name      = self::get_table_name();
    $charset_collate = $wpdb->get_charset_collate();

    // SQL code
    $sql = "CREATE TABLE $table_name (
        ID mediumint(9) NOT NULL AUTO_INCREMENT,
        site_id mediumint(9) NOT NULL,
        user_id mediumint(9) NOT NULL,
        PRIMARY KEY  (`ID`),
        UNIQUE KEY `id` (`ID`),
        KEY `site_id` (`site_id`),
        KEY `user_id` (`user_id`)
    ) $charset_collate;";

    require_once(ABSPATH.'wp-admin/includes/upgrade.php');
    
    @dbDelta($sql);

    return add_network_option(null, 'wu_site_owner_db_version', '1');
    
  } // end create_table;

  /**
   * Retrieve WU_Site_Owner instance.
   *
   * @static
   * @access public
   *
   * @global wpdb $wpdb WordPress database abstraction object.
   *
   * @param int $subscription_id Site_Owner ID.
   * @return WU_Site_Owner|false Site_Owner object, false otherwise.
   */
  public static function get_instance($subscription_id) {

      global $wpdb;

      $subscription_id = (int) $subscription_id;
      if (!$subscription_id)
          return false;

      // Get from cache
      $_subscription = wp_cache_get( $subscription_id, 'wu_subscription' );

      if (!$_subscription) {
        
        $query = $wpdb->prepare( "SELECT * FROM ".self::get_table_name()." WHERE ID = %d LIMIT 1", $subscription_id );

        // Get subscription
        $_subscription = $wpdb->get_row( $query );

        if ( ! $_subscription )
            return false;

        wp_cache_add( $_subscription->ID, $_subscription, 'wu_subscription' );

      } elseif ( empty( $_subscription->filter ) ) {
        $_subscription = sanitize_post( $_subscription, 'raw' );
      }

      return new WU_Site_Owner( $_subscription );

  } // end get_instance;

  /**
   * Get all the sites on the site_owner table
   *
   * @since 1.5.4
   * @param string|boolean $fields
   * @return array
   */
  public static function get_sites($fields = false) {

    global $wpdb;

    $select       = $fields ?: '*';
    $key          = md5($select);
    $last_changed = wp_cache_get_last_changed( 'wu_site_owner' );
    $cache_key    = "wu_get_sites:$key:$last_changed";
    $cache_value  = wp_cache_get( $cache_key, 'wu_site_owner' );

    if ( false === $cache_value ) {
        
      $sites = $wpdb->get_results("SELECT $select FROM ". self::get_table_name()) ?: array();
      wp_cache_add( $cache_key, $sites, 'wu_site_owner' );

    } else {

      $sites = $cache_value;

    }

    return $sites;

  } // end get_sites;

  /**
   * Get the sites of a particular user
   * @since  1.2.0
   * @param  integer $user_id
   * @return array    
   */
  public static function get_user_sites($user_id) {

    global $wpdb;

    $results = $wpdb->get_results($wpdb->prepare("SELECT * FROM ".self::get_table_name()." WHERE user_id = %d", $user_id)) ?: array();

    $results_sites = array();

    foreach($results as $site) {

      $results_sites[$site->site_id] = wu_get_site($site->site_id);

    }

    return $results_sites;

  } // end get_user_sites;

  /**
   * Get the sites of a particular user
   * @since  1.5.4
   * @param  integer $user_id
   * @return array    
   */
  public static function get_site_owner_id($site_id) {

    global $wpdb;

    $result = $wpdb->get_row($wpdb->prepare("SELECT user_id FROM ".self::get_table_name()." WHERE site_id = %d", $site_id));

    return $result ? (int) $result->user_id : false;
    
  } // end get_user_sites;

  /**
   * Constructor.
   *
   * @param WU_Site_Owner|object $subscription Site_Owner object.
   */
  public function __construct($subscription) {
    foreach (get_object_vars($subscription) as $key => $value)
      $this->$key = $value;
  }

}

// TODO: Run all the create tables together at activation
// WU_Site_Owner::create_table();
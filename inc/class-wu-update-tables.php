<?php
/**
 * WP Ultimo Update Tables
 *
 * Handles the update of tables after something is changed
 *
 * @author      Arindo Duque
 * @category    Admin
 * @package     WP_Ultimo/Database
 * @version     0.0.1
*/

if (!defined('ABSPATH')) {
  exit;
}

class WU_Update_Tables {

  public $tables = array(
    'wu_subscription_db_version' => 'WU_Subscription',
    'wu_site_owner_db_version'   => 'WU_Site_Owner',
    'wu_transactions_db_version' => 'WU_Transactions',
  );

  /**
   * Loads the necessary files, compare database versions and update them if necessary.
   */
  public function __construct($force = false) {

    /**
     * Only run on the super admin
     * @since 1.9.3
     */
    if (!is_network_admin()) return;

    /**
     * @since  1.3.0 Multi-Network Support
     */
    require_once WP_Ultimo()->path('inc/class-wu-multi-network.php');

    require_once WP_Ultimo()->path('inc/class-wu-transactions.php');
    require_once WP_Ultimo()->path('inc/models/wu-site-owner.php');
    require_once WP_Ultimo()->path('inc/models/wu-subscription.php');

    // Check tables
    foreach($this->tables as $table_version_slug => $table_class) {

      $table_version = get_network_option(null, $table_version_slug, 1);

      if (version_compare($table_version, WP_Ultimo()->version, '<') || $force) {

        WU_Logger::add('database-changes', sprintf(__('Updating the database table referring to %s. The new table version is now %s.', 'wp-ultimo'), $table_class, WP_Ultimo()->version));

        $table_class::create_table();

        update_network_option(null, $table_version_slug, WP_Ultimo()->version);

      } // end if;

    } // end foreach;

  } // end construct;

} // end class WU_Update_Tables;

new WU_Update_Tables;
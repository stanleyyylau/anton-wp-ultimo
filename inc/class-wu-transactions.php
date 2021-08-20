<?php
/**
 * WP Ultimo Transactions
 *
 * This classes handles the transactions log in our database
 *
 * @author      Arindo Duque
 * @category    Admin
 * @package     WP_Ultimo/Transactions
 * @version     0.0.1
*/

if (!defined('ABSPATH')) {
  exit;
}

class WU_Transactions {

  /**
   * Returns the table name for use
   * @return string Transactions table name
   */
  public static function get_table_name() {
    global $wpdb;
    return apply_filters('wu_core_get_table_prefix', $wpdb->base_prefix) . 'wu_transactions';
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
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        user_id mediumint(9) NOT NULL,
        reference_id varchar(40) NOT NULL,
        gateway varchar(40) NOT NULL,
        amount varchar(20) NOT NULL,
        original_amount varchar(20) NOT NULL,
        type varchar(20) NOT NULL,
        nature varchar(20) DEFAULT 'normal' NOT NULL,
        description varchar(255),
        time datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
        PRIMARY KEY  (`id`),
        UNIQUE KEY `id` (`id`),
        KEY `user_id` (`user_id`)
    ) $charset_collate;";

    require_once(ABSPATH.'wp-admin/includes/upgrade.php');
    
    @dbDelta($sql);

    return add_network_option(null, 'wu_transactions_db_version', '1');
    
  } // end create_table;

  /**
   * Returns the current time from the network
   */
  public static function get_current_time($type = 'mysql') {
    
    switch_to_blog(get_current_site()->blog_id);
    
      $time = current_time($type);
    
    restore_current_blog();
    
    return $time;

  } // end get_current_time;
  
  /**
   * Get all transactions of a specific site
   *
   * @since  1.0.3
   * @since  1.1.0 allows for pagination and ordering
   * 
   * @param  integer $user_id The site id or false, in which case the current blog will be used
   * @return array   Array of objects representing the transactions held in the database
   */
  public static function get_transactions($user_id = false, $per_page = false, $page_number = false, $orderby = false, $order = false, $count = false) {
    
    global $wpdb;

    $table_name = self::get_table_name();

    $sql = $count ? "SELECT count(id) FROM $table_name" : "SELECT * FROM $table_name";

    if ($user_id) {

      $sql .= " WHERE user_id = $user_id";

    }
    
    // Check for order
    if ($orderby && $order) {

      $sql .= " ORDER BY ". $orderby ." ". $order;

    } else {

      $sql .= " ORDER BY time DESC";

    }

    /** 
     * @since  1.1.0 allows for pagination
     */
    if ($per_page && $page_number) {

      $sql .= " LIMIT $per_page";
      $sql .= ' OFFSET ' . ( $page_number - 1 ) * $per_page;

    }

    return $count ? $wpdb->get_var($sql) : $wpdb->get_results($sql);
    
  } // end get_user_transactions;

  /**
   * Get the transaction count for a given user_id
   * @param  boolean/interget $user_id ID of the user
   * @return interger         Number of transactions
   */
  public static function get_transactions_count($user_id = false) {

    global $wpdb;

    $user_id = $user_id ? $user_id : get_current_user_id();

    $prefix = apply_filters('wu_core_get_table_prefix', $wpdb->base_prefix);

    $sql = "SELECT count(id) FROM {$prefix}wu_transactions WHERE user_id = $user_id";

    return $wpdb->get_var($sql);

  } // end get_transactions_count;

  /**
   * Get the total value from payments from a given user
   * TODO: Allow to get ALL the money
   * @param  boolean/integer $user_id ID of the user
   * @return float            Total amount
   */
  public static function get_transactions_total($user_id) {

    global $wpdb;

    $prefix = apply_filters('wu_core_get_table_prefix', $wpdb->base_prefix);

    $query = "
      SELECT 
      SUM(amount) AS total 
      FROM {$prefix}wu_transactions
      WHERE type = 'payment' 
      AND user_id = $user_id";
      // LIMIT %d;";

      $results = $wpdb->get_row(($query));

      return apply_filters('wu_transactions_get_transactions_total', $results->total, $user_id);

  } // end get_transactions_total;

  /**
   * Get the total of refunds given to a given user
   * @param  boolean/interget $user_id ID of the user
   * @return interger         The value of refunds
   */
  public static function get_refunds_total($user_id) {

    global $wpdb;

    $prefix = apply_filters('wu_core_get_table_prefix', $wpdb->base_prefix);

    $query = "
      SELECT 
      SUM(amount) AS total 
      FROM {$prefix}wu_transactions
      WHERE type = 'refund' 
      AND user_id = $user_id";
      // LIMIT %d;";

      $results = $wpdb->get_row(($query));

      return apply_filters('wu_transactions_get_refunds_total', $results->total, $user_id);

  } // end get_refunds_total;

  /**
   * Get total value of transactions after refunds
   * @param  boolean/interget $user_id ID of the user
   * @return integer          Total value of transactions
   */
  public static function get_total_after_refunds($user_id) {

    return WU_Transactions::get_transactions_total($user_id) - WU_Transactions::get_refunds_total($user_id);

  } // end get_total_after_refunds;
  
  /**
   * Get a specific Transactions
   * @param  integer $id The ID of the transaction in our database
   * @return object  The transaction in question, or false in failure
   */
  public static function get_transaction($id) {
    global $wpdb;

    $prefix = apply_filters('wu_core_get_table_prefix', $wpdb->base_prefix);

    $sql  = "SELECT * FROM {$prefix}wu_transactions WHERE id = $id";
    return $wpdb->get_row($sql);
  }
  
  /**
   * Updates a transaction in the database
   * @param  integer $id     The id of the transaction to be updated
   * @param  array   $update The array containing column => value pair
   * @return boolean True in case of success, false on failure
   */
  public static function update_transaction($id, $update) {
    global $wpdb;
    $table_name = self::get_table_name();
    return $wpdb->update($table_name, $update, array('id' => $id));
  } // end update_transaction;

  /**
   * Deletes a transaction in the database
   * @param  integer $id     The id of the transaction to be updated
   * @return boolean True in case of success, false on failure
   */
  public static function delete_transaction($id) {

    global $wpdb;

    $table_name = self::get_table_name();

    return $wpdb->delete($table_name, array('id' => $id));

  } // end delete_transaction;
  
  /**
   * Add a transaction to the database
   * 
   * @since 1.7.0 Added the nature param, to allow us to differentiate between normal payments, setup-fee payments, single charges, etc
   * 
   * @param  integer $user_id      Site ID
   * @param  string  $reference_id The reference ID of the payment
   * @param  string  $type         Type of the transaction
   * @param  string  $amount       Amount of the transaction
   * @param  string  $gateway      The name of the gateway
   * @param  string  $desc         Short description, for reference
   * @param  string  $time         Time
   * @param  string  $nature       Specific Type of transaction, like normal, setup-fee, etc.
   * @return boolean True on success, false on failure
   */
  public static function add_transaction($user_id, $reference_id, $type, $amount, $gateway, $desc, $time = false, $original_amount = false, $nature = 'normal') {
    
    global $wpdb;
    
    $table_name = self::get_table_name();
    
    // Make the insertion
    return $wpdb->insert($table_name, array(
      'user_id'         => $user_id,
      'reference_id'    => $reference_id,
      'type'            => $type,
      'nature'          => $nature,
      'amount'          => $amount,
      'gateway'         => $gateway,
      'description'     => $desc,
      'time'            => $time ?: self::get_current_time('mysql'),
      'original_amount' => $original_amount ?: $amount,
    ));
    
  } // end add_transaction;

  /**
	 * Gets a transaction based on its reference_id.
	 *
	 * @since 1.9.14
	 * @param string $reference_id Transaction reference id.
	 * @return object
	 */
	public function get_transaction_by_reference_id($reference_id) {

		global $wpdb;

		$query = $wpdb->prepare("SELECT * FROM {$wpdb->base_prefix}wu_transactions WHERE reference_id = %s LIMIT 1", $reference_id);

		$transaction = $wpdb->get_row($query);

		return $transaction;

	} // end check_if_wp_ultimo_transaction_exists;

} // end class WU_Transactions;

// global $wpdb;

// /** 
//  * Update our database to the current version
//  * @since  1.1.0
//  */
// if (!$wpdb->get_results("Show columns from ".WU_Transactions::get_table_name()." like 'refunded'")) {

//   WU_Transactions::create_table();

// }
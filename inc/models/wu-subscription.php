<?php
/**
 * WP Ultimo Subscription
 *
 * This classes handles the subscriptions object model
 *
 * @author      Arindo Duque
 * @category    Admin
 * @package     WP_Ultimo/Subscriptions
 * @version     0.0.1
*/

if (!defined('ABSPATH')) {
  exit;
}

class WU_Subscription {

  /**
   * ID of this subscription
   * @var integer
   */
  public $ID;
  
  /**
   * ID of the user related to this subscription
   * @var integer
   */
  public $user_id;
  
  /**
   * Integration key, this can be a token returned by the gateway, for example
   * @var string
   */
  public $integration_key;

  /**
   * Integration status, can be true or false
   * @var boolean
   */
  public $integration_status;
  
  /**
   * Gateway id, to keep control
   * @var string
   */
  public $gateway;
  
  /**
   * Id of the plan related to this subscription
   * @var integer
   */
  public $plan_id;
  
  /**
   * Plan billing frequency
   * @var integer
   */
  public $freq;
  
  /**
   * Price
   * @var string
   */
  public $price;

  /**
   * Credit
   * @since 1.5.0
   * @var string
   */
  public $credit;

  /**
   * Trial days saved from the sign-up time
   * @var integer
   */
  public $trial;

  /**
   * Meta
   * @var Object
   */
  protected $meta_object;
  
  /**
   * Date the integration was first created
   * @var date
   */
  public $created_at;
  
  /**
   * Date until this subscription is active for
   * @var date
   */
  public $active_until;

  /**
   * When was this subscription payment info last changed
   * @var date
   */
  public $last_plan_change;

  /**
   * Check if this subscription already paid its setup fee so we don't double charge
   * @var bool
   */
  public $paid_setup_fee;

  /**
   * Description Lines
   * @since 1.5.0
   * @var string
   */
  private $lines = array();

  /**
   * Returns the table name for use
   * @return string Transactions table name
   */
  public static function get_table_name() {
    global $wpdb;
    return apply_filters('wu_core_get_table_prefix', $wpdb->base_prefix) . 'wu_subscriptions';
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
        user_id mediumint(9) NOT NULL UNIQUE,
        integration_key varchar(90),
        integration_status TINYINT(1) DEFAULT 0,
        gateway varchar(40),
        plan_id mediumint(9),
        freq mediumint(9),
        price decimal(10,2),
        credit decimal(10,2),
        trial mediumint(9),
        meta_object longtext,
        created_at datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
        active_until datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
        last_plan_change datetime DEFAULT '0000-00-00 00:00:00',
        paid_setup_fee TINYINT(1) DEFAULT 1,
        PRIMARY KEY  (`ID`)
    ) $charset_collate;";

    require_once(ABSPATH.'wp-admin/includes/upgrade.php');

    @dbDelta($sql);

    return add_network_option(null, 'wu_subscription_db_version', '1');
    
  } // end create_table;

  /**
   * Retrieve WU_Subscription instance by user_id.
   *
   * @static
   * @access public
   *
   * @global wpdb $wpdb WordPress database abstraction object.
   *
   * @param int $user_id Subscription ID.
   * @return WU_Subscription|false Subscription object, false otherwise.
   */
  public static function get_instance($user_id) {

    global $wpdb;

    $table_name = self::get_table_name();

    $user_id = (int) $user_id;

    if (!$user_id)
        return false;

    // Get from cache
    $_subscription = false; //wp_cache_get( $user_id, 'wu_subscription' );

    if (!$_subscription) {
      
      // Get subscription
      $_subscription = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table_name WHERE user_id = %d LIMIT 1", $user_id ) );

      if ( ! $_subscription )
          return false;

      // wp_cache_set($user_id, $_subscription, 'wu_subscription', false, 24*60*60);

    } elseif ( empty( $_subscription->filter ) ) {

      $_subscription = sanitize_post( $_subscription, 'raw' );

    }

    /**
     * @since  1.1.5 Clean the subscription value, before displaying it to the user
     */
    $_subscription->price = WU_Util::to_float($_subscription->price);

    return new WU_Subscription( $_subscription );

  } // end get_instance;

  /**
   * Constructor.
   *
   * @param WU_Subscription|object $subscription Subscription object.
   */
  public function __construct($subscription) {

    foreach (get_object_vars($subscription) as $key => $value) {
      // Set meta only unserialized
      if ($key == 'meta') $this->meta_object = unserialize($value);
      else $this->$key = $value;
    }

  } // end construct;

  /**
   * Special getter for our Meta
   * @param  string $name Value to get
   * @return mixed        The right value
   */
  function __get($name) {
    if ($name == 'meta') return unserialize($this->meta_object);
  }

  /**
   * __set function.
   * @param mixed $key
   * @return mixed
   */
  public function __set($name, $value) {

    if ($name == 'meta') $this->meta_object = serialize((object) $value);

  }

  /**
   * Save changes to our subscription object in the database
   * @return WU_Subscription The updated object
   */
  public function save() {
 
    global $wpdb;

    $save = apply_filters('wu_subscription_before_save', get_object_vars($this), $this);

    /**
     * Removing undesirable elements
     */
    unset($save['lines']);

    $wpdb->replace(self::get_table_name(), $save);

    wp_cache_delete($this->user_id, 'wu_subscription');

    $this->clear_sites_cache(); // Clear all sites cache

    return $this;

  } // end save;

  /**
   * Fixes the Redis incompatibility issue by clearing the site cache on subscription save
   *
   * @since 1.5.2
   * @return void
   */
  public function clear_sites_cache() {

    $sites = $this->get_sites();

    foreach ($sites as $site) {

      wp_cache_delete($site->site_id, 'wu_site');

    } // end foreach; 

  } // end clear_sites_cache;

  /**
   * Returns the gateway of this subscription
   *
   * @since 1.7.0
   * @return WU_Gateway
   */
  public function get_gateway() {

    return wu_get_gateway($this->gateway);

  } // end get_gateway;

  /**
   * Get the activation key for giving the user a few minutes of access after a payment integration is added
   *
   * @since 1.9.0
   * @return string Activation Permission Key
   */
  public function get_activation_permission_id() {

    return sprintf('wu_activation_permission_%d', $this->user_id);

  } // end get_activation_permission_id;

  /**
   * Checks if there is a permission issued for this particular subscription
   *
   * @since 1.9.0
   * @return boolean
   */
  public function has_activation_permission() {

    $activation_id = $this->get_activation_permission_id();

    return get_site_transient($activation_id);

  } // end has_activation_permission;

  /**
   * Creates an activation permission for this subscription
   *
   * @since 1.9.0
   * @return boolean
   */
  public function create_activation_permission() {

    $activation_id = $this->get_activation_permission_id();

    $permission_time = apply_filters('wu_activation_permission_time', 600, $this);

    return set_site_transient($activation_id, $this->gateway ?: 1, $permission_time);

  } // end create_activation_permission;

  /**
   * Remove the activation permission for this subscription
   *
   * @since 1.9.0
   * @return boolean
   */
  public function revoke_activation_permission() {

    $activation_id = $this->get_activation_permission_id();

    return delete_site_transient($activation_id);

  } // end revoke_activation_permission;

  /**
   * Delete de subscription from the database
   * @return bool Whether the deletion process worked or not
   */
  public function delete() {

    global $wpdb;

    // Remove subscription
    $gateway = wu_get_gateway($this->gateway);

    if ($gateway) $gateway->remove_integration(false, $this);

    $remove = $wpdb->delete(self::get_table_name(), array('user_id' => $this->user_id));

    wp_cache_delete($this->user_id, 'wu_subscription');

    // Get table name
    $site_owner_table_name = WU_Site_Owner::get_table_name();

    // Remove the entry from our table
    $wpdb->delete($site_owner_table_name, array(
      'user_id' => $this->user_id,
    ));

    return $remove;

  } // end delete;

  /**
   * Gets the user owner of this subscription
   * @return WP_User
   */
  public function get_user() {
    return get_user_by('id', $this->user_id);
  }

  /**
   * Get the email of the owner of this subscription
   * @return boolean/string False in case of no user, user email on success
   */
  public function get_user_data($user_data = 'user_mail') {

    $user = get_userdata($this->user_id);

    return $user ? $user->$user_data : false;

  } // end get_user_data;

  /**
   * Checks if a subscription has a 100% off coupon, so we can mark it as active regardless of anything else
   * 
   * @return boolean
   */
  public function has_100_coupon() {
    
    $coupon = $this->get_coupon_code();

    if (!$coupon) return false;
    
    if ( ($coupon['cycles'] === '' || $coupon['cycles'] === 0) && $coupon['type'] == 'percent' && $coupon['value'] == '100') return true;  // Case Coupon 100%
    
    if ( ($coupon['cycles'] === '' || $coupon['cycles'] === 0) && $coupon['type'] == 'absolute' && $coupon['value'] >= $this->price) return true;  // Case Coupon > Subscription value
  
    else return false;

  }

  /**
   * Get the NOW relative to our timezone
   *
   * @since 1.5.1
   * @param string $type
   * @return void
   */
  public static function get_now($type = 'mysql') {

    return new DateTime(WU_Transactions::get_current_time('mysql'));

  } // end get_now;

  /**
   * Returns if this subscription is free or not
   *
   * @since 1.6.0
   * @return boolean
   */
  public function is_free() {

    return $this->price == 0 || $this->has_100_coupon();

  } // end is_free;

  /**
   * Check if a site is active, as well
   * @return boolean Check if it is active
   */
  public function is_active() {

    if ($this->price == 0) return true;
    
    if ($this->has_100_coupon()) return true;

    if ($this->get_trial()) return true;

    // Compare times
    $active_until = new DateTime($this->active_until);

    $now = self::get_now();

    /**
     * Allow plugin developers to filter the results of is_active.
     * 
     * @since 1.7.0
     * @param boolean Current value of the is_active method
     * @param integer User ID
     * @param WU_Subscription Current subscription object
     */
    return apply_filters('wu_subscription_is_active', $active_until > $now, $this->user_id, $this);

  } // end is_active;

  /**
   * Check if a site is on hold, as well
   * @since  1.2.0
   * @return boolean Check if it is on hold
   */
  public function is_on_hold() {

    $allowed_gateways = apply_filters('wu_subscription_on_hold_gateways', array('manual'));

    if (!in_array($this->gateway, $allowed_gateways)) return false;
    
    // Compare times for active until
    $active_until = new DateTime($this->active_until);
    $gp           = new DateTime($this->active_until);

    // Compare times for last changed
    $last_changed = new DateTime($this->last_plan_change);
    $gp_lc        = new DateTime($this->last_plan_change);

    $grace_period_setting = WU_Settings::get_setting($this->gateway . "_waiting_days", 5);

    $grace_period = $gp->add(  date_interval_create_from_date_string("$grace_period_setting days") );
    $grace_period_last_changed = $gp_lc->add(  date_interval_create_from_date_string("$grace_period_setting days") );

    $now = self::get_now();

    // Return boolean
    return ($grace_period >= $now && $active_until < $now) || ($grace_period_last_changed >= $now && $last_changed < $now && !$this->is_active());

  } // end is_active;

  /**
   * Return the status of a subscription
   * 
   * @since  1.2.0
   * @return string Status type
   */
  public function get_status() {

    if ($this->get_trial()) return 'trialing';

    else if ($this->is_on_hold()) return 'on-hold';

    else return $this->is_active() ? 'active' : 'inactive';

  } // end get_status;

  /**
   * Get the Label for a certain status
   * @since  1.2.0
   * @return string label for that status
   */
  public function get_status_label() {

    $status = $this->get_status();

    $labels = apply_filters('wu_subscription_status_labels', array(
      'trialing' => __('Trialing', 'wp-ultimo'),
      'on-hold'  => __('On Hold', 'wp-ultimo'),
      'active'   => __('Active', 'wp-ultimo'),
      'inactive' => __('Inactive', 'wp-ultimo'),
    ));

    return isset($labels[$status]) ? $labels[$status] : __('Inactive', 'wp-ultimo');

  } // end get_status_label;

  /**
   * Get all the sites associated with this subscription
   * @return array site ids
   */
  public function get_sites($array = false) {
    
    global $wpdb;

    $table_name = WU_Site_Owner::get_table_name();

    return $wpdb->get_results($wpdb->prepare("SELECT site_id FROM $table_name WHERE user_id = %d", $this->user_id), $array ? 'ARRAY_A' : 'OBJECT');

  } // end get_sites;

  /**
   * Returns a list of sites but containing only the ids as elements
   *
   * @since 1.7.3
   * @return array
   */
  public function get_sites_ids() {

    return array_column($this->get_sites(true), 'site_id');

  } // end get_sites_ids;

  /**
   * Returns a list of IDs of the sites that are inside the quota of that user
   *
   * @since 1.7.3
   * @return array
   */
  public function get_allowed_sites() {

    $plan = $this->get_plan();

    $primary_site = get_user_meta($this->user_id, 'primary_blog', true);

    $site_list = $this->get_sites_ids();

    if ($plan->get_quota('sites') == 0) {

      return apply_filters('wu_subscription_get_allowed_sites', $site_list, $this);

    } // end if;

    if ($primary_site && in_array($primary_site, $site_list)) {

      $key = array_search($primary_site, $site_list);

      unset($site_list[$key]);

    } // end if;

    $allowed_sites = array_slice($site_list, 0, $plan->get_quota('sites') - 1);

    $allowed_sites[] = get_user_meta($this->user_id, 'primary_blog', true);

    return apply_filters('wu_subscription_get_allowed_sites', $allowed_sites, $this);

  } // end get_allowed_sites;

  /**
   * Get site count for a subscription
   *
   * @since 1.7.0
   * @return integer
   */
  public function get_site_count() {

    $sites = $this->get_sites();

    return apply_filters('wu_subscription_get_site_count', count($sites), $sites, $this);

  } // end get_site_count;

  /**
   * Get the billing start date
   * 
   * @since  0.0.1
   * @since  1.1.0 returns false when now is greater
   * @return string The formated date
   */
  public function get_billing_start($format = 'c', $add_day = false) {

    $trial        = $this->get_trial();
    $active_until = new DateTime($this->get_date('active_until', 'c'));
    $now          = self::get_now();

    if ($trial) {
      return $this->get_date('trial_end', $format, $add_day);
    }

    else if ($now > $active_until || $active_until->format('Y-m-d') == $now->format('Y-m-d')) {
      /**
       * @since  1.1.0 Now, if today is greater, we simply won't pass the trial_end value. This solves a lot of issues!
       */
      return false;
    }

    else {
      return $this->get_date('active_until', $format, $add_day);
    }

  } // end get_billing_start;

  /**
   * Return a formated date fromt he subscription
   * 
   * @param  string  $date     Date to return
   * @param  boolean $format   Format to use, if false, we use the WordPress default
   * @param  boolean $add_time Interval to add to the original time
   * @return string            Formated string of the date
   */
  public function get_date($date = 'active_until', $format = false, $add_time = false) {

    // Sets the format
    $format = $format ? $format : get_option('date_format');

    // Case of trial end
    if ($date == 'trial_end') {

      $date_to_get = new DateTime($this->created_at);
      $date_to_get->add(DateInterval::createFromDateString("$this->trial Days"));

    } else if ($date == 'due_date') {

      // Add due date
      $active_until = new DateTime($this->active_until);

      $grace_period = WU_Settings::get_setting($this->gateway . "_waiting_days", 5);

      $date_to_get = $active_until->add(  date_interval_create_from_date_string("$grace_period days") );

    } else {

      $date_to_get = new DateTime($this->$date);
      
    }

    // If add time is present
    if ($add_time) {
      $date_to_get->add($add_time);
    }

    return date_i18n($format, $date_to_get->format('U'));

  } // end get_date;

  /**
   * Checks if trial is active for this particular site
   * 
   * @since  1.1.0 Returns the days
   * @return boolean/integer False if trial is over, int with trial days left
   */
  public function get_trial($return_days = false) {

    // Get the trial value for this specific site
    $trial = is_numeric($this->trial) ? $this->trial : WU_Settings::get_setting('trial');

    // Compare times
    $registered = new DateTime($this->created_at);
    $now        = self::get_now();

    // Diff
    $diff = $now->diff($registered, true);
    $days = $diff->days;
    
    if ($days >= $trial) return $return_days ? $trial - $days : 0;
    else return $trial - $days > 0 ? $trial - $days : 0;

  } // end is_trial_active;

  /**
   * Extend the subscription, but makes sure we extend to a date in the future
   * This is important for subscriptions that have been inactive for a while
   * 
   * @since 1.7.0
   * @param boolean $months
   * @return void
   */
  public function extend_future($months = false) {

    // The amount of time to extend
    $months = $months ?: $this->freq;

    $active_until     = new DateTime($this->active_until);
    $new_active_until = new DateTime($this->active_until);
    $created_at       = new DateTime($this->created_at);

    // If they are the same, it means that there was never one activation, so we need to add the trial
    if ($active_until < self::get_now()) {

      $new_active_until = self::get_now();

    } // end if;

    // Calculate new end date
    $extend_interval = DateInterval::createFromDateString("$months Months");
    $new_active_until->add($extend_interval);    

    // Saves =D
    $this->active_until = $new_active_until->format('Y-m-d H:i:s');

    $this->save();

    return $this;

  } // end extend_future;

  /**
   * Extend a subscription for a number of months
   * @param  interger $months (optional) Number of months to extend, if not provided fre will be used
   */
  public function extend($months = false) {

    // The amount of time to extend
    $months = $months ?: $this->freq;

    $active_until     = new DateTime($this->active_until);
    $new_active_until = new DateTime($this->active_until);
    $created_at       = new DateTime($this->created_at);

    // If they are the same, it means that there was never one activation, so we need to add the trial
    if ($this->active_until == $this->created_at) {

      $trial_interval = DateInterval::createFromDateString("$this->trial Days");
      $new_active_until->add($trial_interval);

    }

    // Calculate new end date
    $extend_interval = DateInterval::createFromDateString("$months Months");
    $new_active_until->add($extend_interval);    

    // Saves =D
    $this->active_until = $new_active_until->format('Y-m-d H:i:s');

    $this->save();

    return $this;

  } // end extend;

  /**
   * Remove a certain amount of months from a given subscription
   * @param  interger $months (optional) Number of months to extend, if not provided fre will be used
   */
  public function withdraw($months = false) {

    // The amount of time to extend
    $months = $months ?: $this->freq;

    $active_until     = new DateTime($this->active_until);
    $created_at       = new DateTime($this->created_at);
    $new_active_until = $active_until;

    // Calculate new end date
    $extend_interval = DateInterval::createFromDateString("$months Months");
    $new_active_until->sub($extend_interval);    

    // Saves =D
    $this->active_until = $new_active_until->format('Y-m-d H:i:s');

    $this->save();

    return $this;

  } // end withdraw;

  /**
   * Sets the new timestamp for the last plan change
   * @param boolean|string $time
   * @param boolean $save
   * @return void
   */
  public function set_last_plan_change($time = false, $save = false) {

    $time = $time ? new DateTime($time) : new DateTime() ;

    $this->last_plan_change = $time->format('Y-m-d H:i:s');

    if ($save) $this->save();

    return $this;

  } // end set_last_plan_change;

  /**
   * Remove a coupon code from subscription
   *
   * @since 1.7.3
   * @return void
   */
  public function remove_coupon_code() {

    $meta = (array) $this->meta;

    unset($meta['coupon_code']);

    $this->meta = $meta;

    return $this->save();

  } // end remove_coupon_code;

  /**
   * Add a valid coupon code to the subscription
   * @param  $coupon_code Coupon code to be added
   */
  public function apply_coupon_code($coupon_code) {

    $meta   = (array) $this->meta;

    $coupon = new WU_Coupon($coupon_code);

    $meta['coupon_code'] = array(
      'coupon_code' => $coupon_code,
      'type'        => $coupon->type,
      'value'       => $coupon->value,
      'cycles'      => $coupon->cycles,
      'applies_to_setup_fee'      => $coupon->applies_to_setup_fee,
      'setup_fee_discount_value'      => $coupon->setup_fee_discount_value,
      'setup_fee_discount_type'      => $coupon->setup_fee_discount_type,
    );

    $this->meta = $meta;

    /**
     * @since  1.2.0 Run action before savint the subscription
     */
    do_action('wp_ultimo_apply_coupon_code', $coupon, $this);

    return $this->save();

  } // end apply_coupon_code;

  /**
   * Get coupon code
   */
  public function get_coupon_code() {

    $defaults = array(
      'applies_to_setup_fee'     => false,
      'setup_fee_discount_value' => 0,
      'setup_fee_discount_type'  => 'absolute',
    );

    if (is_object($this->meta) && property_exists($this->meta, 'coupon_code')) {

      return wp_parse_args($this->meta->coupon_code, $defaults);

    } else {

      return false;

    } // end if;

  } // end get_coupon_code;

  /**
   * Remove a nuber fo cycles from a coupon code
   * @since  1.1.0
   * @param  integer $cycles Number of cycles to remove
   */
  public function remove_cycles_from_coupon_code($cycles = 1) {

    $meta = (array) $this->meta;

    if (isset($meta['coupon_code']['cycles']) && $meta['coupon_code']['cycles'] > 0) {

      $meta['coupon_code']['cycles'] = $meta['coupon_code']['cycles'] - $cycles;

      // Check for zero
      if ($meta['coupon_code']['cycles'] === 0) $meta['coupon_code']['cycles'] = false;

      $this->meta = $meta;

      $this->save();

    } // end if;

  } // end remove_cycles_from_coupon_code;

  /**
   * Return the string with the remaining or expired string
   * @since  1.1.0
   * @return string
   */
  public function get_active_until_string() {
    
    $now            = self::get_now();
    $active_until   = new DateTime($this->active_until);

    $this->trial = is_numeric($this->trial) ? $this->trial : 0;
    
    $trial_end      = new DateTime($this->created_at);
    $trial_end->add(date_interval_create_from_date_string($this->trial . " days"));
    
    $interval       = $active_until->diff($now);
    $interval_trial = $trial_end->diff($now);

    if ($this->price == 0) {

      // Check if past or future
      return  __('Free subscription. It never expires.', 'wp-ultimo');

    }

    if ($this->has_100_coupon()) {

      // Check if past or future
      return  __('Free subscription (via coupon code). It never expires.', 'wp-ultimo');

    }

    if ($this->get_trial()) {
      
      $diff = human_time_diff($now->format('U'), $trial_end->format('U'));

      // Check if past or future
      return sprintf(__('%s remaining', 'wp-ultimo'), $diff);

    } else {

      // Check if past or future
      $message = $now > $active_until ? __('Expired %s ago', 'wp-ultimo') : __('%s remaining', 'wp-ultimo');

      $diff = human_time_diff($now->format('U'), $active_until->format('U'));

      return sprintf($message, $diff);

    }

  } // end get_active_until_string;

  /**
   * Get the plan attached to this subscription
   * @since  1.1.0
   * @return WU_Plan
   */
  public function get_plan() {

    /**
     * Allow plugin developers to filter the results of get_plan
     * Important: You might also need to filter the methods and get_plan_id as well.
     * 
     * @since 1.7.0
     * @param WU_Plan Current plan object
     * @param integer Plan id on this subscription
     * @param integer User ID
     * @param WU_Subscription Current subscription object
     */
    return apply_filters('wu_subscription_get_plan', new WU_Plan($this->plan_id), $this->plan_id, $this->user_id, $this);

  } // end get_plan;

  /**
   * Checks if there is a plan for this
   *
   * @since 1.7.0
   * @return boolean
   */
  public function has_plan() {

    /**
     * No need for a filter here, since this is just a wrapper of the get_plan_id method.
     * Filter get_plan_id instead.
     */
    return $this->get_plan_id();

  } // end has_plan;

  /**
   * Get the Plan ID
   * 
   * @since  1.5.6
   * @return WU_Plan
   */
  public function get_plan_id() {

    /**
     * Allow plugin developers to filter the results of get_plan_id
     * 
     * @since 1.7.0
     * @param int|bool Current plan id
     * @param integer Plan id on this subscription
     * @param integer User ID
     * @param WU_Subscription Current subscription object
     */
    return apply_filters('wu_subscription_get_plan_id', $this->plan_id ?: false, $this->plan_id, $this->user_id, $this);

  } // end get_plan_id;

  /**
   * Get subscriptions, by status and allowing for pagination and ordering
   *
   * @since 1.7.0 Added the plan_id
   * @since 1.1.0
   * @return array
   */
  public static function get_subscriptions($status = 'all', $per_page = 5, $page_number = 1, $orderby = false, $order = false, $count = false, $search = false, $plan_id = false) {

    global $wpdb;

    $table_name = self::get_table_name();

    $prefix = apply_filters('wu_core_get_table_prefix', $wpdb->base_prefix);

    /**
     * SELECT sub.*, user.user_email, user.display_name, user.user_login FROM `wp_wu_subscriptions` sub LEFT JOIN `wp_users` user ON user.ID = sub.user_id WHERE user.user_login LIKE '%tes%';
     */

    $sql = $count 
      ? "SELECT count(sub.id) FROM $table_name sub LEFT JOIN `{$wpdb->base_prefix}users` user ON user.ID = sub.user_id" 
      : "SELECT sub.*, user.user_email, user.display_name, user.user_login FROM $table_name sub LEFT JOIN `{$wpdb->base_prefix}users` user ON user.ID = sub.user_id";

    // Switch types
    $grace_period = WU_Settings::get_setting('manual_waiting_days');
    $grace_period = is_numeric($grace_period) ? $grace_period : 3;

    $where = false;
    
    if ($status == 'trialing') {

      $where = true;

      $sql .= " WHERE DATE_ADD(created_at, INTERVAL trial DAY) >= NOW()";

    }

    else if ($status == 'active') {

      $where = true;

      $sql .= " WHERE sub.active_until >= NOW() AND NOT DATE_ADD(sub.created_at, INTERVAL sub.trial DAY) >= NOW()";

    }

    else if ($status == 'inactive') {

      $where = true;

      $sql .= " WHERE sub.active_until < NOW() AND NOT DATE_ADD(sub.created_at, INTERVAL sub.trial DAY) >= NOW()";

    }

    /**
     * @since  1.2.0 We need to get subscriptions on hold
     */
    else if ($status == 'on-hold') {

      $where = true;

      $allowed_gateways = array_map(function($item) {
        return "'$item'";
      }, apply_filters('wu_subscription_on_hold_gateways', array('manual')));
      
      $allowed_gateways = implode(', ', $allowed_gateways);

      $sql .= " WHERE 
      (sub.active_until < NOW() AND DATE_ADD(sub.active_until, INTERVAL $grace_period DAY) >= NOW() 
      OR sub.last_plan_change < NOW() AND DATE_ADD(sub.last_plan_change, INTERVAL $grace_period DAY) >= NOW() AND sub.active_until < NOW() ) 
      AND sub.gateway IN ($allowed_gateways)";

    }

    /**
     * Search String
     */
    if ($search) {

      $where_or_and = $where ? 'AND' : 'WHERE';

      $sql .= " $where_or_and ( user.user_login LIKE '%$search%' || user.user_email LIKE '%$search%' || user.display_name LIKE '%$search%' ) ";

    }

    /**
     * Plan ID
     * @since 1.7.0
     */
    if ($plan_id) {

      $where_or_and = $where ? 'AND' : 'WHERE';

      $sql .= $wpdb->prepare(" $where_or_and sub.plan_id = %d ", $plan_id);

    } // end if;

    // Check for order
    if ($orderby && $order) {

      $sql .= " ORDER BY ". $orderby ." ". $order;

    } else {

      $sql .= " ORDER BY user_id DESC";

    }

    // Pagination
    if ($per_page && $page_number) {

      $sql .= " LIMIT $per_page";

      $sql .= ' OFFSET ' . ($page_number - 1) * $per_page;

    }

    return $count ? $wpdb->get_var($sql) : $wpdb->get_results($sql);

  } // end get_subscriptions;

  /**
   * Get the string for the discount
   * @since  1.1.0
   * @param  array $coupon_code Coupon code elements
   * @return string
   */
  public function get_coupon_code_string() {

    $coupon_code = $this->get_coupon_code();

    if (!$coupon_code) return __('No coupon code applied to this account.', 'wp-ultimo');

    $message = __('%1$s - Coupon code of <b>%2$s on Subscription</b>, for %3$s.', 'wp-ultimo');

    if ($coupon_code['applies_to_setup_fee'] && $coupon_code['setup_fee_discount_value']) {

      $message = __('%1$s - Coupon code of <b>%2$s on Subscription</b> and <b>%4$s on Setup Fee</b>, for %3$s.', 'wp-ultimo');

    } // end if;

    $value = $coupon_code['type'] == 'percent' ? $coupon_code['value']."%" : wu_format_currency($coupon_code['value']);

    $setup_fee_value = $coupon_code['setup_fee_discount_type'] == 'percent' ? $coupon_code['setup_fee_discount_value']."%" : wu_format_currency($coupon_code['setup_fee_discount_value']);

    $cycles  = $coupon_code['cycles'] == 0 ? __('unlimited cycles', 'wp-ultimo') : sprintf(__('%s cycles', 'wp-ultimo'), $coupon_code['cycles']);

    if ($coupon_code['cycles'] === false) {
      $cycles = __('no cycles (coupon is over)', 'wp-ultimo');
    }

    /**
     * @since 1.1.2 Coupon codes are not supported by PayPal Buttons
     */
    if (WU_Settings::get_setting('paypal_standard') && $this->gateway == 'paypal') {
      $message .= '<br>'.__('PayPal does not support coupon codes. Use a alternative method, if available.', 'wp-ultimo');
    }

    return sprintf($message, $coupon_code['coupon_code'], $value, $cycles, $setup_fee_value);

  } // end get_discount_string;

  /**
   * Returns the price of the subscription
   *
   * @since 1.4.0
   * @return integer
   */
  public function get_price() {

    return apply_filters('wu_subscription_get_price', $this->price, $this);

  } // end get_price;

  /**
   * Get price after Coupon Code
   * @since  1.1.0
   * @return interger
   */
  public function get_price_after_coupon_code() {

    $value = (float) $this->price;

    $cc = $this->get_coupon_code();

    if ($cc && $cc['cycles'] !== false) {

      $cc['value'] = floatval($cc['value']);

      if ($cc['type'] == 'percent') {

        $value = (float) $this->price * (1 - ($cc['value'] / 100));

      } else {

        $value = $this->price - $cc['value'];

        $value = $value > 0 ? $value : 0;

      } // end if;

    } // end if;

    return apply_filters('wu_subscription_get_price_after_coupon_code', $value, $this);

  } // end get_price_after_coupon_code;

  /**
   * Credit and Change Plan
   */

  /**
   * Change this subscription to a new plan
   * @param integer $new_plan_id
   * @return void
   */
  public function swap($new_plan_id) {

  } // end swap;

  /**
   * Returns the management screen URL for this particular Subscription
   *
   * @since 1.7.0
   * @return string
   */
  public function get_manage_url() {

    $url = network_admin_url('admin.php?page=wu-edit-subscription&user_id=') . $this->user_id;

    /**
     * Allow plugin developers to filter the subscription managememt URL
     * 
     * @since 1.7.0
     * @param string URL
     * @param WU_Subscription Current Subscription
     * @return string The URL for the management screen
     */
    return apply_filters('wu_get_manage_url', $url, $this);

  } // end get_manage_url;

  /**
   * Calculate how many days the user has used the subscription on this particular theme.
   * @since 1.5.0
   * @return interger
   */
  public function get_days_used() {

    // First we need to define the previous starting date, which is our active until - frequency
    $active_until = new DateTime($this->active_until);
    $created_at   = new DateTime($this->created_at);
    $start_date   = $active_until->modify("-$this->freq Months");

    // But we also need to compare that to our created date
    if ($start_date < $created_at) { $start_date = $created_at; }

    $now = self::get_now();

    $diff = $now->diff($start_date, true);

    $days_used = $diff->days;

    // Return the days used
    return (int) apply_filters('wu_get_days_used', $days_used, $this);

  } // end days_used;

  /**
   * Get the credit for the subscription on that particular subscription;
   * Used on changing plans.
   * @since 1.5.0
   * @return void
   */
  public function calculate_credit() {

    $days_to_divide = 30 * $this->freq;

    return apply_filters('wu_calculate_credit', $this->get_days_used() * ((float) $this->get_price_after_coupon_code() / $days_to_divide), $this);

  } // end get_credit;

  /**
   * Sets the absolute value for the credit of this subscription. Returns the new amount.
   * @since 1.5.0
   * @return float
   */
  public function set_credit($amount) {
    
    $this->credit = $amount;

    $this->save();

    return $this->credit;

  } // end add_credit;

  /**
   * Adds credit from this subscription. Returns the new amount.
   * @since 1.5.0
   * @return float
   */
  public function add_credit($amount) {

    $this->credit = is_numeric($this->credit) ? (float) $this->credit : 0;

    $this->credit += (float) $amount;

    $this->save();

    return $this->credit;

  } // end add_credit;

  /**
   * Removes credit from this subscription. Returns the new amount.
   * @since 1.5.0
   * @return float
   */
  public function remove_credit($amount) {

    $this->credit = is_numeric($this->credit) ? (float) $this->credit : 0;

    $this->credit -= (float) abs($amount);
    
    $this->save();

    return $this->credit;

  } // end remove_credit;

  /**
   * Returns the Credit amount on that subscription. Value can be negative for debits.
   * @since 1.5.0
   * @return float
   */
  public function get_credit() {

    return apply_filters('wu_subscription_get_credit', (float) $this->credit, $this);

  } // end get_credit;

  /**
   * Add invoice line to the subscription;
   * This will be used to add discount lines and etc;
   * @param array $line
   * @return void
   */
  public function add_invoice_line($line = false) {

    if (!is_array($line)) return $this;

    $this->lines[] = $line;

    return $this;

  } // end add_invoice_line;

  /**
   * Return the invoice lines
   * @return array
   */
  public function get_invoice_lines() {

    return apply_filters('wu_subscription_get_invoice_lines', $this->lines, $this); 
    
  } // end get_formatted_invoice_lines;

  /**
   * Returns the formatted version of the invoice lines;
   * @return string
   */
  public function get_formatted_invoice_lines() {

    $lines = $this->get_invoice_lines();

    return array_reduce($lines, function($string, $item) {

      $string .= $item['text'] .' ('. wu_format_currency($item['value']) .')';

      return $string;

    }, '');

  } // end get_formatted_invoice_lines;

  /**
   * Returns the amount to be paid in the next invoice, considering credits and debits.
   * If the return here is negative, we need to get that return to re-save that as credit.
   * @since 1.5.0
   * @return float
   */
  public function get_outstanding_amount() {

    $message = $this->get_credit() > 0 
      ? apply_filters('wu_subscription_invoice_line_credit_message', __('Credit gained from previous plan usage.', 'wp-ultimo')) 
      : apply_filters('wu_subscription_invoice_line_debit_message', __('Pro-rated adjustment from previous plan.', 'wp-ultimo'));

    $this->add_invoice_line(array(
      'text'  => $message,
      'value' => - (float) $this->get_credit(),
    ));

    $teste =  $this->get_price_after_coupon_code() ;
    $teste2 = $this->get_credit();

    $price = $this->get_price_after_coupon_code() - $this->get_credit();

    return apply_filters('wu_subscription_get_outstanding_amount', $price, $this);

  } // end get_outstanding_amount;

  public function set_meta($name, $value) {

    return update_user_meta($this->user_id, $name, $value);

  } // end set_meta;

  public function get_meta($name) {

    return get_user_meta($this->user_id, $name, true);

  } // end set_meta;

  /**
   * Charges a given amount from the subscription payment option
   *
   * @param float  $amount
   * @param string $description
   * @return void
   */
  public function charge($amount, $description, $type = 'single_charge') {

    /**
     * Let the active gateway do their job
     * @since 1.6.3
     */
    do_action("wu_subscription_charge_$this->gateway", $amount, $description, $this, $type);

  } // end charge;

  /**
   * Checks if subscription has paid setup fee
   *
   * @since 1.7.0
   * @return boolean
   */
  public function has_paid_setup_fee() {

    return apply_filters('wu_subscription_has_paid_setup_fee', $this->paid_setup_fee, $this);

  } // end has_paid_setup_fee;

} // end class WU_Subscription;

/**
 * Return a subscription object based on the user
 * 
 * @param  interger $user_id User id to get subscription from
 * @return WU_Subscription   The subscription object
 */
function wu_get_subscription($user_id) {

  return WU_Subscription::get_instance($user_id);

} // end wu_get_subscription;

/**
 * Return a subscription object based on the integration key
 * 
 * @param  interger $user_id User id to get subscription from
 * @return WU_Subscription   The subscription object
 */
function wu_get_subscription_by_integration_key($integration_key) {

  global $wpdb;

  $table_name = WU_Subscription::get_table_name();

  $sub = $wpdb->get_row($wpdb->prepare("SELECT user_id FROM $table_name WHERE integration_key = %s LIMIT 1", $integration_key));

  return $sub && $sub->user_id ? WU_Subscription::get_instance($sub->user_id) : false;

} // end wu_get_subscription_by_integration_key;

/**
 * Return a subscription object based on the current user
 * 
 * @since 1.7.3
 * @return WU_Subscription   The subscription object
 */
function wu_get_current_subscription() {

  return wu_get_subscription( get_current_user_id() );

} // end wu_get_current_subscription;

/**
 * Checks if the current user is an active subscriber
 *
 * @since 1.6.2
 * @param integer $user_id
 * @return boolean
 */
function wu_is_active_subscriber($user_id = false) {

  $user_id = $user_id ?: wu_get_current_site()->site_owner_id;

  $subscription = wu_get_subscription($user_id);

  return $subscription && ($subscription->is_active() || $subscription->is_on_hold());

} // end wu_is_paying_user;

/**
 * Checks if a given user is a customer of a given plan
 *
 * @since 1.6.2
 * @param integer $user_id
 * @param integer $plan_id
 * @return boolean
 */
function wu_has_plan($user_id, $plan_id) {

  $subscription = wu_get_subscription($user_id);

  return $subscription && $subscription->plan_id == $plan_id;

} // end wu_has_plan;

<?php
/**
 * Site Model Class
 *
 * Handles the model for sites
 *
 * @author      Arindo Duque
 * @category    Admin
 * @package     WP_Ultimo/Model
 * @version     0.0.1
*/

if (!defined('ABSPATH')) {
  exit;
}

use Mercator\Mapping;

/**
 * WU_Site Model Class.
 */
class WU_Site {

  /**
   * Site ID
   */
  public $ID;

  /**
   * Site ID Owner table
   * @var boolean
   */
  public $table_id = false;

  /**
   * WP_Site object
   * @var WP_Site
   */
  public $site;
    
  /**
   * Add Site owner property
   */
  public $site_owner;

  /**
   * Add Site owner property
   */
  public $site_owner_id = false;

  /**
   * The subscription object
   * @var WU_Subscription
   */
  public $subscription = false;

  /**
   * Plan ID
   * @var interger/boolean
   */
  public $plan_id;

  /**
   * Site Name
   * @since  1.1.3 Used for template lists and etc
   * @var Name of the site
   */
  public $name;

  /**
   * Uses SSL?
   * @since  1.2.0
   */
  public $use_ssl = false;

  /**
   * Holds the domain Mapping object for that site
   * @since  1.2.0
   * @var boolean
   */
  public $mapping = false;

  /**
   * Original URL, used for mapping purposes
   * @since  1.2.0
   * @var boolean
   */
  public $original_url = false;

  /**
   * Get the instance
   * @since  1.1.3 Now we also get the site name;
   * @param  integer $site_id The site ID to get
   */
  public function __construct($site_id, $plan_id = false) {

    // Get site instance
    $this->site = WP_Site::get_instance($site_id);

    if (!$this->site) return false;

    /**
     * @since  1.1.3 Adds the site name
     */
    $this->name = get_blog_details(array('blog_id' => $site_id))->blogname;

    // Set the id for later user
    $this->ID = $this->site->id;

    // Get the site owner
    $this->site_owner    = $this->get_owner();
    $this->site_owner_id = $this->site_owner ? $this->site_owner->ID : false;

    /**
     * @since  1.2.0 Since we already here, maybe this is a good place to add the new cap necessary to see the account page
     */
    $cap = 'manage_wu_'. $this->ID .'_account';

    if ($this->site_owner && !user_can($this->site_owner_id, $cap)) {

      $this->site_owner->add_cap($cap);

    }

    // Get the subscription
    $this->subscription = $this->get_subscription();

    // Set plan to easier access
    $this->plan_id = $this->subscription ? $this->subscription->plan_id : false;

    /**
     * 
     * Domain Mapping Related Stuff
     * 
     */
    
    /** @var Mapping Valid mapping, or false */
    $this->mapping = $this->get_active_mapping();

    /** @var string Original URL for this site */
    $this->original_url = $this->get_original_url();

  } // end construct;

  /**
   * Return the Scheme to use with a certain site when it is mapped
   * @since  1.2.0
   * @return string
   */
  public function get_scheme($force = false) {

    /**
     * Case Main Site
     */
    if (is_main_site($this->ID) || (is_admin() && !is_subdomain_install()) || $GLOBALS['pagenow'] === 'wp-login.php') {

      $use_ssl = $force || (defined('FORCE_SSL_ADMIN') && FORCE_SSL_ADMIN) || $this->get_meta('force_https');

      $scheme = $use_ssl ? 'https://' : (is_ssl() ? 'https://' : 'http://');

    /**
     * Case Mapped Domain
     */
    } else if ($this->mapping) {

        $unmapped = (WU_Settings::get_setting('force_subdomains_https') && ($_SERVER['HTTP_HOST'] !== $this->mapping->get_domain())) && WU_Settings::get_setting('force_admin_https');
    
        $scheme =  $unmapped || $force || WU_Settings::get_setting('force_mapped_https') || $this->get_meta('force_https') ? 'https://' : 'http://';

    /**
     * Case: Site with Mapped Domain or Subdomais
     */
    } else if (is_subdomain_install()) { // Removed this: $this->mapping || 

      $scheme =  $force || WU_Settings::get_setting('force_subdomains_https') || $this->get_meta('force_https') ? 'https://' : 'http://';

    /**
     * Case: Subdirectories
     */
    } else {

      $scheme = is_ssl() || $this->get_meta('force_https') ? 'https://' : 'http://';

    }

    wp_cache_set($this->ID, $scheme, 'wu_site_schemes', 30*60);

    return $scheme;

  } // end get_scheme;

  /**
   * Gets the original URL for this site, prior to domain mapping
   * @since  1.2.0
   * @return string
   */
  public function get_original_url() {

    return is_subdomain_install() ? $this->site->domain : rtrim($this->site->domain . $this->site->path, '/');

  } // end get_original_url;

  /**
   * Return the first active mapping for that site
   * @since  1.2.0
   * @return Mercator\Mapping
   */
  public function get_active_mapping() {

    if (!class_exists('\Mercator\Mapping')) return false;

    /** Get from Cache */
    $found = false;

    $_mapping = wp_cache_get($this->ID, 'wu_mapping', false, $found);

    if ($found) {

      return $_mapping;

    } else {

      $mappings = Mapping::get_by_site($this->ID);

      if (is_wp_error($mappings) || !$mappings) {
        
        return false;

      }

      // Loop all Valid Mappings
      foreach ($mappings as $mapping) {

        $is_enabled = apply_filters('mercator.redirect.enabled', $mapping->is_active(), $mapping);

        if ($is_enabled) {

          wp_cache_set($this->ID, $mapping, 'wu_mapping');

          return $mapping;

        } // end if;

      } // end foreach;

      return false;

    }

  } // end get_active_mapping;

  /**
   * Get the user count for this particular site.
   * 
   * This is used on the limitation functions to see if the admin can add additional users
   *
   * @since 1.9.2
   * @return int
   */
  public function get_user_count() {

    /**
     * Allow plugins developers to add roles to the exclusion list.
     * 
     * This is useful because some specific users should not count towards the users limit, 
     * like WooCommerce customers
     * 
     * @since 1.9.2
     * @param array $roles List of roles
     * @return array $roles Modified list of roles
     */
    $exclude_roles = apply_filters('wu_site_get_user_count_exclude_roles', array(
      'customer' // WooCommerce users
    ));

    $args = array(
      'blog_id'      => $this->ID,
      'role__not_in' => $exclude_roles,
      'fields'       => array('ID'),
    ); 

    $users = get_users($args);

    /**
     * Allow plugins developers to change the actual user count
     * 
     * @since 1.9.2
     * @param int $user_count User count
     * @param array $users Array of user IDs
     * @return int $roles Modified count number
     */
    return apply_filters('wu_site_get_user_count', count($users), $users);

  } // end get_user_count;

  /**
   * Get the subscription object
   * 
   * @since  1.1.4 Checks if user is super admin before giving a subscription to him
   * @return WU_Subscription Subscription object
   */
  public function get_subscription() {

    if (!$this->site_owner) return false;

    // Get the current subscription
    $subscription = wu_get_subscription($this->site_owner->ID);
    
    // if it does exists yet, create it
    // if (!$subscription && !is_super_admin($this->site_owner->ID)) {
    //   $subscription = new WU_Subscription((object) array(
    //     'user_id'      => $this->site_owner->ID,
    //     'created_at'   => date('Y-m-d H:i:s'),
    //     'active_until' => date('Y-m-d H:i:s'),
    //   ));
    //   $subscription = $subscription->save();
    // }

    return $subscription;

  } // end get_subscription;

  /**
   * Returns the site owner to do stuff
   * @return integer/boolean Integer of the user id that owns this site
   */
  public function get_owner() {

    global $wpdb;

    $data = false;

    $table_name = WU_Site_Owner::get_table_name();

    $result = $wpdb->get_row("SELECT user_id, ID FROM $table_name WHERE site_id = $this->ID");

    if ($result) {

      $data = get_user_by('id', (int) $result->user_id);

      $this->table_id = $result->ID;

    } else {

      $this->table_id = 0;

    }

    return $data;

  } // end get_owner;

  /**
   * Set a new owner for a specifc site
   * @param interger $user_id The id of the new owner
   */
  public function set_owner($user_id, $role = 'administrator') {

    global $wpdb, $current_site;

    if ($this->table_id == false) {
      
      $db_call = $wpdb->insert(WU_Site_Owner::get_table_name(), array(
        'site_id' => $this->ID,
        'user_id' => (int) $user_id,
      ));

    } else {

      $db_call = $wpdb->replace(WU_Site_Owner::get_table_name(), array(
        'id'      => $this->table_id,
        'site_id' => $this->ID,
        'user_id' => (int) $user_id,
      ));

    } // end if;

    /**
     * @since  1.3.4 Check if roles exists and if not, reset them
     */
    $user_roles = get_option($wpdb->prefix.'user_roles', false);

    if (!$user_roles) {

      if (!function_exists('populate_roles')) {

        require_once(ABSPATH.'wp-admin/includes/schema.php');

      } // end if;

      switch_to_blog($this->ID);

        populate_roles();

        // populate_roles() clears previous role definitions so we start over.
        $wp_roles = new WP_Roles();

      restore_current_blog();

    } // end if;

    // Add user to blog
    add_user_to_blog($this->ID, $user_id, $role);

    // If there is another previous owner, remove him
    if ($this->site_owner && $this->site_owner->ID !== $user_id) {

      remove_user_from_blog($this->site_owner->ID, $this->ID);

    } // end if;

    // Set new owner here
    $this->site_owner    = $this->get_owner();
    $this->site_owner_id = $this->site_owner->ID;
    $this->subscription  = $this->get_subscription();
    $this->plan_id       = $this->subscription ? $this->subscription->plan_id : false;
    
    $remove_cache = wp_cache_delete($this->ID, 'wu_site');

    switch_to_blog(get_current_site()->blog_id);

      $remove_cache = wp_cache_delete($this->ID, 'wu_site');

    restore_current_blog();

    return $db_call;

  } // end set_owner;

  /**
   * Returns if the user passed is the current owner of a site
   * @param  integer [$user_id          = false] User ID
   * @param  integer [$site_id          = false] Site ID
   * @return boolean If it is the owner or not
   */
  public function is_user_owner($user_id = false) {

    // If master admin, always return true
    // if (current_user_can('manage_network')) return true;
    
    // If not one user was passed, we set to the current
    $user_id = $user_id ? $user_id : get_current_user_id();

    if (!$this->site_owner) return false;

    return $user_id == $this->site_owner->ID;
    
  } // end is_user_owner;

  /**
   * Returns a site Plan
   * @return WU_Plan Plan object
   */
  public function get_plan() {

    if (!$this->plan_id) return false;
    
    // Get the plan
    return wu_get_plan($this->plan_id);

  } // end get_plan;

  /**
   * Returns the plan id
   *
   * @since 1.5.4
   * @return void
   */
  public function get_plan_id() {

    return $this->plan_id ?: false;

  } // end get_plan_id;

  /**
   * Set the custom domain to a site
   * @param string $site_domain The domain to integrate to this site
   */
  public function set_custom_domain($site_domain, $active = true) {

    // Save the meta
    $this->set_meta('custom-domain', $site_domain);

    // Get previous instances
    $mappings = Mercator\Mapping::get_by_site($this->ID);

    // Remove all previous entries
    if (is_array($mappings)) {
      foreach($mappings as $mapping) {
        $mapping->delete();
      }
    }

    // Create new mapping
    if ($site_domain != '') {
      $mapping = Mercator\Mapping::create($this->ID, $site_domain, $active);
    }

    /** Refresh Cache */
    wp_cache_delete($this->ID, 'wu_site');

  } // end set_custom_domain;

  /**
   * Set a meta field to the site, using options API
   * @param string $meta_key   Key, without prefix
   * @param string $meta_value Value of the field to be saved
   */
  public function set_meta($meta_key, $meta_value) {

    wp_cache_delete($this->ID, 'wu_site');

    return update_blog_option($this->ID, 'wu_'.$meta_key, $meta_value);

  } // end set_meta;

  /**
   * Get site meta by meta key
   * @param string $meta_key Metakey name to be returned, without prefix
   */
  public function get_meta($meta_key) {

    return get_blog_option($this->ID, 'wu_'.$meta_key, false);

  } // end get_meta;

  /**
   * Returns the visit count reset date
   *
   * @since 1.7.0
   * @param boolean $format
   * @return string
   */
  public function get_visit_count_reset_date($format = false) {

    $days_to_next_reset = apply_filters('wu_reset_visits_count_days', 30);

    $format = $format ?: get_option('date_format');

    $last_reseted = $this->get_meta('visits_reseted_at'); 

    try {
      
      $last_reseted = $last_reseted ? new DateTime($last_reseted) : new DateTime( $this->site->registered );

    } catch (Exception $e) {

      $last_reseted = new DateTime( $this->site->registered );

    } // end catch;

    return $last_reseted
      ->add(date_interval_create_from_date_string("+$days_to_next_reset days"))
      ->format($format);

  } // end get_visit_count_reset_date;

  /**
   * Resets the visit count on this site
   *
   * @since 1.7.0
   * @return WU_Site
   */
  public function reset_site_visit_count() {

    $count = 0;

    $this->set_meta('visits_reseted_at', WU_Transactions::get_current_time('mysql'));
    $this->set_meta('visits_count', $count);

    $this->set_meta('visits_limit_approaching_alert_sent', false);
    $this->set_meta('visits_limit_reached_alert_sent', false);

    return $this;

  } // end reset_site_visit_count;

} // end class WU_Site;

/**
 * Return the current Site Object
 * @return WU_Site Object of the current site, to allow rapid access
 */
function wu_get_current_site() {

  return wu_get_site(get_current_blog_id());

} // end wu_get_current_site;

/**
 * Returns a WU Site instance, based on ID
 * @param integer $site_id The site to return
 */
function wu_get_site($site_id) {

  $found = false;

  $_site = wp_cache_get($site_id, 'wu_site', false, $found);

  if ($found) {

    return $_site;

  } else {

    $site = new WU_Site($site_id);

    wp_cache_set($site_id, $site, 'wu_site', 10 * MINUTE_IN_SECONDS);

    return $site;

  } // end if;

} // end wu_get_site;

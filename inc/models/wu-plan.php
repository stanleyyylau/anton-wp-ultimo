<?php
/**
 * Plan Class
 *
 * Handles the addition of new plans
 *
 * @author      Arindo Duque
 * @category    Admin
 * @package     WP_Ultimo/Model
 * @version     0.0.1
*/

if (!defined('ABSPATH')) {
  exit;
}

/**
 * WU_Plan class.
 */
class WU_Plan {

  /**
   * Holds the ID of the WP_Post, to be used as the ID of each plan
   * @var integer
   */
  public $id = 0;

  /**
   * Holds the WP_Post Object of the Plan
   * @var null
   */
  public $post = null;

  /**
   * The status of the post
   * @var string
   */
  public $post_status = '';

  /**
   * Meta fields contained as attributes of each plan
   * @var array
   */
  public $meta_fields = array(
    'title',
    'free',
    'order',
    'price_1',
    'price_3',
    'price_12',
    'description',
    'top_deal',
    'custom_domain',
    'allowed_plugins',
    'allowed_themes',
    'quotas',
    'site_template',
    'feature_list',
    'display_post_types',
    'hidden',                // @since 1.2.0
    'disabled_post_types',   // @since 1.5.4
    'override_templates',    // @since 1.9.0
    'templates',             // @since 1.5.4
    'role',                  // @since 1.5.4
    'trial',                 // @since 1.6.0
    'setup_fee',             // @since 1.7.0
    'advanced_options',      // @since 1.7.0
    'copy_media',            // @since 1.7.0
    'unlimited_extra_users', // @since 1.7.0
    'is_contact_us',         // @since 1.9.0
    'contact_us_label',      // @since 1.9.0
    'contact_us_link',       // @since 1.9.0
  );

  /**
   * Retrieve the main site id for that network
   * @param  integer $network_id Network ID to retrieve
   * @return string               Main site id
   */
  public function get_network_main_site_id($network_id = null) {
  
    global $wpdb;

    $network = get_network($network_id);

    if ($network) {

      return $wpdb->get_var($wpdb->prepare("SELECT `blog_id` FROM `$wpdb->blogs` WHERE `domain` = '%s' AND `path` = '%s' ORDER BY `blog_id` ASC LIMIT 1", $network->domain, $network->path));

    } else {

      return 0;

    } // end if;

  } // end get_network_main_site;

  /**
   * Returns the plan id
   *
   * @since 1.5.5
   * @return integer
   */
  public function get_id() {
    return $this->id;
  } // end get_id

  /**
   * Construct our new plan
   */
  public function __construct($plan = false) {

    if ( is_numeric( $plan ) ) {
      $this->id   = absint( $plan );
      $this->post = get_post( $plan );
      $this->getPlan( $this->id );
    } elseif ( $plan instanceof WU_Plan ) {
      $this->id   = absint( $plan->id );
      $this->post = $plan->post;
      $this->getPlan($this->id);
    } elseif ( isset( $plan->ID ) ) {
      $this->id   = absint( $plan->ID );
      $this->post = $plan;
      $this->getPlan( $this->id );
    }

  } // end construct;

  /**
   * Gets a plan from the database.
   * @param int  $id (default: 0).
   * @return bool
   */
  public function getPlan($id = 0) {

    if (!$id) {
      return false;
    }

    if ($result = get_blog_post( (int) get_current_site()->blog_id, $id) ) {
      $this->populate( $result );
      return true;
    }

    return false;

  }

  /**
   * Populates an order from the loaded post data.
   * @param mixed $result
   */
  public function populate($result) {

    // Standard post data
    $this->id           = $result->ID;
    $this->post_status  = $result->post_status;

  } // end populate;

  /**
   * Checks if the plan really exists.
   *
   * @return bool
   * @since 1.10.6
   */
  public function plan_exists() {
    return $this->id > 0;
  } // end plan_exists;

  /**
   * __isset function.
   * @param mixed $key
   * @return bool
   */
  public function __isset($key) {
    if (!$this->id) return false;
    // Swicth to main blog
    switch_to_blog( get_current_site()->blog_id );
    $value = metadata_exists('post', $this->id, 'wpu_' . $key);
    restore_current_blog();
    return $value;
  }

  /**
   * __get function.
   * @param mixed $key
   * @return mixed
   */
  public function __get($key) {
    
    // Switch to main blog
    switch_to_blog( get_current_site()->blog_id );
    
    $value = get_post_meta( $this->id, 'wpu_' . $key, true);
    
    restore_current_blog();

    if ($key == 'allowed_themes' || $key == 'allowed_plugins') {
      $value = $value ?: array();
    }

    // Quotas
    if ($key == 'quotas') {

      $value = wp_parse_args($value, array(
        'sites' => 1,
      ));

    } // end if;

    if ($key == 'display_post_types' && is_array($value) && empty($value)) {

      $value = true;

    }

    if ($key == 'display_post_types' && $value === false) {

      $value = wp_parse_args($value, array(
        'post'       => true,
        'page'       => true,
        'attachment' => true,
        'sites'      => true,
        'upload'     => true,
      ));

    } // end if;

    if ($key == 'templates') {
      return $value ?: array();
    }

    return $value;

  }

  /**
   * Checks if a template is available for a given template
   *
   * @param integer $template_id
   * @return boolean
   */
  public function is_template_available($template_id) {

    $templates = $this->templates;

    if (!$templates && !$this->id) return true;

    return isset( $templates[$template_id] ) && $templates[$template_id];

  } // end if;

  /**
   * Check if we need to override the templates for this plan
   *
   * @since 1.9.0
   * @return boolean
   */
  public function should_override_templates() {

    return apply_filters('wu_plan_should_override_templates', $this->override_templates, $this);

  } // end should_override_templates;

  /**
   * Check if this is a Contact Us plan
   *
   * @since 1.9.0
   * @return boolean
   */
  public function is_contact_us() {

    return apply_filters('wu_plan_is_contact_us', $this->is_contact_us, $this);

  } // end is_contact_us;

  /**
   * Get the contact us label
   *
   * @since 1.9.0
   * @return boolean
   */
  public function get_contact_us_label() {

    return apply_filters('wu_plan_get_contact_us_label', $this->contact_us_label ?: __('Contact Us', 'wp-ultimo'), $this);

  } // end get_contact_us_label;

  /**
   * Returns wether or not we should display a given quota type in the Quotas and Limits widgets
   *
   * @since 1.5.4
   * @param string $quota_type
   * @return bool
   */
  public function should_display_quota($quota_type) {

    /**
     * Extra check for visits
     * @since 1.7.3
     */
    if ($quota_type == 'visits' && ! WU_Settings::get_setting('enable_visits_limiting')) return false;

    /**
     * @since 1.3.3 Only Show elements desired by the admin
     */
    $elements = WU_Settings::get_setting('limits_and_quotas');

    if (!$elements) return true;

    return isset( $elements[$quota_type] ) && $elements[$quota_type];

  } // end should_display_quota;

  /**
   * Returns wether or not we should display a given quota type in the Quotas and Limits widgets
   *
   * @since 1.5.4
   * @param string $quota_type
   * @return bool
   */
  public function should_display_quota_on_pricing_tables($quota_type, $default = false) {

    /**
     * @since  1.3.3 Only Show elements allowed on the plan settings
     */
    $elements = $this->display_post_types;

    if (!$elements) return true;

    if (!isset( $elements[$quota_type] ) && $default) {

      return true;

    } // end if;

    return isset( $elements[$quota_type] ) && $elements[$quota_type];

  } // end should_display_quota_on_pricing_tables;

  /**
   * Checks if we should copy the media files for this particular plan
   *
   * @since 1.7.0
   * @return boolean
   */
  public function should_copy_media() {

    $global_setting = WU_Settings::get_setting('copy_media', true);

    // We should onyl use this if advanced options is enabled
    if (! $this->advanced_options) return $global_setting;

    $should_copy_media = $this->copy_media == false ? $global_setting : $this->copy_media == 'yes';

    return apply_filters('wu_plan_should_copy_media', $should_copy_media, $this);

  } // end should_copy_media;

  /**
   * Checks if this plan allows unlimited extra users
   * 
   * @since 1.7.0
   * @return boolean
   */
  public function should_allow_unlimited_extra_users() {

    return apply_filters('wu_plan_should_allow_unlimited_extra_users', (bool) $this->unlimited_extra_users, $this);

  } // end should_allow_unlimited_extra_users;

  /**
   * Returns wether or not we should display a given quota type in the Quotas and Limits widgets
   *
   * @since 1.5.4
   * @param string $quota_type
   * @return bool
   */
  public function is_post_type_disabled($post_type) {

    $elements = $this->disabled_post_types;

    if (!$elements) return false;

    return isset( $elements[$post_type] ) && $elements[$post_type];

  } // end should_display_quota_on_pricing_tables;

  /**
   * Returns the post_type quotas
   *
   * @since 1.7.0
   * @return array
   */
  public function get_post_type_quotas() {

    $quotas = $this->quotas;

    return array_filter($quotas, function($quota_name) {

      return !in_array($quota_name, array(
        'sites', 'attachment', 'upload', 'users', 'visits'
      ));

    }, ARRAY_FILTER_USE_KEY);

  } // end get_post_type_quotas;

  /**
   * Returns the quota value for a given post type (or sites).
   * Returns FALSE when it is not set, but might return 0 for unlimited quota
   *
   * @since 1.5.4
   * @param string $quota_type
   * @return integer|false
   */
  public function get_quota($quota_type) {

     $quotas = $this->quotas;

     $quota = is_array($quotas) && isset($quotas[$quota_type]) ? (int) $quotas[$quota_type] : false;

     return apply_filters('wu_plan_get_quota', $quota, $quota_type, $quotas, $this);

  } // end get_quota;

  /**
   * Get the legacy shareable link, used before 1.9.0
   *
   * @since 1.9.0
   * @param int|boolean $plan_freq
   * @return string
   */
  public function get_legacy_shareable_link($plan_freq = false) {

    $plan_freq = $plan_freq ?: WU_Settings::get_setting('default_pricing_option', 1);

    $atts = shortcode_atts(array(
      'skip_plan' => 1, 
      'plan_id'   => $this->id,
      'plan_freq' => $plan_freq,
      'action'    => 'wu_process_plan_select',
    ), array(), 'wu_plan_link');

    $url = add_query_arg($atts, admin_url('admin-ajax.php'));

    return $url;

  } // end get_legacy_shareable_link;

  /**
   * Get the shareable link for this plan, depending on the permalinks structure
   *
   * @since 1.9.0
   * @param int|boolean $plan_freq
   * @return string
   */
  public function get_shareable_link($plan_freq = false) {

    $is_permalinks_enabled = get_blog_option(get_current_site()->blog_id, 'permalink_structure', false);

    $plan_freq = $plan_freq ?: WU_Settings::get_setting('default_pricing_option', 1);

    if ($is_permalinks_enabled && WU_Settings::get_setting('registration_url', false)) {

      $plan_slug = $this->post->post_name ? $this->post->post_name : $this->id;

      return WU_Signup()->get_signup_url() . "/$plan_freq/$plan_slug";

    } // end if;

    return $this->get_legacy_shareable_link($plan_freq);

  } // end get_shareable_link;

  /**
   * Set attributes in a plan, based on a array. Useful for validation
   * @param array $atts Attributes
   */
  public function set_attributes($atts) {
    
    foreach($atts as $att => $value) {
      $this->{$att} = $value;
    }

    $this->top_deal      = isset($atts['top_deal']);
    $this->free          = isset($atts['free']);
    $this->custom_domain = isset($atts['custom_domain']);
    $this->hidden        = isset($atts['hidden']); // @since 1.2.0

    return $this;

  } // end set_attributes;

  /**
   * Save the current Plan
   */
  public function save() {

    // Switch to main blog
    switch_to_blog( get_current_site()->blog_id );

    $this->title = wp_strip_all_tags($this->title);

    $planPost = array(
      'post_type'     => 'wpultimo_plan',
      'post_title'    => $this->title,
      'post_content'  => '',
      'post_status'   => 'publish',
    );

    /**
     * Add the option to set the slug
     * @since 1.9.0
     */
    if ($this->slug) {

      $planPost['post_name'] = $this->slug;

    } // end if;

    if ($this->id !== 0 && is_numeric($this->id)) $planPost['ID'] = $this->id;

    // Insert Post
    $this->id = wp_insert_post($planPost);

    // Add the meta
    foreach ($this->meta_fields as $meta) {
      update_post_meta($this->id, 'wpu_'.$meta, $this->{$meta});
    }

    // Do something
    restore_current_blog();
    
    // Return the id of the new post
    return $this->id;

  } // end save;

  /**
   * Return if this plan is updgradable, in case of having plans with hight order
   */
  public function is_upgradable() {
    // TODO: Select posts with higher order from database
  }

  /**
   * Returns object containing all users from this plan
   */
  public function get_subscription_count() {
    
    global $wpdb;
    
    $table_name = WU_Subscription::get_table_name();
    $result = $wpdb->get_row("SELECT count(id) as count FROM $table_name WHERE plan_id = $this->id");
    return is_object($result) ? $result->count : '';

  } // end get_subscription_count;
  
  /**
   * Gets all the subscriptions of that plan
   * @return array Array of subscriptions of that plan
   */
  public function get_subscriptions() {

    global $wpdb;
    
    $table_name = WU_Subscription::get_table_name();
    return $result = $wpdb->get_results("SELECT id, user_id FROM $table_name WHERE plan_id = $this->id");

  } // end get_subscriptions;  

  /**
   * Count the sites on a given plan
   * 
   * @since 1.5.5
   * @return integer The site count
   */
  public function get_site_count() {

    return count( array_filter( array_column($this->get_sites(), 'site_id') ));

  } // end get_sites;

  /**
   * Gets all the sites of that plan
   * @return array Array of sites of that plan
   */
  public function get_sites() {

    global $wpdb;

    $table_name_sub  = WU_Subscription::get_table_name();
    $table_name_site = WU_Site_Owner::get_table_name();

    $sql = "SELECT site.site_id FROM $table_name_sub sub LEFT JOIN $table_name_site site ON site.user_id = sub.user_id WHERE sub.plan_id = $this->id";

    $result = $wpdb->get_results($sql);

    return $result;

  } // end get_sites;

  /**
   * Returns the price of the plan for a given billing frequency
   *
   * @since 1.4.0
   * @param integer $billing_frequency
   * @return integer
   */
  public function get_price($billing_frequency = 1) {

    $price = ($this->free) ? 0 : $this->{"price_$billing_frequency"};

    return apply_filters('wu_plan_get_price', $price, $billing_frequency);

  } // end get_price;

  /**
   * Get trial value for this plan
   * 
   * @since 1.6.0
   * @return integer
   */
  public function get_trial() {

    return (int) $this->trial;

  } // end get_trial;

  /**
   * Get the setup fee for this particular plan, if none is set, check for a default value
   *
   * @since 1.7.0
   * @return float
   */
  public function get_setup_fee() {

    return apply_filters('wu_plan_get_setup_fee', $this->setup_fee ? (float) $this->setup_fee : 0, $this);

  } // end get_setup_fee;

  /**
   * Alias for the get_setup_fee, just in case we need additional logic in the future
   * 
   * @since 1.7.0
   * @return boolean
   */
  public function has_setup_fee() {

    return (bool) $this->get_setup_fee();

  } // end has_setup_fee;

  /**
   * Get the pricing table lines to be displayed on the pricing tables
   * @since  1.4.0
   * @return array
   */
  public function get_pricing_table_lines() {

    $pricing_table_lines = array();

    /**
     * Setup Fee
     * @since 1.7.0
     */
    if ($this->should_display_quota_on_pricing_tables('setup_fee', true)) {

      if ($this->is_contact_us()) {

        $pricing_table_lines[] = __('Contact Us to know more', 'wp-ultimo');

      } else {

        $pricing_table_lines[] = $this->has_setup_fee() 
          ? sprintf(__('Setup Fee: %s', 'wp-ultimo'), "<strong class='pricing-table-setupfee' data-value='" . $this->get_setup_fee() . "'>" . wu_format_currency($this->get_setup_fee()) . '</strong>') 
          : __('No Setup Fee', 'wp-ultimo');

      } // end if;

    } // end if;

    /**
     *
     * Post Type Lines
     * Gets the post type lines to be displayed on the pricing table options
     * 
     */
    $post_types = get_post_types(array('public' => true), 'objects');
    $post_types = apply_filters('wu_get_post_types', $post_types);
    
    foreach ($post_types as $pt_slug => $post_type) {

      /**
       * @since  1.1.3 Let users choose which post types to display on the pt
       */
      if ($this->should_display_quota_on_pricing_tables($pt_slug)) {

        /**
         * Get if disabled
         */
        if ($this->is_post_type_disabled($pt_slug)) {

          // Translators: used as "No Posts" where a post type is disabled
          $pricing_table_lines[] = sprintf(__('No %s', 'wp-ultimo'), $post_type->labels->name);
          continue;

        } // end if;

        /**
         * Get the values
         * @var integer|string
         */
        $value = $this->get_quota($pt_slug) == 0 
          ? __('Unlimited', 'wp-ultimo') 
          : $this->get_quota($pt_slug);

        // Add Line
        $label = $value == 1 ? $post_type->labels->singular_name : $post_type->labels->name;
        
        $pricing_table_lines[] = sprintf('%s %s', $value, $label); 

      } // end if;

    } // end foreach;

    /**
     *
     * Site, Disk Space and Trial
     * Gets the Disk Space and Sites to be displayed on the pricing table options
     * 
     */
    if (WU_Settings::get_setting('enable_multiple_sites') && $this->should_display_quota_on_pricing_tables('sites')) {

      $value = $this->quotas['sites'] == 0 ? __('Unlimited', 'wp-ultimo') : $this->quotas['sites'];

      // Add Line
      $pricing_table_lines[] = sprintf('<strong>%s %s</strong>', $value, _n('Site', 'Sites', $this->quotas['sites'], 'wp-ultimo'));

    } // end if;

    /**
     * Display DiskSpace
     */
    if ($this->should_display_quota_on_pricing_tables('upload')) {

      $disk_space = WU_Util::format_megabytes($this->quotas['upload']);

      // Add Line
      $pricing_table_lines[] = sprintf(__('%s <strong>Disk Space</strong>', 'wp-ultimo'), $disk_space);

    } // end if;

    /**
     * Visits
     * @since 1.6.0
     */
    if ($this->should_display_quota_on_pricing_tables('visits')) {

      $value = $this->get_quota('visits') == 0 ? __('Unlimited', 'wp-ultimo') : number_format($this->get_quota('visits'));

      // Add Line
      $pricing_table_lines[] = sprintf('%s %s', $value, _n('Visit per month', 'Visits per month', $this->get_quota('visits'), 'wp-ultimo'));

    } // end if;

    /**
     * Display Trial, if some
     */
    $trial_days      = WU_Settings::get_setting('trial');
    $trial_days_plan = $this->get_trial();

    if ($trial_days > 0 || $trial_days_plan) {

      $trial_days = $trial_days_plan ? $trial_days_plan : $trial_days;

      $pricing_table_lines[] = !$this->free ? sprintf(__('%s day <strong>Free Trial</strong>', 'wp-ultimo'), $trial_days) : '-';

    } // end if;

    /**
     *
     * Site, Disk Space and Trial
     * Gets the Disk Space and Sites to be displayed on the pricing table options
     * 
     */
    
    /** Loop custom lines */
    $custom_features = explode('<br />', nl2br($this->feature_list));

    foreach($custom_features as $custom_feature) {

      if (trim($custom_feature) == '') continue; 

      $pricing_table_lines[] = sprintf('%s', trim($custom_feature));

    } // end if;

    /**
     * Return Lines, filterable
     */
    return apply_filters("wu_get_pricing_table_lines_$this->id", $pricing_table_lines, $this);

  } // end get_pricing_table_lines;

} // end Class WU_Plan

/**
 * Returns a plan based on the id passed
 *
 * @param integer $plan_id
 * @return WU_Plan|boolean
 */
function wu_get_plan($plan_id) {

  $plan = new WU_Plan($plan_id);

  return $plan->plan_exists() ? $plan : false;

} // end wu_get_plan;

/**
 * Gets a plan by its slug
 *
 * @since 1.9.0
 * @param string $plan_slug
 * @return WU_Plan|boolean
 */
function wu_get_plan_by_slug($plan_slug) {

  $plans = get_posts(array(
    'post_name__in'  => array($plan_slug),
    'post_type'      => 'wpultimo_plan',
    'fields'         => 'ids',
    'posts_per_page' => 1,
    'post_status'    => 'publish',
  ));

  return !empty($plans) ? wu_get_plan( array_pop($plans) ) : false;

} // end wu_get_plan_by_slug;
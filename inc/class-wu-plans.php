<?php
/**
 * Plans Class Init
 *
 * Handles the infrastructure of this part of the plugin
 *
 * @author      Arindo Duque
 * @category    Admin
 * @package     WP_Ultimo/Plans
 * @version     0.0.1
*/

if (!defined('ABSPATH')) {
  exit;
}

class WU_Plans {

  /**
   * Holds the Subscription instance
   * 
   * @return WU_Plans
   */
  static $instance;

  /**
   * Returns the one and only instance of this class
   *
   * @since 1.8.2
   * @return WU_Plans
   */
  public static function get_instance() {

    if (!isset(self::$instance)) {

      self::$instance = new self();

    } // end if;

    return self::$instance;

  } // end get_instance;

  /**
   * Instantiates the class
   */
  public function __construct() {

    // Create our Custom post Type for Plans in the Main Site
    add_action('init', array($this, 'add_plan_cpt'));
    add_action('init', array($this, 'add_coupon_cpt'));
    
    // Dynamically changes the plan order
    add_action('wp_ajax_wu-change-plan-order', array($this, 'change_plan_order'));

    // Handle roles on plan_change
    add_action('wu_subscription_change_plan', array($this, 'refresh_role_after_plan_change'), 9, 3);
    add_action('wu_subscription_change_plan', array($this, 'refresh_plan_id_meta_for_user'), 9, 3);

    // Adds the activation code for the network admin plugins page
    add_action('pre_current_active_plugins', array($this, 'render_activate_plugins_for_plan_form'));
    add_action('wp_ajax_wu-handle-activate-plugins-for-plan', array($this, 'handle_activate_plugins_for_plan'));
    add_action('wp_ajax_wu-get-activation-status', array($this, 'handle_get_activation_status'));

    // Add our localization strings
    add_filter('wu_js_variables', array($this, 'add_plan_localization_strings_to_main_script'));

    // Add notice when coupons are disabled
    add_action('wu_page_load', array($this, 'coupon_page_hooks'));

    // Filter template id for plans with a template associated with it.
    add_filter('wu_site_template_id', array($this, 'filter_template_id_on_new_site'), 9, 2);

    add_filter('wu_plan_get_setup_fee', array($this, 'filter_coupon_apply_setupfee'));
    
  } // end construct;

  public function filter_coupon_apply_setupfee($value) {

    $subscription = wu_get_current_subscription();

    if ($subscription) {

      $cc = $subscription->get_coupon_code();

      if ($cc && $cc['applies_to_setup_fee'] && $cc['cycles'] !== false) {

          $cc['setup_fee_discount_value'] = floatval($cc['setup_fee_discount_value']);

          if ($cc['setup_fee_discount_type'] == 'percent') {

            $value = $value * (1 - ($cc['setup_fee_discount_value'] / 100));

          } else {

            $value = $value - $cc['setup_fee_discount_value'];

            $value = $value > 0 ? $value : 0;

          } // end if;

      } // end if;

    } // end if;

    return $value ? (float) $value : 0;

  } // end filter_coupon_apply_setupfee;
  
  /**
   * When a user adds a new site, we should check if there is a template associated with that plan. 
   *
   * @since 1.9.9
   * @param int $site_template ID of the current plan passed.
   * @param int $user_id ID of the current user.
   * @return int
   */
  public function filter_template_id_on_new_site($site_template, $user_id) {
    
    if (WU_Settings::get_setting('allow_template', false) != false) {

      return $site_template;

    } // end if;

    $subscription = wu_get_subscription($user_id);

    if ($subscription) {

      $plan = $subscription->get_plan();

      if ($plan && $plan->site_template) {

        return $plan->site_template;

      } // end if;

    } // end if;

    return $site_template;

  } // end filter_template_id_on_new_site;

  /**
   * Adds some hooks to the coupon page after loading
   *
   * @since 1.9.0
   * @param string $page_id
   * @return void
   */
  public function coupon_page_hooks($page_id) {

    if (!in_array($page_id, array('wp-ultimo-coupons', 'wu-edit-coupon'))) return;

    add_action('admin_enqueue_scripts', array($this, 'add_coupon_notice_if_coupons_are_disabled'));

  } // end coupon_page_hooks;

  /**
   * Adds a message to alert super admins that coupon codes are disabled and they need to enable them before editing
   *
   * @since 1.9.0
   * @return void
   */
  public function add_coupon_notice_if_coupons_are_disabled() {

    if (WU_Settings::get_setting('enable_coupon_codes', 'url_and_field') !== 'disabled') return;
    
    wp_enqueue_script('jquery-blockui');

    $message = sprintf('<span class=\"dashicons dashicons-tickets\"></span><h2>%s</h2><p>%s</p><a href=\"%s\" class=\"button button-primary button-large\">%s</a>', __('Oh, no! Coupons Codes are disabled!', 'wp-ultimo'), __('You can go to your the WP Ultimo &rarr; Network Settings page and turn it on.', 'wp-ultimo'), network_admin_url('admin.php?page=wp-ultimo&wu-tab=network#enable_coupon_codes'), __('Go to your Settings Page &rarr;', 'wp-ultimo'));

    $inline_code = "
    (function($) {
      $('#wpbody-content').block({
        message: '$message',
        css: {
          padding: '30px',
          background: 'transparent',
          border: 'none',
          color: '#444',
          top: '150px',
        },
        overlayCSS: {
          background: '#F1F1F1',
          opacity: 0.75,
          cursor: 'initial',
        }
      });
    })(jQuery);";

    wp_add_inline_script('jquery-blockui', $inline_code, 'after');

  } // end add_coupon_notice_if_coupons_are_disabled;

  /**
   * Add the plan localization strings to the main script
   *
   * @since 1.7.3
   * @param array $variables
   * @return array
   */
  public function add_plan_localization_strings_to_main_script($variables) {

    $variables['delete_plan_title']   = __('Are you sure?', 'wp-ultimo');
    $variables['delete_plan_confirm'] = __('Yes, I\'m sure', 'wp-ultimo');
    $variables['delete_plan_cancel']  = __('Cancel', 'wp-ultimo');
    $variables['delete_plan_text']    = __('Are you sure you want to delete this plan? All the users of this plan will become become plan-less and have no limitations applied to their accounts. You can\'t undo this action.', 'wp-ultimo');

    return $variables;

  } // end add_plan_localization_strings_to_main_script;

  /**
   * Handle the activation status for the bulk
   *
   * @since 1.5.5
   * @return void
   */
  public function handle_get_activation_status() {

    if (!current_user_can('manage_network')) {
      
      wp_die(0);

    } // end if;

    wp_send_json( get_site_option('wu_bulk_activation_status', array(
      'running' => false,
      'status'  => true,
      'total'   => 0,
      'action'  => 'activate',
      'message' => __('No action was taken', 'wp-ultimo')
    )));

  } // end handle_get_activation_status;

  /**
   * Activates or deactivates plugins, called the appropriate hook
   *
   * @since 1.5.5
   * @param string  $plugin
   * @param integer $site_id
   * @param string  $action
   * @return void
   */
  function run_activate_plugin($plugin, $site_id, $action = 'activate') {

    $current = get_blog_option($site_id, 'active_plugins');

    $plugin  = plugin_basename(trim($plugin));

    if (is_array($current)) {
      
      if ($action == 'activate') {
        
        if (in_array($plugin, $current)) return;

        $current[] = $plugin;

        sort($current);

        switch_to_blog($site_id);

          do_action('activate_plugin', trim($plugin));        
          update_blog_option($site_id, 'active_plugins', $current);
          do_action('activate_' . trim($plugin));
          do_action('activated_plugin', trim($plugin));

        restore_current_blog();

      } else {

        if (!in_array($plugin, $current)) return;

        $current = array_filter($current, function($plugin_name) use ($plugin) {
          return $plugin != $plugin_name;
        });

        sort($current);

        switch_to_blog($site_id);

          do_action('deactivate_plugin', trim($plugin));

          update_blog_option($site_id, 'active_plugins', $current);

          do_action('deactivate_' . trim($plugin));
          do_action('deactivated_plugin', trim($plugin));

        restore_current_blog();

      } // end else;  

    } // end if;

  } // end run_activate_plugin;

  /**
   * Handle activate plugins for plan
   *
   * @since 1.5.5
   * @return void
   */
  public function handle_activate_plugins_for_plan() {

    check_ajax_referer('bulk-plugins');

    if (!current_user_can('manage_network')) wp_die(0);

    if (isset($_POST['activate-for-plan']) && $_POST['activate-for-plan'] && isset($_REQUEST['wu-select-plan-action'])) {

      delete_site_option('wu_bulk_activation_status');

      /**
       * Get plan, so we can later get sites
       */
      $plan   = wu_get_plan($_POST['activate-for-plan']);
      $sites  = array_filter( array_column($plan->get_sites(), 'site_id') );
      $action = $_REQUEST['wu-select-plan-action'];

      $plugin_count = isset($_POST['checked']) ? count($_POST['checked']) : 0;
      $site_count   = count($sites);
      $count        = 0;

      foreach($sites as $site_id) {

        array_map(function($plugin) use ($site_id, $action, $plugin_count, $site_count, &$count) {

          $this->run_activate_plugin($plugin, $site_id, $action);

          $count++;

          /**
           * Save Status
           */
          delete_site_option('wu_bulk_activation_status');

          if ($count < ($plugin_count * $site_count)) {

            $message = $action == 'activate' 
              ? __('Activating plugins for plan... <strong>(%s/%s) - %s&percnt;</strong> - Please, do not navigate away or refresh this page.', 'wp-ultimo')
              : __('Deactivating plugins for plan... <strong>(%s/%s) - %s&percnt;</strong> - Please, do not navigate away or refresh this page.', 'wp-ultimo');

            $message = sprintf($message, $count, $plugin_count * $site_count, number_format(($count / ($plugin_count * $site_count)) * 100));

          } else {

            $message = $action == 'activate' 
              ? __("%d plugin(s) <strong>activated</strong> across %d sites.", 'wp-ultimo')
              : __("%d plugin(s) <strong>deactivated</strong> across %d sites.", 'wp-ultimo');

            $message = sprintf($message, $plugin_count, $site_count);

          }
          
          update_site_option('wu_bulk_activation_status', array(
            'added'   => true,
            'running' => $count < ($plugin_count * $site_count),
            'action'  => $action,
            'message' => sprintf($message, $plugin_count, $site_count)
          ));

        }, $_POST['checked']);

      } // end foreach;

      wp_die($count < ($plugin_count * $site_count));

    } // end if;

    wp_die(0);

  } // end handle_activate_plugins_for_plan;

  /**
   * Adds the activation and deactivation select and buttons
   *
   * @since 1.5.5
   * @return void
   */
  public function render_activate_plugins_for_plan_form() {

    if (!is_network_admin()) return;

    WP_Ultimo()->render('forms/activate-plugins-for-plan');

  } // end render_activate_plugins_for_plan_form;

  /**
   * Change user role after change plan
   *
   * @since 1.5.4
   * @param WU_Subscription $subscription
   * @param WU_Plan         $new_plan
   * @param WU_Plan         $old_plan
   * @return void
   */
  function refresh_role_after_plan_change($subscription, $new_plan, $old_plan) {

    $default_role  = WU_Settings::get_setting('default_role');
    $previous_role = $old_plan->role ?: $default_role;
    $new_role      = $new_plan->role ?: $default_role;

    $user = wp_get_current_user();

    // Check if using latest role
    if (isset($user->caps[$previous_role]) && $previous_role != $new_role) {
      
      $user->set_role($new_role);

    } // end if;

  } // end refresh_role_after_plan_change;

  /**
   * Refreshs the user plan id meta after a change in plans
   *
   * @since 1.5.5
   * @param WU_Subscription $subscription
   * @param WU_Plan         $new_plan
   * @param WU_Plan         $old_plan
   * @return void
   */
  function refresh_plan_id_meta_for_user($subscription, $new_plan, $old_plan) {

    return update_user_meta($subscription->user_id, 'plan_id', $new_plan->get_id());

  } // end refresh_plan_id_meta_for_user
  
  /**
   * Get all plans based on certain parameters
   * 
   * @since  1.2.0 Has flag to determine that it only gets plans that are not hidden
   * @return array Array of WU_Plan objects
   */
  public static function get_plans($remove_hidden = false, $current_plan_id = false) {
    
    global $wpdb;

    $prefix = apply_filters('wu_core_get_table_prefix', $wpdb->base_prefix);

    $sql  = "SELECT DISTINCT ID FROM {$prefix}posts, {$prefix}postmeta";
    $sql .= " WHERE {$prefix}posts.ID = {$prefix}postmeta.post_id AND {$prefix}postmeta.meta_key = 'wpu_order' AND post_type = 'wpultimo_plan' && post_status = 'publish' ORDER BY CAST({$prefix}postmeta.meta_value as unsigned) ASC";
    
    $results = $wpdb->get_results($sql, 'ARRAY_A');

    foreach ($results as $index => &$result) {

      $result = new WU_Plan($result['ID']);

      if ($result->hidden && $remove_hidden) {

        // If this is the current, display it
        if (!$current_plan_id || $result->id != $current_plan_id) {

          unset($results[$index]);

        }

      }

    } // end foreach;

    return $results;
    
  }
  
  /**
   * Get the most popular plan of the platform
   * @return object WP_Plan instance
   */
  public static function get_most_popular_plan() {
    
    $most_popular_count = 0;

    $most_popular_plan = false;
    
    $plans = self::get_plans();

    foreach ($plans as $plan) {

      $plan_count = $plan->get_subscription_count();

      if ($plan_count >= $most_popular_count) {

        $most_popular_count = $plan_count;

        $most_popular_plan  = $plan;

      } // end if;
    
    } // end foreach;
    
    return $most_popular_plan;
    
  } // end get_most_popular_plan;
  
  /**
   * Reset the disk space for each blog of the network on 
   * @param object $plan       WU_Plan
   * @param string $disk_space New disk space passed by post
   */
  function refresh_disk_space($plan, $disk_space) {

    if ($disk_space == 0) {

      $disk_space = '';
    
    } // end if;
    
    $sites = $plan->get_sites();

    foreach($sites as $site) {

      update_blog_option($site->site_id, 'blog_upload_space', $disk_space);

    } // end foreach;
    
  } // end refresh_disk_space;

  /**
   * Resets the roles for sites under this plan
   *
   * @since 1.5.4
   * @param string $previous_role
   * @param string $new_role
   * @return void
   */
  function refresh_roles($plan, $previous_role, $new_role) {
    
    $sites = $plan->get_sites();

    foreach($sites as $site) {

      $site_owner = WU_Site_Owner::get_site_owner_id($site->site_id);

      if (!$site_owner) continue;

      switch_to_blog($site->site_id);

        $user = get_user_by('id', $site_owner);

        // Check if using latest role
        if (isset($user->caps[$previous_role])) {
          
          $user->set_role($new_role);

        } // end if;

      restore_current_blog();

    } // end foreach;
    
  } // end refresh_roles;
  
  /**
   * Adds our custom post type, so we can use it to save our plans
   */
  public function add_plan_cpt() {
    
    $labels = array(
      'name'               => _x('Plans', 'post type general name', 'wp-ultimo'),
      'singular_name'      => _x('Plan', 'post type singular name', 'wp-ultimo'),
      'menu_name'          => _x('Plans', 'admin menu', 'wp-ultimo'),
      'name_admin_bar'     => _x('Plan', 'add new on admin bar', 'wp-ultimo'),
      'add_new'            => _x('Add New', 'Plan', 'wp-ultimo'),
      'add_new_item'       => __('Add New Plan', 'wp-ultimo'),
      'new_item'           => __('New Plan', 'wp-ultimo'),
      'edit_item'          => __('Edit Plan', 'wp-ultimo'),
      'view_item'          => __('View Plan', 'wp-ultimo'),
      'all_items'          => __('All Plans', 'wp-ultimo'),
      'search_items'       => __('Search Plans', 'wp-ultimo'),
      'parent_item_colon'  => __('Parent Plans:', 'wp-ultimo'),
      'not_found'          => __('No Plans found.', 'wp-ultimo'),
      'not_found_in_trash' => __('No Plans found in Trash.', 'wp-ultimo')
   );

    $args = array(
      'labels'             => $labels,
      'description'        => __('Description.', 'wp-ultimo'),
      'public'             => false,
      'publicly_queryable' => false,
      'show_ui'            => false,
      'show_in_menu'       => false,
      'query_var'          => true,
      'rewrite'            => array('slug' => 'wpultimo_plan'),
      'capability'         => 'manage_network',
      'has_archive'        => true,
      'hierarchical'       => false,
      'can_export'         => true,
      'menu_position'      => null,
      'supports'           => array('title', 'custom-fields'),
    );

    register_post_type('wpultimo_plan', $args);
    
  } // end add_plan_cpt

  /**
   * Adds our custom post type for coupons
   */
  public function add_coupon_cpt() {
    
    $labels = array(
      'name'               => _x('Coupons', 'post type general name', 'wp-ultimo'),
      'singular_name'      => _x('Coupon', 'post type singular name', 'wp-ultimo'),
      'menu_name'          => _x('Coupons', 'admin menu', 'wp-ultimo'),
      'name_admin_bar'     => _x('Coupon', 'add new on admin bar', 'wp-ultimo'),
      'add_new'            => _x('Add New', 'Coupon', 'wp-ultimo'),
      'add_new_item'       => __('Add New Coupon', 'wp-ultimo'),
      'new_item'           => __('New Coupon', 'wp-ultimo'),
      'edit_item'          => __('Edit Coupon', 'wp-ultimo'),
      'view_item'          => __('View Coupon', 'wp-ultimo'),
      'all_items'          => __('All Coupons', 'wp-ultimo'),
      'search_items'       => __('Search Coupons', 'wp-ultimo'),
      'parent_item_colon'  => __('Parent Coupons:', 'wp-ultimo'),
      'not_found'          => __('No Coupons found.', 'wp-ultimo'),
      'not_found_in_trash' => __('No Coupons found in Trash.', 'wp-ultimo')
   );

    $args = array(
      'labels'             => $labels,
      'description'        => __('Description.', 'wp-ultimo'),
      'public'             => false,
      'publicly_queryable' => false,
      'show_ui'            => false,
      'show_in_menu'       => false,
      'query_var'          => true,
      'rewrite'            => array('slug' => 'wpultimo_coupon'),
      'capability'         => 'manage_network',
      'has_archive'        => true,
      'hierarchical'       => false,
      'can_export'         => true,
      'menu_position'      => null,
      'supports'           => array('title', 'custom-fields'),
    );

    register_post_type('wpultimo_coupon', $args);
    
  } // end add_cupom_cpt

  /**
   * Handles the saving and the edittion of a Plan
   */
  public function save_plan() {

    if (!current_user_can('manage_network')) {

      wp_die(__('You do not have the necessary permissions to perform this action.', 'wp-ultimo'));

    } // end if;

    // Get the plan
    $id = isset($_POST['plan_id']) ? (int) $_POST['plan_id'] : 0;
  
    // Get our Plan
    $plan = new WU_Plan($id);
    
    // Check if Disk Size changed, so we can set it to the blogs
    $disk_space_changed = isset($plan->quotas['upload']) && isset($_POST['quotas']['upload']) && $plan->quotas['upload'] != $_POST['quotas']['upload'];

    // Role changed
    $previous_role = $plan->role;
    $role_changed  = isset($_POST['role']) && $_POST['role'] != $plan->role;

    // Error message
    $messages = array();
    
    /**
     * Validations about price, only if free
     */
    if (!isset($_POST['free']) && !isset($_POST['is_contact_us'])) {

      $billing_periods = array(
        '1'  => __('monthly', 'wp-ultimo'),
        '3'  => __('quaterly', 'wp-ultimo'),
        '12' => __('yearly', 'wp-ultimo'),
      );

      foreach($billing_periods as $period => $period_label) {

        $_POST["price_$period"] = WU_Util::to_float($_POST["price_$period"]);

        $required_period = WU_Settings::get_setting("enable_price_$period", true);

        if ( 
          ( $required_period && ( !isset($_POST["price_$period"]) || !$_POST["price_$period"] ) ) 
          || ( isset($_POST["price_$period"]) && !is_numeric($_POST["price_$period"]) )
        ) {

          $messages[] = sprintf(__('You must define a valid %s price.', 'wp-ultimo'), $period_label);

        } // end if;

      } // end foreach;

    } // end if;

    /**
     * Validates the Contact Us plans
     */
    if ( isset($_POST['is_contact_us']) ) {

      if ( !isset($_POST['contact_us_link']) || empty($_POST['contact_us_link']) ) {

        $messages[] = __('You must enter a valid contact us link for this plan.', 'wp-ultimo');

      } // end if;

    } // end if;

    // Return errors
    if (!empty($messages)) {
      WP_Ultimo()->add_message(implode('<br>', $messages), 'error', true);
      return;
    }

    // Load Info
    $plan->title        = sanitize_text_field($_POST['title']);
    $plan->description  = sanitize_text_field($_POST['description']);
    $plan->price_1      = sanitize_text_field($_POST['price_1']);

    if (isset($_POST['price_3'])) {

      $plan->price_3 = sanitize_text_field($_POST['price_3']);

    } // end if;

    if (isset($_POST['price_12'])) {

      $plan->price_12 = sanitize_text_field($_POST['price_12']);

    } // end if;
    
    $_POST['setup_fee'] = WU_Util::to_float($_POST['setup_fee']);

    $plan->setup_fee    = sanitize_text_field($_POST['setup_fee']); // @since 1.7.0
    
    $plan->trial        = sanitize_text_field($_POST['trial']);

    // Add Feature List
    $plan->feature_list = wp_filter_kses($_POST['feature_list']);
    
    // Booleans
    $plan->top_deal        = isset($_POST['top_deal']);
    $plan->free            = isset($_POST['free']);
    $plan->custom_domain   = isset($_POST['custom_domain']);
    $plan->hidden          = isset($_POST['hidden']); // @since 1.2.0

    $plan->is_contact_us    = isset($_POST['is_contact_us']);                  // @since 1.9.0
    $plan->contact_us_label = sanitize_text_field($_POST['contact_us_label']); // @since 1.9.0
    $plan->contact_us_link  = sanitize_text_field($_POST['contact_us_link']);  // @since 1.9.0

    $plan->site_template   = isset($_POST['site_template']) ? $_POST['site_template'] : ''; // @since 1.5.4
    $plan->role            = isset($_POST['role']) ? $_POST['role'] : '';                   // @since 1.5.4
    
    // Allowed Plugins and Themes && Quotas
    $plan->allowed_plugins = isset($_POST['allowed_plugins']) ? $_POST['allowed_plugins'] : array();
    $plan->allowed_themes  = isset($_POST['allowed_themes']) ? $_POST['allowed_themes'] : array();
    $plan->quotas          = isset($_POST['quotas']) ? $_POST['quotas'] : array();

    /**
     * @since  1.1.3 Let users choose which post types to display on the pt
     */
    $plan->display_post_types = isset($_POST['display_post_types']) ? $_POST['display_post_types'] : array();
    
    /**
     * @since  1.5.4 Disabled some post types
     */
    $plan->disabled_post_types = isset($_POST['disabled_post_types']) ? $_POST['disabled_post_types'] : array();
    
    /**
     * @since  1.5.4 Allow custom template section per plan
     */
    $plan->override_templates = isset($_POST['override_templates']);
    $plan->templates = isset($_POST['templates']) ? $_POST['templates'] : array();
    
    $plan->unlimited_extra_users = isset($_POST['unlimited_extra_users']);

    /**
     * @since 1.7.0 Advanced options and copy media
     */
    $plan->advanced_options = isset($_POST['advanced_options']);
    
    if ($plan->advanced_options) {

      $plan->copy_media = isset($_POST['copy_media']) ? 'yes' : 'no';

    } // end if;

    /**
     * Set the plan slug
     * @since 1.9.0
     */
    if (isset($_POST['slug'])) {

      $plan->slug = $_POST['slug'];

    } // end if;

    $plan_id = $plan->save();
    
    // If disk space changed, refresh it
    if ($disk_space_changed) {
      $this->refresh_disk_space($plan, $_POST['quotas']['upload']);
    }

    // Refresh user roles on case of a role change
    if ($role_changed) {
      $this->refresh_roles($plan, $previous_role, $_POST['role']);
    }

    do_action('wu_save_plan', $plan);
    
    // Redirect to the edit page
    wp_redirect(network_admin_url('admin.php?page=wu-edit-plan&updated=1&plan_id=').$plan_id);

    exit;
    
  } // end save_plan;

  /**
   * Handles the saving of a new plan order
   */
  public function change_plan_order() {

    if (!current_user_can('manage_network') || !isset($_POST['plan_order'])) {

      die('0');

    } // end if;
    
    foreach ($_POST['plan_order'] as $plan_id => $new_order) {

      /**
       * Changed from instantiating EVERY plan to just updating the right meta.
       */
      update_post_meta($plan_id, 'wpu_order', $new_order);

    } // end foreach;
    
    die('1');

  } // end change_plan_order;

  /**
   * Handles the saving of a cupon
   */
  public function save_coupon() {

    if (!current_user_can('manage_network')) {

      wp_die(__('You do not have the necessary permissions to perform this action.', 'wp-ultimo'));

    } // end if;

    // Get the plan
    $id = isset($_POST['coupon_id']) ? (int) $_POST['coupon_id'] : 0;

    // Error message
    $messages = array();

    if ($_POST['expiring_date']) {

      try {
        $expiring_date = new DateTime($_POST['expiring_date']);
      }

      catch (Exception $e) {
        $messages[] = __('You must enter a valid expiring date.', 'wp-ultimo');
      }

    } else {
      $expiring_date = false;
    }

    /**
     * Check Title
     */
    if (empty($_POST['title']))
      $messages[] = __('You must enter a valid coupon code.', 'wp-ultimo');

    $coupon_exists = post_exists(sanitize_text_field($_POST['title']));

    if ($coupon_exists && $coupon_exists != $id) {
      $messages[] = __('The code for the coupon must be unique.', 'wp-ultimo');
    }

    // Get our Plan
    $coupon = new WU_Coupon($id);

    /**
     * Check if date is later than today
     */
    if ($expiring_date) {

      $now = new DateTime();

      if ($now > $expiring_date)
        $messages[] = __('The coupon expiring date should be in the future.', 'wp-ultimo');

      // Load into our variable
      $coupon->expiring_date = $expiring_date->format('Y-m-d H:i:s');

    } // end if;
    
    /**
     * Validations about price
     */
    if (!isset($_POST['value']) || !is_numeric($_POST['value']))
      $messages[] = __('You must define a valid numeric value.', 'wp-ultimo');

    /**
     * Validations about value of setup if exists
     */
    if (isset($_POST['applies_to_setup_fee']) && !isset($_POST['setup_fee_discount_value']) || !empty($_POST['setup_fee_discount_value']) && !is_numeric($_POST['setup_fee_discount_value']))
      $messages[] = __('You must define a valid numeric setup fee value.', 'wp-ultimo');

    // Return errors
    if (!empty($messages)) {
      WP_Ultimo()->add_message(implode('<br>', $messages), 'error', true);
      return;
    }
    
    // Load Info
    $coupon->title        = sanitize_text_field($_POST['title']);
    $coupon->description  = sanitize_text_field($_POST['description']);
      
    $coupon->allowed_uses  = (int) $_POST['allowed_uses'];

    $coupon->type   = $_POST['type'];
    $coupon->cycles = $_POST['cycles'];

    $_POST['value'] = WU_Util::to_float($_POST['value']);
    
    $coupon->value  = sanitize_text_field($_POST['value']);

    $coupon->applies_to_setup_fee = isset($_POST['applies_to_setup_fee']);

    if (isset($_POST['applies_to_setup_fee'])){

      $coupon->setup_fee_discount_type        = $_POST['setup_fee_discount_type'];
      $_POST['setup_fee_discount_value']      = WU_Util::to_float($_POST['setup_fee_discount_value']);
      $coupon->setup_fee_discount_value       = sanitize_text_field($_POST['setup_fee_discount_value']);

    } // end if;

    /**
     * @since 1.5.5
     */
    $coupon->allowed_plans = isset($_POST['allowed_plans']) ? $_POST['allowed_plans'] : array();
    $coupon->allowed_freqs = isset($_POST['allowed_freqs']) ? $_POST['allowed_freqs'] : array();
    
    $coupon_id = $coupon->save();

    /**
     * @since  1.2.0 Hook to allow the inclusion saving extra fields
     */
    do_action('wp_ultimo_coupon_after_save', $coupon);
    
    // Redirect to the edit page
    wp_redirect(network_admin_url('admin.php?page=wu-edit-coupon&updated=1&coupon_id=').$coupon_id);

    exit;
    
  } // end save_coupon;

} // end class WP_Plans;

// Run our Class
WU_Plans::get_instance();

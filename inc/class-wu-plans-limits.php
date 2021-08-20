<?php
/**
 * Plan Limits
 *
 * Applies Plan Limits to each user account and site
 *
 * @author      Arindo Duque
 * @category    Admin
 * @package     WP_Ultimo/Plans
 * @version     0.0.1
*/

if (!defined('ABSPATH')) {
  exit;
}

class WU_Plans_Limits {
  
  /**
   * Construct method
   * @private
   */
  public function __construct() {
    
    // Initialize
    add_action('init', array($this, 'init'));

    // Block Frontend Access
    // @since 1.0.4
    add_action('wp', array($this, 'block_frontend_access'), 101);
    add_action('wp', array($this, 'limit_sites_on_the_frontend'), 102); // @since 1.7.3
    add_action('init', array($this, 'display_alert_messages'));
    
    // @since 1.6.0
    add_action('wp', array($this, 'limit_visits'), 101);
    add_action('wp_ajax_nopriv_wu_count_visits', array($this, 'count_visits_ajax'));
    add_action('admin_init', array($this, 'reset_visit_counter'));

    // @since 1.7.0
    add_action('wu_subscription_change_plan', array($this, 'move_posts_on_downgrade'), 9, 3);
    add_action('wu_change_plan_alerts', array($this, 'print_move_posts_alert_on_downgrade'));
    
  } // end construct;
  
  /**
   * Gets the user plan and saves it for future reference
   * also adds the hooks for each limitation
   */
  function init() {

    // Don't apply to superadmins
    if (current_user_can('manage_network')) return;
    
    // Set the user for later use
    $this->user_id = get_current_user_id();
    
    // Get the plan of the site
    $this->plan = wu_get_current_site()->get_plan();
    
    /**
     * Allow plugin developers to short-circuit the limitations.
     * 
     * You can use this filter to run arbitrary code before any of the limits get initiated.
     * If you filter returns any truthy value, the process will move on, if it returns any falsy value,
     * the code will return and none of the hooks below will run.
     * 
     * @since 1.7.0
     * @param WU_Plan|false Current plan object
     * @param integer User ID
     */
    if ( ! apply_filters('wu_apply_plan_limits', $this->plan, $this->user_id) ) return;

    /**
     * Apply the limits
     */
    
    // Trial
    add_action('admin_init', array($this, 'limit_trial'), 1);
    add_action('admin_init', array($this, 'limit_sites_on_the_backend'), 200);
    
    // Display only available themes
    add_filter('wp_prepare_themes_for_js', array($this, 'limit_themes'));

    /**
     * Added to prevent issues with Jetpack connection.
     * @since 1.9.14
     */
    add_filter('site_allowed_themes', array($this, 'limit_themes'));
    add_filter('allowed_themes', array($this, 'limit_themes'));
    
    // Display only the available plugins
    add_filter('all_plugins', array($this, 'limit_plugins'));
    
    // Limit adding new posts and users depending on the quota
    add_action('load-post-new.php', array($this, 'limit_posts'));
    add_action('load-user-new.php', array($this, 'limit_users'));
    add_action('load-dashboard_page_wu-new-site', array($this, 'limit_sites')); // @since 1.2.0

    // Limit Media
    add_filter('wp_handle_upload', array($this, 'limit_media'));
    
    // Remove Tabs
    add_filter('media_upload_tabs', array($this, 'limit_tabs'));
    
    // Prevent users from trashing posts and restoring them later to bypass the limitation
    add_action('current_screen', array($this, 'limit_restoring'), 10); // @since 1.6.2

    // Check if the user is trying to publish drafts
    add_filter('wp_insert_post_data', array($this, 'limit_draft_publishing'), 10, 2);

    // Checks if in grace activation period
    add_filter('wu_display_payment_integration_buttons', array($this, 'hide_integration_buttons_on_activation_permission'), 10, 2);

  } // end init;

  /**
   * Block access to the frontend of sites in cases where the subscription is not active anymore
   */
  public function limit_sites_on_the_frontend() {

    $dont_block_site_frontend = WU_Settings::get_setting('block_sites_on_downgrade') == 'none' || WU_Settings::get_setting('block_sites_on_downgrade') == 'block-backend';

    // If admin
    if (current_user_can('manage_network') || is_main_site() || is_admin() || $dont_block_site_frontend || WU_Util::is_login_page()) return;

    // "This site has been archived or suspended."
    if (wu_get_current_site()->subscription && !in_array(get_current_blog_id(), wu_get_current_site()->subscription->get_allowed_sites())) {

      $message = apply_filters('wu_block_frontend_message', __('This site is not available at this time.', 'wp-ultimo'), wu_get_current_site()->subscription);
      $message .= sprintf(' <a href="%s">%s</a>', wp_login_url(admin_url('admin.php?page=wu-my-account')), __('Are you the site owner?', 'wp-ultimo'));

      wp_die( $message, __('This site is not available at this time.', 'wp-ultimo'), array(
        'back_link' => false
      ));

    } // end if;

  } // end limit_sites_on_the_frontend;

  /**
   * Move a lists of post ids to the trash
   * 
   * @since 1.7.0
   * @param integer $site_id
   * @param array   $posts
   * @return void
   */
  public function move_site_posts_to_trash($site_id, $posts) {

    if (defined('EMPTY_TRASH_DAYS') && EMPTY_TRASH_DAYS == 0) return;

    switch_to_blog( $site_id );

      foreach($posts as $post_id) {

        wp_trash_post($post_id);

      } // end foreach;

    restore_current_blog();

  } // end move_site_posts_to_trash;

  /**
   * Move a lists of post ids to a different status, defaults to draft
   * 
   * @since 1.7.0
   * @param integer $site_id
   * @param array   $posts
   * @param string  $status = 'draft'
   * @return void
   */
  public function move_site_posts_to_status($site_id, $posts, $status = 'draft') {

    switch_to_blog( $site_id );

      foreach($posts as $post_id) {

        $post = array( 'ID' => $post_id, 'post_status' => $status );
        
        wp_update_post($post);

      } // end foreach;

    restore_current_blog();

  } // end move_site_posts_to_status;

  /**
   * Returns a list of the post ids above the quota for a given site
   * 
   * @since 1.7.0
   * @param integer $site_id
   * @param string  $post_type
   * @param integer $quota
   * @return array
   */
  public function get_posts_above_quota($site_id, $post_type, $quota) {

    switch_to_blog( $site_id );

      $exclude = array(get_option('page_on_front', 0), get_option('page_for_posts', 0));

      $counts = wp_count_posts($post_type);

      $count_above_quota = isset($counts->publish) ? $counts->publish - $quota : 0;

      if ($count_above_quota <= 0) return array();

      $query = get_posts(array(
        'fields'         => 'ids',
        'post_status'    => 'publish',
        'no_found_rows'  => true,
        'posts_per_page' => $count_above_quota,
        'post_type'      => $post_type,
        'exclude'        => $exclude,
      ));

    restore_current_blog();
    
    return $query;

  } // end get_posts_above_quota;

  /**
   * Refactor the quota number for later checking if this post type is unlimited OR has been disabled for the new plan
   *
   * @since 1.7.2
   * @param integer $post_type_quota
   * @param string  $post_type
   * @param WU_Plan $new_plan
   * @return integer
   */
  public function change_quota_number_to_handle_unlimited($post_type_quota, $post_type, $new_plan) {

    if ($new_plan->is_post_type_disabled($post_type)) return 0;

    if (!$post_type_quota) return 999999999;

    return $post_type_quota;

  } // end change_quota_number_to_handle_unlimited;

  /**
   * When a user downgrades, we move all the posts above the quota to the trash or to another status, like draft
   *
   * @since 1.7.0
   * @param WU_Subscription $subscription
   * @param WU_Plan $new_plan
   * @param WU_Plan $old_plan
   * @return void
   */
  public function move_posts_on_downgrade($subscription, $new_plan, $old_plan) {

    $action = WU_Settings::get_setting('move_posts_on_downgrade', 'none');

    if ($action == 'none') return; // Check if we do nothing;

    $quotas = $new_plan->get_post_type_quotas();

    foreach($quotas as $post_type => $post_type_quota) {

      /**
       * @since 1.7.2 Check the quota for unlimited
       */
      $post_type_quota = $this->change_quota_number_to_handle_unlimited($post_type_quota, $post_type, $new_plan);

      $sites = $subscription->get_sites();

      foreach ($sites as $site) {

        $posts = $this->get_posts_above_quota($site->site_id, $post_type, $post_type_quota);

        if ($action == 'trash') {

          $this->move_site_posts_to_trash($site->site_id, $posts);

        } else if ($action == 'draft') {

          $this->move_site_posts_to_status($site->site_id, $posts, 'draft');

        } // end if;

      } // end foreach;

    } // end foreach;

  } // end move_posts_on_downgrade;

  /**
   * Prints the alert on plan upgrade/downgrade for move posts
   *
   * @since 1.7.0
   * @param WU_Plan $new_plan
   * @return void
   */
  public function print_move_posts_alert_on_downgrade($new_plan) {

    $action = WU_Settings::get_setting('move_posts_on_downgrade', 'none');

    if ($action == 'none') return; // Check if we do nothing;

    $message = '';

    if ($action == 'trash') {

      $message = __('If you choose to proceed, all the posts, pages and etc above the new quotas will be moved to the trash (you can restore them later, if you free your quota limit).', 'wp-ultimo');

    } else if ($action == 'draft') {

      $message = __('If you choose to proceed, all the posts, pages and etc above the new quotas will be marked as drafts (you can re-publish them later, if you free your post limit).', 'wp-ultimo'); 

    } // end if;

    $message = apply_filters('wu_move_posts_alert_on_downgrade', $message, $action, $new_plan);

    echo $message ? "<p>$message</p>" : '';

  } // end print_move_posts_alert_on_downgrade;

  /**
   * Limit restoring posts to avoid users bypassing the limitations
   *
   * @since 1.6.2
   * @return void
   */
  public function limit_restoring() {

    if (isset($_REQUEST['action']) && $_REQUEST['action'] == 'untrash') {
      
      $this->limit_posts();

    } // end if;

  } // end limit_restoring;

  /**
   * Block access to the frontend of sites in cases where the subscription is not active anymore
   */
  public function block_frontend_access() {

    // If admin
    if (current_user_can('manage_network') || is_main_site() || is_admin() || !WU_Settings::get_setting('block_frontend') || WU_Util::is_login_page()) return;

    // "This site has been archived or suspended."
    if (wu_get_current_site()->subscription && (!wu_get_current_site()->subscription->is_active() && !wu_get_current_site()->subscription->is_on_hold())) {

      /**
       * Check grace period
       * @since 1.7.0
       */
      if ($grace_period = WU_Settings::get_setting('block_frontend_grace_period', 0)) {

        $active_until = wu_get_current_site()->subscription->get_date('active_until', 'Y-m-d H:m:s');

        if (wu_get_days_ago($active_until) <= $grace_period) {

          return;

        } // end if;

      } // end if;

      $message = apply_filters('wu_block_frontend_message', __('This site is not available at this time.', 'wp-ultimo'), wu_get_current_site()->subscription);
      $message .= sprintf(' <a href="%s">%s</a>', wp_login_url(admin_url('admin.php?page=wu-my-account')), __('Are you the site owner?', 'wp-ultimo'));

      wp_die( $message, __('This site is not available at this time.', 'wp-ultimo'), array(
        'back_link' => false
      ));

    }

  } // end block_frontend_access;

  /**
   * Display message letting the user know that his subscription is inactive
   */
  public function display_alert_messages() {

    if (current_user_can('manage_network')) return;

    // Switch the message depending if we block the frontend access or not
    $message = sprintf(__('Your subscription is currently inactive. Go to your %sAccount Page%s to check your payment options.', 'wp-ultimo'), '<a href="'. admin_url('admin.php?page=wu-my-account') .'">', '</a>');

    $message = WU_Settings::get_setting('block_frontend') ? $message .' '. __('Due to that reason, visitors are currently unable to access your sites.', 'wp-ultimo') : $message;

    if (wu_get_current_site()->subscription && !wu_get_current_site()->subscription->is_active() && !wu_get_current_site()->subscription->is_on_hold()) {

      if (wu_get_current_site()->subscription->has_activation_permission()) return;

      WP_Ultimo()->add_message($message, 'warning');

    } // end if;

  } // end display_alert_messages;

  /**
   * Decides if we should reset the visits count
   *
   * @param WU_Site $site
   * @return void
   */
  public function should_reset_site_count($site) {

    $last_reseted = $site->get_meta('visits_reseted_at');

    $now = new DateTime( WU_Transactions::get_current_time('mysql') );
    
    $last_reseted = $last_reseted ? new DateTime($last_reseted) : new DateTime( $site->site->registered );

    return $now->diff($last_reseted)->days > apply_filters('wu_reset_visits_count_days', 30);

  } // end increase_visits_count;

  /**
   * Allow superadmins to reset a given site cache
   *
   * @since 1.7.0
   * @return void
   */
  public function reset_visit_counter() {

    if (isset($_GET['action']) && $_GET['action'] == 'wu_reset_visit_counter' && current_user_can('manage_network')) {
      
      $this->flush_known_caches();

      $site = wu_get_current_site();

      $site->reset_site_visit_count();

      $redirect_url = isset($_REQUEST['redirect']) ? $_REQUEST['redirect'] : admin_url();

      wp_redirect($redirect_url);
      
      exit;

    } // end if;

  } // end reset_visit_counter;

  /**
   * Count visits via ajax to handle caching plugins
   *
   * @since 1.7.0
   * @return void
   */
  public function count_visits_ajax() {

    /**
     * Check if its enabled
     * @since 1.7.3
     */
    if (!WU_Settings::get_setting('enable_visits_limiting') || !wp_verify_nonce($_GET['code'], 'wu-visit-counter')) {

      die('0');

    } // end if;

    $site = wu_get_current_site();

    /**
     * Check if we need to reset the count value
     */
    if ($this->should_reset_site_count($site) && $site) {
      
      $site->reset_site_visit_count();

      die('1');
      
    } // end if;

    /**
     * Get the plan and check the limits
     */
    $plan = $site->get_plan();

    if (!$site || !$plan) {

      die('0');

    } // end if;

    $count = (int) $site->get_meta('visits_count');

    /**
     * Checks the limit
     */
    if ($plan->get_quota('visits') != 0 && $plan->get_quota('visits') <= $count) {

      $this->flush_known_caches();

      echo 'flushing caches';
      
      die('2');

    } // end if;

    /**
     * Save new count
     */
    $count++;
    
    $site->set_meta('visits_count', $count);

    /**
     * Send emails
     * @since 1.7.0
     */
    if ($plan->get_quota('visits') != 0) {

      $this->send_visit_alert_emails($site, $count, $plan->get_quota('visits'));

    } // end if;

    die('1');

  } // end count_visits_ajax;
  
  /**
   * Flush known caching plugins, offers hooks to add more plugins in the future
   *
   * @since 1.7.0
   * @return void
   */
  public function flush_known_caches() {

    if (function_exists('wp_cache_clear_cache')) {

      global $file_prefix, $supercachedir;

      if (empty($supercachedir) && function_exists('get_supercache_dir')) {
        
        $supercachedir = get_supercache_dir();

      } // end if;

      wp_cache_clear_cache($file_prefix); // WP Super Cache Flush

    } // end if;

    if (function_exists('w3tc_pgcache_flush')) {

      w3tc_pgcache_flush(); // W3TC Cache Flushing

    } // end if;

    global $wp_fastest_cache;

    if (method_exists('WpFastestCache', 'deleteCache') && !empty($wp_fastest_cache)) {

      $wp_fastest_cache->deleteCache();

    } // end if;

    $this->flush_wpengine_cache(); // WPEngine Cache Flushing

    /**
     * Hook to additional cleaning
     */
    do_action('wu_flush_known_caches');

  } // end flush_known_caches;

  /**
   * Enqueue the javascript file responsible to send the request counting visits
   *
   * @param WU_Site $site
   * @return void
   */
  public function enqueue_visit_counter_script($site) {

    $subscription = $site ? $site->get_subscription() : false;

    if (!$subscription || current_user_can('manage_network') || is_main_site() || is_admin() || WU_Util::is_login_page() || is_user_logged_in()) return;

    $suffix = WP_Ultimo()->min;

    wp_register_script('wu-visit-counter', WP_Ultimo()->get_asset("wu-visit-counter$suffix.js", 'js'), array('jquery'));

    wp_localize_script('wu-visit-counter', 'wu_visit_counter', array(
      'ajaxurl' => admin_url('admin-ajax.php'),
      'code'    => wp_create_nonce('wu-visit-counter'),
    ));

    wp_enqueue_script('wu-visit-counter');

  } // end should_enqueue_visit_counter_script;

  public function visits_limit_approaching_email($site, $count, $limit) {

    $user = $site->get_owner();

    if (!$user) return;

    WU_Mail()->send_template('visits_limit_approaching', $user->user_email, array(
      'visits_count'     => $count,
      'visits_limit'     => $limit,
      'user_name'        => $user->display_name,
      'user_site_name'   => $site->name,
      'reset_date'       => $site->get_visit_count_reset_date(),
      'admin_panel_link' => get_admin_url($site->ID, ''),
    ));

    return $site->set_meta('visits_limit_approaching_alert_sent', true);

  } // end visits_limit_approaching_email;

  public function visits_limit_reached_email($site, $count, $limit) {

    $user = $site->get_owner();

    if (!$user) return;

    WU_Mail()->send_template('visits_limit_reached', $user->user_email, array(
      'visits_count'     => $count,
      'visits_limit'     => $limit,
      'user_name'        => $user->display_name,
      'user_site_name'   => $site->name,
      'reset_date'       => $site->get_visit_count_reset_date(),
      'admin_panel_link' => get_admin_url($site->ID, ''),
    ));

    return $site->set_meta('visits_limit_reached_alert_sent', true);

  } // end visits_limit_reached_email;

  public function send_visit_alert_emails($site, $count, $limit) {

    if (($count / $limit) >= 0.8 && !$site->get_meta('visits_limit_approaching_alert_sent')) {

      $this->visits_limit_approaching_email($site, $count, $limit);

    } // end if;

    if ($count >= $limit && !$site->get_meta('visits_limit_reached_alert_sent')) {

      $this->visits_limit_reached_email($site, $count, $limit);

    } // end if;

  } // end send_visit_alert_emails;

  /**
   * Block the frontend for visits limit
   * @since 1.6.0
   */
  public function limit_visits() {

    /**
     * Check if its enabled
     * @since 1.7.3
     */
    if (!WU_Settings::get_setting('enable_visits_limiting')) return;

    $site = wu_get_current_site();

    /**
     * Check if we need to reset the count value
     */
    if ($this->should_reset_site_count($site) && $site) {
      
      $site->reset_site_visit_count();
      return;
      
    } // end if;
    
    // If admin
    if (current_user_can('manage_network') || is_main_site() || is_admin() || WU_Util::is_login_page() || is_user_logged_in()) return;
    
    /**
     * Get the plan and check the limits
     */
    $plan = $site->get_plan();

    if (!$site || !$plan) return;

    $count = (int) $site->get_meta('visits_count');

    /**
     * Send emails
     * @since 1.7.0
     */
    if ($plan->get_quota('visits') != 0) {

      $this->send_visit_alert_emails($site, $count, $plan->get_quota('visits'));

    } // end if;

    /**
     * Checks the limit
     */
    if ($plan->get_quota('visits') != 0 && $plan->get_quota('visits') <= $count) {

      $message = apply_filters('wu_limit_visits_message', __('This site is not available at this time.', 'wp-ultimo'), $site, $site->get_subscription() );
      $message .= sprintf(' <a href="%s">%s</a>', wp_login_url(admin_url('admin.php?page=wu-my-account')), __('Are you the site owner?', 'wp-ultimo'));

      wp_die( $message, __('This site is not available at this time.', 'wp-ultimo'), array(
        'back_link' => false
      ));

    } else {

      $this->enqueue_visit_counter_script($site);

    } // end if;

  } // end limit_visits;

  /**
   * Get payment integration title
   *
   * @since 1.7.0
   * @return string
   */
  public function get_payment_integration_title() {

    $title = WU_Settings::get_setting('payment_integration_title');

    return apply_filters('wu_signup_payment_step_title', $title ?: __('Payment Integration Needed', 'wp-ultimo'));

  } // end get_payment_integration_title;

  /**
   * Get payment integration description
   *
   * @since 1.7.0
   * @return string
   */
  public function get_payment_integration_description() {

    $desc = WU_Settings::get_setting('payment_integration_desc');

    return apply_filters('wu_signup_payment_step_text', $desc ?: __('We now need to complete your payment settings, setting up a payment subscription. Use the buttons below to add a integration.', 'wp-ultimo'));

  } // end get_payment_integration_description;

  /**
   * Limit sites above the quota limit on the backend as well
   *
   * @since 1.7.3
   * @return void
   */
  public function limit_sites_on_the_backend() {

    $block_site_backend = WU_Settings::get_setting('block_sites_on_downgrade') == 'block-backend' || WU_Settings::get_setting('block_sites_on_downgrade') == 'block-both';

    if (! $block_site_backend || current_user_can('manage_network')) return;

    $subscription = wu_get_current_subscription();

    $site_id = get_current_blog_id();

    if ($subscription && ! in_array($site_id, $subscription->get_allowed_sites())) {

      // $is_site_allowed = $subscription
      $my_sites_link = get_admin_url( get_user_meta($this->user_id, 'primary_blog', true), 'my-sites.php');

      wp_die(sprintf(__('You need to upgrade your account to a higher tier to regain access to this site. <a href="%s">Upgrade your account</a>.', 'wp-ultimo'), wu_get_active_gateway()->get_url('change-plan')) . sprintf(__(' <a href="%s">%s</a>', 'wp-ultimo'), $my_sites_link, __('See your sites', 'wp-ultimo')), __('Site Unavailable', 'wp-ultimo'), array('back_link' => true));

    } // end if;

  } // end limit_sites_on_the_backend;

  /**
   * Handles the WC Setup redirect, to prevent it from messing up with our payment screens
   *
   * @since 1.9.0
   * @return boolean
   */
  public function handle_wc_redirect() {

    set_transient( '_wc_activation_redirect', 1 );

    return true;

  } // end handle_wc_redirect,

  /**
   * Display the error message if the trial was 
   */
  public function limit_trial() {

    global $pagenow;

    if (wp_doing_ajax() || wp_doing_cron() || $pagenow == 'ms-delete-site.php') return;

    if ($pagenow == 'admin.php' && isset($_GET['page']) && $_GET['page'] === 'wu-remove-account') return;

    $subscription = wu_get_current_site()->get_subscription();
    
    // Return if there is no trial ending
    if ($subscription->get_trial() || $subscription->is_free() || $subscription->is_on_hold()) return;

    // Returns if permission to access is present
    if (wu_get_current_site()->subscription->has_activation_permission()) return;

    /**
     * Prevent the WooCommerce redirect in here
     * @since 1.9.0
     */
    add_filter('woocommerce_prevent_automatic_wizard_redirect', array($this, 'handle_wc_redirect'));
    
    /**
     * Exclude Account page from locking down
     * @since 1.9.0
     */
    if (isset($_GET['page']) && $_GET['page'] == 'wu-my-account') return;

    // Set the redirect URL
    $redirect_url = admin_url('admin.php?page=wu-my-account');

    // Check if there is a trial or the trial is over
    if (!$subscription->is_active() && !$subscription->integration_status) {

      wp_enqueue_script('jquery');

      $message = $this->get_payment_integration_description();

      $buttons = '';
          
      /**
       * @since  1.1.0 displays all possible gateways
       */
      foreach (wu_get_gateways() as $gateway) : $gateway = $gateway['gateway'];

        $active_gateways = is_array(WU_Settings::get_setting('active_gateway')) ? WU_Settings::get_setting('active_gateway') : array();

        if (!in_array($gateway->id, array_keys($active_gateways))) continue;

        $content = $gateway->get_button_label();

        $class = !$subscription->integration_status ? 'button-primary' : '';
        
        ob_start(); ?>
        
      <div class="submit wu-signup-payment-description"><a style="width: 100%; text-align: center; margin-top: 10px;" class="button <?php echo $class; ?> button-streched" href="<?php echo $gateway->get_url('process-integration'); ?>">
        <?php echo $content; ?>
      </a></div>
      <?php 
      
      $button = ob_get_clean();
      
      $buttons .= apply_filters("wu_gateway_integration_button_$gateway->id", $button, $content); 
    
      endforeach;

      $title = $this->get_payment_integration_title();

    } else return;

    /**
     * Add a link to the accounts page
     * @since 1.7.0
     */
    $accounts_link = sprintf("<a class='wu-accounts-link' href='%s'>%s</a>", admin_url('admin.php?page=wu-my-account'), __('Go to the Account Page', 'wp-ultimo'));

    // Display the wp_die
    return WU_Util::wp_die($message.'<br><br>'.$buttons.$accounts_link, $title, false, false, array('response' => 200));

  } // end limit_trial;
  
  /**
   * Only displays the enabled themes for the user's plan
   * @since  1.1.3 Allowing Theme Overwrite, as suggested by Richard
   * @param  array $themes All themes available
   * @return array Only themes available to that plan
   */
  public function limit_themes($themes) {

    /**
     * @since 1.1.3 allowing overwrite
     */
    $overwrite_themes = get_option('allowedthemes', array());
    
    // Get current theme for comparation
    $current_theme = wp_get_theme();
    
    foreach($themes as $theme_slug => $theme) {
      
      // If the theme is active, we need to display
      if ($current_theme->stylesheet == $theme_slug) {
        
        continue;

      } // end if;
      
      // check if it is allowed
      if (!in_array($theme_slug, $this->plan->allowed_themes) && !in_array($theme_slug, array_keys($overwrite_themes))) {

        unset($themes[$theme_slug]);

      }
      
    } // end foreach;
    
    return $themes;
    
  } // end limit_themes;
  
  /**
   * Only displays the enabled plugins for the user's plan
   * @param  array $plugins All plugins available
   * @return array Only the plugins allowed to that plan
   */
  public function limit_plugins($plugins) {
    
    foreach ($plugins as $plugin_slug => $plugin) {

      // Check if it is network active
      if (is_plugin_active_for_network($plugin_slug) && !current_user_can('manage_network')) {
        unset($plugins[$plugin_slug]);
        continue;
      }
      
      // If the plugin is active, we need to display
      if (is_plugin_active($plugin_slug)) continue;
      
      // check if it is allowed
      if (!in_array($plugin_slug, (array) $this->plan->allowed_plugins)) {
        unset($plugins[$plugin_slug]);
      }
      
    } // end foreach;
    
    return $plugins;
    
  } // end limit_plugins;

  /**
   * Based on the post type, we check if this post is supported fotr this plan
   *
   * @since 1.7.0
   * @param string $post_type
   * @return boolean
   */
  public function is_post_type_supported($post_type) {

    /**
     * Checks if a given post type is allowed on this plan
     * Allow plugin developers to filter the return value
     * 
     * @since 1.7.0
     * @param bool If the post type is disabled or not
     * @param WU_Plan Plan of the current user
     * @param int User id
     */
    return apply_filters('wu_limits_is_post_type_supported', ! $this->plan->is_post_type_disabled($post_type), $this->plan, $this->user_id);

  } // end is_post_type_supported;

  /**
   * Get the post counted based on what status we want to consider
   *
   * @since 1.9.1
   * @param object $post_counts WordPress object return by the wp_count_posts fn
   * @param string $post_type   The post type slug
   * @return int   Total post count
   */
  public static function get_post_count($post_counts, $post_type) {

    $count = 0;

    /**
     * Allow plugin developers to change which post status should be counted
     * By default, published and private posts are counted
     * 
     * @since 1.9.1
     * @param array $post_status The list of post statuses
     * @param string $post_type  THe post type slug
     * @return array New array of post status
     */
    $post_statuses = apply_filters('wu_post_count_statuses', array('publish', 'private'), $post_type);

    foreach($post_statuses as $post_status) {

      if (isset($post_counts->{$post_status})) {

        $count += (int) $post_counts->{$post_status};

      } // end if;

    } // end foreach;

    /**
     * Allow plugin developers to change the count total
     * 
     * @since 1.9.1
     * @param int $count The total post count
     * @param object $post_counts WordPress object return by the wp_count_posts fn
     * @param string $post_type  THe post type slug
     * @return int New total
     */
    return apply_filters('wu_post_count', $count, $post_counts, $post_type);

  } // end get_post_count;

  /**
   * Check if a given post count is above the post limit for the user plan
   *
   * @since 1.7.0
   * @param string $post_type
   * @return boolean
   */
  public function is_post_above_limit($post_type) {

    // Get user post count in that post type
    $post_count = wp_count_posts($post_type);

    /**
     * Calculate post count based on all different status
     */
    $post_count = self::get_post_count($post_count, $post_type);

    // Get the allowed quota
    $quota = $this->plan->get_quota($post_type);
    
    /**
     * Checks if a given post type is allowed on this plan
     * Allow plugin developers to filter the return value
     * 
     * @since 1.7.0
     * @param bool If the post type is disabled or not
     * @param WU_Plan Plan of the current user
     * @param int User id
     */
    return apply_filters('wu_limits_is_post_above_limit', $quota > 0 && $post_count >= $quota, $this->plan, $this->user_id);

  } // is_post_above_limit;
  
  /**
   * Limit the posts after the user reach his plan limits
   * 
   * @since 1.0.0
   * @since 1.5.4 Checks for blocked post types
   */
  public function limit_posts() {

    // Get the screen
    $screen = get_current_screen();

    /**
     * @since 1.5.4 Check if disabled
     */
    if (!$this->is_post_type_supported($screen->post_type)) {

      wp_die(sprintf(__('Your plan do not support this post type. <a href="%s">Upgrade your account.</a>', 'wp-ultimo'), wu_get_active_gateway()->get_url('change-plan')), __('Limit Reached', 'wp-ultimo'), array('back_link' => true));

    } // end if;
    
    // Check if that is more than our limit
    if ($this->is_post_above_limit($screen->post_type)) {
      
      // Display Errors Message
      // TODO: display a better error message
      wp_die(sprintf(__('You reached your plan\'s post limit. <a href="%s">Upgrade your account.</a>', 'wp-ultimo'), wu_get_active_gateway()->get_url('change-plan')), __('Limit Reached', 'wp-ultimo'), array('back_link' => true));
      
    } // end if;
    
  } // end limit_posts

  /**
   * Checks if the user is trying to publish a draft post. 
   * if that's the case, only allow him to do it if the post count is not above the quota.
   *
   * @since 1.7.0
   * @param array $data
   * @param array $modified_data
   * @return array
   */
  public function limit_draft_publishing($data, $modified_data) {

    if (get_post_status($modified_data['ID']) == 'publish') return $data; // If the post is already published, no need to make changes

    if (isset($data['post_status']) && $data['post_status'] != 'publish') return $data;

    $post_type = isset($data['post_type']) ? $data['post_type'] : 'post';

    if ( ! $this->is_post_type_supported($post_type) || $this->is_post_above_limit($post_type)) {

      $data['post_status'] = 'draft';

    }

    return $data;

  } // end limit_draft_publishing;
  
  /**
   * Limit the media uploads after the user reach his plan limits
   */
  public function limit_media($file) {
    
    // Get user post count in that post type
    $post_count = wp_count_posts('attachment');
    $post_count = $post_count->inherit;

    // Get the allowed quota
    $quota = $this->plan->get_quota('attachment');

    // This bit is for the flash uploader
    if ($file['type']=='application/octet-stream' && isset($file['tmp_name'])) {
      $file_size = getimagesize($file['tmp_name']);
      if (isset($file_size['error']) && $file_size['error']!=0) {
        $file['error'] = "Unexpected Error: {$file_size['error']}";
        return $file;
      } else {
        $file['type'] = $file_size['mime'];
      }
    }

    if ($quota > 0 && $post_count >= $quota) {
      $file['error'] = sprintf(__('You reached your media upload limit of %d images. Upgrade your account to unlock more media uploads.', 'wp-ultimo'), $quota, wu_get_active_gateway()->get_url('change-plan'));
    }
    
    // Return with errors
    return $file;
    
  } // end limit_media

  /**
   * Remove tabs on media uploader, if reached limit
   * 
   * @param   array  $tabs
   * @return  array  $tabs
   */
  public function limit_tabs($tabs) {
    
    // Get user post count in that post type
    $post_count = wp_count_posts('attachment');
    $post_count = $post_count->inherit;

    // Get the allowed quota
    $quota = $this->plan->get_quota('attachment');

    if ($quota > 0 && $post_count > $quota) {
      unset( $tabs['type'] );
      unset( $tabs['type_url'] );
    }
    
    return $tabs;

  } // end limit_tabs
  
  /**
   * Limit the media uploads after the user reach his plan limits
   */
  public function limit_users() {

    // Checks if we allow unlimited extra users
    if ($this->plan->should_allow_unlimited_extra_users()) return;
    
    // Get the user count of that blog
    $user_count_total = wu_get_current_site()->get_user_count();

    // Get the allowed quota
    $quota = $this->plan->get_quota('users') + 1;

    // Check if that is more than our limit
    if ($user_count_total >= $quota) {
      
      // Display Errors Message
      wp_die(__('You reached your users limit.', 'wp-ultimo'), __('Limit Reached', 'wp-ultimo'), array('back_link' => true));
      
    } // end if;
    
  } // end limit_users

  /**
   * Limit the media uploads after the user reach his plan limits
   */
  public function limit_sites() {
    
    $subscription = wu_get_current_site()->get_subscription();

    if (!$subscription) return;

    $sites_count = $subscription->get_site_count();

    // Get the allowed quota
    $quota = $this->plan->get_quota('sites');

    // Check if that is more than our limit
    if ($quota > 0 && $sites_count >= $quota) {
      
      // Display Errors Message
      wp_die(__('You reached the limit of sites allowed on your plan. Upgrade your account to have access to more sites.', 'wp-ultimo'), __('Limit Reached', 'wp-ultimo'), array('back_link' => true));
      
    } // end if;
    
  } // end limit_sites;

  /**
   * Flush WPEngine cache when we hit the visits limit
   *
   * @since 1.7.0
   * @return void
   */
  public function flush_wpengine_cache() {

    if ( ! class_exists( 'WpeCommon' ) ) {
      return false;
    }

    if ( function_exists( 'WpeCommon::purge_memcached' ) ) {
      \WpeCommon::purge_memcached();
    }

    if ( function_exists( 'WpeCommon::clear_maxcdn_cache' ) ) {
      \WpeCommon::clear_maxcdn_cache();
    }

    if ( function_exists( 'WpeCommon::purge_varnish_cache' ) ) {
      \WpeCommon::purge_varnish_cache();
    }

  } // end flush_wpengine_cache;

  public function hide_integration_buttons_on_activation_permission($value, $subscription) {

    if ($subscription->has_activation_permission()) {

      $gateway = $subscription->get_gateway();

      echo "<div style='text-align: center; padding: 12px; margin-bottom: -13px;'>";

        if ($gateway) {

          echo sprintf(__('Waiting for payment confirmation from %s. This can take a few minutes...', 'wp-ultimo'), $gateway->get_title());

        } else {

          echo __('Waiting for payment confirmation. This can take a few minutes...', 'wp-ultimo');

        } // end if;

      echo "</div>";

      return false;

    } // end if

    return $value;

  } // end hide_integration_buttons_on_activation_permission;
  
} // end class WU_Plans_Limits

new WU_Plans_Limits;

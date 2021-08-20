<?php
/**
 * Subscriptions Class Init
 *
 * Handles the infrastructure of this part of the plugin
 *
 * @author      Arindo Duque
 * @category    Admin
 * @package     WP_Ultimo/Subscriptions
 * @version     1.1.0
*/

if (!defined('ABSPATH')) {
  exit;
}

class WU_Subscriptions {

  /**
   * Holds the Subscription instance
   * 
   * @return WU_Subscriptions
   */
  static $instance;

  /**
   * Returns the one and only instance of this class
   *
   * @since 1.8.2
   * @return WU_Subscriptions
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

    /**
     * @since  1.1.3 Adds the ajax call to extend
     */
    add_action('wp_ajax_wu_extend_subscription', array($this, 'add_remove_time_to_subscription'));

    /**
     * @since  1.2.0 Serves the site list for the add new page
     */
    add_action('wp_ajax_wu_get_sites_user_is_part_of', array($this, 'get_sites_user_is_part_of'));

    /**
     * @since  1.3.3 Add Export to CSV action
     */
    add_action('wp_ajax_wu_generate_subscriptions_csv', array($this, 'generate_subscriptions_csv'));

    /**
     * @since  1.4.2 Add transaction manually
     */
    add_action('wp_ajax_wu_add_transaction', array($this, 'add_transaction'));

    add_action('admin_menu', array($this, 'add_remove_account_page'));

    add_filter('parent_file', array($this, 'fix_parent_file_for_menu'));

    add_filter('admin_init', array($this, 'handle_remove_account'));

    /**
     * @since 1.7.3 Removes coupons
     */
    add_action('admin_init', array($this, 'remove_coupon'));
    
    // @since 1.10.0
    add_action("wp_ajax_callback_transaction_process_delete", array($this, 'callback_transaction_process_delete'));

  } // end construct;

  public function callback_transaction_process_delete() {

    if (!isset($_POST['transaction_id'])) {

      wp_send_json_error(__('An error happened.', 'wp-ultimo') . ': transaction_id undefined');

    } // end if;

    $nonce = esc_attr( $_POST['_wpnonce']  );

    if (!current_user_can('manage_network') && !wp_verify_nonce( $nonce, 'wpultimo_delete_transaction' )) {

      wp_send_json_error(__('Go get a life script kiddies', 'wp-ultimo'));

    } // end if;

    $deleted = WU_Transactions::delete_transaction($_POST['transaction_id']);

    if (!$deleted) {

      wp_send_json_error(__('An error happened.', 'wp-ultimo'));

    } // end if;

    wp_send_json_success(array(
      'message' => __('Transaction Deleted!', 'wp-ultimo'),
    ));

  } // end callback_transaction_process_delete;

  /**
   * Fix the menu highlight
   *
   * @since 1.7.0
   * @param string $slug
   * @return void
   */
  public function fix_parent_file_for_menu($slug) {

    global $parent_file, $submenu_file, $plugin_page;

    if (isset($_GET['page']) && $_GET['page'] == 'wu-remove-account') {
      
      $plugin_page = 'wu-my-account';

    } // end if;

    return $slug;

  } // end fix_parent_file_for_menu;

  /**
   * Adds the remove account confirmation page
   *
   * @since 1.7.0
   * @return void
   */
  public function add_remove_account_page() {

    if (wu_get_current_site()->site_owner_id != get_current_user_id()) return;

    add_submenu_page(null, __('Delete your Account', 'wp-ultimo'), __('Delete your Account', 'wp-ultimo'), 'read', 'wu-remove-account', array($this, 'render_remove_account'));

  } // end add_remove_account_page;

  /**
   * Render the remove account 
   *
   * @since 1.7.0
   * @return void
   */
  public function render_remove_account() {

    WP_Ultimo()->render('meta/confirm-remove-account');
    
  } // end remove_account;

  /**
   * Handles the actual account deletion
   *
   * @since 1.7.0
   * @return void
   */
  public function handle_remove_account() {

    if (isset($_POST['wu_action']) && $_POST['wu_action'] == 'wu_remove_account') {

      if (!current_user_can(sprintf('manage_wu_%s_account', get_current_blog_id()))) {

        wp_die(__('You do not have the necessary permissions to perform this action.', 'wp-ultimo'));

      } // end if;

      if (!wp_verify_nonce($_POST['_wpnonce'], 'wu-remove-account')) return;

      $user_id = get_current_user_id();

      $user_data = get_userdata($user_id);

      // Get the subscription
      $subscription = wu_get_subscription($user_id);

      if (!$subscription) {

        return WP_Ultimo()->add_message(__('Subscription not found.', 'wp-ultimo'), 'error');

      } // end if;

      // Get the sites
      $site_list = $subscription->get_sites();

      foreach($site_list as $site) {

        wpmu_delete_blog($site->site_id, true);

      } // end foreach;

      $subscription->delete();
      
      wpmu_delete_user($user_id);

      /**
       * Attach extra actions
       */
      do_action('wu_remove_account', $user_id);

      /**
       * Sends the account removal email for the user and admin
       * @since 1.7.0
       */
      WU_Mail()->send_template('account_removed', get_network_option(null, 'admin_email'), array(
        'user_id'    => $user_id,
        'user_name'  => $user_data->display_name,
        'user_email' => $user_data->user_email,
      ));

      WU_Mail()->send_template('account_removed_user', $user_data->user_email, array(
        'user_id'    => $user_id,
        'user_name'  => $user_data->display_name,
        'user_email' => $user_data->user_email,
      ));

      // Display success message
      return wp_die(__('Your account, site, and subscription was successfully deleted.', 'wp-ultimo'), __('Account successfully removed', 'wp-ultimo'), array(
        'back_link' => false,
        'code'      => 200,
      ));

    } // end if;

  } // end handle_remove_account;

  /**
   * Handles the manual addition of new payments to the payment table
   *
   * @since 1.4.2
   * @return void
   */
  public function add_transaction() {

    if (!current_user_can('manage_network')) {

      wp_send_json(array(
        'status'  => false,
        'message' => __('You don\'t have permissions to perform that action.', 'wp-ultimo'),
      ));

    } // end if;

    // Verify Nonce
    if (!wp_verify_nonce($_GET['_wpnonce'], 'wu_transactions_form')) {

      die(json_encode(array(
        'status'  => false,
        'message' => __('You don\'t have permissions to perform that action.', 'wp-ultimo'),
      )));

    } // end nonce check;

    // Treaty value
    $_GET['amount'] = str_replace(',', '.', $_GET['amount']);

    /**
     * Validate Fields
     */
    if (!is_numeric($_GET['amount'])) {

      die(json_encode(array(
        'status'  => false,
        'message' => __('Amount needs to be a numeric value.', 'wp-ultimo'),
      )));

    } // end if;
    
    /**
     * If that's a charge, we bypass further validation and try to create the charge
     * @since 1.7.0
     */
    if (isset($_GET['remote_payment'])) {

      $subscription = wu_get_subscription($_GET['user_id']);

      $subscription->charge($_GET['amount'], $_GET['description']);

      wp_send_json(array(
        'status'  => true,
        'message' => __('Charge sent to payment processor. The payment can take up to 10 minutes to appear on the Billing History.', 'wp-ultimo'),
      ));

    } // end if;

    if (empty($_GET['description']) || empty($_GET['reference_id']) || empty($_GET['description'])) {

      die(json_encode(array(
        'status'  => false,
        'message' => __('All fields are required.', 'wp-ultimo'),
      )));

    } // end if;

    /**
     * Finally add the transaction
     * @param  string  $user_id      Site ID
     * @param  string  $reference_id The reference ID of the payment
     * @param  string  $type         Type of the transaction
     * @param  string  $amount       Amount of the transaction
     * @param  string  $gateway      The name of the gateway
     * @param  string  $desc         Short description, for reference
     */

    $status = WU_Transactions::add_transaction($_GET['user_id'], $_GET['reference_id'], 'payment', $_GET['amount'], 'manual', $_GET['description'], $_GET['date']);

    if ($status) {

      die(json_encode(array(
        'status'  => true,
        'message' => __('Payment added successfully.', 'wp-ultimo'),
      )));

    } else {

      die(json_encode(array(
        'status'  => false,
        'message' => __('Something went wrong.', 'wp-ultimo'),
      )));

    } // end if;

  } // end add_transaction;

  /**
   * Generate the CSV with all the susbcriptions on the system
   * @return
   */
  public function generate_subscriptions_csv() {

    if (!current_user_can('manage_network')) {

      wp_die(__('You do not have the necessary permissions to perform this action.', 'wp-ultimo'));

    } // end if;

    global $wpdb;

    $subscriptions = WU_Subscription::get_subscriptions('all', 0);

    $headers = array(
      "ID"                 => " ".__('ID', 'wp-ultimo'),
      'user_id'            => __('User ID', 'wp-ultimo'),
      'integration_status' => __('Integration Status', 'wp-ultimo'),
      'gateway'            => __('Gateway', 'wp-ultimo'),
      'plan_id'            => __('Plan ID', 'wp-ultimo'),
      'freq'               => __('Billing Frequency', 'wp-ultimo'),
      'price'              => __('Price', 'wp-ultimo'),
      'coupon_code'        => __('Coupon Code', 'wp-ultimo'),
      'price_after_coupon' => __('Price (after coupon code)', 'wp-ultimo'),
      'trial'              => __('Trial Days', 'wp-ultimo'),
      'created_at'         => __('Created At', 'wp-ultimo'),
      'active_until'       => __('Active Until', 'wp-ultimo'),
      'last_plan_change'   => __('Last Plan Change', 'wp-ultimo'),
      'paid_setup_fee'     => __('Paid Setup Fee', 'wp-ultimo'),
      'user_email'         => __('User Email', 'wp-ultimo'),
      'display_name'       => __('User Nicename', 'wp-ultimo'),
      'user_login'         => __('User Login', 'wp-ultimo'),
      'first_name'         => __('First Name', 'wp-ultimo'),
      'last_name'          => __('Last Name', 'wp-ultimo'),
      'status'             => __('Subscription Status', 'wp-ultimo'),
    );

    /**
     * Format elements
     * @var array
     */
    $subscriptions = array_map(function($element) {

      $subs = wu_get_subscription($element->user_id);

      $coupon_code = $subs->get_coupon_code();

      $element_array = array(
        "ID"                 => $subs->ID,
        'user_id'            => $subs->user_id,
        'integration_status' => $subs->integration_status,
        'gateway'            => $subs->gateway,
        'plan_id'            => $subs->plan_id,
        'freq'               => $subs->freq,
        'price'              => $subs->price,
        'coupon_code'        => $coupon_code ? $coupon_code['coupon_code'] : '',
        'price_after_coupon' => $subs->get_price_after_coupon_code(),
        'trial'              => $subs->trial,
        'created_at'         => $subs->created_at,
        'active_until'       => $subs->active_until,
        'last_plan_change'   => $subs->last_plan_change,
        'paid_setup_fee'     => $subs->paid_setup_fee,
        'user_email'         => $subs->get_user_data('user_email'),
        'display_name'       => $subs->get_user_data('display_name'),
        'user_login'         => $subs->get_user_data('user_login'),
        'first_name'         => $subs->get_user_data('first_name'),
        'last_name'          => $subs->get_user_data('last_name'),
        'status'             => $subs->get_status(),
      );

      return $element_array;

    }, $subscriptions);

    $file_name = sprintf('wp-ultimo-subscriptions-%s', date('Y-m-d'));

    /**
     * Generate the CSV
     */
    WU_Util::generate_csv($file_name, array_merge(array($headers), $subscriptions));

    die;

  } // end generate_subscriptions_CSV;

  /**
   * Returns the list of sites a user is part of via Ajax
   * @return html
   */
  public function get_sites_user_is_part_of() {

    if (!current_user_can('manage_network')) {

       wp_die('<p style="padding: 12px;">' . __('You do not have the necessary permissions to perform this action.', 'wp-ultimo') . '</p>');

    } // end if;

    $sites = array_filter(get_blogs_of_user($_POST['user_id']), function($blog) {

      if (is_main_site($blog->userblog_id)) {

        return false;

      }

      return property_exists($blog, 'site_id') && $blog->site_id == get_current_site()->id;

    });

    WP_Ultimo()->render('widgets/subscriptions/add-new/sites-table', array(
      'sites' => $sites,
    )); 

    exit;

  } // end get_sites_user_is_part_of;

  public function add_remove_time_to_subscription() {

    if (!current_user_can('manage_network')) {

      wp_send_json(array(
        'status'  => false,
        'message' => __('You do not have the necessary permissions to perform this action.', 'wp-ultimo'),
      ));

    } // end if;

    // Check referer
    if (!wp_verify_nonce($_POST['nonce'], 'wu-subscription-add-remove')) {
     
      wp_send_json(array(
        'status'  => false,
        'message' => __('Are you cheating?', 'wp-ultimo'),
      ));

    } // end if;

    // Check for type
    if (!isset($_POST['type'])) {
      
      wp_send_json(array(
        'status'  => false,
        'message' => __('Type not found.', 'wp-ultimo'),
      ));

    } // end if;

    $subscription = wu_get_subscription($_POST['user_id']);

    // If subscription
    if (!$subscription) {

     wp_send_json(array(
        'status'  => false,
        'message' => __('Subscription not found.', 'wp-ultimo'),
      ));

    }

    // Switch by type
    switch ($_POST['type']) {

      case 'add':

        $subscription->extend();

        $active_until = new DateTime($subscription->active_until);

        wp_send_json(array(
          'status'  => true,
          'message' => __('Subscription extended with success... Updating info.', 'wp-ultimo'),
          'remaining_string' => $subscription->get_active_until_string(),
          'status'           => $subscription->get_status(),
          'status_label'     => $subscription->get_status_label(),
          'active_until'     => $subscription->get_date('active_until', get_blog_option(1, 'date_format') . ' @  H:i' ),
        ));

        break;

      case 'remove':

        $subscription->withdraw();

        $active_until = new DateTime($subscription->active_until);

        wp_send_json(array(
          'status'           => true,
          'message'          => __('Subscription shortened with success... Updating info.', 'wp-ultimo'),
          'remaining_string' => $subscription->get_active_until_string(),
          'status'           => $subscription->get_status(),
          'status_label'     => $subscription->get_status_label(),
          'active_until'     => $subscription->get_date('active_until', get_blog_option(1, 'date_format') . ' @  H:i' ),
        ));

        break;

    } // end switch;

    wp_send_json(array(
      'status'  => false,
      'message' => __('Something happened.', 'wp-ultimo'),
    ));

  } // end add_remove_time_to_subscription;

  public function save_new_subscription() {

    if (!isset($_POST['saving_new_subscription'])) return;
        
    if (wp_verify_nonce('_wpultimo_nonce', 'saving_new_subscription')) wp_die(__('You don\'t have permission to access this page', 'wp-ultimo'));

    $user = get_user_by('id', $_POST['user_id']);

    // Checks if user is valid
    if (!$user) {

      return WP_Ultimo()->add_message(__('This user does not exist in our databases.'), 'error', true);

    } // end if;

    // Checks if user is valid
    if (user_can($user->ID, 'manage_network')) {

      return WP_Ultimo()->add_message(__('You cannot create a subscription for a network admin.'), 'error', true);

    } // end if;

    // Checks if the user already have a subscription, and kills the process if it has
    if ($subscription = wu_get_subscription($user->ID)) {

      $message = sprintf(__('The selected user already has a subscription attached to it. You can manage that subscription <a href="%s">here</a>.'), network_admin_url('admin.php?page=wu-edit-subscription&user_id='.$user->ID));

      return WP_Ultimo()->add_message($message, 'error', true);
      
    } // end if;

    /**
     * Checks for plan id
     * @since 1.5.4
     */
    if (!isset($_POST['plan_id']) || !isset($_POST['freq'])) {

      return WP_Ultimo()->add_message(__('You must select a valid plan and frequency.'), 'error', true);

    } // end if;

    $plan = wu_get_plan($_POST['plan_id']);

    /**
     * Checks for plan id
     * @since 1.5.4
     */
    if (!$plan) {

      return WP_Ultimo()->add_message(__('You must select a valid plan.'), 'error', true);

    } // end if;

    /**
     * Create the subscription
     */
    $subscription = new WU_Subscription((object) array(
      'user_id'          => $user->ID,
      'created_at'       => WU_Transactions::get_current_time('mysql'),
      'active_until'     => WU_Transactions::get_current_time('mysql'),
      'last_plan_change' => date('Y-m-d H:i:s', 0),
      'plan_id'          => $plan->id,
      'price'            => $plan->get_price($_POST['freq']),
      'freq'             => $_POST['freq'],
    ));

    $subscription = $subscription->save();

    /**
     * For each of the sites sent, we need to make this user the owner
     */
    foreach($_POST['sites'] as $site_id) {

      $site = wu_get_site($site_id);

      if ($site) {

        $site->set_owner($user->ID, apply_filters('wu_register_default_role', WU_Settings::get_setting('default_role', 'administrator')));

      } // end if;

    } // end foreach;

    // Redirect to the edit page
    wp_redirect(network_admin_url('admin.php?page=wu-edit-subscription&user_id='.$user->ID));

    exit;

  } // end save_new_subscription;

  /**
   * Updates the coupon code of a subscription
   *
   * @since 1.7.3
   * @param WU_Subscription $subscription
   * @return void
   */
  public function save_apply_coupon_code($subscription) {

    if (!$subscription || $subscription->integration_status) {

      return WP_Ultimo()->add_message(__('You can only apply coupon codes to subscriptions that have not integrated a payment method yet.', 'wp-ultimo'), 'error', true);

    } // end if;

    $coupon = wu_get_coupon($_POST['coupon']);

    if (!$coupon) {

      return WP_Ultimo()->add_message(__('The coupon code you entered is not valid or is expired.', 'wp-ultimo'), 'error', true);

    } else {

      /**
       * Test for plan_id and plan_freq
       * @since 1.5.5
       */
      if (!$coupon->is_plan_allowed( $subscription->plan_id ) || !$coupon->is_freq_allowed( $subscription->freq )) {

        return WP_Ultimo()->add_message(__('This coupon is not allowed for this plan or billing frequency.', 'wp-ultimo'), 'error', true);

      } // end if;

    } // end if;

    $subscription->apply_coupon_code($coupon->title);

    // Redirect to the edit page
    wp_redirect(network_admin_url('admin.php?page=wu-edit-subscription&updated=1&user_id=').$_POST['user_id']);

    exit;

  } // end save_apply_coupon_code;
  
  /**
   * Handles the removal of coupon codes
   * 
   * @since 1.7.3
   * @return void
   */
  public function remove_coupon() {

    if (!isset($_REQUEST['action']) || $_REQUEST['action'] !== 'remove_coupon') return;

    if (!current_user_can('manage_network')) {

      wp_die(__('You do not have the necessary permissions to perform this action.', 'wp-ultimo'));

    } // end if;

    check_admin_referer('remove_coupon');

    // subscription
    $subscription = wu_get_subscription($_REQUEST['user_id']);

    // Get the subscription
    if (!isset($_REQUEST['user_id']) || !$subscription) {

      wp_die(__('This is not a valid subscription.', 'wp-ultimo'));

    } // end if;

    if (!$subscription || $subscription->integration_status) {

      return WP_Ultimo()->add_message(__('You can only remove coupon codes to subscriptions that have not integrated a payment method yet.', 'wp-ultimo'), 'error', true);

    } // end if;

    $subscription->remove_coupon_code();

    // Redirect to the edit page
    wp_redirect(network_admin_url('admin.php?page=wu-edit-subscription&updated=1&user_id=') . $_REQUEST['user_id']);

    exit;

  } // end remove_coupon;

  /**
   * Handles the saving and the edittion of a Plan
   * 
   * @return void
   */
  public function save_subscription() {
    
    // Get the plan
    $id = isset($_POST['plan_id']) ? (int) $_POST['plan_id'] : 0;
  
    // Get our Plan
    $plan = new WU_Plan($id);

    // subscription
    $subscription = wu_get_subscription($_POST['user_id']);

    // Get the subscription
    if (!isset($_POST['user_id']) || !$subscription) {

      wp_die(__('This is not a valid subscription.', 'wp-ultimo'));

    } // end if;

    /**
     * @since 1.7.3 - Added Apply coupon code for subscriptions
     */
    if (isset($_POST['apply-coupon-code']) && !empty($_POST['coupon'])) {

      return $this->save_apply_coupon_code($subscription);

    } // end if;
  
    // Error message
    $messages = array();

    if (!$plan->id) {

      $messages[] = __('You must select a valid plan.', 'wp-ultimo');

    } // end if;
    
    /**
     * Validations about price, only if free
     */
    if (!isset($_POST['free'])) {

      $_POST['price'] = WU_Util::to_float($_POST['price']);

      if (!$_POST['price'] || !isset($_POST['price']) || !is_numeric($_POST['price'])) {

        $messages[] = __('You must define a valid subscription price.', 'wp-ultimo');

      } // end if;

      if (!WU_Gateway::check_frequency($_POST['freq'])) {

        $messages[] = __('You must use a valid frequency.', 'wp-ultimo');

      } // end if;

    } else {

      // Set the price as zero
      $_POST['price'] = 0;
      $_POST['freq']  = 1;

    } // end if;

    // Return errors
    if (!empty($messages)) {

      return WP_Ultimo()->add_message(implode('<br>', $messages), 'error', true);

    } // end if;

    /**
     * Save Subscription
     */
    $trial_days = ((int) $_POST['trial'] - $subscription->get_trial(true)) + $subscription->trial;

    /**
     * Check if the plan has changed
     */
    $plan_changed = $subscription->plan_id == $plan->id;

    $subscription->plan_id = $plan->id;

    if ($subscription->created_at != $_POST['created_at']) {

      $subscription->created_at = $_POST['created_at'];

    } // end if;

    if ($subscription->active_until != $_POST['active_until']) {

      $subscription->active_until = $_POST['active_until'];

    } // end if;

    if ($trial_days != $subscription->trial) {

      /**
       * @since 1.5.5 Reset the trial date
       */
      $new_active_until = DateTime::createFromFormat('U', $subscription->get_date('created_at', 'U'));
      $new_active_until->add( DateInterval::createFromDateString("$trial_days days") );
      $subscription->active_until = $new_active_until->format('Y-m-d H:i:s');

    } // end if;

    // Check if we need to remove the integration
    if ($subscription->integration_status && ($subscription->price != $_POST['price'] || $subscription->freq != $_POST['freq'])) {

      wu_get_gateway($subscription->gateway)->remove_integration(false, $subscription);

    } // end if;

    $subscription->price = $_POST['price'];

    $subscription->freq  = $_POST['freq'];

    $subscription->trial = $trial_days;

    $subscription->paid_setup_fee = ! isset($_POST['should_charge_setup_fee']); // @since 1.7.0

    $subscription->save();

    // Run the refresher of disk space
    WU_Site_Hooks::refresh_upload_quota_on_change_plan($subscription, $plan);

    // Redirect to the edit page
    wp_redirect(network_admin_url('admin.php?page=wu-edit-subscription&updated=1&user_id=').$_POST['user_id']);

    exit;
    
  } // end save_plan;

} // end class WP_Plans;

// Run our Class
WU_Subscriptions::get_instance();

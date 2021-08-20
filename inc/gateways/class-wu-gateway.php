<?php
/**
 * Gateway Class
 *
 * This is the reference class of our gateway integrations
 * Gateways should extend this class. There is some documentation on each function.
 * There is also a tutorial on our documentation site on how to expand this class:
 *
 * @link https://docs.wpultimo.com/docs/implementing-new-gateways/
 *
 * @author      Arindo Duque
 * @category    Admin
 * @package     WP_Ultimo/Gateways
 * @version     0.0.1
*/

if (!defined('ABSPATH')) {
  exit;
}

/**
 * WU_Gateway
 */
class WU_Gateway {
  
  /**
   * ID of the Gateway
   * @var string
   */
  public $id = '';

  /**
   * Gateway Title
   * @var string
   */
  public $title;

  /**
   * Description of the framework
   * @var string
   */
  public $gateway_desc;

  /**
   * The class name of the gateway
   * @var string
   */
  public $class_name;

  /**
   * Object that handles errors
   * @var object
   */
  public $errors;

  /**
   * Notify URL to be used by the APIs
   * @var string
   */
  public $notification_url;

  /**
   * Get the plan of the user
   * @var WU_Plan
   */
  public $plan;

  /**
   * Plans price
   * @var string/interger
   */
  public $price;

  /**
   * Plans Freq
   * @var string/interger
   */
  public $freq;

  /**
   * Holds the gateway pages, like the processing, cancel and change plan
   * You can register extra pages for custom actions required by your gateway
   * @since  1.1.0
   */
  private $pages = array();

  /**
   * If routed
   */
  public $routed = false;
  
  /**
   * Initialize the gateway class for later use
   * @private
   * @param integer $id         ID of the Gateway
   * @param string  $title      Title of the Gateway
   * @param string  $desc       Description of the Gateway
   * @param string  $class_name Name of the Class
   */
  public function __construct($id, $title, $desc, $class_name) {

    // Instanciate a error object
    $this->errors = new WP_Error;
    
    $this->id            = $id;
    $this->title         = $title;
    $this->gateway_desc  = $desc;
    $this->class_name    = $class_name;

    // Get Global Values
    $this->site         = wu_get_current_site();
    $this->subscription = $this->site->get_subscription();

    // If the subscription exists
    if ($this->subscription) {

      $this->plan  = $this->site->get_plan();
      $this->freq  = $this->subscription->freq;
      $this->price = $this->subscription->price;

    }

    /**
     * @since  1.1.0 Register the default pages for the gateways, such as success_page, cancel_page and so on
     */
    $this->register_default_pages();

    // Initialize
    $this->init();

    // Build the ajax endpoint to receive notifications
    $this->notification_url = admin_url('admin-ajax.php?action=notify_gateway_' . $this->id);

    // Add the action as well
    add_action('wp_ajax_notify_gateway_' . $this->id, array($this, 'handle_notifications'));

    add_action('wp_ajax_nopriv_notify_gateway_' . $this->id, array($this, 'handle_notifications'));
    
    // Register the settings
    add_filter('wu_settings_section_payment_gateways', array($this, 'add_settings'));
    
    // Register the actions for the My Accounts Page
    add_action('wu_page_wu-my-account_load', array($this, 'router'), 10);

    // Register the actions for the My Accounts Page
    add_action('wu_page_wu-my-account_load', array($this, 'fallback_router'), 20);

    // @since 1.2.0
    add_action("wp_ajax_wu_process_refund_$this->id", array($this, 'process_refund'));

    add_action("wp_ajax_wu_cancel_subscription_integration_$this->id", array($this, 'admin_cancel_integration'));

    /**
     * Add temporary permission while we wait for callback calls
     * @since 1.9.0
     */
    add_action('wu_gateway_page_load_success', array($this, 'create_activation_permission'));
  
  } // end construct;

  /**
   * Retrieves the admin initiated cancel subscription link
   * 
   * @since 1.7.0
   * @param integer $user_id ID of the user of that subscription
   * @return string;
   */
  public function get_admin_cancel_integration_url($user_id) {

    return sprintf(admin_url('admin-ajax.php?action=%s&user_id=%s'), "wu_cancel_subscription_integration_$this->id", $user_id);

  } // end get_admin_cancel_integration_url;

  /**
   * Process subscription cancelation initiated by the super admin
   * 
   * @since 1.7.0
   * @return void;
   */
  public function admin_cancel_integration() {

    $subscription = isset($_REQUEST['user_id']) ? wu_get_subscription($_REQUEST['user_id']) : false;

    if (!$subscription || ! current_user_can('manage_network')) {

      wp_redirect( network_admin_url('admin.php?page=wp-ultimo-subscriptions') );

      exit;

    } // end if

    $gateway = wu_get_gateway($subscription->gateway);

    $gateway->remove_integration(false, $subscription);

    wp_redirect($subscription->get_manage_url());

    exit;

  } // end admin_cancel_integration;

  /**
   * This is used to check if a given gateway supports single payments
   * Custom gateways should implement this accordingly
   *
   * @since 1.7.0
   * @return bool
   */
  public function supports_single_payments() {

    return apply_filters("wu_gateway_supports_single_payments_$this->id", false);

  } // end supports_single_payments;

  /**
   * Register a gateway page, with a callback to be run whenever that page is accessed
   * @since  1.1.0
   * @param  string       $page_slug The slug of the page being registered
   * @param  string/array $callback  Callback function to be run
   */
  public function register_gateway_page($page_slug, $callback) {

    // Add it to our 
    $this->pages[$page_slug] = $callback;

  } // end register_gateway_page;

  /**
   * Return the URL for a giving page, registered using register_gateway_page
   * @param  string $page_slug The page slug of the registered page
   * @return string            The desired URL for that page
   */
  public function get_url($page_slug) {

    // Let's generate a nonce code to later verification, just to be sure...
    $security_code = wp_create_nonce('wu_gateway_page');

    return apply_filters('wu_gateway_get_url', admin_url(sprintf('admin.php?page=wu-my-account&action=%s&code=%s&gateway=%s', $page_slug, $security_code, $this->id)), $page_slug, $security_code);

  } // end get_url;

  /**
   * Call the callback passed as the callable for a given page
   * @since  1.1.0
   * @param  string $page_slug Page slug to be run
   */
  public function call_page($page_slug) {

    // Call the callback
    if (isset($this->pages[$page_slug])) {

      call_user_func($this->pages[$page_slug]);
      
    }

  } // end call_page;

  /**
   * Returns the gateway title, filterable
   * 
   * @since 1.4.3
   * @return string
   */
  public function get_title() {

    return apply_filters('wu_get_gateway_title_' . $this->id, $this->title);

  } // end get_title;

  /**
   * Register the default pages of the integration flow
   * @since  1.1.0
   */
  public function register_default_pages() {

    $default_pages = array(

      'process-integration' => array($this, 'process_integration'), // Handles the integration
      'remove-integration'  => array($this, 'remove_integration'),  // Handles the remove of a particular integration from a Subscription
      'process-refund'      => array($this, 'process_refund'),      // Handles the issuance of partial and total refunds
      'success'             => array($this, 'success_page'),        // Success page to be displayed after a successful integration
      'error'               => array($this, 'error_page'),          // Error page to be displayed if the user cancels the integration flow
      'change-plan'         => array($this, 'before_change_plan'),  // Displays the custom page to select the new plan and frequency
    );

    foreach($default_pages as $page_slug => $callable) {

      // Add that to our page list
      $this->register_gateway_page($page_slug, $callable);

    } // end foreach;

  } // end register_default_pages;

  /**
   * Initialize the Gateway key elements
   */
  public function init() {} // end init;

  /**
   * Check if this is the desired gateway to run
   * @since  1.1.0
   * @return boolean 
   */
  public function is_desired_gateway() {

    // Special case: change_plan
    // if (isset($_REQUEST['action']) && $_REQUEST['action'] == 'change-plan') return true;

    // return wu_get_active_gateway()->id == $this->id || ( isset($_REQUEST['gateway']) && $_REQUEST['gateway'] == $this->id);

    return ( isset($_REQUEST['gateway']) && $_REQUEST['gateway'] == $this->id );
  
  } // end is_desired_gatweway
  
  /**
   * The router for the processing steps
   */
  public function router() {

    if (!$this->is_desired_gateway()) return;

    if (isset($_GET['action']) && wp_verify_nonce($_GET['code'], 'wu_gateway_page')) {

      /**
       * Check if we need to fallback
       * @var boolean
       */
      $this->routed = true;

      // Get the action we want to perform
      $action = $_GET['action'];

      // Allow developers to run actions
      do_action("wu_gateway_page_load_$action", $this->subscription);

      // Call that page
      $this->call_page($action);

    } // end if;
    
  } // end router;

  /**
   * The router for the processing steps
   * @since  1.2.1
   */
  public function fallback_router() {

    if ($this->routed === true) return;

    if (isset($_GET['action']) && wp_verify_nonce($_GET['code'], 'wu_gateway_page')) {

      // Get the action we want to perform
      $action = $_GET['action'];

      // Allow developers to run actions
      do_action("wu_gateway_page_load_$action", $this->subscription);

      // Gateway padrão
      $gateway = new WU_Gateway(null, null, null, null);

      // Call that page
      $gateway->call_page($action);

    }
    
  } // end fallback_router;

  /**
   * Returns the button label to be used
   *
   * @since 1.7.0
   * @return string
   */
  public function get_button_label() {

    $label = WU_Settings::get_setting( $this->id . '_title' );

    $label = $label ?: sprintf(__('Add Payment Account - %s', 'wp-ultimo'), $this->title);

    return apply_filters('wu_signup_payment_step_button_text', $label, $this->title, $this); 

  } // end get_button_label;
  
  /**
   * Handles the addition of the admin fields to the actual Settings Page
   * 
   * @since  1.1.0 requires now works with multiple gateways
   * 
   * @param  array $fields Fields array passed by Settings Page
   * @return array New array containing new fields
   */
  public function add_settings($fields) {
   
    // Get settings passed by the extended class, return if empty
    $new_fields = $this->settings();

    // Check if the fields are empty
    if (empty($new_fields)) return $fields;
    
    // Generates the heading for our settings API
    $heading = array(
      
      $this->id => array(
        'title'    => sprintf(__('%s Gateway', 'wp-ultimo'), $this->title),
        'desc'     => $this->gateway_desc,
        'type'     => 'heading',
        'require'  => array("active_gateway[$this->id]" => true), // @since 1.0.5
      ),

      /**
       * @since 1.7.0
       */
      $this->id.'_title' => array(
        'title'       => __('Button Label', 'wp-ultimo'),
        'desc'        => '',
        'tooltip'     => '',
        'type'        => 'text',
        'placeholder' => sprintf(__('Add Payment Account - %s', 'wp-ultimo'), $this->title),
        'require'     => array("active_gateway[$this->id]" => true), 
      ),

    );
    
    // Return the new fields array
    return array_merge($fields, $heading, $new_fields);
    
  } // end add_settings;
  
  /**
   * Returns the settings array
   */
  public function settings() {} // end settings;
  
  /**
   * First step of the payment flow: process_form
   */
  public function process_integration() {} // end process_form;

  /**
   * Handles the canceling of a subscription
   */
  public function remove_integration($redirect = true, $subscription = false) {} // end remove_integration;

  /**
   * Handles the notifications sent by APIs, like Paypal and Stripe
   */
  public function handle_notifications() {} // end handle_notifications;

  /**
   * Check if we are trying to upgrade to the same plan
   *
   * @since 1.8.2
   * @return void
   */
  public function before_change_plan() {

    if (!isset($_POST['wu_action']) || $_POST['wu_action'] !== 'wu_change_plan' || !$this->is_desired_gateway()) return;
    
    /**
     * Add new check, for same plan and billing freq
     * @since 1.8.2
     */
    if ($this->subscription->freq == $_POST['plan_freq'] && $this->subscription->plan_id == $_POST['plan_id']) {

      WP_Ultimo()->add_message(__('You cannot change to your current plan with the same billing frequency.', 'wp-ultimo'), 'error');

      return;

    } // end if;

    $this->change_plan();

  } // end before_change_plan;

  /**
   * Change Plan security checks
   */
  public function change_plan() {

    // Just return in the wrong pages
    if (!isset($_POST['wu_action']) || $_POST['wu_action'] !== 'wu_change_plan') return;

    // Security check
    if (!wp_verify_nonce($_POST['_wpnonce'], 'wu-change-plan')) {
      WP_Ultimo()->add_message(__('You don\'t have permissions to perform this action.', 'wp-ultimo'), 'error');
      return;
    }

    if (!isset($_POST['plan_id'])) {
      WP_Ultimo()->add_message(__('You need to select a valid plan to change to.', 'wp-ultimo'), 'error');
      return;
    }

    // Check frequency
    if (!isset($_POST['plan_freq']) || !self::check_frequency($_POST['plan_freq'])) {
      WP_Ultimo()->add_message(__('You need to select a valid frequency to change to.', 'wp-ultimo'), 'error');
      return;
    }

    // Get Plans - Current and new one
    $current_plan = $this->plan;
    $new_plan     = new WU_Plan((int) $_POST['plan_id']);

    if (!$new_plan->id) {
      WP_Ultimo()->add_message(__('You need to select a valid plan to change to.', 'wp-ultimo'), 'error');
      return;
    }

    /**
     * Now we have the new plan and the new frequency
     */
    // Case: new plan if free
    if ($new_plan->free) {

      // Send email confirming things
      // TODO: send email
      
      // Set new plan
      $this->subscription->plan_id = $new_plan->id;
      $this->subscription->freq    = 1;
      $this->subscription->price   = 0; // $new_plan->{"price_".$_POST['plan_freq']};
      $this->subscription->integration_status = false;
      $this->subscription->save();

      // Hooks, passing new plan
      do_action('wu_subscription_change_plan', $this->subscription, $new_plan, $current_plan);
      
      // Redirect to success page
      wp_redirect(WU_Gateway::get_url('plan-changed'));
      exit;

    } // end if free;

    /**
     * Really start to process things =D
     * We need to decide if we need to pro-rate this or no
     */

    $new_price = $new_plan->{"price_".$_POST['plan_freq']};
    $new_freq  = (int) $_POST['plan_freq'];

    // First we need to define the previous starting date, which is our active until - frequency
    $active_until = new DateTime($this->subscription->active_until);
    $created_at   = new DateTime($this->subscription->created_at);

    // Changes to the subscription
    $this->subscription->active_until = $active_until->format('Y-m-d H:i:s');
    $this->subscription->price        = $new_price;
    $this->subscription->freq         = $new_freq;
    $this->subscription->plan_id      = $new_plan->id;

    $this->subscription->gateway      = '';

    $this->subscription->save();

    // Hooks, passing new plan
    do_action('wu_subscription_change_plan', $this->subscription, $new_plan, $current_plan);

    // Redirect to success page
    wp_redirect(WU_Gateway::get_url('plan-changed'));
    
    exit;

  } // end change_plan;

  /**
   * Displays the success page message
   * @since 1.1.0
   */
  public function success_page() {

    // Only display if no domain mapping is there
    if (!isset($_REQUEST['custom-domain'])) {

      $title   = apply_filters('wu_payment_integration_success_title', __('Success!', 'wp-ultimo'));
      $message = apply_filters('wu_payment_integration_success_message', __('Your new payment method was added with success! You should get the first receipt for your subscription soon. Do not panic if no integration appears at first. It may take up to 5 minutes before we hear back from your selected payment method.', 'wp-ultimo'));

      // Display our success message
      WU_Util::display_alert($title, $message);

    } // end if;

  } // end success_page;

  /**
   * Displays the error page message
   * @since 1.1.0
   */
  public function error_page() {

    $title   = __('Error!', 'wp-ultimo');
    $message = __('You canceled the integration process.', 'wp-ultimo');

    // Display our error message
    WU_Util::display_alert($title, $message, 'error');

  } // end success_page;

  /**
   * Check if a frequency is valid
   * 
   * @since  1.1.5
   * @param  integer $frequency Frequency to be checked
   * @return boolean            Wether that frequency is a valid one or not
   */
  public static function check_frequency($frequency) {

    $allowed = array();

    // Check if pricings 3 and 12 are allowed
    if (WU_Settings::get_setting('enable_price_1'))  $allowed[] = 1;
    if (WU_Settings::get_setting('enable_price_3'))  $allowed[] = 3;
    if (WU_Settings::get_setting('enable_price_12')) $allowed[] = 12;

    return in_array($frequency, $allowed);

  } // end check_frequency;

  public function calculate_pro_rate($old_price, $old_freq, $new_price, $new_freq, $start_date) {

    // Calculate different prices per day
    $old_price_per_day = $old_price / ($old_freq * 30);
    $new_price_per_day = $new_price / ($new_freq * 30);

    // Check which day of the period we are
    $start_date = new DateTime($start_date);
    $now        = new DateTime();

    // Calculate the difference
    $diff = $now->diff($start_date, true);
    if ($diff->days == 0) $days = 0;
    else $days = $diff->days - 1;

    $total_days = $new_freq * 30;

    $total = 0;

    // Add first rate
    $total += $days * $old_price_per_day;
    $total += ($total_days - $days) * $new_price_per_day;

    // Calculate if we need to give a refund
    $refund = $old_price - $total;

    // Return Objects
    $return = array(
      'total'  => number_format($total, 2),
      'refund' => number_format($refund, 2),
    );

    return (object) $return;
    // return $total;

  } // end calculate_pro_rate;

  /**
   * Log predefined actions that occur during integration and cancelling. This is here just to save us some time and make the code clear
   * @param  WU_Subscription $subscription Subscription object
   * @param  string $type                  Type of log to do
   */
  public function log($subscription, $type = 'setup') {

    // Set different messages to each case
    switch ($type) {

      case 'setup':
        $message = sprintf(__('A new payment integration, with %s, was added to user with ID %s.', 'wp-ultimo'), $this->title, $subscription->user_id, $subscription->ID);
        break;

      default:
        $message = '';
        break;

    } // end switch;

    // Add to log using our Logger class
    WU_Logger::add('gateway-'.$this->id, $message);

  } // end log;
 
  /**
   * Create the integration in the database
   * @since  0.0.1
   * @since  1.1.0   Moved the security checks to a different method
   * 
   * @param  WU_Subscription  $subscription    Subscription object
   * @param  WU_Plan          $plan            Plan object
   * @param  interger         $freq            Frequency of the subscription
   * @param  string           $integration_key Integration key to be saved
   * @param  array            $meta            Meta array
   * @param  boolean          $changing_plan   Flag telling if we are changing the plan or not
   * 
   * @return WU_Subscription                   Return the Subscription object
   */
  public function create_integration($subscription, $plan, $freq, $integration_key, $meta, $changing_plan = false) {

    /** Security Checks */
    if (!$this->security_checks($subscription, $plan, $freq)) return;

    // Save changes to the subscription
    $subscription->integration_status = 1;
    $subscription->integration_key    = $integration_key;
    $subscription->gateway            = $this->id;
    $subscription->meta               = array_merge((array) $subscription->meta, $meta);
    
    // Add plan data
    // $subscription->price   = $plan->{"price_$freq"};
    $subscription->freq    = (int) $freq;
    $subscription->plan_id = $plan->id;
    
    // Save Subscription
    $subscription->save();

    // Log Transaction
    WU_Transactions::add_transaction($subscription->user_id, $integration_key, 'recurring_setup', '--', $this->id, sprintf(__('Setup of recurring payment for plan %s', 'wp-ultimo'), $plan->title));

    // Log
    $this->log($subscription, 'setup');

    // Hook before saving
    do_action('wu_subscription_create_integration', $subscription, $plan, $integration_key, $this);

    // Return the object
    return $subscription;

  } // end create_subscription;

  public function escape_array($arr) {

    global $wpdb;

    $escaped = array();
    
    foreach($arr as $k => $v){
      if (is_numeric($v)) {
        $escaped[] = $wpdb->prepare("%d", $v);
      } else {
        $escaped[] = $wpdb->prepare("%s", $v);
      }
    }

    return implode(',', $escaped);

  }

  /**
   * Get the last relevant transaction, to refund the user if necessary
   * @param  string $value Value to check against
   * @return string        Transaction if, to be refunded
   */
  public function get_last_relevant_transaction($user_id, $value = false, $type = array('payment')) {
    
    global $wpdb;

    $table_name = WU_Transactions::get_table_name();
    $type = !is_array($type) ? array($type) : $type;

    $in_types = $this->escape_array($type);

    $query = $wpdb->prepare("SELECT reference_id, amount, id, type FROM $table_name WHERE user_id = %d AND type IN (".$in_types.") AND gateway = %s ORDER BY time DESC", $user_id, $this->id, $value);

    return $wpdb->get_row($query);

  }

  /**
   * Do the processing check to make sure that we have all valid data
   *
   * @since  1.1.0
   * 
   * @param  WU_Subscription  $subscription    Subscription object
   * @param  WU_Plan          $plan            Plan object
   * @param  interger         $freq            Frequency of the subscription
   * @return boolean                           Return boolean
   */
  public function security_checks($subscription, $plan, $freq) {

    // Check Frequency
    if (!self::check_frequency($freq)) {
      WP_Ultimo()->add_message(__('The frequency applied is not valid.', 'wp-ultimo'), 'error');
      return false;
    }

    // Check Subscription
    if (!is_a($subscription, 'WU_Subscription')) {
      WP_Ultimo()->add_message(__('No valid subscription was found. Contact the administrator.', 'wp-ultimo'), 'error');
      return false;
    }

    // Check Plan as well
    if (!is_a($plan, 'WU_Plan') && $plan->id != 0) {
      WP_Ultimo()->add_message(__('A valid plan is necessary to create a subscription.', 'wp-ultimo'), 'error');
      return false;
    }

    return true;

  } // end security_checks;

  /**
   * Generate a Invoice for payments received
   * @since  1.2.0
   * @param  string          $gateway      Gateway ID
   * @param  WU_Subscription $subscription Subscription object
   * @param  string          $message      Item Description
   * @param  interger        $value        Value of the invoice
   */
  public function generate_invoice($gateway, $subscription, $message, $value, $paid = true) {

    $should_generate_invoice = WU_Settings::get_setting('attach_invoice_pdf') || $this->id == 'manual';

    // Check for option
    if (apply_filters('wu_gateway_should_generate_invoice', $should_generate_invoice, $this) == false) return false;

    // Invoice Path
    switch_to_blog(get_current_site()->blog_id);
    
      $path = wp_upload_dir('invc', true);
      
    restore_current_blog();

    $date = date('Y-m-d', time());
    
    // Generate Invoice Name
    $name = sanitize_file_name( sprintf('%s %s - %s.pdf', __('Invoice for', 'wp-ultimo'), $subscription->get_user_data('display_name'), $date) );

    include_once ( WP_Ultimo()->path('inc/invoicer/phpinvoice.php') );

    // Instantiate the Invoicer
    $invoice = new phpinvoice('A4', WU_Settings::get_setting('currency_symbol')); // get_wu_currency_symbol()

    // Format
    $invoice->setNumberFormat(WU_Settings::get_setting('decimal_separator'), WU_Settings::get_setting('thousand_separator'), WU_Settings::get_setting('precision')); 

    $logo = WU_Settings::get_logo();

    /* Header Settings */
    if ($logo) {
      $invoice->setLogo( preg_replace('#^https?://#', 'http://', $logo) );
    }
    $invoice->setColor( WU_Settings::get_setting('primary-color') );
    
    $invoice_title = apply_filters('wu_invoice_title', __('Invoice', 'wp-ultimo'), $subscription, $invoice, $this); 
    
    $invoice->setType($invoice_title);

    /**
     * Allow Plugin developers to filter the reference number and change it to something else.
     * 
     * Usage:
     * 
     * add_filter('wu_invoice_reference', 'my_new_reference', 10, 2);
     * 
     * function my_new_reference($reference, $subscription) {
     *   return 'new reference text';
     * }
     * 
     * @since                 1.7.3
     * @param string          Default reference
     * @param WU_Subscription Current subscription being used to generate the invoice
     * @return string
     */
    $reference = apply_filters('wu_invoice_reference', "SUB #$subscription->user_id", $subscription);
    $invoice->setReference($reference);

    $invoice->setDate($date);
    
    $merchant_info = WU_Settings::get_setting('merchant_address') 
      ? explode("\n", WU_Settings::get_setting('merchant_address')) 
      : array(get_site_option('site_name'));

    $merchant_info[] = get_site_option('admin_email');

    // Filter Merchant Info
    // @since 1.4.3
    $merchant_info = apply_filters('wu_invoice_from', $merchant_info);

    // Headers
    $invoice->setFrom($merchant_info);

    // @since 1.4.3
    $invoice->setTo(apply_filters('wu_invoice_to', array(
      $subscription->get_user_data('display_name'),
      $subscription->get_user_data('user_email'),
    ), $subscription->user_id));
    
    /* Adding Items in table */
    // $invoice->addItem(name,description,amount,vat,price,discount,total)
    $invoice->addItem("Subscription", $message, 1, false, $value, false, $value);

    /**
     * Now we add the other lines
     */
    foreach($subscription->get_invoice_lines() as $line) {

      if (isset($line['text']) && isset($line['value'])) {

        $invoice->addItem($line['text'], '', 1, false, $line['value'], false, $line['value']);

        // Check for the negative greater that the total

        // Deduct the value
        $value += $line['value'];

      } // end if;

    } // end foreach;

    // Set total
    $total = $value > 0 ? $value : 0;
    
    /* Add totals */
    $invoice->addTotal(__('Total', 'wp-ultimo'), $total, false);

    // If it was paid
    if ($paid) {

      $invoice->addTotal(__('Amount Paid', 'wp-ultimo'), -$total, false);
      $invoice->addTotal(__('Amount Due', 'wp-ultimo'), 0, true);

      /* Set badge */ 
      $invoice->addBadge(__('Paid', 'wp-ultimo'));

    } else {

      $invoice->addTotal(__('Amount Due', 'wp-ultimo'), $total, true);

    } // end if;
    
    // Display Instructions to pay
    if ($this->id == 'manual') {

      if (WU_Settings::get_setting('manual_payment_instructions')) {

        /* Add title */
        $invoice->addTitle(__('Instructions to Pay', 'wp-ultimo') );
        
        /* Add Paragraph */
        $invoice->addParagraph(strip_tags(WU_Settings::get_setting('manual_payment_instructions')));

      }

      // Add due date
      $active_until = new DateTime($subscription->active_until);

      $grace_period = WU_Settings::get_setting('manual_waiting_days');

      $grace_period = $active_until->add(  date_interval_create_from_date_string("$grace_period days") );

      $invoice->setDate($subscription->get_date('active_until'));
      
      $invoice->setDue($subscription->get_date('due_date'));

    } // end if;

    /**
     * Let admins add new Information to the bottom
     * @since 1.4.3
     */
    $custom_bottom_message = apply_filters('wu_invoice_bottom_message', false, $subscription);
    
    if ($custom_bottom_message) {

      // Checks if has title
      if (is_array($custom_bottom_message)) {

        $invoice->addTitle($custom_bottom_message['title']);
        
        /* Add Paragraph */
        $invoice->addParagraph($custom_bottom_message['message']);

      } else {

        $invoice->addParagraph($custom_bottom_message);

      } // end if;

    } // end if;
    
    /* Set footer note */
    $invoice->setFooternote(get_site_option('site_name'));
    
    /* Render */
    $invoice->render($path['path'].$name, 'F'); /* I => Display on browser, D => Force Download, F => local path save, S => return document path */

    return $path['path'].$name;

  } // end generate_invoice;

  /**
   * Create an activation permission for the subscription when a new payment is added
   *
   * @since 1.9.0
   * @param WU_Subscription $subscription
   * @return void
   */
  public function create_activation_permission($subscription) {

    if ($subscription->integration_status == 1) return;

    return $subscription && $subscription->create_activation_permission();

  } // end create_activation_permission;
  
} // end class WU_Gateway;

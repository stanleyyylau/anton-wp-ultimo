<?php
/**
 * Webhooks Class
 *
 * Handles the register of webhooks events as well as the admin interface for adding Webhooks
 * 
 * @since       1.6.0
 * @author      Arindo Duque
 * @category    Admin
 * @package     WP_Ultimo/Webhooks
 * @version     0.0.1
 */

if (!defined('ABSPATH')) {
  exit;
}

class WU_Webhooks {

  /**
   * Holds the registered Webhook events
   * @var array
   */
  public $events = array(); 

  /**
   * Makes sure we are only using one instance of the plugin
   * @var object WU_Webhooks
   */
  public static $instance;

  /**
   * Returns the instance of WP_Ultimo
   * @return object A WU_Webhooks instance
   */
  public static function get_instance() {
    if (null === self::$instance) self::$instance = new self();
    return self::$instance;
  } // end get_instance;

  /**
   * Set the important hooks
   */
  function __construct() {

    // Add our settings - including the option to edit email templates
    add_filter('wu_settings_section_tools', array($this, 'add_settings'));

    add_action('wp_ajax_wu_serve_logs', array($this, 'serve_logs'));

    add_action('wp_ajax_wu_get_webhooks', array($this, 'get_webhooks'));

    add_action('wp_ajax_wu_update_webhook', array($this, 'update_webhook'));

    add_action('wp_ajax_wu_delete_webhook', array($this, 'delete_webhook'));

    add_action('wp_ajax_wu_send_test_webhook', array($this, 'send_test_event'));

    add_action('init', array($this, 'register_webhook_listeners'));

  } // end construct;

  /**
   * Adds the listeners to the webhook callers, extend this by adding actions to wu_register_webhook_listeners
   *
   * @return void
   */
  public function register_webhook_listeners() {

    if (!WU_Settings::get_setting('enable-webhooks', true)) return;

    add_action('wp_ultimo_registration', array($this, 'send_account_created_events'), 10, 4);

    add_action('wu_after_domain_mapping', array($this, 'send_new_domain_mapping_events'), 10, 3);

    add_action('wp_ultimo_payment_completed', array($this, 'send_payment_successful_events'), 10, 3);

    add_action('wp_ultimo_payment_failed', array($this, 'send_payment_failed_events'), 10, 3);

    add_action('wp_ultimo_payment_refunded', array($this, 'send_refund_issued_events'), 10, 3);

    add_action('wpmu_delete_user', array($this, 'send_account_delete_events'), 10, 1);

    add_action('wu_subscription_change_plan', array($this, 'send_plan_change_events'), 10, 3);

    /**
     * Allow plugin developers to add new webhook callers
     */
    do_action('wu_register_webhook_listeners');

  } // end register_webhook_listeners;

  /**
   * Sends the webhooks for the ACCOUNT_CREATED event
   *
   * @param integer $site_id
   * @param integer $user_id
   * @param array   $transient
   * @param WU_Plan $plan
   * @return void
   */
  public function send_account_created_events($site_id, $user_id, $transient, $plan) {

    // Attempt to get a subscription
    $subscription = wu_get_subscription($user_id);

    if (!$subscription) return;

    $user = $subscription->get_user();

    $data = array(
      'user_id'           => $user_id,
      'user_site_id'      => $site_id,
      'plan_id'           => (int) $subscription->plan_id,
      'billing_frequency' => (int) $subscription->freq,
      'price'             => $subscription->get_price_after_coupon_code(),
      'trial_days'        => $subscription->get_trial(),
      'user_login'        => $user->user_login,
      'user_email'        => $user->user_email,
      'user_site_url'     => get_site_url($site_id),
      'plan_name'         => $plan ? $plan->title : __('Invalid Plan', 'wp-ultimo'),
      'created_at'        => $subscription->created_at,
      'user_site_name'    => $transient['blog_title'],
      'user_site_slug'    => $transient['blogname'],
      'template_id'       => isset($transient['template']) ? $transient['template'] : 0,
      'user_firstname'    => isset($transient['first_name']) ? $transient['first_name'] : get_user_meta($user_id, 'first_name', true),
      'user_lastname'     => isset($transient['last_name']) ? $transient['last_name'] : get_user_meta($user_id, 'last_name', true),
    );

    $this->send_webhooks('account_created', apply_filters('wu_event_payload_account_created', $data, $user_id));

  } // end send_account_created_events;

  /**
   * Sends the webhooks for the NEW_DOMAIN_MAPPING event
   *
   * @param string  $url
   * @param integer $site_id
   * @param integer $user_id
   * @return void
   */
  public function send_new_domain_mapping_events($url, $site_id, $user_id) {

    $data = array(
      'user_id'           => $user_id,
      'user_site_id'      => $site_id,
      'mapped_domain'     => $url,
      'user_site_url'     => get_site_url($site_id),
      'network_ip'        => WU_Settings::get_setting('network_ip', isset($_SERVER['SERVER_ADDR']) ? $_SERVER['SERVER_ADDR'] : '0.0.0.0'),
    );

    $this->send_webhooks('new_domain_mapping', apply_filters('wu_event_payload_new_domain_mapping', $data, $user_id));

  } // end send_new_domain_mapping_events;

  /**
   * Sends the webhooks for the PAYMENT_RECEIVED event
   *
   * @param integer $user_id
   * @param string  $gateway
   * @param float   $amount
   * @return void
   */
  public function send_payment_successful_events($user_id, $gateway, $amount) {

    $data = array(
      'user_id'           => $user_id,
      'amount'            => $amount,           
      'gateway'           => $gateway,
      'status'            => 'successful',
      'date'              => WU_Transactions::get_current_time('mysql'),
    );

    $this->send_webhooks(array('payment_received', 'payment_successful'), apply_filters('wu_event_payload_payment_received', $data, $user_id));

  } // end send_payment_successful_events;

  /**
   * Sends the webhooks for the PAYMENT_FAILED event
   *
   * @param integer $user_id
   * @param string  $gateway
   * @param float   $amount
   * @return void
   */
  public function send_payment_failed_events($user_id, $gateway, $amount) {

    $data = array(
      'user_id'           => $user_id,
      'amount'            => $amount,           
      'gateway'           => $gateway,
      'status'            => 'failed',
      'date'              => WU_Transactions::get_current_time('mysql'),
    );

    $this->send_webhooks(array('payment_received', 'payment_failed'), apply_filters('wu_event_payload_payment_failed', $data, $user_id));

  } // end send_payment_failed_events;

  /**
   * Sends the webhooks for the REFUND_ISSUED event
   *
   * @param integer $user_id
   * @param string  $gateway
   * @param float   $amount
   * @return void
   */
  public function send_refund_issued_events($user_id, $gateway, $amount) {

    $data = array(
      'user_id'           => $user_id,
      'amount'            => $amount,           
      'gateway'           => $gateway,
      'status'            => 'refund',
      'date'              => WU_Transactions::get_current_time('mysql'),
    );

    $this->send_webhooks(array('payment_received', 'refund_issued'), apply_filters('wu_event_payload_refund_issued', $data, $user_id));

  } // end send_refund_issued_events;

  /**
   * Sends the webhooks for the ACCOUNT_DELETED event
   *
   * @param integer $user_id
   * @return void
   */
  public function send_account_delete_events($user_id) {

    $user = get_user_by('id', $user_id);

    $data = array(
      'user_id'           => $user_id,
      'user_name'         => $user->user_login,
      'user_email'        => $user->user_email,
      'date'              => WU_Transactions::get_current_time('mysql'),
    );

    $this->send_webhooks('account_deleted', apply_filters('wu_event_payload_account_deleted', $data, $user_id));

  } // end send_account_delete_events;
  
  /**
   * Sends the webhooks for the PLAN_CHANGE event
   *
   * @param WU_Subscription $subscription
   * @param WU_Plan $new_plan
   * @param WU_Plan $old_plan
   * @return void
   */
  public function send_plan_change_events($subscription, $new_plan, $old_plan) {

    $data = array(
      'user_id'               => $subscription->user_id,
      'new_price'             => (float) $subscription->price,
      'new_billing_frequency' => $subscription->freq,
      'old_plan_id'           => $old_plan->id,
      'new_plan_id'           => $new_plan->id,
      'old_plan_name'         => $old_plan->title,
      'new_plan_name'         => $new_plan->title,
      'date'                  => WU_Transactions::get_current_time('mysql'),
    );

    $this->send_webhooks('plan_change', apply_filters('wu_event_payload_plan_change', $data, $subscription->user_id));

  } // end send_plan_change_events;

  /**
   * Get all the available integrations, so we can display them as filters on the front-end
   *
   * @since 1.7.4
   * @param string $key
   * @return array
   */
  public function get_all_filters($key = 'wpu_integration') {

    global $wpdb;

    $results = $wpdb->get_col( 
      $wpdb->prepare( "
        SELECT DISTINCT pm.meta_value FROM {$wpdb->postmeta} pm
        LEFT JOIN {$wpdb->posts} p ON p.ID = pm.post_id
        WHERE pm.meta_key = '%s' && p.post_type = 'wpultimo_webhook' && pm.meta_value != ''
        AND p.post_status = 'publish'
        ORDER BY pm.meta_value", 
        $key
      ) 
    );
    
    $default = array(
      '*' => __('All'),
      '-' => __('Manual', 'wp-ultimo'),
    );

    return array_merge($default, array_combine($results, array_map(array($this, 'get_filter_name'), $results)));
  
  } // end get_all_filters;

  /**
   * Formats the integration name
   * 
   * @since 1.7.4
   * @param string $filter
   * @return string
   */
  public function get_filter_name($filter) {

    return ucwords(str_replace(array('-', '_'), '', $filter));

  } // end get_filter_name;

  /**
   * Saves new webhooks and edit existing ones
   *
   * @return void
   */
  public function get_webhooks() {

    check_ajax_referer('wu-updating-webhooks');

    $args = array();

    if (isset($_POST['filter']) && $_POST['filter'] !== '*') {

      $filter = esc_sql($_POST['filter']);
      
      $args['meta_query'] = array(
        'has_filter' => array(
          'key'       => 'wpu_integration',
          'compare'   => 'EXISTS',
        ),
        'filter'     => array(
          'key'       => 'wpu_integration',
          'compare'   => '=',
          'value'     => $filter != '-' ? $filter : '',
        ),
      );

    } // end if;

    wp_send_json(WU_Webhook::get_webhooks_for_js($args));

  } // end update_webhook;

  /**
   * Saves new webhooks and edit existing ones
   *
   * @return void
   */
  public function update_webhook() {

    check_ajax_referer('wu-updating-webhooks');

    if (!current_user_can('manage_network')) {

      wp_send_json(WU_Webhook::get_webhooks_for_js());

    } // end if;

    $webhook = new WU_Webhook($_POST['data']['id']);

    $webhook->set_attributes($_POST['data']);

    $webhook->save();

    wp_send_json(WU_Webhook::get_webhooks_for_js());

  } // end update_webhook;

  /**
   * Saves new webhooks and edit existing ones
   *
   * @return void
   */
  public function delete_webhook() {

    check_ajax_referer('wu-updating-webhooks');

    if (!current_user_can('manage_network')) {

      wp_send_json(WU_Webhook::get_webhooks_for_js());

    } // end if;

    foreach($_POST['data'] as $webhook_id) {

      $webhook = new WU_Webhook($webhook_id);

      $webhook->delete();

    } // foreach;

    wp_send_json(WU_Webhook::get_webhooks_for_js());

  } // end delete_webhook;

  /**
   * Senda test event of the webhook
   *
   * @return void
   */
  public function send_test_event() {

    check_ajax_referer('wu-updating-webhooks');

    if (!current_user_can('manage_network')) {

      wp_send_json(array(
        'response' => __('You do not have enough permissions to send a test event.', 'wp-ultimo'),
        'webhooks' => WU_Webhook::get_webhooks_for_js(),
      ));

    } // end if;

    $webhook = new WU_Webhook($_POST['data']['id']);

    // Get the URL
    $event = $webhook->event;
    $url   = $webhook->url;

    // Get the event
    $registered_event = $this->get_event($event);

    if ($registered_event) {
      
      $info_to_send = $registered_event['data'];

      $response = $this->send_event($event, $url, $info_to_send, $webhook->id, true);

    } else {
      
      $response = sprintf(__('An event named %s is not registered.', 'wp-ultimo'), $event);
      
    } // end if;

    wp_send_json(array(
      'response' => json_encode($response),
      'webhooks' => WU_Webhook::get_webhooks_for_js(),
    ));

  } // end send_test_event;

  /**
   * Serve Logs
   *
   * @return string
   */
  public function serve_logs() {

    echo "<style>
      body { 
        font-family: monospace;
        line-height: 20px;
      }
      pre { 
        background: #ececec;
        border: solid 1px #ccc;
        padding: 10px;
        border-radius: 3px;
      }
      hr {
        margin: 25px 0;
        border-top: 1px solid #cecece;
        border-bottom: transparent;
      }
    </style>";

    if (!current_user_can('manage_network')) {

      echo __('You do not have enough permissions to read the logs of this webhooks.', 'wp-ultimo');

      exit;

    } // end if;

    $id = abs($_REQUEST['id']);

    $logs = array_map(function($line) {

      $line = str_replace(' - ', ' </strong> - ', $line);
      
      $matches = array();

      $line = str_replace('\'', '\\\'', $line);
      $line = preg_replace('~(\{(?:[^{}]|(?R))*\})~', '<pre><script>document.write(JSON.stringify(JSON.parse(\'${1}\'), null, 2));</script></pre>', $line);

      return '<strong>' . $line . '<hr>';

    }, WU_Logger::read_lines("webhook-events-$id", 10));

    echo implode('', $logs);

    exit;

  } // end server_logs;

  /**
   * Add the admin interface to create new webhooks
   * 
   * @param array $fields Admin settings fields
   */
  public function add_settings($fields) {

    $new_fields = array(
      'integrations'    => array(
        'title'         => __('Integrations', 'wp-ultimo'),
        'desc'          => __('Handles WP Ultimo integrations with third-party services.', 'wp-ultimo'),
        'type'          => 'heading',
      ),

      'enable-webhooks' => array(
        'title'         => __('Enable Webhooks', 'wp-ultimo'),
        'desc'          => __('Tick this box if you want WP Ultimo to send event calls to webhooks. This is useful to allow integrations with services like Zapier, for example.', 'wp-ultimo'),
        'tooltip'       => '',
        'type'          => 'checkbox',
        'default'       => 1,
      ),
      
      'webhooks'        => array(
        'title'         => __('Webhooks', 'wp-ultimo'),
        'desc'          => __('Handles WP Ultimo integrations with third-party services.', 'wp-ultimo') . '<br><br><a style="font-style: normal;" class="button button-primary" href="'. network_admin_url('admin.php?page=wp-ultimo-webhooks') .'">'. __('Edit Webhooks &rarr;', 'wp-ultimo') .'</a>',
        'type'          => 'heading',
        'require'       => array('enable-webhooks' => 1)
      ),
      
      'webhook-calls-blocking' => array(
        'title'         => __('Wait for Response (Advanced)', 'wp-ultimo'),
        'desc'          => __('Tick this box if you want the WP Ultimo\'s webhook calls to wait for the remote server to respond. Keeping this option enabled can have huge effects on your network\'s performance, only enable it if you know what you are doing and need to debug webhook calls.', 'wp-ultimo'),
        'tooltip'       => '',
        'type'          => 'checkbox',
        'default'       => 0,
        'require'       => array('enable-webhooks' => 1)
      ),

    );

    return array_merge($fields, $new_fields);

  } // end add_settings;

  /**
   * Register a new webhook event to the list of supported avents
   *
   * @param string $slug
   * @param array  $args
   * @return WU_Webhooks
   */
  public function register_event($slug, $args) {

    // Check all the subjects
    $args = shortcode_atts(array(
      'type'        => $slug,
      'name'        => ucwords(str_replace('_', '', $slug)),
      'description' => __('No description', 'wp-ultimo'),
      'data'        => array()
    ), $args);

    // Add that to our events list
    $this->events[$slug] = $args;

    // Flip array
    $this->events[$slug]['data'] = $this->events[$slug]['data'];

    // Allow Chains
    return $this;

  } // end register_event;

  /**
   * Returns the list of event
   *
   * @since 1.6.0
   * @return array
   */
  function get_events() { return $this->events; }

  public function get_event($slug) { 

    // Get the template to send
    if (isset($this->events[$slug]))
      return $this->events[$slug];
    else return false;

  } // end get_event;

  /**
   * Decode JSON if the string is a valid json, otherwise, return the string
   *
   * @param string $string
   * @return mixed
   */
  public function maybe_json_decode($string) {
    
    $object = json_decode($string);

    return (json_last_error() == JSON_ERROR_NONE ? $object : $string);

  } // end maybe_json_decode;

  /**
   * Log the log event for future reference
   *
   * @param string $slug
   * @param array $data
   * @return void
   */
  public function log_event($slug, $id, $url, $data, $response) {

    $is_request_blocking = WU_Settings::get_setting('webhook-calls-blocking', false);

    $message  = sprintf('Sent a %s event to the URL %s with data: %s ', $slug, $url, json_encode($data));

    $message .= $is_request_blocking ? sprintf('Got response: %s', json_encode($response)) : 'To debug the remote server response, turn the "Wait for Reponse" option on the WP Ultimo Settings > Advanced Tab';

    WU_Logger::add("webhook-events-$id", $message);

  } // end log_event;

  /**
   * Sends all the active webhooks for a given event
   *
   * @param string|array $slug
   * @param array $data
   * @return void
   */
  public function send_webhooks($slug, $data) {

    $args = array(
      'meta_query'  => array(
        'event'     => array(
          'key'     => 'wpu_event',
          'value'   => $slug
        ),
        'active'    => array(
          'key'     => 'wpu_active',
          'value'   => 1,
        )
      ),
    );

    if (is_array($slug)) {

      // Handles arrays being passed as slug
      $args['meta_query']['event']['compare'] = 'IN';

    } // end if;

    foreach (WU_Webhook::get_webhooks($args) as $webhook) {

        $this->send_event($webhook->event, $webhook->url, $data, $webhook->id);

    } // end foreach;

  } // end get_events_to_send;

  /**
   * Send a particular event to the desired URL, with the relevant data
   *
   * @param string $event
   * @param string $url
   * @param array  $data
   * @return bool
   */
  public function send_event($slug, $url, $data, $id, $blocking = false) {

    // Get the template to send
    if (isset($this->events[$slug]))
      $event = $this->events[$slug];
    else return false;

    $data = array(
      'webhook_id' => $id,
      'type'       => $slug,
      'data'       => $data
    );

    $response = wp_remote_post($url, apply_filters('wu_send_event_data', array(
      'method'      => 'POST',
      'timeout'     => 45,
      'redirection' => 5,
      'headers'     => array(),
      'cookies'     => array(),
      'body'        => $data,
      'blocking'    => $blocking ? $blocking : WU_Settings::get_setting('webhook-calls-blocking', false),
    ), $slug));

    if ( is_wp_error( $response ) ) {
      return $response->get_error_message(); // TODO: Log error on the webhook
    } 

    // Get the body
    $response = $this->maybe_json_decode( wp_remote_retrieve_body($response) );

    // Log the event sent call
    $this->log_event($slug, $id, $url, $data, $response);

    /**
     * Increase sent count
     */
    $webhook = new WU_Webhook($id);

    if ($webhook) {

      $webhook->sent_events_count++;

      $webhook->save();

    } // end if;

    return $response;

  } // end send_event;

} // end class WU_Webhooks;

/**
 * Returns the singleton
 */
function WU_Webhooks() {
  return WU_Webhooks::get_instance();
}

// Initialize
WU_Webhooks();

/**
 *
 * Register the events
 * 
 */

/**
 * Account Created
 */
WU_Webhooks()->register_event('account_created', array(
  'name'        => __('Account Created', 'wp-ultimo'),
  'description' => __('This event is fired every time a new user registers using the registration flow.', 'wp-ultimo'),
  'data'        => array(
    'user_id'           => 1,
    'user_site_id'      => 1,
    'plan_id'           => 1,
    'trial_days'        => 10,
    'billing_frequency' => 12,
    'template_id'       => 1,
    'price'             => 9.99,
    'user_login'        => 'johndoe',
    'user_firstname'    => 'John',
    'user_lastname'     => 'Doe',
    'user_email'        => 'johndoe@acme.com',
    'user_site_url'     => 'http://test.mynetwork.com/',
    'plan_name'         => 'Medium',
    'created_at'        => '2018-06-30 10:23:25',
    'user_site_name'    => 'John\'s Blog',
    'user_site_slug'    => 'test',
  )
));

/**
 * Account Deleted
 */
WU_Webhooks()->register_event('account_deleted', array(
  'name'        => __('Account Deleted', 'wp-ultimo'),
  'description' => __('This event is fired every time an account is deleted from the network.', 'wp-ultimo'),
  'data'        => array(
    'user_id'           => 1,
    'user_name'         => 'johndoe',
    'user_email'        => 'johndoe@acme.com',
    'date'              => '2018-06-30 10:23:25',
  )
));

/**
 * New Domain Mapping Added
 */
WU_Webhooks()->register_event('new_domain_mapping', array(
  'name'        => __('New Domain Mapping Added', 'wp-ultimo'),
  'description' => __('This event is fired every time a new domain mapping is added by the user.', 'wp-ultimo'),
  'data'        => array(
    'user_id'           => 1,
    'user_site_id'      => 1,
    'mapped_domain'     => 'mydomain.com',
    'user_site_url'     => 'http://test.mynetwork.com/',
    'network_ip'        => '125.399.3.23',
  )
));

/**
 * Payment Received
 */
WU_Webhooks()->register_event('payment_received', array(
  'name'        => __('Payment Received', 'wp-ultimo'),
  'description' => __('This event is fired every time a new payment (successful, failed and refund) is received via a gateway.', 'wp-ultimo'),
  'data'        => array(
    'user_id'           => 1,
    'amount'            => 9.99,           
    'date'              => '2018-06-18 10:23:25',     
    'gateway'           => 'stripe',
    'status'            => 'successful',
  )
));

/**
 * Payment Successful
 */
WU_Webhooks()->register_event('payment_successful', array(
  'name'        => __('Sucessful Payment', 'wp-ultimo'),
  'description' => __('This event is fired every time a new successfully payment is received via a gateway.', 'wp-ultimo'),
  'data'        => array(
    'user_id'           => 1,
    'amount'            => 9.99,           
    'date'              => '2018-06-18 10:23:25',     
    'gateway'           => 'stripe',
    'status'            => 'successful',
  )
));

/**
 * Payment Failed
 */
WU_Webhooks()->register_event('payment_failed', array(
  'name'        => __('Failed Payment', 'wp-ultimo'),
  'description' => __('This event is fired every time a new failed payment is received via a gateway.', 'wp-ultimo'),
  'data'        => array(
    'user_id'           => 1,
    'amount'            => 9.99,           
    'date'              => '2018-06-18 10:23:25',     
    'gateway'           => 'stripe',
    'status'            => 'failed',
  )
));

/**
 * Refund Issued
 */
WU_Webhooks()->register_event('refund_issued', array(
  'name'        => __('Refund Issued', 'wp-ultimo'),
  'description' => __('This event is fired every time a new refund is issued via a gateway.', 'wp-ultimo'),
  'data'        => array(
    'user_id'           => 1,
    'amount'            => 9.99,           
    'date'              => '2018-06-18 10:23:25',     
    'gateway'           => 'stripe',
    'status'            => 'refund',
  )
));

/**
 * Plan Change
 */
WU_Webhooks()->register_event('plan_change', array(
  'name'        => __('Plan Change (Upgrade & Downgrade)', 'wp-ultimo'),
  'description' => __('This event is fired every time a user changes his or her subscription plan.', 'wp-ultimo'),
  'data'        => array(
    'user_id'               => 1,
    'old_plan_id'           => 1,
    'new_plan_id'           => 2,
    'new_price'             => 19.99,
    'new_billing_frequency' => 3,
    'old_plan_name'         => 'Medium',
    'new_plan_name'         => 'Pro',
    'date'                  => '2018-06-18 10:23:25',
  )
));

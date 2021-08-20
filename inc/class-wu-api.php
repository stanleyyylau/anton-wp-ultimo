<?php
/**
 * WP Ultimo API
 *
 * Handles the API
 *
 * @since       1.7.4
 * @author      Arindo Duque
 * @category    Admin
 * @package     WP_Ultimo/Plans
 * @version     0.0.1
*/

if (!defined('ABSPATH')) {
  exit;
}

class WU_API {

  /**
   * Namespace of our API endpoints
   *
   * @since 1.7.4
   * @var string
   */
  private $namespace = 'wp-ultimo';

  /**
   * Version fo the API, this is used to build the API URL
   *
   * @since 1.7.4
   * @var string
   */
  private $api_version = 'v1';

  /**
   * Initiates the API hooks
   * 
   * @since 1.7.4
   * @return void
   */
  public function __construct() {

    /**
     * Add the admin settings for the API
     * 
     * @since 1.7.4
     */
    add_filter('wu_settings_section_tools', array($this, 'add_settings'));

    /**
     * Refreshing API credentials
     * 
     * @since 1.7.4
     */
    add_action('wu_before_save_settings', array($this, 'refresh_API_credentials'));

    /**
     * Register the routes
     * @since 1.7.4
     */
    add_action('rest_api_init', array($this, 'register_routes'));

  } // end construct;

  /**
   * Allow admins to refresh their API credentials
   *
   * @since 1.7.4
   * @param array $data
   * @return void
   */
  public function refresh_API_credentials($data) {

    if (isset($data['refresh_api_crendentials']) && $data['refresh_api_crendentials']) {

      WU_Settings::save_setting('api-key', wp_generate_password(24));
      WU_Settings::save_setting('api-secret', wp_generate_password(24));

      wp_safe_redirect( network_admin_url('admin.php?page=wp-ultimo&wu-tab=tools') );

      exit;

    } // end end if;

  } // end refresh_API_credentials;

  /**
   * Add the admin interface to create new webhooks
   * 
   * @since 1.7.4
   * @param array $fields Admin settings fields
   */
  function add_settings($fields) {

    $new_fields = array(
      'api'        => array(
        'title'         => __('API Settings', 'wp-ultimo'),
        'desc'          => __('Handles WP Ultimo integrations with third-party services, via the API.', 'wp-ultimo'),
        'type'          => 'heading',
      ),

      'enable-api' => array(
        'title'         => __('Enable API', 'wp-ultimo'),
        'desc'          => __('Tick this box if you want WP Ultimo to add its own endpoints to the WordPress REST API. This is required for some integrations to work, most notabily, Zapier.', 'wp-ultimo'),
        'tooltip'       => '',
        'type'          => 'checkbox',
        'default'       => 1,
      ),

      'enable-api' => array(
        'title'         => __('Enable API', 'wp-ultimo'),
        'desc'          => __('Tick this box if you want WP Ultimo to add its own endpoints to the WordPress REST API. This is required for some integrations to work, most notabily, Zapier.', 'wp-ultimo'),
        'tooltip'       => '',
        'type'          => 'checkbox',
        'default'       => 1
      ),

      'api-key' => array(
        'title'         => __('API Key', 'wp-ultimo'),
        'desc'          => __('This is your API Key. You cannot change it directly. To reset the API key and secret, use the button "Refresh API credentials" below.', 'wp-ultimo'),
        'append'        => sprintf('<a href="#" class="wu-tooltip wu-copy-icon wu-copy-target" data-target="#api-key" title="%s"><span class="dashicons dashicons-admin-page"></span></a>', __('Copy to clipboard', 'wp-ultimo')),
        'tooltip'       => '',
        'type'          => 'text',
        'default'       => wp_generate_password(24),
        'require'       => array('enable-api' => 1),
        'html_attr'     => array(
          'disabled'     => 'disabled'
        )
      ),

      'api-secret' => array(
        'title'         => __('API Secret', 'wp-ultimo'),
        'desc'          => sprintf('<a class="button" onclick="%s">%s</a>', 'return document.getElementById(\'api-secret\').type = \'text\';', __('Reveal', 'wp-ultimo')) . ' ' . sprintf('<button class="button" name="refresh_api_crendentials" value="1">%s</a>', __('Refresh API credentials', 'wp-ultimo')),
        'append'        => sprintf('<a href="#" class="wu-tooltip wu-copy-icon wu-copy-target" data-target="#api-secret" title="%s"><span class="dashicons dashicons-admin-page"></span></a>', __('Copy to clipboard', 'wp-ultimo')),
        'tooltip'       => '',
        'type'          => 'password',
        'default'       => wp_generate_password(24),
        'require'       => array('enable-api' => 1),
        'html_attr'     => array(
          'disabled'     => 'disabled'
        )
      ),
      
      'api-log-calls' => array(
        'title'         => __('Log API calls (Advanced)', 'wp-ultimo'),
        'desc'          => __('Tick this box if you want to log all calls received via WP Ultimo API endpoints. You can access the logs on WP Ultimo &rarr; System Info &rarr; Logs.', 'wp-ultimo'),
        'tooltip'       => '',
        'type'          => 'checkbox',
        'default'       => 0,
        'require'       => array('enable-api' => 1)
      ),

    );

    return array_merge($fields, $new_fields);

  } // end add_settings;

  /**
   * Returns the namespace of our API endpoints
   *
   * @since 1.7.4
   * @return void
   */
  public function get_namespace() {
    
    return "$this->namespace/$this->api_version";
    
  } // end get_namespace;
  
  /**
   * Returns the credentials
   *
   * @since 1.7.4
   * @return array
   */
  public function get_auth() {

    return array(
      'api_key'    => WU_Settings::get_setting('api-key', 'prevent'),
      'api_secret' => WU_Settings::get_setting('api-secret', 'prevent'),
    );

  } // end get_auth;

  /**
   * Validate a pair of API credentials
   *
   * @since 1.7.4
   * @param string $api_key
   * @param string $api_secret
   * @return boolean
   */
  public function validate_credentials($api_key, $api_secret) {

    return compact('api_key', 'api_secret') == $this->get_auth();

  } // end validate_credentials;

  /**
   * Checks if we should log api calls or not, and if we should, log them
   *
   * @since 1.7.4
   * @param object $request
   * @return void
   */
  public function maybe_log_api_calls($request) {

    if (apply_filters('wu_should_log_api_calls', WU_Settings::get_setting('api-log-calls', false))) {

      $payload = array(
        'route'   => $request->get_route(),
        'method'  => $request->get_method(),
        'params'  => $request->get_params(),
      );

      WU_Logger::add("api-calls", json_encode($payload));

    } // end if;

  } // end maybe_log_api_calls;

  /**
   * Tries to validate the API key and secret from the request
   *
   * @since 1.7.4
   * @param WP_REST_Request $request
   * @return boolean
   */
  public function check_authorization($request) {

    $this->maybe_log_api_calls($request);

    if (!isset($_SERVER['PHP_AUTH_USER'])) return false;

    return $this->validate_credentials($_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW']);

  } // end check_authorization;

  /**
   * Checks if the API routes are available or not, via the settings
   *
   * @since 1.7.4
   * @return boolean
   */
  public function is_api_enabled() {

    /**
     * Allow plugin developers to force a given state for the API
     * 
     * @since 1.7.4
     * @return boolean
     */
    return apply_filters('wu_is_api_enabled', WU_Settings::get_setting('enable-api', true));

  } // end is_api_enabled;

  /**
   * Register the API routes
   *
   * @since 1.7.4
   * @return void
   */
  public function register_routes() {

    if (!$this->is_api_enabled()) return;

    $namespace = $this->get_namespace();

    register_rest_route($namespace, '/auth', array(
      'methods'             => 'GET',
      'callback'            => array($this, 'auth'),
      'permission_callback' => array($this, 'check_authorization'),
    ));

    register_rest_route($namespace, '/hooks', array(
      'methods'             => 'POST',
      'callback'            => array($this, 'add_hook'),
      'permission_callback' => array($this, 'check_authorization'),
    ));

    register_rest_route($namespace, '/hooks', array(
      'methods'             => 'DELETE',
      'callback'            => array($this, 'remove_hook'),
      'permission_callback' => array($this, 'check_authorization'),
    ));

    register_rest_route($namespace, '/hook-sample', array(
      'methods'             => 'GET',
      'callback'            => array($this, 'sample_hook'),
      'permission_callback' => array($this, 'check_authorization'),
    ));

  } // end register_routes;

  /**
   * Dummy endpoint toa low services to test the authentication method being used
   *
   * @since 1.7.4
   * @return void
   */
  public function auth($request) {

    $current_site = get_current_site();

    wp_send_json(array(
      'success' => true,
      'label'   => $current_site->site_name,
      'message' => __('Welcome to our API', 'wp-ultimo'),
    ));

  } // end auth;

  public function add_hook($request) {

    $webhook = new WU_Webhook;

    $body = $request->get_params();

    $webhook->set_attributes(array(
      'name'        => sprintf("%s - %s", ucfirst($body['from']), $body['event']),
      'url'         => $body['url'],
      'event'       => $body['event'],
      'integration' => $body['from'],
      'active'      => true,
    ));

    $id = $webhook->save();

    wp_send_json(array(
      'success' => true,
      'id'      => $id,
    ));

  } // end add_hook;

  /**
   * Remove a hook registered by Zapier
   * 
   * @since 1.7.4
   * @return void
   */
  public function remove_hook($request) {
    
    $body = $request->get_params();
    
    WU_Logger::add("api", json_encode($body));

    $webhook = new WU_Webhook($body['id']);

    $webhook->delete();

    wp_send_json(array(
      'success' => true,
      'id'      => $body['id'],
    ));

  } // end remove_hook;

  /**
   * Send sample data to Zapier and other services
   * 
   * @since 1.7.4
   * @return void
   */
  public function sample_hook($request) {

    $event = $request->get_param('event');

    $registered_event = WU_Webhooks()->get_event($event);

    if ($registered_event) {
      
      $info_to_send = $registered_event['data'];

    } else {
      
      $response = sprintf(__('An event named %s is not registered.', 'wp-ultimo'), $event);
      
    } // end if;

    wp_send_json(array($info_to_send));

  } // end sample_hook;

} // end class WU_API;

new WU_API;
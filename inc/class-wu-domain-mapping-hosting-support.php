<?php
/**
 * Handles Hosting Support
 *
 * @since       1.6.0 Adds custom hooks to support hosting providers
 * @author      Arindo Duque
 * @category    Admin
 * @package     WP_Ultimo/Domains
 * @version     0.0.1
*/

if (!defined('ABSPATH')) {
  exit;
}

class WU_Domain_Mapping_Hosting_Support {

  /**
   * Add the hooks for the various hosting providers we support
   */
  public function __construct() {

    /**
     * WP Engine Domain Mapping Integration
     * @since 1.6.0
     */
    add_action('mercator.mapping.created', array($this, 'add_domain_to_wpengine'), 20);
    add_action('mercator.mapping.updated', array($this, 'add_domain_to_wpengine'), 20);
    add_action('mercator.mapping.deleted', array($this, 'remove_domain_from_wpengine'), 20);

    /**
     * Cloudways Domain Mapping Integration
     * @since 1.6.1
     */
    if ($this->uses_cloudways()) {

      add_action('wp_ajax_update_cloudways_via_ajax', array($this, 'update_cloudways_async'));
      add_action('mercator.mapping.created', array($this, 'enqueue_cloudways_sync_script'));
      add_action('mercator.mapping.updated', array($this, 'enqueue_cloudways_sync_script'));
      add_action('mercator.mapping.deleted', array($this, 'enqueue_cloudways_sync_script'));
      add_action('admin_init', array($this, 'maybe_enqueue_cloudways_sync_script'));

    } // end if;

    /**
     * CPanel Support
     * @since 1.6.2
     */
    if ($this->uses_cpanel()) {

      add_action('mercator.mapping.created', array($this, 'add_domain_to_cpanel'), 20);
      add_action('mercator.mapping.updated', array($this, 'add_domain_to_cpanel'), 20);
      add_action('mercator.mapping.deleted', array($this, 'remove_domain_from_cpanel'), 20);
      add_action('wpmu_new_blog', array($this, 'add_subdomain_to_cpanel'), 20, 4);

    } // end if;
    
    /**
     * RunCloud.io Support
     * @since 1.7.0
     */
    if ($this->uses_runcloud()) {

      add_action('mercator.mapping.created', array($this, 'add_domain_to_runcloud'), 20);
      add_action('mercator.mapping.updated', array($this, 'add_domain_to_runcloud'), 20);
      add_action('mercator.mapping.deleted', array($this, 'remove_domain_from_runcloud'), 20);

    } // end if;

    /**
     * Closte.com Support
     * @since 1.7.3
     */
    if ($this->uses_closte()) {

      add_action('mercator.mapping.created', array($this, 'add_domain_to_closte'), 20);
      add_action('mercator.mapping.updated', array($this, 'add_domain_to_closte'), 20);
      add_action('mercator.mapping.deleted', array($this, 'remove_domain_from_closte'), 20);
      add_action('wu_custom_domain_after', array($this, 'display_closte_domain_status'));

    } // end if;

    /**
     * ServerPilot.io Support
     * @since 1.7.4
     */
    if ($this->uses_server_pilot()) {

      add_action('mercator.mapping.created', array($this, 'add_domain_to_server_pilot'), 20);
      add_action('mercator.mapping.updated', array($this, 'add_domain_to_server_pilot'), 20);
      add_action('mercator.mapping.deleted', array($this, 'remove_domain_from_server_pilot'), 20);
      add_action('wpmu_new_blog', array($this, 'add_subdomain_to_server_pilot'), 5, 4);

    } // end if;

  } // end construct;

   /**
   * Checks if this site is hosted on ServerPilot.io or not
   *
   * @since 1.7.4
   * @return bool
   */
  public function uses_server_pilot() {

    return defined('WU_SERVER_PILOT') && WU_SERVER_PILOT;

  } // end uses_server_pilot;

  /**
   * Sends a request to Closte, with the right API key
   *
   * @since  1.7.3
   * @param  string $endpoint Endpoint to send the call to
   * @param  array  $data     Array containing the params to the call
   * @return object
   */
  public function send_server_pilot_api_request($endpoint, $data = array(), $method = 'POST') {

    $post_fields = array(
      'timeout'     => 45,
      'blocking'    => true,
      'method'      => $method,
      'body'        => $data ? json_encode($data) : array(),
      'headers' => array(
        'Authorization' => 'Basic ' . base64_encode(WU_SERVER_PILOT_CLIENT_ID . ':' . WU_SERVER_PILOT_API_KEY),
        'Content-Type'  => 'application/json',
      ),
    );

    $response = wp_remote_request('https://api.serverpilot.io/v1/apps/'. WU_SERVER_PILOT_APP_ID . $endpoint, $post_fields);

    if (!is_wp_error($response)) {
      
      $body = json_decode(wp_remote_retrieve_body($response), true);

      if (json_last_error() === JSON_ERROR_NONE) return $body;

    } // end if;

    return $response;

  } // end send_server_pilot_api_request;

  /**
   * Makes sure ServerPilot autoSSL is always on, when possible
   *
   * @since 1.7.4
   * @return void
   */
  public function turn_server_pilot_auto_ssl_on() {

    return $this->send_server_pilot_api_request('/ssl', array(
      'auto' => true,
    ));

  } // end turn_server_pilot_auto_ssl_on;

  /**
   * Get the current list of domains added on Server Pilot
   * 
   * @since 1.7.4
   * @return mixed
   */
  public function get_server_pilot_domains() {

    $app_info = $this->send_server_pilot_api_request('', array(), 'GET');

    if (isset($app_info['data']['domains'])) {
      
      return $app_info['data']['domains']; 

    } // end if;

    /**
     * Log response so we can see what went wrong
     */
    WU_Logger::add('server-pilot', sprintf(__('A error happening trying to get the current list of domains: %s', 'wp-ultimo'), json_encode($app_info)));

    return false;

  } // end get_server_pilot_domains;

  /**
   * Add subdomains to ServerPilot as well so they can leverage autoSSL
   *
   * @since 1.7.4
   * @param int $blog_id
   * @param int $user_id
   * @param string $domain
   * @param string $path
   * @return void
   */
  public function add_subdomain_to_server_pilot($blog_id, $user_id, $domain, $path) {

    if (!$this->uses_server_pilot() || ! $domain || ! is_subdomain_install()) {
			return;
    }

    $current_domain_list = $this->get_server_pilot_domains();

    if ($current_domain_list && is_array($current_domain_list)) {

      $this->send_server_pilot_api_request('', array(
        'domains' => array_merge($current_domain_list, array($domain)),
      ));

      /**
       * Makes sure autoSSL is always on
       */
      $this->turn_server_pilot_auto_ssl_on();

    } // end if;

  } // end add_subdomain_to_server_pilot;

  /**
   * Add domain to ServerPilot.io
   *
   * @since 1.7.4
   * @param  Mercator\Mapping $mapping
   * @return void
   */
  public function add_domain_to_server_pilot($mapping) {

    $domain = $mapping->get_domain();
    
		if (!$this->uses_server_pilot() || ! $domain) {
			return;
    }

    $current_domain_list = $this->get_server_pilot_domains();

    if ($current_domain_list && is_array($current_domain_list)) {

      $this->send_server_pilot_api_request('', array(
        'domains' => array_merge($current_domain_list, array($domain, 'www.'.$domain)),
      ));

      /**
       * Makes sure autoSSL is always on
       */
      $this->turn_server_pilot_auto_ssl_on();

    } // end if;

  } // end add_domain_to_server_pilot;

  /**
   * Removes a mapped domain from ServerPilot.io
   *
   * @since 1.7.4
   * @param  Mercator\Mapping $mapping
   * @return void
   */
  public function remove_domain_from_server_pilot($mapping) {

    $domain = $mapping->get_domain();
    
		if (!$this->uses_server_pilot() || ! $domain) {
			return;
    }

    $current_domain_list = $this->get_server_pilot_domains();

    if ($current_domain_list && is_array($current_domain_list)) {

      /**
       * Removes the current domain fromt he domain list
       */
      $current_domain_list = array_filter($current_domain_list, function($remote_domain) use($domain) {

        return $remote_domain !== $domain && $remote_domain !== 'www.'.$domain;

      });

      $this->send_server_pilot_api_request('', array(
        'domains' => $current_domain_list
      ));

    } // end if;

  } // end remove_domain_from_server_pilot;

  /**
   * Display the Closte Domain Status
   *
   * @since 1.7.3
   * @param string $domain
   * @return void
   */
  public function display_closte_domain_status($domain) {

    if (!$this->uses_closte() || ! $domain) {
			return;
    }

    add_thickbox();

    printf('<a href="%s&TB_iframe=true&width=800&height=700" title="%s" class="thickbox">%s</a>', "https://app.closte.com/api/client/domainsiframewpultimo?apikey=". urlencode(CLOSTE_CLIENT_API_KEY) ."&domains=" . urlencode($domain), __('Mapped Domain Status', 'wp-ultimo'), __('Check Domain Status &rarr;', 'wp-ultimo'));

  } // end display_closte_domain_status;

  /**
   * Checks if this site is hosted on Closte.com or not
   *
   * @since 1.7.3
   * @return bool
   */
  public function uses_closte() {

    return defined('CLOSTE_CLIENT_API_KEY') && CLOSTE_CLIENT_API_KEY;

  } // end uses_closte;

  /**
   * Sends a request to Closte, with the right API key
   *
   * @since  1.7.3
   * @param  string $endpoint Endpoint to send the call to
   * @param  array  $data     Array containing the params to the call
   * @return object
   */
  public function send_closte_api_request($endpoint, $data) {

    $post_fields = array(
      'blocking'    => true,
      'timeout'     => 45,
      'method'      => 'POST',
      'body'        => array_merge(array(
        'apikey'       => CLOSTE_CLIENT_API_KEY,
      ), $data)
    );

    $response = wp_remote_post('https://app.closte.com/api/client'.$endpoint, $post_fields);

      if (!is_wp_error($response)) {
        
        $body = json_decode(wp_remote_retrieve_body($response), true);

        if (json_last_error() === JSON_ERROR_NONE) {

            return $body;

        } // end if;

        return (object) array(
          'success' => false, 
          'error'   => 'unknown'
        );

      } // end if;

      return $response;

  } // end send_closte_api_request;

  /**
   * Sends call to Closte to add the new domain
   *
   * @since 1.7.3
   * @param Mercator\Mapping $mapping
   * @return void
   */
  public function add_domain_to_closte($mapping) {

    $domain = $mapping->get_domain();
    
		if (!$this->uses_closte() || ! $domain) {
			return;
    }      

    $this->send_closte_api_request('/adddomainalias', array(
      'domain'   => $domain,
      'wildcard' => strpos($domain, '*.') === 0
    ));

  } // end add_domain_to_closte;

  /**
   * Sends call to Closte to remove a domain
   *
   * @since 1.7.3
   * @param Mercator\Mapping $mapping
   * @return void
   */
  public function remove_domain_from_closte($mapping) {

    $domain = $mapping->get_domain();
    
		if (!$this->uses_closte() || ! $domain) {
			return;
    }

    $this->send_closte_api_request('/deletedomainalias', array(
      'domain'   => $domain,
      'wildcard' => strpos($domain, '*.') === 0
    ));

  } // end add_domain_to_closte;

  /**
   * Returns an array of all the mapped domains currently on the network
   *
   * @since 1.6.0
   * @return array
   */
  public function get_all_mapped_domains() {

    global $wpdb;

    // Prepare the query
    $query = "SELECT domain FROM {$wpdb->dmtable}";

		// Suppress errors in case the table doesn't exist
		$suppress = $wpdb->suppress_errors();
		$mappings = $wpdb->get_col($query, 0);
    $wpdb->suppress_errors( $suppress );
    
    return $mappings;

  } // end get_all_mapped_domains;

  /**
   * Get extra domains for Cloudways
   *
   * @since 1.6.1
   * @return array
   */
  public function get_extra_domains_for_cloudways() {

    $extra_domains = defined('WU_CLOUDWAYS_EXTRA_DOMAINS') && WU_CLOUDWAYS_EXTRA_DOMAINS;

    return $extra_domains ? array_filter( array_map('trim', explode(',', WU_CLOUDWAYS_EXTRA_DOMAINS) ) ): array();

  } // end get_extra_domains_for_cloudways;

  /**
   * Determine if we need to make update calls to Cloudways
   *
   * @since 1.6.0
   * @return bool
   */
  public function uses_cloudways() {

    return defined('WU_CLOUDWAYS') && WU_CLOUDWAYS;

  } // end uses_cloudways;

  /**
   * Request a Cloudways API Key to later updates and caches it
   *
   * @since 1.6.0
   * @return string
   */
  public function get_cloudways_access_token() {

    $token = get_site_transient('wu_cloudways_token');

    if (!$token) {

      $response = wp_remote_post('https://api.cloudways.com/api/v1/oauth/access_token', array(
        'blocking'    => true,
        'method'      => 'POST',
        'headers'     => array(
          "cache-control" => "no-cache",
          "content-type"  => "application/x-www-form-urlencoded",
        ),
        'body'        => array(
          'email'     => defined('WU_CLOUDWAYS_EMAIL') ? WU_CLOUDWAYS_EMAIL : '',
          'api_key'   => defined('WU_CLOUDWAYS_API_KEY') ? WU_CLOUDWAYS_API_KEY : '',
        ),
      ));

      if (!is_wp_error($response)) {
        
        $body = json_decode(wp_remote_retrieve_body($response), true);

        if (isset($body['access_token'])) {

          $expires_in = isset($body['expires_in']) ? $body['expires_in'] : 50 * MINUTE_IN_SECONDS;

          set_site_transient('wu_cloudways_token', $body['access_token'], $expires_in);

          $token = $body['access_token'];

        } // end if;

      } // end if;

    } // end if;

    return $token;

  } // end get_cloudways_access_token;

  /**
   * Handles domains added via the admin panel for Cloudways
   *
   * @since 1.6.1
   * @return void
   */
  public function maybe_enqueue_cloudways_sync_script() {

    if (isset($_GET['action']) && $_GET['action'] == 'mercator-aliases' && isset($_GET['did_action']) ) {

      $this->enqueue_cloudways_sync_script();

    } // end if;

  } // end maybe_enqueue_cloudways_sync_script;

  /**
   * Cloudways was giving 502 errors on our API calls from the server, so we moved this call to the front-end
   * Not ideia, but it works...
   *
   * @since 1.6.1
   * @return void
   */
  public function enqueue_cloudways_sync_script() {

    if (!$this->uses_cloudways()) return;

    $domains = array_merge($this->get_extra_domains_for_cloudways(), $this->get_all_mapped_domains());

    $suffix = WP_Ultimo()->min;

    wp_register_script('wu-domain-mapping', WP_Ultimo()->get_asset("wu-domain-mapping$suffix.js", 'js'), array('jquery'), WP_Ultimo()->version, true);

    wp_localize_script('wu-domain-mapping', 'wu_dm_settings', array(
      'url'       => 'https://api.cloudways.com/api/v1/app/manage/aliases',
      'token'     => $this->get_cloudways_access_token(),
      'data'      => http_build_query(array(
          'server_id' => defined('WU_CLOUDWAYS_SERVER_ID') ? WU_CLOUDWAYS_SERVER_ID : '',
          'app_id'    => defined('WU_CLOUDWAYS_APP_ID') ? WU_CLOUDWAYS_APP_ID : '',
          'aliases'   => $domains,
      ))
    ));

    wp_enqueue_script('wu-domain-mapping');

  } // end enqueue_cloudways_sync_script

  /**
   * Loads the WP Engine API to make sure we can add domains in the user panel
   *
   * @since 1.6.0
   * @return bool
   */
	public function load_wpengine_api() {

		if (!class_exists('WPE_API')) {

			// if WPEngine is not defined, then return
			if (!defined('WPE_PLUGIN_DIR') || !is_readable(WPE_PLUGIN_DIR . '/class-wpeapi.php')) {

        return false;
        
			} // end if;

      include_once WPE_PLUGIN_DIR . '/class-wpeapi.php';
      
			if (!class_exists('WPE_API')) {

        return false;
        
      } // end if;
      
		} // end if;

    return true;
    
	} // end load_wpengine_api;

	/**
   * Adds a new domain to the WP Engine panel 
   *
   * @since 1.6.0
   * @param Mercator/Mapping $mapping
   * @return void
   */
	public function add_domain_to_wpengine($mapping) {

    $domain = $mapping->get_domain();
    
    // return if we can't locate WPEngine API class
		if (!$this->load_wpengine_api() || ! $domain) {
			return;
		}

		// add domain to WPEngine
		$api = new WPE_API();

		// set the method and domain
		$api->set_arg('method', 'domain');
		$api->set_arg('domain', $domain);

		// do the api request
    $api->get();
    
	} // end add_domain_to_wpengine;

  /**
   * Removes a domain to the WP Engine panel 
   *
   * @since 1.6.0
   * @param Mercator/Mapping $mapping
   * @return void
   */
	public function remove_domain_from_wpengine($mapping) {
    
    $domain = $mapping->get_domain();
    
    // return if we can't locate WPEngine API class
		if (!$this->load_wpengine_api() || ! $domain) {
			return;
		}

		// add domain to WPEngine
		$api = new WPE_API();

		// set the method and domain
		$api->set_arg('method', 'domain-remove');
		$api->set_arg('domain', $domain);

		// do the api request
    $api->get();
    
  } // end remove_domain_from_wpengine;

  /**
   * Checks if we need to send CPanel calls
   *
   * @since 1.6.2
   * @return bool
   */
  public function uses_cpanel() {

    return defined('WU_CPANEL') && WU_CPANEL;

  } // end uses_cpanel;
  
  /**
   * Loads the CPanel API and connects using the credentials set as constants on wp-config.php
   *
   * @since 1.6.2
   * @return WU_CPanel
   */
  public function load_cpanel_api() {

    require_once WP_Ultimo()->path('inc/hosting/class-wu-cpanel.php');

    $username = defined('WU_CPANEL_USERNAME') ? WU_CPANEL_USERNAME : '';
    $password = defined('WU_CPANEL_PASSWORD') ? WU_CPANEL_PASSWORD : '';
    $host     = defined('WU_CPANEL_HOST')     ? WU_CPANEL_HOST : '';
    $port     = defined('WU_CPANEL_PORT')     ? WU_CPANEL_PORT : 2083;

    return new WU_CPanel($username, $password, preg_replace('#^https?://#', '', $host), $port);

  } // end load_cpanel_api;

	/**
   * Adds a new domain to the CPanel Aliases panel 
   *
   * @since 1.6.2
   * @param Mercator/Mapping $mapping
   * @return void
   */
	public function add_domain_to_cpanel($mapping) {

    $domain = $mapping->get_domain();
    
    // Checks if we use CPANEL
		if (!$this->uses_cpanel() || ! $domain) {
			return;
		}

		// Loads the API
    $cpanel = $this->load_cpanel_api();
    
    // Root Directory
    // TODO: Maybe use filter_input(INPUT_SERVER, 'DOCUMENT_ROOT') in the future to automatically pick up the root_dir
    $root_dir = defined('WU_CPANEL_ROOT_DIR') && WU_CPANEL_ROOT_DIR ? WU_CPANEL_ROOT_DIR : '/public_html';

    // Send Request
		$results = $cpanel->api2('AddonDomain', 'addaddondomain', array(
      'dir'       => $root_dir,
      'newdomain' => $domain,
      'subdomain' => $this->get_subdomain($domain),
    ));

    // Check the results
    $this->log_cpanel_api_call($results);
    
  } // end add_domain_to_cpanel;

  /**
   * Add a subdomain to cpanel on site creation
   *
   * @since 1.7.4
   * @param int $blog_id
   * @param int $user_id
   * @param string $domain
   * @param string $path
   * @return void
   */
	public function add_subdomain_to_cpanel($blog_id, $user_id, $domain, $path) {
    
    // Checks if we use CPANEL
		if (!$this->uses_cpanel() || ! $domain || ! is_subdomain_install()) {
			return;
		}

		// Loads the API
    $cpanel = $this->load_cpanel_api();
    
    // Root Directory
    // TODO: Maybe use filter_input(INPUT_SERVER, 'DOCUMENT_ROOT') in the future to automatically pick up the root_dir
    $root_dir = defined('WU_CPANEL_ROOT_DIR') && WU_CPANEL_ROOT_DIR ? WU_CPANEL_ROOT_DIR : '/public_html';

    // Send Request
	  $results = $cpanel->api2('SubDomain', 'addsubdomain', array(
      'dir'        => $root_dir,
      'domain'     => $this->get_subdomain($domain, false),
      'rootdomain' => $this->get_site_url(),
    ));

    // Check the results
    $this->log_cpanel_api_call($results);
    
  } // end add_subdomain_to_cpanel;

  /**
   * Returns the subdomain version of the domain
   *
   * @since 1.6.2
   * @param string $domain
   * @param string $mapped_domain If this is a mapped domain.
   * @return string
   */
  public function get_subdomain($domain, $mapped_domain = true) {

    if ($mapped_domain == false) {

      $domain_parts = explode('.', $domain);
      
      return array_shift($domain_parts);

    } // end if;

    $subdomain = str_replace(array('.', '/'), '', $domain);

    return $subdomain;

  } // end get_subdomain;

  /**
   * Returns the Site URL
   * 
   * @since  1.6.2
   * @return string
   */
  public function get_site_url() {

    return trim(preg_replace('#^https?://#', '', get_site_url()), '/');

  } // end get_site_url;
  
	/**
   * Removes a domain to the CPanel Aliases panel 
   *
   * @since 1.6.2
   * @param Mercator/Mapping $mapping
   * @return void
   */
	public function remove_domain_from_cpanel($mapping) {

    $domain = $mapping->get_domain();
    
    // Checks if we use CPANEL
		if (!$this->uses_cpanel() || ! $domain) {
			return;
		}

		// Loads the API
		$cpanel = $this->load_cpanel_api();

    // Send Request
		$results = $cpanel->api2('AddonDomain', 'deladdondomain', array(
       'domain'    => $domain,
       'subdomain' => $this->get_subdomain($domain) . '_' . $this->get_site_url(),
    ));

		// $results = $cpanel->api2('Park', 'unpark', array(
    //    'domain' => $domain
    // ));
    
    // Check the results
    $this->log_cpanel_api_call($results);
    
  } // end remove_domain_from_cpanel;
  
  /**
   * Logs the results of the calls for debugging purposes
   *
   * @since 1.6.2
   * @param object $results
   * @return void
   */
  public function log_cpanel_api_call($results) {

    if (is_object($results->cpanelresult->data)) {

      return WU_Logger::add('cpanel', $results->cpanelresult->data->reason);

    } else if (!isset($results->cpanelresult->data[0])) {

      return WU_Logger::add('cpanel', __('Unexpected error ocurred trying to sync domains with CPanel', 'wp-ultimo'));

    } // end if;

    return WU_Logger::add('cpanel', $results->cpanelresult->data[0]->reason);

  } // end handle_cpanel_error;

  /**
   * Determine if we need to make update calls to RunCloud.io
   *
   * @since 1.7.0
   * @return bool
   */
  public function uses_runcloud() {

    return defined('WU_RUNCLOUD') && WU_RUNCLOUD;

  } // end uses_runcloud;

  /**
   * Returns the base domain API url to our calls
   * 
   * @since 1.7.0
   * @return string
   */
  public function get_runcloud_base_url($path = '') {

    $serverid = defined('WU_RUNCLOUD_SERVER_ID') ? WU_RUNCLOUD_SERVER_ID : '';
    $appid    = defined('WU_RUNCLOUD_APP_ID') ? WU_RUNCLOUD_APP_ID : '';
    
    return "https://manage.runcloud.io/api/v2/servers/{$serverid}/webapps/{$appid}/{$path}";

  } // end get_runcloud_base_url;

  /**
   * Sends the request to a given runcloud URL with a given body
   * 
   * @since 1.7.0
   * @return mixed
   */
  public function send_runcloud_request($url, $data, $method = 'POST') {

    $username = defined('WU_RUNCLOUD_API_KEY') ? WU_RUNCLOUD_API_KEY : '';
    $password = defined('WU_RUNCLOUD_API_SECRET') ? WU_RUNCLOUD_API_SECRET : '';

    $response = wp_remote_request($url, array(
      'timeout'     => 100,
      'redirection' => 5,
      'body'        => $data,
      'method'      => $method,
      'headers'     => array(
        'Authorization' => 'Basic ' . base64_encode($username . ':' . $password),
      ),
    ));

    return $response;

  } // end send_runcloud_request;

  /**
   * Treats the response, maybe returning the json decoded version
   * 
   * @since 1.7.0
   * @return mixed
   */
  public function maybe_return_runcloud_body($response) {

    if (is_wp_error( $response )) {

      return $response->get_error_message();

    } else {

      return json_decode( wp_remote_retrieve_body($response) );

    } // end if;

  } // end maybe_return_runcloud_body;

  /**
   * Add a domain to Runcloud.io on mapping
   * 
   * @since 1.7.0
   * @return void
   */
  public function add_domain_to_runcloud($mapping) {
    
    $domain = $mapping->get_domain();
    
		if (!$this->uses_runcloud() || ! $domain) {
			return;
    }

    $domain_list = array($domain);

    if (strpos($domain, 'www.') !== 0) {
       
      $domain_list[] = "www.$domain";

    } // end if;

    $success = false;
    
    foreach($domain_list as $domain) {

      $response = $this->send_runcloud_request($this->get_runcloud_base_url('domains'), array(
        'name' => $domain
      ), 'POST');

      if (is_wp_error( $response )) {

        WU_Logger::add('runcloud', $response->get_error_message());

      } else {
        
        $success = true; // At least one of the calls was successful;

        WU_Logger::add('runcloud', wp_remote_retrieve_body($response));

      } // end if;

    } // end foreach;

    /**
     * Only redeploy SSL if at least one of the domains were successfully added
     */
    if ($success) {

      $ssl_id = $this->get_runcloud_ssl_id();

      if ($ssl_id) {

        $this->redeploy_runcloud_ssl($ssl_id);

      } // end if;

    } // end if;

  } // end add_domain_to_runcloud;

  /**
   * Returns the RunCloud.io domain id to remove
   * 
   * @since 1.7.0
   * @return string
   */
  public function get_runcloud_domain_id($domain) {

    $domains_list = $this->send_runcloud_request($this->get_runcloud_base_url('domains'), array(), 'GET');

    $list = $this->maybe_return_runcloud_body($domains_list);

    if (is_object($list) && !empty($list->data)) {

      foreach ($list->data as $remote_domain) {

        if ($remote_domain->name == $domain) return $remote_domain->id;

      }

    } // end if;

    return false;

  } // end get_runcloud_domain_id;

  /**
   * Removes a domain from the RunCloud panel
   * 
   * @since 1.7.0
   * @return void
   */
  public function remove_domain_from_runcloud($mapping) {

    $domain = $mapping->get_domain();
    
		if (!$this->uses_runcloud() || ! $domain) {
			return;
    }
    
    $domain_list = array($domain);

    if (strpos($domain, 'www.') !== 0) {
       
      $domain_list[] = "www.$domain";

    } // end if;
    
    foreach ($domain_list as $domain) {

      $domain_id = $this->get_runcloud_domain_id($domain);

      if (!$domain_id) {

        WU_Logger::add('runcloud', __('Domain name not found on runcloud', 'wp-ultimo'));

      } // end if;

      $response = $this->send_runcloud_request($this->get_runcloud_base_url("domains/$domain_id"), array(), 'DELETE');

      if (is_wp_error( $response )) {

        WU_Logger::add('runcloud', $response->get_error_message());

      } else {

        WU_Logger::add('runcloud', wp_remote_retrieve_body($response));

      } // end if

    } // end foreach;

  } // end remove_domain_from_runcloud

  /**
   * Checks if RUnCloud has a SSL cert installed or not, and returns the ID.
   *
   * @since 1.10.4
   * @return bool|int
   */
  public function get_runcloud_ssl_id() {

    $ssl_id = false;

    $response = $this->send_runcloud_request($this->get_runcloud_base_url("ssl"), array(), 'GET');

    if (is_wp_error( $response )) {

      WU_Logger::add('runcloud', $response->get_error_message());

    } else {

      $data = $this->maybe_return_runcloud_body($response);

      WU_Logger::add('runcloud', json_encode($data));

      if (property_exists($data, 'id')) {

        $ssl_id = $data->id;

      } // end if;

    } // end if;

    return $ssl_id;

  } // end check_for_runcloud_ssl;

  /**
   * Redeploys the SSL cert when a new domain is added.
   *
   * @since 1.10.4
   * @param int $ssl_id The SSL id on RunCloud.
   * @return void
   */
  public function redeploy_runcloud_ssl($ssl_id) {

    $response = $this->send_runcloud_request($this->get_runcloud_base_url("ssl/$ssl_id"), array(), 'PUT');

    if (is_wp_error( $response )) {

      WU_Logger::add('runcloud', $response->get_error_message());

    } else {

      WU_Logger::add('runcloud', wp_remote_retrieve_body($response));

    } // end if;

  } // end redeploy_runcloud_ssl;

} // end class WU_Domain_Mapping_Hosting_Support;

new WU_Domain_Mapping_Hosting_Support;
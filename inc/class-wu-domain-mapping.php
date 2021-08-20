<?php
/**
 * Handles the integration with Mercator
 *
 * @since  1.2.0 Better handling of Domain Mapping and HTTPs
 *
 * @author      Arindo Duque
 * @category    Admin
 * @package     WP_Ultimo/Domains
 * @version     0.0.1
*/

if (!defined('ABSPATH')) {
  exit;
}

use Mercator\Mapping;

class WU_Domain_Mapping {

  /**
   * Is it a subdomain install?
   * @var boolean
   */
  public $is_subdomain = false;

  /**
   * The code to set the redirects
   * @var integer
   */
  public $redirect_code = 302;

  /**
   * Caches current site to reduce database calls
   * @var
   */
  public $current_site;

  /**
   * Initializes
   */
  public function __construct() {

    /**
     * Ajax endpoints
     */
    add_action('wp_ajax_wu_upgrade_sunrise', array($this, 'upgrade_sunrise'));

    /** Load the functions in case we need them */
    if (!function_exists('is_subdomain_install')) {

      require_once ABSPATH . '/wp-includes/ms-load.php';

    } // end if;

    $this->is_subdomain = is_subdomain_install();

    $this->redirect_code = WP_DEBUG ? 302 : 301;

    /**
     * Check of we have everything we need to run
     */
    if (!$this->has_feature()) return;

    /**
     * Set Admin panel SSL
     */
    if (WU_Settings::get_setting('force_admin_https')) {

      if (!wu_get_current_site()->mapping) {

        force_ssl_admin(true);

      } else {

        // define("COOKIEPATH", wu_get_current_site()->mapping->get_domain());
        // define('ADMIN_COOKIE_PATH', '/');
        // define('COOKIE_DOMAIN', '');
        // define('COOKIEPATH', '');
        // define('SITECOOKIEPATH', '');

      } // end if;

    } // end if;
    
    /**
     * If it has custom domains
     */
    if (WU_Settings::get_setting('custom_domains')) {

      add_action('wu-my-account-page', array($this, 'add_meta_boxes'));

      add_action('admin_init', array($this, 'save_domain_mapping'));

      /**
       * Add the unmap metaboxes
       */
      if (WU_Settings::get_setting('allow_page_unmapping')) {
        
        add_action('add_meta_boxes', array($this, 'add_unmap_metaboxes'), 10, 2);

        add_action('save_post', array($this, 'save_unmap_metaboxes'), 10, 2);

      } // end if;

    } // end if;

    add_action('init', array($this, 'handle_redirect'), 10);

    /**
     *
     * Filters Get Site URL
     * 
     * We need to add a filter to the site URL getter to ensure we dont get a path alongside with the domain 
     * when a domain is mapped to a subdirectory site
     * 
     */
    add_filter('site_url', array($this, 'filter_site_url'), 10, 4);

    add_filter('home_url', array($this, 'filter_site_url'), 10, 4);

    add_filter('theme_file_uri', array($this, 'replace_url_for_assets'), 99);

    add_filter('stylesheet_directory_uri', array($this, 'replace_url_for_assets'));

    add_filter('template_directory_uri', array($this, 'replace_url_for_assets'));

    add_filter('plugins_url', array($this, 'replace_url_for_assets'));

    add_filter('wp_ultimo_url', array($this, 'replace_url_for_assets'));

    add_filter('wp_get_attachment_url', array($this, 'replace_url_for_assets'), 1);

    add_filter('script_loader_src', array($this, 'replace_url_for_assets'));

    add_filter('style_loader_src', array($this, 'replace_url_for_assets'));

    add_filter('theme_mod_header_image', array($this, 'replace_url_for_assets')); // @since 1.5.5

    add_filter('wu_get_logo', array($this, 'replace_url_for_assets')); // @since 1.9.0
    
    add_filter('autoptimize_filter_base_replace_cdn', array($this, 'replace_url_for_assets'), 8); // @since 1.8.2 - Fix for Autoptimiza

    /**
     * Update the custom domain option if the admin changes it on the alias panel
     */
    add_action('mercator.mapping.created', array($this, 'update_custom_domain'));
    add_action('mercator.mapping.updated', array($this, 'update_custom_domain'));
    add_action('mercator.mapping.deleted', array($this, 'remove_custom_domain'));

    // Fix for cross-origin requests
    add_filter('allowed_http_origins', array($this, 'add_allowed_origins'));

    // UX Builder Fix and others
    add_filter('wu_skip_redirect', array($this, 'skip_redirect'));

    // Create the summary table for the Domain Mapping tables
    add_action('current_screen', array($this, 'add_meta_box'));

    // Fix srcset
    add_filter('wp_calculate_image_srcset', array($this, 'fix_srcset')); // @since 1.5.5

    // Gutenberg Support
    add_filter('the_content', array($this, 'filter_url_on_content'));

    // WPML Support
    add_filter('wpml_url_converter_get_abs_home', array($this, 'replace_url_for_assets'));

  } // end construct;

  /**
   * Checks the IP address for this network
   * TODO: Move this logic to a cron-based approach.
   *
   * @since 1.9.0
   * @return string The IP address of the network
   */
  public static function get_ip_address() {

    // $ip = get_site_transient('wu-network-ip');

    // if (!$ip) {

    //   $response = wp_remote_get('https://ipv4.icanhazip.com/s/');

    //   $ip = trim(wp_remote_retrieve_body($response), "\n");

    //   if (filter_var($ip, FILTER_VALIDATE_IP)) {
        
    //     set_site_transient('wu-network-ip', $ip, DAY_IN_SECONDS);

    //   } else {

    $ip = $_SERVER['SERVER_ADDR'];

    //   } // end if;

    // } // end if;

    return apply_filters('wu_domain_mapping_get_ip_address', $ip, $_SERVER['SERVER_ADDR']);

  } // end get_ip_address;

  /**
   * Adds a fix to the srcset URLs when we need that domain mapped
   *
   * @since 1.5.5
   * @param array $sources
   * @return array
   */
  public function fix_srcset($sources) {

    foreach ($sources as &$source) {
      $sources[ $source['value'] ]['url'] = $this->replace_url_for_assets($sources[ $source['value'] ][ 'url' ]);
    }

    return $sources;

  } // end fix_srcset

  /**
   * Change the URLs for the content
   *
   * @since 1.8.2
   * @param string $content
   * @return string
   */
  public function filter_url_on_content($content) {

    $site = $this->get_current_site();

    if ($site->mapping) {

      $search = array(
        'https://'.$site->original_url,
        'http://'.$site->original_url,
      );

      foreach($search as $search) {
        
        $content = str_replace($search, $this->replace_url_for_assets($search), $content);

      } // end foreach;

    } // end if;

    return $content;

  } // end filter_url_on_content;

  /**
   * Saves the domain mapping
   *
   * @return void
   */
  public function save_domain_mapping() {

    if (isset($_POST['wu-action-save-custom-domain'])) {

      self::save_custom_domain();

    } // end if;

  } // end save_domain_mapping;

  /**
   * Add the meta-box for the summary
   * @return void
   */
  public function add_meta_box() {

    if (isset($_GET['wu-tab']) && $_GET['wu-tab'] == 'domain_mapping' && WU_Settings::get_settings('enable_domain_mapping')) {

      add_meta_box('wu-domain-mapping', __('SSL Settings - Summary', 'wp-ultimo'), array($this, 'render_domain_mapping_table'), get_current_screen()->id, 'normal');

      add_meta_box('wu-domain-mapping-support', __('Hosting Support', 'wp-ultimo'), array($this, 'render_domain_mapping_support_widget'), get_current_screen()->id, 'normal'); // @since 1.6.0

    } // end if;

  } // end add_meta_box;

  public static function get_hosting_support_text() {

    // Start buffer
    ob_start();

    ?>

    <p><?php _e('Our domain mapping will work out-of-the-box with most hosting environments, but some managed hosting platforms like WPEngine, Kinsta, and Cloudways may require the network admin to manually add the mapped domains as additional domains on their platform as well.', 'wp-ultimo'); ?></p>

    <p><?php _e('We are working closely with the hosting platforms to automate this process so no manual action is required from the admin after a client maps a new domain.', 'wp-ultimo'); ?></p>

    <p><?php _e('So far, WP Ultimo integrates with:', 'wp-ultimo'); ?></p>

    <ul class="wu-supported-hosting">
      
      <li class="wu-supported-hosting-done">
        <span class="dashicons dashicons-yes"></span>WP Engine <br>
        <small><?php _e('Works automatically - no additional set-up required.', 'wp-ultimo'); ?></small>
      </li>

      <li class="wu-supported-hosting-done">
        <span class="dashicons dashicons-yes"></span>WPMU DEV Hosting <br>
        <small><?php _e('Works automatically (including auto-SSL) – no additional set-up required.', 'wp-ultimo'); ?></small>
      </li>

      <li class="wu-supported-hosting-done">
        <span class="dashicons dashicons-yes"></span>Closte.com<br>
        <small><?php _e('Works automatically (including AutoSSL) - no additional set-up required.', 'wp-ultimo'); ?></small>
      </li>

      <li class="wu-supported-hosting-done-requires-action">
        <span class="dashicons dashicons-yes"></span>Cloudways <br>
        <small><?php _e('Works automatically, but <strong>requires additional set-up</strong>', 'wp-ultimo'); ?>. <br>
          <a href="<?php echo WU_Links()->get_link('cloudways-tutorial'); ?>" target="_blank"><?php _e('Read the Tutorial &rarr;', 'wp-ultimo'); ?></a>
        </small>
      </li>

      <li class="wu-supported-hosting-done-requires-action">
        <span class="dashicons dashicons-yes"></span>CPanel <br>
        <small><?php _e('Works automatically, but <strong>requires additional set-up</strong>', 'wp-ultimo'); ?>. <br>
          <a href="<?php echo WU_Links()->get_link('cpanel-tutorial'); ?>" target="_blank"><?php _e('Read the Tutorial &rarr;', 'wp-ultimo'); ?></a>
        </small>
      </li>

      <li class="wu-supported-hosting-done-requires-action">
        <span class="dashicons dashicons-yes"></span>RunCloud.io <br>
        <small><?php _e('Works automatically, but <strong>requires additional set-up</strong>', 'wp-ultimo'); ?>. <br>
          <a href="<?php echo WU_Links()->get_link('runcloud-tutorial'); ?>" target="_blank"><?php _e('Read the Tutorial &rarr;', 'wp-ultimo'); ?></a>
        </small>
      </li>

      <li class="wu-supported-hosting-done-requires-action">
        <span class="dashicons dashicons-yes"></span>ServerPilot.io <br>
        <small><?php _e('Works automatically, but <strong>requires additional set-up. Supports auto-SSL!</strong>', 'wp-ultimo'); ?>. <br>
          <a href="<?php echo WU_Links()->get_link('serverpilot-tutorial'); ?>" target="_blank"><?php _e('Read the Tutorial &rarr;', 'wp-ultimo'); ?></a>
        </small>
      </li>

    </ul>

    <p class="description">
      <?php _e('We contacted <strong>Kinsta</strong> and they don\'t have the necessary APIs in place yet, but assured us it is on their road-map. As soon as they implement the necessary tools, WP Ultimo will support them as well.', 'wp-ultimo'); ?>
    </p>

    <?php 

    return ob_get_clean();

  }// end get_hosting_support_text;

  /**
   * Render the Hosting Support widget
   *
   * @return void
   */
  public function render_domain_mapping_support_widget() {

    echo self::get_hosting_support_text();

  } // end render_domain_mapping_support_widget;

  /**
   * Renders the domain mapping table summary
   * @return void
   */
  public function render_domain_mapping_table() { 

    $is_subdomain = is_subdomain_install();
    
    $array = array(
      //__('Main Site', 'wp-ultimo')                                                  => WU_Settings::get_settings('force_admin_https'),
      __('Admin Panel', 'wp-ultimo')                                                => WU_Settings::get_setting('force_admin_https'),
      $is_subdomain ? __('Sub-domains', 'wp-ultimo') : __('Sub-sites', 'wp-ultimo') => $is_subdomain ? WU_Settings::get_setting('force_subdomains_https') : WU_Settings::get_setting('force_admin_https'),
      __('Mapped Domains', 'wp-ultimo')                                             => WU_Settings::get_setting('force_mapped_https'),
      __('Unmapped Pages', 'wp-ultimo')                                             => WU_Settings::get_setting('force_https_unmapped'),
    );

    $style = 'font-weight: bold; color: darkgreen;';

    ?>

    <table class="widefat striped" style="margin-bottom: -10px;">
      <thead>
        <tr>
          <th class="row-title"><?php _e('Type', 'wp-ultimo'); ?></th>
          <th><?php _e('HTTPS', 'wp-ultimo'); ?></th>
          <th><?php _e('HTTP', 'wp-ultimo'); ?></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach($array as $item => $ssl) : ?>
          <tr valign="top">
            <td scope="row"><?php echo $item; ?></td>
            <td style="width: 15%; text-align: center; <?php echo $ssl ? $style : ''; ?>"><?php echo $ssl ? __('Yes') : __('No'); ?></td>
            <td style="width: 15%; text-align: center; <?php echo !$ssl ? $style : ''; ?>"><?php echo !$ssl ? __('Yes') : __('No'); ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>

  <?php } // end render_domain_mapping_table;

  /**
   * Adds mapped domains to the cross-origin allowed list
   * 
   * @since 1.5.0
   * @param array $origins
   * @return void
   */
  public function add_allowed_origins($origins) {

    $site = $this->get_current_site();

    $origins[] = 'http://'.$site->original_url;
    $origins[] = 'https://'.$site->original_url;

    return $origins;

  } // end add_allowed_origins;

  /**
   * Excludes cases were we don't need to map
   * @param  bool $skip Wether or not to skip
   * @return bool
   */
  public function skip_redirect($skip) {

    if (isset($_GET['uxb_iframe']) && is_customize_preview()) {

      return true;

    } // end if;

    if (isset($_GET['elementor-preview']) || (isset($_GET['action']) && $_GET['action'] == 'elementor')) {

      return true;

    } // end if;

    return $skip;

  } // end skip_redirect;

  public function get_current_site() {

    if (!is_a($this->current_site, 'WU_Site')) {

      $this->current_site = wu_get_current_site();

    } // end if;

    return $this->current_site;

  } // end get_current_site;

  /**
   * Update our custom field with the newly updated domain
   * @param  Mercator/Mapping $mapping
   */
  public function update_custom_domain($mapping) {

    $site = wu_get_site($mapping->get_site_id());

    if ($site) {

      $site->set_meta('custom-domain', $mapping->get_domain());

    } // end if;

  } // end update_custom_domain;

  /**
   * Remove domain from custom site field
   * @param  Mercator/Mapping $mapping
   */
  public function remove_custom_domain($mapping) {

    $site = wu_get_site($mapping->get_site_id());

    if ($site) {

      $site->set_meta('custom-domain', '');

    } // end if;

  } // end remove_custom_domain;

  /**
   * Checks if our current URL already starts with the mapped domain. 
   * This helps with WWW. and other subdomain mappings
   *
   * @since 1.6.0
   * @param string $domain
   * @param string $url
   * @return bool
   */
  public function check_same_domain($domain, $url) {

    if (!is_string($domain)) {

      return true;

    } // end if;

    return substr( $this->remove_scheme($url), 0, strlen($domain) ) === $domain;

  } // end check_same_domain

  /**
   * Checks if a given domain is a valid mapping, if it is, allow for the redirection to occur
   * @param  boolean/string $domain
   * @return boolean        
   */
  public function has_valid_mapping($domain = false) {

    // Defaults to server
    $domain = $domain ?: $_SERVER['HTTP_HOST'];

    // Get Mapping
    $mapping = Mercator\Mapping::get_by_domain($domain);

    // Return
    return is_wp_error($mapping) || !$mapping || !$this->is_enabled($mapping);

  } // end has_valid_mapping;

  /**
   * Replace the URL with the new one, if necessary
   * @param  string $url URL to be filtered and replaced
   * @return string      The new URL
   */
  public function replace_url_for_assets($url) {

    /**
     * Fix for empty Image URLs
     */
    if (!$url || !is_string($url)) {

      return $url;

    } // end if;

    // Check fir UX Builder
    if (apply_filters('wu_skip_redirect', false)) {

      return $url;

    } // end if;

    $force = false;

    $site = $this->get_current_site();

    if ($site->mapping) {

      if ($site->mapping->get_domain() == $_SERVER['HTTP_HOST']) {
    
        $url = ! $this->check_same_domain($url, $site->mapping->get_domain()) 
          ? str_replace($site->original_url, $site->mapping->get_domain(), $url)
          : $url;

      } else {

        $force = ($site->mapping->get_domain() != $_SERVER['HTTP_HOST']) && !is_admin() && WU_Settings::get_setting('force_https_unmapped');

      } // end if;

    } // end if mapping;

    if ($site) {

      return $site->get_scheme($force) . $this->remove_scheme($url);

    } // end if;

    return $url;

  } // end replace_url_for_assets;

  /**
   * Replaces the last occurrence of the needle string    
   *
   * @param string $search
   * @param string $replace
   * @param string $subject
   * @return string
   */
  public function str_lreplace($search, $replace, $subject) {
    
    $pos = strrpos($subject, $search);

    if ($pos !== false) {
        $subject = substr_replace($subject, $replace, $pos, strlen($search));
    }

    return $subject;

  } // end str_lreplace

  /**
   * Filters the URL in case of subdirectory sites
   * Should not be called on subdomain networks
   * @param  string $url     
   * @param  string $path    
   * @param  string $scheme  
   * @param  int    $blog_id 
   * @return string
   */
  public function filter_site_url($url, $path, $scheme, $blog_id) {

    if ($scheme == 'relative') {

      return $url;

    } // end if;

    $blog_id = $blog_id ?: get_current_blog_id();

    $scheme = $this->get_scheme($blog_id, !empty($GLOBALS['mercator_current_mapping']) ? $GLOBALS['mercator_current_mapping'] : false);

    /**
     * Handles the Scheme - Replaces http for https, if needed
     */
    if (strpos($url, $scheme) !== -1) {

      $url = $scheme . $this->remove_scheme($url);

    }

    $site = get_site($blog_id);

    /**
     * Replace Subdirectory if it is needed
     */
    if ($this->is_subdomain || empty($GLOBALS['mercator_current_mapping']) || $site->path == '/') {

      return $url;

    } else {

      /**
       * We only replace if we are already on the mapped domain
       */
      if (stristr($url, $GLOBALS['mercator_current_mapping']->get_domain())) {

        return $this->str_lreplace(rtrim($site->path, '/'), '', $url);

      } else {

        return $url;

      } // end if;
      
    } // end if;

  } // end filter_site_url;


  /**
   * Return the Scheme to use with a certain site when it is mapped
   * @since  1.2.0
   * @return string
   */
  public function get_scheme($site_id, $mapping, $force = false) {

    /**
     * Case Main Site
     */
    if (is_main_site($site_id) || (is_admin() && !is_subdomain_install()) || $GLOBALS['pagenow'] === 'wp-login.php') {

      $use_ssl = $force || (defined('FORCE_SSL_ADMIN') && FORCE_SSL_ADMIN) || get_blog_option($site_id, 'wu_force_https', false);

      $scheme = $use_ssl ? 'https://' : (is_ssl() ? 'https://' : 'http://');

    /**
     * Case Mapped Domain
     */
    } else if ($mapping) {

        $unmapped = (WU_Settings::get_setting('force_subdomains_https') && ($_SERVER['HTTP_HOST'] !== $mapping->get_domain())) && WU_Settings::get_setting('force_admin_https');
    
        $scheme =  $unmapped || $force || WU_Settings::get_setting('force_mapped_https') || get_blog_option($site_id, 'wu_force_https', false) ? 'https://' : 'http://';

    /**
     * Case: Site with Mapped Domain or Subdomais
     */
    } else if (is_subdomain_install()) { // Removed this: $this->mapping || 

      $scheme =  $force || WU_Settings::get_setting('force_subdomains_https') || get_blog_option($site_id, 'wu_force_https', false) ? 'https://' : 'http://';

    /**
     * Case: Subdirectories
     */
    } else {

      $scheme = is_ssl() || get_blog_option($site_id, 'wu_force_https', false) ? 'https://' : 'http://';
    }

    wp_cache_set($site_id, $scheme, 'wu_site_schemes', 30*60);

    return $scheme;

  } // end get_scheme;

  /**
   * Remove Scheme from string
   * @param  string $url
   * @return string
   */
  public function remove_scheme($url) {

    if (!is_string($url)) return $url;

    if (substr($url, 0, 2) === '//') {

      $url = str_replace('//', '', $url);

    } // end if;

    return preg_replace('#^https?://#', '', $url);

  } // end remove_scheme;

  /**
   * Add metaboxes
   * @since  1.1.5 Add metaboxes for unmapping
   */
  public function add_unmap_metaboxes($post_type, $post) {

    add_meta_box('wu-unmap-page', __('Unmap Page', 'wp-ultimo'), array($this, 'render_unmap'), apply_filters('wu_post_types_unmap', array('post', 'page')), 'side', null);

  } // end add_unmap_metaboxes;

  /**
   * Render the unmap form
   * @since  1.1.5 Add metaboxes for unmapping
   * @return
   */
  public function render_unmap() { 
    
    global $post;
    
    ?>

    <p><?php _e('Use this option if you need SSL for this. This will leave this page unmapped and under the network SSL certificate.', 'wp-ultimo'); ?><p>

    <label><input name="wu_unmap_page" type="checkbox" <?php checked( get_post_meta($post->ID, 'wu_unmap_page', true) ); ?>> <?php _e('Unmap this page', 'wp-ultimo'); ?></label>

  <?php } // end render_unmap;

  /**
   * Save the unmap value, from the unmap metabox
   * @param  interget $post_id
   * @param  WP_Post  $post
   */
  public function save_unmap_metaboxes($post_id, $post) {

    // Check if this is one of the unmapped types
    $allowed = apply_filters('wu_post_types_unmap', array('post', 'page'));

    if (!in_array($post->post_type, $allowed)) return;

    $unmap = isset($_POST['wu_unmap_page']);

    wp_cache_delete($post_id, 'wu_unmmaped');

    return update_post_meta($post_id, 'wu_unmap_page', $unmap);

  } // end save_unmap_metaboxes;

  /**
   * Add the metabox and the saving processing
   */
  public function add_meta_boxes() {

    add_meta_box('wp-ultimo-custom-domain', __('Custom Domain', 'wp-ultimo'), array('WU_Domain_Mapping', 'output_widget_custom_domain'), 'wu-my-account', 'side', 'high');

  } // end add_meta_boxes;

  /**
   * Saves custom domain
   * @since  1.1.3 Validate the domain, of course
   * @return
   */
  public function save_custom_domain() {

    if (!wp_verify_nonce($_POST['_wpnonce'], 'wu-save-custom-domain') || !isset($_POST['custom-domain'])) return;

    // Clean URL
    $url = trim(strtolower($this->remove_scheme($_POST['custom-domain'])));

    $network_url = strtolower($this->remove_scheme(get_site_url(get_current_site()->blog_id)));

    /**
     * @since  1.1.3 Validate the domain, of course
     */
    if (preg_match('/^(?!\-)(?:[a-zA-Z\d\-]{0,62}[a-zA-Z\d]\.){1,126}(?!\d+)[a-zA-Z\d]{1,63}$/', $url) && !empty($url)) {

      /**
       * Check if it already exists
       */
      if (Mapping::get_by_domain($url)) {

        // Domain Exists already
        WP_Ultimo()->add_message(__('That domain is already being used by other account.', 'wp-ultimo'), 'error');

      } else if ($url === $network_url || strpos($url, $network_url) !== false) {

        // Prevent invalid domains
        WP_Ultimo()->add_message(__('This domain name is not valid.', 'wp-ultimo'), 'error');

      } else {

       $site_ip = WU_Settings::get_setting('network_ip') ? WU_Settings::get_setting('network_ip') : $_SERVER['SERVER_ADDR'];

        // if ( gethostbyname($url) !== $site_ip ) {

        //   return WP_Ultimo()->add_message(sprintf(__("Custom Domain DNS Invalid: %s is not resolving to %s (check your domain's DNS records).", 'wp-ultimo'), $url, $site_ip), 'error');

        // } // end if;

        $site = wu_get_current_site();

        // Save field in the site
        $site->set_custom_domain($url);

        /**
         * @since  1.2.1 Makes sure it gets added as a active one
         */
        if ($mapping = Mapping::get_by_domain($url)) {

          $mapping->set_active(true);

        } // end if;

        /**
         * Creates the admin notification email about the domain mapping
         * @since 1.5.4
         */
        WU_Mail()->send_template('domain_mapping', get_network_option(null, 'admin_email'), array(
          'user_name'              => $site->site_owner->data->display_name,
          'user_site_name'         => get_bloginfo('name'),
          'user_site_url'          => get_home_url($site->ID),
          'mapped_domain'          => $url,
          'alias_panel_url'        => network_admin_url('admin.php?action=mercator-aliases&id=') . $site->ID,
          'user_account_page_link' => get_admin_url($site->ID, 'admin.php?page=wu-my-account'),
        ));

        /**
         * @since 1.6.0 Hook for after a mapped domain is added
         */
        do_action('wu_after_domain_mapping', $url, $site->ID, $site->site_owner->ID);

        // Add Success Message
        WP_Ultimo()->add_message(__('Custom domain updated successfully!', 'wp-ultimo'));


      } // end if;      

    } elseif (empty($url)) {

      $site = wu_get_current_site();

      $current_domain = $site->get_meta('custom-domain');

      if (empty($current_domain)) {

        return WP_Ultimo()->add_message(__('You need to enter a valid domain address.', 'wp-ultimo'), 'error');

      } // end if;

      // Save field in the site
      $site->set_custom_domain('');

      /**
       * @since  1.2.1 Makes sure it gets added as a active one
       */
      if ($mapping = Mapping::get_by_domain($url)) {

        $mapping->delete();

      } // end if;

      /**
       * @since 1.6.1 After the domain map is removed
       */
      do_action('wu_after_domain_mapping_removed', $url, $site->ID, $site->site_owner->ID);

      WP_Ultimo()->add_message(__('Custom domain removed successfully.', 'wp-ultimo'));
    
    } else {

      // Add Error Message
      WP_Ultimo()->add_message(__('You need to enter a valid domain address.', 'wp-ultimo'), 'error');

    }

  } // save_custom_domain;

  /**
   * Checks if we need to enqueue this functionality
   * @return boolean If all the dependencies were met or not
   */
  public function has_feature() {

    // True by default
    if (!WU_Settings::get_setting('enable_domain_mapping')) {

      return false;

    }

    // Check if the user has the settings on but does not have mercator
    else if (!class_exists('\Mercator\Mapping')) {

      add_action('network_admin_notices', array($this, 'notice_has_not_mercator'));
      
      return false;
    
    }

    // Check if the user has the settings on but does not have mercator
    else if (!defined('SUNRISE')) {

      add_action('network_admin_notices', array($this, 'notice_has_not_mercator'));
      
      return false;
    
    } // end if;

    /**
     * Finally, we check if the user has the sunrise, but an older version of
     */
    if (!defined('WPULTIMO_SUNRISE_VERSION') || version_compare(WP_Ultimo()->sunrise_version, WPULTIMO_SUNRISE_VERSION, '>')) {

      add_action('network_admin_notices', array($this, 'notice_old_sunrise'));

    } // end if;

    return true;

  } // end checker;

  /**
   * Display notice if the user chose to activate custom domains, but does not have mercator
   */
  public function notice_has_not_mercator() {

    WP_Ultimo()->render('notices/mercator');

  } // end notice_has_not_mercator;

  /**
   * Display the user a notice if he or sha has to update their sunrise version
   */
  public function notice_old_sunrise() {

    WP_Ultimo()->render('notices/old-sunrise');

  } // end notice_has_not_mercator;

  /**
   * Add Widget for custom domain
   * @return
   */
  public static function output_widget_custom_domain() {

    WP_Ultimo()->render('widgets/account/custom-domain');

  } // end output_widget_custom_domain;

  /**
   * Tries to upgrade the sunrise.php version automatically
   *
   * @return void
   */
  function upgrade_sunrise() {

    if (!current_user_can('manage_network')) {

      echo json_encode(array(
        'status'  => false,
        'message' => __('You do not have enough permissions to replace the Sunrise.php file.', 'wp-ultimo')
      ));

    } // end if;

    try {

      $copy_results = copy(WP_PLUGIN_DIR.'/wp-ultimo/sunrise.php', WP_CONTENT_DIR.'/sunrise.php');

    } catch(Exception $e) {

      $copy_results = false;

    } // end try

    if ($copy_results) {

      echo json_encode(array(
        'status'  => true,
        'message' => __('Sunrise.php upgraded successfully!', 'wp-ultimo')
      ));

    } else {

      echo json_encode(array(
        'status'  => false,
        'message' => __('We were unable to automatically update your sunrise.php version. Please, do it manually following the instructions above.', 'wp-ultimo')
      ));

    } // end if/else;

    exit;

  } // end upgrade_sunrise;

  function is_enabled( $mapping = null ) {
      $mapping = $mapping ?: $GLOBALS['mercator_current_mapping'];

      /**
       * Determine whether a mapping should be used
       *
       * Typically, you'll want to only allow active mappings to be used. However,
       * if you want to use more advanced logic, or allow non-active domains to
       * be mapped too, simply filter here.
       *
       * @param boolean $is_active Should the mapping be treated as active?
       * @param Mapping $mapping   Mapping that we're inspecting
       */
      return apply_filters( 'mercator.redirect.enabled', $mapping->is_active(), $mapping );
  }

  function redirect_admin() {
      /**
       * Whether to redirect mapped domains on visits to the WP admin.
       * Recommended to leave this false as there's no SEO problem
       * and avoids having a broken / unreachable admin if the
       * custom domain DNS is incorrect or not fully propagated.
       *
       * @param bool Set to true to enable admin redirects
       */
      return apply_filters( 'mercator.redirect.admin.enabled', false );
  }

  function use_legacy_redirect() {
      /**
       * If you still have blogs with the main domain as the subdomain
       * or subsite path this will allow you to still redirect to the
       * first active alias found.
       *
       * @param bool Set to true to enable legacy redirects
       */
      return apply_filters( 'mercator.redirect.legacy.enabled', true );
  }

  /**
   * Removes the subdirectory from the URL
   * @param  string $request_url URL elements
   * @return string              URL stripped from the path
   */
  function remove_subdirectory($request_url) {

      $site = get_site( get_current_blog_id() );

      $to_remove = !is_subdomain_install() ? rtrim($site->path, '/') : false;

      if (!$this->has_valid_mapping() || !$to_remove || $to_remove == '/') return esc_url_raw($request_url);

      else return esc_url_raw( str_replace($to_remove, '', $request_url) );

  } // end remove_subdirectory;

  /**
   * Performs the redirect to the primary domain
   */
  function handle_redirect() {

    // Custom domain redirects need SUNRISE.
    if (!defined('SUNRISE') || ! SUNRISE) {
      return;
    }

    // Exits on Ajax
    if (defined('DOING_AJAX') && DOING_AJAX) {
      return;
    }

    // Exits on REST
    if (defined('REST_REQUEST') && REST_REQUEST) {
      return;
    }

    // Don't redirect REST API requests
    if (function_exists('rest_url') && isset($_SERVER['REQUEST_URI']) && 0 === strpos($_SERVER['REQUEST_URI'], parse_url(rest_url(), PHP_URL_PATH))) {
        return;
    }

    // Check fir UX Builder
    if (apply_filters('wu_skip_redirect', false)) {
      return;
    }

    $site = wu_get_current_site();

    // Checks if there is a mapping active, otherwise, do nothing
    if (!$site->mapping) {

      return;

    } // end if;

    /**
     * Should we redirect visits to the admin?
     */
    if ((is_admin() || $GLOBALS['pagenow'] === 'wp-login.php')) {

      $force_admin_redirect = WU_Settings::get_setting('force_admin_redirect');

      if ($force_admin_redirect == 'both') {

        return; 

      } else if ($force_admin_redirect == 'force_network') {
          
        /**
         * We have to do the inverse here, redirecting the user to our panel, if this option is set to false
         */
        $this->force_original_url();

        return;

      } // end if;

    } // end if;

    /**
     * Redirect using the Legacy redirect code
     */
    if ($this->use_legacy_redirect()) {

      $this->legacy_redirect();

      return;

    } // end if;

  } // end handle_redirect;

  /**
   * If the admin chooses to do so, he can force admin access using his network domain
   */
  public function force_original_url($force = false) {

    // Exits on Ajax
    if (defined('DOING_AJAX') && DOING_AJAX) {
      return;
    }

    $site = $this->get_current_site();

    if ($site->mapping && !stristr($site->original_url, $_SERVER['HTTP_HOST'])) {

      $new_url = $site->get_scheme($force) . $site->original_url . add_query_arg(array());

      wp_redirect($new_url, 302);

      exit;

    } // end if;

  } // end force_original_url;

  /**
   * Do not redirect if a page is marked unmaped
   * @return boolean
   */
  function is_unmapped_page() {

    /**
     * Admin pages cannot be unmapped
     */
    if (is_admin() || !did_action('init')) {

      return false;

    }

    $found = false;

    $post_id = url_to_postid(add_query_arg(array()));

    $_unmapped = wp_cache_get($post_id, 'wu_unmmaped', false, $found);

    if ($found) {

      return $_unmapped;

    } else {

      if (!$post_id) {

        $unmapped = false;

      } else {

        $unmapped = get_post_meta($post_id, 'wu_unmap_page', true);

      } // end if;

      wp_cache_set($post_id, $unmapped, 'wu_unmmaped', 30*60);

      return $unmapped;

    } // end if;

  } // end is_unmapped_page;

  /**
   * Check if the main site domain contains the network hostname
   * and use the first active alias if so
   */
  function legacy_redirect() {

    /**
     * Exclude unmapped pages
     */
    if (!is_admin() && $this->is_unmapped_page()) {

      $this->force_original_url(WU_Settings::get_setting('force_https_unmapped'));

      return;

    } // end if;

    $site = wu_get_current_site();

    if ($site->mapping) {

      // Check the blog domain isn't a subdomain or subfolder
      if (false === strpos($site->site->domain, str_replace('www.', '', get_current_site()->domain))) {

          if ($_SERVER['HTTP_HOST'] !== $site->site->domain) {
            
            wp_redirect($site->get_scheme() . $site->site->domain . $this->remove_subdirectory($_SERVER['REQUEST_URI']), $this->redirect_code);
            
            exit;

          } // end if;

          return;

      } // end if;

      /**
       * Do the actual redirecting, when necessary
       */
      if ($_SERVER['HTTP_HOST'] !== $site->mapping->get_domain()) {

        wp_redirect($site->get_scheme() . $site->mapping->get_domain() . $this->remove_subdirectory($_SERVER['REQUEST_URI']), $this->redirect_code);
        
        exit;

      } // end if;

    } // end if;

  } // end legacy_redirect;

} // end class WU_Domain_Mapping;

new WU_Domain_Mapping;
<?php
/**
 * Plugin Name: WP Ultimo
 * Description: The Complete Membership and Network Solution.
 * Plugin URI: http://wpultimo.com
 * Text Domain: wp-ultimo
 * Version: 1.10.13
 * Author: Arindo Duque - NextPress
 * Author URI: http://nextpress.co/
 * Network: true
 * License: GPL2
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * 
 * WP Ultimo is distributed under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 2 of the License, or
 * any later version.
 *
 * WP Ultimo is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with WP Ultimo. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author   Arindo Duque
 * @category Core
 * @package  WP_Ultimo
 * @version  1.10.13
 */

if (!defined('ABSPATH')) {
  exit; // Exit if accessed directly.
}

if (!class_exists('WP_Ultimo')) :

/**
 * Loads our incredibly awesome Paradox Framework, which we are going to use a lot.
 */
require_once plugin_dir_path(__FILE__).'paradox/paradox.php';

/**
 * Here starts our plugin.
 */
final class WP_Ultimo extends ParadoxFrameworkSafe {
    
  /**
   * Makes sure we are only using one instance of the plugin
   * 
   * @var object WU_Ultimo
   */
  public static $instance;

  /**
   * Version of the Plugin
   * 
   * @var string
   */
  public $version = '1.10.13';

  /**
   * Version of the Plugin's sunrise, 
   * to display an update message when changes are made
   * 
   * @var string
   */
  public $sunrise_version = '1.0.0';

  /**
   * Is this a beta version
   * 
   * @var boolean
   */
  public $beta = false;

  /**
   * List the beta flags
   * 
   * @since 1.7.0
   * @var array
   */
  public $beta_flags = array('beta', 'alpha', 'rc', 'RC');
  
  /**
   * Defines if we will use minified versions of scripts or the full dev versions
   *
   * @var string
   */
  public $min = '.min';

  /**
   * Contains our error or success messages
   * 
   * @var array
   */
  protected $messages = array(
    'admin'         => array(), 
    'network_admin' => array()
  );
  
  /**
   * Instantiate the Plugin
   * 
   * @param array $config
   * @since 0.0.1
   * @return WU_Ultimo
   */
  public static function get_instance($config = array()) {

    if (null === self::$instance) self::$instance = new self($config);

    return self::$instance;
    
  } // end get_instance;
  
  /**
   * Get an option from our database table
   * @param  string [$option               = 'settings'] The setting to get
   * @return array  Get the result
   */
  public function getOption($option = 'settings') {    
    $optionName = $this->slugfy($option);
    $result = get_network_option(null, $optionName);
    return is_array($result) ? $result : array();
  }
  
  public function saveOption($option = 'settings', $value) {    
    $optionName = $this->slugfy($option);
    return update_network_option(null, $optionName, $value);
  }

  /**
   * DEPRECATED
   * 
   * Check if we need to enqueue our scripts in this particular page in the admin
   * We use this check to prevent loading our scripts every time, even when we don't need them
   * 
   * @since 1.2.0
   * @return boolean True if it is one of our pages
   */
  public function check_for_enqueue() {

    /**
     * This is going to be leaving the API soon, be careful
     * @since 2.0.0
     */
    _deprecated_function(__FUNCTION__, '1.9.0');

    return true;

  } // end check_for_enqueue;

  /**
   * DEPRECATED
   * 
   * Keeping the deprecated version for backwards compatibility puposes
   *
   * @param string $asset
   * @param string $assets_dir
   * @return string
   */
  public function getAsset($asset, $assets_dir = 'img', $deprecated = false) {

    /**
     * This is going to be leaving the API soon, be careful
     * @since 1.9.0
     */
    _deprecated_function(__FUNCTION__, '1.9.0', 'get_asset()');

    /**
     * Get the asset using the newer function
     */
    return $this->get_asset($asset, $assets_dir, $deprecated);

  } // end getAsset;

  /**
   * Select 2, to be used when necessary
   * @return [type] [description]
   */
  public function enqueue_select2() {

    wp_register_style('wu-select2css', 'https://cdnjs.cloudflare.com/ajax/libs/select2/3.4.8/select2.css', false, '1.0', 'all');
    wp_register_script('wu-select2', 'https://cdnjs.cloudflare.com/ajax/libs/select2/3.4.8/select2.js', array('jquery'), '1.0', true);
    
    wp_enqueue_style('wu-select2css');
    wp_enqueue_script('wu-select2');

  } // end enqueue_select2;
  
  /**
   * IMPORTANT METHODS
   * Set below are the must important methods of this framework. Without them, none would work.
   */
  
  /**
   * Add messages to be displayed as notices
   * 
   * @param string  $message Message to be displayed
   * @param string  $type    Success, error, warning or info
   * @param boolean $network Where to display, network admin or normal admin
   */
  public function add_message($message, $type = 'success', $network = false) {

    $location = $network ? 'network_admin' : 'admin';

    $this->messages[$location][] = array(
      'type'    => $type,
      'message' => $message,
    );

  } // end add_message;

  /**
   * Get All the messages stored
   * 
   * @param  boolean $network Where to display, network admin or normal admin
   * @return array            The array containing all the messages
   */
  public function get_messages($network = false) {

    return apply_filters('wu_admin_notices', WP_Ultimo()->messages[$network ? 'network_admin' : 'admin']);

  } // end get_messages;
  
  /**
   * DEPRECATED
   * 
   * All pages added to this pages array will get our branding top and help tabs
   * 
   * @param string $page
   */
  public function add_page_to_branding($page) {

    /**
     * This is going to be leaving the API soon, be careful
     * @since 2.0.0
     */
    _deprecated_function(__FUNCTION__, '1.9.0', 'WU_UI_Elements()->add_page_to_branding($page)');

    WU_UI_Elements()->add_page_to_branding($page);

  } // end add_page_to_branding;

  /**
   * Checks if a given release number is a beta release or not
   *
   * @since 1.7.0
   * @param string  $new_version
   * @param array   $flags
   * @return boolean
   */
  public function is_release_beta($version, $flags) {

    foreach($flags as $flag) {

      if (stripos($version, $flag) !== false) return true;
    
    } // end foreach;

    return false;

  } // end is_new_release_beta;

  /**
   * Add the beta flag to the URL
   * 
   * @since 1.7.4
   * @return array
   */
  public function pass_beta_program_flag($args) {

    $args['beta_program'] = (int) WU_Settings::get_setting('beta-program', false);

    return $args;

  } // end pass_beta_program_flag;

  /**
   * Check if the beta program is enable and if we need to show the update to the user
   *
   * @since  1.7.0
   * @param  object $update
   * @param  string $new_version
   * @param  string $installed_version
   * @return mixed
   */
  public function check_if_beta_program_is_enabled($update, $new_version, $installed_version) {

    if (strpos($update->slug, 'wp-ultimo') === false) return;

    $is_beta_program_enabled = WU_Settings::get_setting('beta-program', false);

    if ($this->is_release_beta($new_version, $this->beta_flags)) {

      if (!$is_beta_program_enabled) return;

      // Add Beta Program message
      $hook = "in_plugin_update_message-$update->filename";

      add_action($hook, array($this, 'add_beta_program_notice'), 10, 2); 

    } // end if;

  } // end check_if_beta_program_is_enabled;

  /**
   * Adds an message telling the user his update is part of the Beta Program
   * 
   * @since 1.7.0
   * @return void
   */
  public function add_beta_program_notice($plugin_data, $r) {

    $report_link  = WU_Links()->get_link('report-bug');
    $opt_out_link = network_admin_url('admin.php?page=wp-ultimo&wu-tab=activation#beta-program-heading');

    echo '<br><br>' . sprintf(__('You are receiving this update notification because you are a member of our <strong>Beta Program</strong>. This version is beta stage and it might <strong>not be an stable release</strong>. You are welcome to test it out on development enviroments and contact us on the Forums with bug reports if you find anything strange. Thanks for helping making WP Ultimo even better!', 'wp-ultimo'));

    echo ' ' . sprintf('<a href="%s" target="_blank">%s</a>', $report_link, __('Click here to report a bug', 'wp-ultimo'));
    echo sprintf(' %s <a href="%s">%s</a>.', __('or'), $opt_out_link, __('here to opt-out of the Beta Program', 'wp-ultimo'));

  } // end add_beta_program_notice;

  /**
   * Run when all plugins are loaded ana ready to go
   * 
   * @since  0.0.1
   * @since  1.1.5 Checks settings version
   * @since  1.2.0 Loads important files when we need them
   */
  public function onPluginsLoaded() {

    // Check if we need to minify scripts
    $this->min = defined('SCRIPT_DEBUG') && SCRIPT_DEBUG ? '' : '.min' ;

    // Text Domain
    load_plugin_textdomain('wp-ultimo', false, plugin_basename(dirname(__FILE__)).'/lang');

    // Check if this install is fitted for WP Ultimo
    if (!$this->check_before_run()) return;

    /**
     * @since 1.9.0 Error Reporting
     */
    require_once $this->path('inc/class-wu-error-reporting.php');

    /**
     * @since  1.3.0 Multi-Network Support
     */
    require_once $this->path('inc/class-wu-multi-network.php');

    /**
     * @since  1.4.0 Customizer Options
     */
    require_once $this->path('inc/class-wu-customizer.php'); // @since 1.4.0

    /**
     * Essential elements that need to get loaded first
     * @since  1.2.0
     */
    require_once $this->path('inc/class-wu-links.php'); // @since 1.7.0
    require_once $this->path('inc/wu-functions.php');
    require_once $this->path('inc/class-wu-util.php');
    require_once $this->path('inc/class-wu-site-hooks.php');
    require_once $this->path('inc/class-wu-exporter-importer.php'); // @since 1.7.4
    require_once $this->path('inc/class-wu-admin-settings.php');

    // Models
    require_once $this->path('inc/models/wu-plan.php');
    require_once $this->path('inc/models/wu-coupon.php');
    require_once $this->path('inc/models/wu-site-owner.php');
    require_once $this->path('inc/models/wu-subscription.php');
    require_once $this->path('inc/models/wu-site.php');
    require_once $this->path('inc/models/wu-broadcast.php');     // @since 1.1.5
    require_once $this->path('inc/models/wu-site-template.php'); // @since 1.2.0
    require_once $this->path('inc/models/wu-webhook.php');       // @since 1.6.0

    // Domain Mapping
    require_once $this->path('inc/class-wu-domain-mapping.php');
    require_once $this->path('inc/class-wu-domain-mapping-hosting-support.php'); // @since 1.6.1

    // Gateways
    require_once $this->path('inc/gateways/class-wu-gateway.php');

    // Check if we should display beta and RC updates
    add_filter('paradox_updater_should_display_update', array($this, 'check_if_beta_program_is_enabled'), 10, 3);

    add_filter('puc_request_info_query_args-wp-ultimo', array($this, 'pass_beta_program_flag'));

  } // end onPluginsLoaded;

  /**
   * Place code for your plugin's functionality here.
   */
  public function Plugin() {
    
    // Check if this install is fitted for WP Ultimo
    if (!$this->check_before_run()) return;

    // @since 1.2.0
    $this->create_cron_job();

    // Require our includes
    $this->init();
    
  } // end Plugin;

  /**
   * Gets a configuration param
   *
   * @since 1.8.2
   * @param string $config
   * @return mixed
   */
  public function get_config($config) {

    return isset($this->config[ $config ]) ? $this->config[ $config ] : false;

  } // end get_config;
  
  /**
   * Include the files containing important functionality to our plugin
   */
  public function init() {

    // Geo IP
    require_once $this->path('inc/class-wu-geo-ip.php');

    // Geolocation
    require_once $this->path('inc/class-wu-geolocation.php');

    // Util
    require_once $this->path('inc/wu-functions.php');

    require_once $this->path('inc/class-wu-util.php');

    // Scripts 
    require_once $this->path('inc/class-wu-scripts.php');

    // Screenshots
    require_once $this->path('inc/class-wu-screenshot.php');
    
    // Logger
    require_once $this->path('inc/class-wu-logger.php');
    
    // Transactions
    require_once $this->path('inc/class-wu-transactions.php');

    require_once $this->path('inc/gateways/class-wu-gateway-manual.php'); // @since 1.2.0

    require_once $this->path('inc/gateways/class-wu-gateway-paypal.php');

    require_once $this->path('inc/gateways/class-wu-gateway-stripe.php');

    require_once $this->path('inc/class-wu-pro-sites-support.php');

    // Mail
    require_once $this->path('inc/class-wu-mail.php');
    
    // Widgets
    require_once $this->path('inc/class-wu-widgets.php');
    
    // Pages
    require_once $this->path('inc/class-wu-pages.php');

    require_once $this->path('inc/class-wu-pages-edit.php'); // since 1.8.2
    
    require_once $this->path('inc/class-wu-pages-list.php'); // since 1.8.2

    require_once $this->path('inc/class-wu-pages-settings.php'); // since 1.8.2

    require_once $this->path('inc/class-wu-pages-stats.php');

    require_once $this->path('inc/class-wu-pages-broadcast.php');

    // Webhook
    require_once $this->path('inc/class-wu-webhooks.php');

    // API
    require_once $this->path('inc/class-wu-api.php');

    require_once $this->path('inc/class-wu-pages-webhooks.php');
    
    require_once $this->path('inc/class-wu-pages-addons.php');
    
    require_once $this->path('inc/class-wu-pages-feature-plugins.php');

    require_once $this->path('inc/class-wu-pages-about.php');

    require_once $this->path('inc/class-wu-pages-my-account.php');

    require_once $this->path('inc/class-wu-pages-system-info.php');
    
    // Setup Classes (Wizard)
    require_once $this->path('inc/setup/class-wu-setup.php');
    
    // Plan & Coupons Classes
    require_once $this->path('inc/class-wu-plans.php');
    
    require_once $this->path('inc/class-wu-pages-list-plans.php'); // since 1.8.2

    require_once $this->path('inc/class-wu-pages-edit-plan.php'); // since 1.8.2

    require_once $this->path('inc/class-wu-pages-list-coupons.php'); // since 1.8.2

    require_once $this->path('inc/class-wu-pages-edit-coupon.php'); // since 1.8.2

    require_once $this->path('inc/class-wu-subscriptions.php'); // @since 1.1.0

    require_once $this->path('inc/class-wu-pages-list-subscriptions.php'); // since 1.8.2
    
    require_once $this->path('inc/class-wu-pages-add-new-subscription.php'); // since 1.8.2

    require_once $this->path('inc/class-wu-pages-edit-subscription.php'); // since 1.8.2

    require_once $this->path('inc/class-wu-plans-limits.php');

    require_once $this->path('inc/class-wu-site-templates.php'); // @since 1.2.0

    require_once $this->path('inc/class-wu-notifications.php'); // @since 1.5.5

    require_once $this->path('inc/class-wu-gutenberg-support.php'); // @since 1.9.14
    
    // Duplicate helper functions
    // Thanks to MultiSite Clone Duplicator
    // Contributors: pdargham, julienog, globalis
    require_once $this->path('inc/duplicate/duplicate.php');
    
    // UI Elements
    require_once $this->path('inc/class-wu-ui-elements.php');

    // Sign-up
    require_once $this->path('inc/class-wu-signup.php');

    // Login
    require_once $this->path('inc/class-wu-login.php'); // @since 1.7.0

    // Shortcodes
    require_once $this->path('inc/class-wu-shortcodes.php');
    
    // Shortcodes
    require_once $this->path('inc/class-wu-jumper.php'); // @since 1.6.0

    /**
     * @since 1.4.0 View Override
     */
    require_once $this->path('inc/class-wu-view-override.php'); // @since 1.4.0

    /**
     * Update Tables
     */
    require_once $this->path('inc/class-wu-update-tables.php'); // @since 1.5.0
    
  } // end init;
  
  /**
   * This function is here to make sure that the plugin is network active
   * and that this is a multisite install
   * 
   * @since 1.0.0
   * @return boolean True if everything is ok, false otherwise
   */
  public function check_before_run() {

    // Return
    $checker = true;

    /**
     * Check if we are running PHP 5.6 or later
     * @since 1.7.2
     */
    if (version_compare(phpversion(), '5.6.0', '<')) {

      add_action('admin_notices', array($this, 'notice_old_php'));
      
      $checker = false;

    } // end if;
    
    // Check if this is a multi-site install
    if (!is_multisite()) {

      add_action('admin_notices', array($this, 'notice_not_multisite'));

      $checker = false;

    } // end if;
    
    // If this is not mulsite enabled, then...
    if (!$this->config['multisite'] && !(defined('WP_ULTIMO_TEST_SUITE') && WP_ULTIMO_TEST_SUITE) ) {

      add_action('admin_notices', array($this, 'notice_not_network_active'));

      $checker = false;

    } // end if;
    
    return $checker;
    
  } // end check_before_run;
  
  /**
   * Display the notice in the case of the plugin not being network active
   * 
   * @since 1.7.2
   * @return void
   */
  public function notice_old_php() {

    $this->render('notices/network-old-php');

  } // end notice_old_php;
  
  /**
   * Display the notice in the case of the plugin not being network active
   * 
   * @return void
   */
  public function notice_not_network_active() {

    $this->render('notices/network-active');

  } // end notice_not_network_active;
  
  /**
   * Display the notice in the case of the site not being a multsite install
   * 
   * @return void
   */
  public function notice_not_multisite() {

    $this->render('notices/multisite');

  } // end notice_not_multisite;

  /**
   * Functions run on plugin activation
   */
  public function onActivation() {

    if (!get_network_option(null, 'wp_ultimo_activated', false)) {

      // Set registration to true
      update_network_option(null, 'registration', 'all');

      /**
       * @since  1.3.0 Multi-Network Support
       */
      require_once $this->path('inc/class-wu-multi-network.php');

      require_once $this->path('inc/class-wu-transactions.php');
      require_once $this->path('inc/models/wu-site-owner.php');
      require_once $this->path('inc/models/wu-subscription.php');

      /**
       * Create necessary database Tables
       */
      WU_Transactions::create_table();
      WU_Site_Owner::create_table();
      WU_Subscription::create_table();

      // Save the activation
      update_network_option(null, 'wp_ultimo_activated', true);

    } // end if;

    // Create Cron Job
    $this->create_cron_job();

  } // end onActivation;

  /**
   * Functions run on plugin deactivation
   * @since  1.2.0
   */
  public function onDeactivation() {

    wp_clear_scheduled_hook('wu_cron');

    update_network_option(null, 'wu_cron_created', false);

  } // end onDeactivation;

  /**
   * Create the cron job we will use to run some background functionality
   * @since  1.2.0 
   */
  public function create_cron_job() {

    if (!get_network_option(null, 'wu_cron_created', false)) {

      // Create our Cron Job
      if (!wp_next_scheduled('wu_cron')) {
        
        wp_schedule_event(time(), 'hourly', 'wu_cron');

        // Mark the option
        update_network_option(null, 'wu_cron_created', true);

      } // end if;

    } // end if;

  } // end create_cron_job;
  
} // end class WP_Ultimo;

endif;

/**
 * We execute our plugin, passing our config file
 */
function WP_Ultimo($config = array()) {

  return WP_Ultimo::get_instance($config);

} // end WP_Ultimo;

// Pluggable
require_once plugin_dir_path(__FILE__).'inc/wu-pluggable.php';

// Now we need to load our config file
$config = include plugin_dir_path(__FILE__).'/config.php';

// Set global
$GLOBALS['WP_Ultimo'] = WP_Ultimo($config);

<?php
/**
 * Error Reporting Class
 *
 * If the user gives us permission, we track the errors on their install so we can 
 *
 * @since       1.9.0
 * @author      Arindo Duque
 * @category    Admin
 * @package     WP_Ultimo/Error_Reporting
 * @version     0.0.1
*/

if (!defined('ABSPATH')) {
  exit;
}

class WU_Error_Reporting {

  // class instance
  static $instance;

  /**
   * Singleton
   */
  public static function get_instance() {

    if (!isset(self::$instance)) {

      self::$instance = new self();

    } // end if;

    return self::$instance;

  } // end get_instance;

  /**
   * Add the necessary hooks and call the installer
   *
   * @since 1.9.0
   * @return void
   */
  public function __construct() {

    add_action('wu_before_save_settings', array($this, 'saves_error_reporting_option'));

    $this->setup();

  } // end construct;

  /**
   * Listens to the saving of settings and changes the error reporting value if necessary
   *
   * @since 1.9.0
   * @param array $settings
   * @return void
   */
  public function saves_error_reporting_option($settings) {

    /**
     * Check if we are in the right tab
     */
    if (!isset($settings['trial'])) return;

    if (isset($settings['enable_error_reporting']) && $settings['enable_error_reporting']) {

      return update_site_option('wp-ultimo-enable-error-reporting', 'yes');

    } // end if;

    return update_site_option('wp-ultimo-enable-error-reporting', 'no');

  } // end saves_error_reporting_option;

  /**
   * Instantiates the reporter and add the PHP hooks so we can monitor fatal errors
   *
   * @since 1.9.0
   * @return void
   */
  public function setup() {

    /**
     * Check if the user has allowed us to collect error data
     */
    if ($this->is_reporting_enabled() === false) return;

    if (!class_exists('Raven_Autoloader')) {
      
      require_once WP_Ultimo()->path('inc/error-reporting/Raven/Autoloader.php');

      Raven_Autoloader::register();

    } // end if;

    /**
     * Creates the client
     */
    $client = new Raven_Client('https://f4fbe15fe43043c192ee438c02fa0202:07cdb534184a4caab1ac3dd15b616fb4@sentry.nextpress.co/2');

    /**
     * Sets Ultimo's path to help filter out issues not related to WP Ultimo
     */
    $client->setAppPath( WP_Ultimo()->path('') );

    /**
     * Checks the environment based on the WP_DEBUG constant
     */
    $client->setEnvironment( $this->get_environment() );

    /**
     * Sends the WP Ultimo version being run
     */
    $client->setRelease( WP_Ultimo()->version );

    /**
     * Check if the issues are in fact related to WP Ultimo before sending them over
     * @since 1.9.2
     */
    $client->setSendCallback(array($this, 'filter_non_related_errors'));

    /**
     * Send info about the network, like if this is a subdomain install
     * and if the users that encountered the issues was a super admin or not
     */
    $client->extra_context(array(
      'is_subdomain_install' => is_subdomain_install(),
      'is_super_admin'       => current_user_can('manage_network'),
      'network_admin_email'  => get_site_option('admin_email'), 
    ));

    /**
     * Install Error capturing hooks
     */
    $client->install();

  } // end setup;

  /**
   * Checks if we have the user's permission to track error data
   *
   * @since 1.9.0
   * @return boolean
   */
  public function is_reporting_enabled() {

    return get_site_option('wp-ultimo-enable-error-reporting', 'no') === 'yes';

  } // end is_reporting_enabled;

  /**
   * Returns the environment to send to Sentry
   *
   * @since 1.9.0
   * @return string
   */
  public function get_environment() {

    return defined('WP_DEBUG') && WP_DEBUG ? 'development' : 'production';

  } // end get_environment;

  /**
   * Checks the stack to make sure we are only reporting WP Ultimo related errors to the server
   * 
   * @since 1.9.2
   * @param array $data
   * @return bool
   */
  public function filter_non_related_errors($data) {
    
    foreach($data['exception']['values'] as $error) {

      foreach($error['stacktrace']['frames'] as $stackline) {

        if ($stackline['in_app'] === true) return true;

      } //end foreach;

    } // end foreach;

    return false;

  } // end filter_non_related_errors;

} // end class WU_Error_Reporting;

// Run our Class
WU_Error_Reporting::get_instance();

/**
 * Return the instance of the function
 */
function WU_Error_Reporting() {

  return WU_Error_Reporting::get_instance();

} // end WU_Error_Reporting;

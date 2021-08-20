<?php
/**
 * Pages System Info
 *
 * Displays all the relevant information about the network.
 * This is something that is super useful for us when debugging issues on users site =)
 * 
 * Based on SysInfo Plugin
 * Author: Dave Donaldson
 * Author URI: http://arcware.net
 *
 * @author      Arindo Duque
 * @category    Admin
 * @package     WP_Ultimo/Pages
 * @version     1.1.3
*/

if (!defined('ABSPATH')) {
  exit;
}

class WU_Page_System_Info extends WU_Page {

  /**
   * Check for problems to set the menu badge
   *
   * @since 1.8.1
   * @return void
   */
  public function init() {

    $this->check_problems();

  } // end init;

  /**
   * Add the actions
   *
   * @since 1.8.1
   * @return void
   */
  public function hooks() {

    /**
     * Handles the delete, download and see log actions
     * @since 1.5.0
     */
    $this->handle_action();

  } // end hooks;

  /**
   * Handles the actions for the logs and system info
   *
   * @return void
   */
  public function handle_action() {

    $logs_list = glob(WU_Logger::get_logs_folder()."*.log");

    $file = isset($_GET['file']) ? urldecode($_GET['file']) : false;

    $file_name = '';

    $contents = '';

    // Secutiry check
    if ($file && !stristr($file, WU_Logger::get_logs_folder())) {

      wp_die(__('You can see files that are not WP Ultimo\'s logs', 'wp-ultimo'));

    }

    if (!$file && !empty($logs_list)) {

      $file = !$file && !empty($logs_list) ? $logs_list[0] : false;

    } // end if;

    $file_name = str_replace(WU_Logger::get_logs_folder(), '', $file);

    $contents = $file && file_exists($file) ? file_get_contents($file) : '--';

    /**
     * Switch the different cases
     */
    if (isset($_GET['action']) && $file) {

      if ($_GET['action'] == 'download') {

        if (file_exists($file)) {

          header('Content-Type: application/octet-stream');
          header("Content-Disposition: attachment; filename=$file_name");
          header('Pragma: no-cache');
          
          readfile($file);

          exit;

        } // end if;

      } else if ($_GET['action'] == 'delete') {

        if (file_exists($file)) {

          $status = unlink($file);

          if ($status) {

            WP_Ultimo()->add_message(sprintf(__('Item <code>%s</code> successfully deleted.', 'wp-ultimo'), $file_name), 'success', true);

            $key = array_search($file, $logs_list);

            if ($key !== false) unset($logs_list[ $key ]);

            $file = isset($logs_list[0]) ? $logs_list[0] : false;

            $file_name = str_replace(WU_Logger::get_logs_folder(), '', $file);

            $contents = $file && file_exists($file) ? file_get_contents($file) : '--';

          } else {

            WP_Ultimo()->add_message(sprintf(__('Deleting file <code>%s</code> failed.', 'wp-ultimo'), $file_name), 'error', true);

          } // end if;

        } // end if;

      } // end if;

    } // end if;

    /**
     * Set the params for use on the templates
     */
    $this->file      = $file;
    $this->file_name = $file_name;
    $this->contents  = $contents;
    $this->logs_list = $logs_list;

  } // end handle_action;

  /**
   * Check the problems we have
   * 
   * @return integer
   */
  public function check_problems() {

    /**
     * Check if the logs directory exists and try to create it
     */
    if (!is_dir(WU_Logger::get_logs_folder())) {

      WU_Logger::create_logs_folder();

    } // end if;

    /**
     * Logs Directory
     */
    if (!is_writable(WU_Logger::get_logs_folder())) {

      $this->badge_count++;

    } // end if;

  } // end get_problems_count;
  
  /**
   * Outputs the System Info page
   *
   * @since 1.5.0
   * @return void
   */
  public function output() {

    WP_Ultimo()->render('meta/system-info', array(
      'system_info' => $this,
      'file'        => $this->file,
      'file_name'   => $this->file_name,
      'contents'    => $this->contents,
      'logs_list'   => $this->logs_list,
    ));

  } // end output;

  /**
   * Adds Sysinfo Page
   *
   * @return void
   */
  function add_sysinfo_page() {

    WP_Ultimo()->render('meta/system-status');

  } // end add_sysinfo_page;
  
  /**
   * Return the browser's info
   *
   * @return array
   */
  public function get_browser() {

    // http://www.php.net/manual/en/function.get-browser.php#101125.
    // Cleaned up a bit, but overall it's the same.

    $user_agent   = $_SERVER['HTTP_USER_AGENT'];
    $browser_name = 'Unknown';
    $platform     = 'Unknown';
    $version      = "";

    // First get the platform
    if (preg_match('/linux/i', $user_agent)) {

      $platform = 'Linux';

    } elseif (preg_match('/macintosh|mac os x/i', $user_agent)) {

      $platform = 'Mac';

    } elseif (preg_match('/windows|win32/i', $user_agent)) {

      $platform = 'Windows';

    } // end if;
    
    // Next get the name of the user agent yes seperately and for good reason
    if (preg_match('/MSIE/i', $user_agent) && !preg_match('/Opera/i', $user_agent)) {

      $browser_name = 'Internet Explorer';
      $browser_name_short = "MSIE";

    } elseif (preg_match('/Firefox/i', $user_agent)) {

      $browser_name = 'Mozilla Firefox';
      $browser_name_short = "Firefox";

    } elseif (preg_match('/Chrome/i', $user_agent)) {

      $browser_name = 'Google Chrome';
      $browser_name_short = "Chrome";

    } elseif (preg_match('/Safari/i', $user_agent)) {

      $browser_name = 'Apple Safari';
      $browser_name_short = "Safari";

    } elseif (preg_match('/Opera/i', $user_agent)) {

      $browser_name = 'Opera';
      $browser_name_short = "Opera";

    } elseif (preg_match('/Netscape/i', $user_agent)) {

      $browser_name = 'Netscape';
      $browser_name_short = "Netscape";

    } // end if;
    
    // Finally get the correct version number
    $known = array('Version', $browser_name_short, 'other');
    $pattern = '#(?<browser>' . join('|', $known) . ')[/ ]+(?<version>[0-9.|a-zA-Z.]*)#';

    if (!preg_match_all($pattern, $user_agent, $matches)) {
      // We have no matching number just continue
    }
    
    // See how many we have
    $i = count($matches['browser']);

    if ($i != 1) {

      // We will have two since we are not using 'other' argument yet
      // See if version is before or after the name
      if (strripos($user_agent, "Version") < strripos($user_agent, $browser_name_short)) {

        $version= $matches['version'][0];

      } else {

        $version= $matches['version'][1];

      } // end if;

    } else {

      $version= $matches['version'][0];

    } // end if;
    
    // Check if we have a number
    if ($version == null || $version == "") { 
      
      $version = "?"; 
    
    } // end if;
    
    return array(
      'user_agent' => $user_agent,
      'name'       => $browser_name,
      'version'    => $version,
      'platform'   => $platform,
      'pattern'    => $pattern
    );
  
  } // end get_browser;
  
  /**
   * Get list of all the plugins
   *
   * @return array
   */
  public function get_all_plugins() {

    return get_plugins();

  } // end get_all_plugins;
  
  /**
   * Get only the active plugins
   *
   * @return array
   */
  public function get_active_plugins() {

    return (array) get_site_option( 'active_sitewide_plugins', array() );

  } // end get_active_plugins;
  
  /**
   * Get only the active plugins on main site
   *
   * @return array
   */
  public function get_active_plugins_on_main_site() {

    return (array) get_option( 'active_plugins', array() );

  } // end get_active_plugins;
  
  /**
   * Get memory usage
   *
   * @return int
   */
  public function get_memory_usage() {

    return round(memory_get_usage() / 1024 / 1024, 2);

  } // end get_memory_usage;

  /**
   * Get all the ioptions
   *
   * @return void
   */
  public function get_all_options() {

    // Not to be confused with the core deprecated get_alloptions
    return wp_load_alloptions();

  } // end if;

  /**
   * Return all the desired WP Ultimo Settings
   * @since  1.1.5
   * @return array
   */
  public function get_all_wp_ultimo_settings() {

    $exclude = array(
      'email', 
      'logo', 
      'color', 
      'from_name', 
      'paypal', 
      'stripe', 
      'terms_content', 
      'wu-', 
      'license_key', 
      'api-', 
      'manual_payment_instructions',
    );
    
    $include = array('enable');

    $return_settings = array(); 

    foreach (WU_Settings::get_settings() as $setting => $value) {

      $add = true;

      foreach($exclude as $ex) {

        if (stristr($setting, $ex) !== false) {

          $add = false; 
          
          break;

        } // end if;

      } // end foreach;

      if ($add) {
        
        $return_settings[$setting] = $value;

      } // end if;

    } // end foreach;

    return $return_settings;

  } // end get_all_wp_ultimo_settings;

  /**
   * Get the transients om the options
   *
   * @param array $options
   * @return array
   */
  public function get_transients_in_options($options) {

    $transients = array();

    foreach ($options as $name => $value) {

      if (stristr($name, 'transient')) {

        $transients[$name] = $value;

      } // end if;

    } // end foreach;
    
    return $transients;

  } // end get_transients_in_options;
  
} // end class WU_Page_System_Info;

new WU_Page_System_Info(true, array(
  'id'         => 'wp-ultimo-system-info',
  'type'       => 'submenu',
  'capability' => 'manage_network',
  'title'      => __('System Info', 'wp-ultimo'),
  'menu_title' => __('System Info', 'wp-ultimo'),
));

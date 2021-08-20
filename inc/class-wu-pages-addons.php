<?php
/**
 * Pages Add-ons
 *
 * Handles the addition of the Addons Page
 * @since  0.0.1
 * @since  1.1.4 Let users now buy and remotely install add-ons
 * @since  1.8.2 Uses the new WU_Page version
 *
 * @author      Arindo Duque
 * @category    Admin
 * @package     WP_Ultimo/Pages
 * @version     0.0.1
*/

if (!defined('ABSPATH')) {
  exit;
}

class WU_Page_Addons extends WU_Page {

  /**
   * Remote URL for getting the add-ons
   *
   * @var string
   */
  public $remote_url = 'https://versions.nextpress.co/updates/?slug=wp-ultimo';

  /**
   * Checks if this user is a golden ticket account
   *
   * @var boolean
   */
  public $golden_ticket = false;

  /**
   * Checks the type of the golden ticket account
   *
   * @var boolean
   */
  public $golden_ticket_type = 0;

  /**
   * Keeps the refresh time for the add-ons list
   *
   * @var integer
   */
  public $refresh_time = 7 * DAY_IN_SECONDS;

  /**
   * Initializes the page
   *
   * @return void
   */
  public function init() {

    // Add a options to count new addons;
    $this->addon_count = get_network_option(null, 'wu-addons-count', 2);

    // Add license key to remote url
    $this->remote_url .= '&license_key=' . rawurlencode(WU_Settings::get_setting('license_key', ''));

    // Checks if the user has a golden ticket
    $this->golden_ticket = $this->get_remote_golden_ticket();

    $this->golden_ticket_type = (int) get_site_transient('wu_golden_ticket_type');

    // Handles the ajax actions
    add_action('wp_ajax_wu_install_addon', array($this, 'install_plugin'));

    // Sets the badge count
    $this->badge_count = $this->get_new_addon_count();

  } // end init;

  /**
   * Get the add-on count from our remote server and compares to the current count
   *
   * @return integer
   */
  public function get_new_addon_count() {

    $addon_count = (int) $this->get_remote_addons_count();
    
    return $addon_count - $this->addon_count;

  } // end get_new_addon_count;

  /**
   * Register the scripts we will need for this page
   *
   * @return void
   */
  public function register_scripts() {

    $suffix = WP_Ultimo()->min;

    wp_register_script('wu-addons-page', WP_Ultimo()->get_asset("wu-addons-page$suffix.js", 'js'), array('wp-backbone', 'wp-a11y'), WP_Ultimo()->version);

  } // end register_scripts;

  /**
   * Handles the installation of add-ons and other plugins
   * 
   * @return void
   */
  public function install_plugin() {

    if (!current_user_can('manage_network') || !check_ajax_referer('wu_install_addon', 'nonce', false)) {
      
      die(__('Not authorized.', 'wp-ultimo'));

    } // end if;

    if (!isset($_POST['download_url']) || filter_var($_POST['download_url'], FILTER_VALIDATE_URL) === false) die(__('Invalid download URL', 'wp-ultimo'));

    //includes necessary for Plugin_Upgrader and Plugin_Installer_Skin
    include_once( ABSPATH . 'wp-admin/includes/file.php' );
    include_once( ABSPATH . 'wp-admin/includes/misc.php' );
    include_once( ABSPATH . 'wp-admin/includes/class-wp-upgrader.php' );

    $upgrader = new Plugin_Upgrader(new Plugin_Installer_Skin(array()));

    /**
     * We check if the URL is one of our websites
     */
    $allowed_sites = array(
      'http://nextpress.co', 'https://nextpress.co',  // New Domain
      'http://versions.nextpress.co', 'https://versions.nextpress.co',  // New Domain
      'http://weare732.com', 'https://weare732.com'   // Old Updates Domain
    );
    
    if (WP_DEBUG) {

      $allowed_sites[] = 'http://localhost';
      $allowed_sites[] = 'http://wp-ultimo.local';

    } // end if;

    $allowed = false;

    foreach ($allowed_sites as $allowed_site) {

      if (strpos($_POST['download_url'], $allowed_site) === 0) {

        $allowed = true;

        break;

      } // end if;

    } // end foreach;

    if ($allowed) {

      $upgrader->install($_POST['download_url']); 
      
      die;

    } else {

      die(__('Package not valid.', 'wp-ultimo'));

    } // end if;

  } // end install_plugin;

  /**
   * Get the remote URL
   * 
   * @param  string $action The action to be performed  
   * @return atring         The right remote URL to our updates API
   */
  public function get_remote_url($action = 'addons') {

    return add_query_arg(array(
      'action'            => $action,
      'installed_version' => WP_Ultimo()->version,
    ), $this->remote_url);

  } // end get_remote_url;

  /**
   * Get the remote add-ons count
   * 
   * @return interger   The number of addons on our remote server
   */
  public function get_remote_addons_count() {

    $saved_count = get_site_transient('wu_saved_addon_count');

    if ($saved_count === false) {

      $response = wp_remote_get($this->get_remote_url('addons_count'));

      if (!is_wp_error($response)) {

        $body = wp_remote_retrieve_body($response); // use the content

        // Let's save that for the next 20 minutes
        set_site_transient('wu_saved_addon_count', $body, $this->refresh_time);

        return is_numeric($body) ? $body : 0;

      } else {

        return (int) $this->addon_count;

      } // end if;

    } else {

      return (int) $saved_count;

    } // end if;

  } // end get_addons_count;

  /**
   * Get if this is a golden ticket
   * 
   * @return boolean  Checks if that user has a golden ticket
   */
  public function get_remote_golden_ticket() {

    $is_golden = get_site_transient('wu_golden_ticket');
    
    $golden_type = get_site_transient('wu_golden_ticket_type');

    if ($is_golden === false || $golden_type === false) {

      $response = wp_remote_get($this->get_remote_url('golden_ticket'));

      if (!is_wp_error($response)) {

        $body = wp_remote_retrieve_body($response); // use the content

        if (is_numeric($body)) {

          $is_golden = (boolean) $body ? 'yes' : 'no';

        } else {

          $is_golden = 'no';

        } // end if;

        // Let's save that for the next 20 minutes
        set_site_transient('wu_golden_ticket', $is_golden, $this->refresh_time);

        set_site_transient('wu_golden_ticket_type', (int) $body, $this->refresh_time);

        return is_numeric($body) ? $body : false;

      } else {

        return false;

      } // end if;

    } else {

      return $is_golden == 'yes';

    } // end if;

  } // end get_addons_count;

  /**
   * Call the API to get the Add-on list
   * 
   * @return array Add-on list
   */
  public function get_remote_addons_list() {
    
    $response = wp_remote_get($this->get_remote_url('addons'));

    if (!is_wp_error($response)) {
      
      $body = wp_remote_retrieve_body($response); // use the content
      
    } else return array(
      'no-conection'  => (object) array(
        'name'        => 'No connection =/',
        'type'        => 'stand-alone', // Can be recommended, stand-alone, add-on
        'image_url'   => WP_Ultimo()->get_asset('no-connection.png'),
        'description' => 'You are currently offline, so it was impossible to retrieve the add-ons list. Try again later.',
        'url'         => '#',
        'product_id'  => 'no-conection',
        'sale'        => false,
        'categories'  => array(),
        'tags'        => array(),
        'author'      => 'NextPress',
        'author_url'  => 'https://nextpress.co',
        'offline'     => true,
      ),
    );

    // List of addons
    $addons = json_decode($body);

    // Return the body
    return (array) $addons;

  } // end get_remote_addons_list;

  /**
   * Get the installed plugins
   *
   * @return array
   */
  public function get_installed_plugins() {

    return implode(' - ', array_keys(get_plugins()));

  } // end get_installed_plugins;

  /**
   * Checks if a given plugin is installed
   *
   * @param string $plugin_slug
   * @return boolean
   */
  public function is_plugin_installed($plugin_slug) {

    return stristr($this->get_installed_plugins(), $plugin_slug) !== false;

  } // end is_plugin_installed;

  /**
   * Prepare Plugins for JS on our new plugins page
   * 
   * @return array
   */
  public function prepare_addons_for_js() {

    $all_addons = apply_filters( 'all_addons', $this->get_remote_addons_list() );

    $prepared_addons = array();

    $categories = array();

    foreach($all_addons as $addon_slug => $addon) {

      $categories = array_merge($categories, $addon->categories);

      $active = $this->is_plugin_installed($addon_slug);

      $this_addon = array(
        'id'            => $addon_slug,
        'name'          => $addon->name,
        'screenshot'    => array($addon->image_url), // @todo multiple
        'description'   => $addon->description,
        'author'        => $addon->author,
        'authorAndUri'  => $addon->author,
        'version'       => '',
        'tags'          => implode(', ', $addon->categories),
        'parent'        => false,
        'active'        => $active, // $addon_slug === $current_theme,
        'installed'     => $active,
        'hasUpdate'     => false,
        'hasPackage'    => false,
        'update'        => false,
        'network'       => false,
        
        'sale'          => $addon->sale,
        'download'      => property_exists($addon, 'download') ? $addon->download : false,
        'url'           => $addon->url,
        'golden_ticket' => $this->golden_ticket || (property_exists($addon, 'free') && $addon->free),
        
        'actions'       => array(
          'install'       => '#',
          'buy'           => "https://wpultimo.com/addons?addon={$addon_slug}",
          'moreInfo'      => $addon->url
        ),
      );

      $prepared_addons[] = $this_addon;

    } // end foreach;

    $categories = array_filter($categories, function($item) {

      return $item !== 'Free' && $item !== 'Premium';

    });

    sort($categories);

    return array($categories, $prepared_addons);

  } // end prepare_addons_for_js;
  
  /**
   * Displays the page content
   * 
   * @return void
   */
  public function output() {

    // Get the animate css
    wp_enqueue_style('animate-css');

    // Addons
    list($categories, $addons) = $this->prepare_addons_for_js();

    // Update the count after the user enters
    update_network_option(null, 'wu-addons-count', count($addons));

    // Render, passing the add-on lists
    WP_Ultimo()->render('meta/addons', array(
      'addons'        => $addons,
      'categories'    => $categories,
      'golden_ticket' => $this->golden_ticket,
      'addons_count'  => count($addons),
      'golden_ticket_type' => $this->golden_ticket_type,
    ));

  } // end output;
  
} // end class WU_Page_Addons;

new WU_Page_Addons(true, array(
  'id'         => 'wp-ultimo-addons',
  'type'       => 'submenu',
  'capability' => 'manage_network',
  'title'      => __('Add-ons', 'wp-ultimo'),
  'menu_title' => __('Add-ons', 'wp-ultimo'),
));

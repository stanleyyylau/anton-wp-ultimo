<?php
/**
 * Jumper Class
 *
 * Little helper interface ta allows super admins to jump around admin pages faster
 * 
 * @since       1.6.0
 * @author      Arindo Duque
 * @category    Admin
 * @package     WP_Ultimo/Jumper
 * @version     0.0.1
 */

if (!defined('ABSPATH')) {
  exit;
}

class WU_Jumper {

  /**
   * Makes sure we are only using one instance of the plugin
   * @var object WU_Jumper
   */
  public static $instance;

  /**
   * Returns the instance of WP_Ultimo
   * @return object A WU_Jumper instance
   */
  public static function get_instance() {
    if (null === self::$instance) self::$instance = new self();
    return self::$instance;
  } // end get_instance;

  /**
   * Set the important hooks
   */
  function __construct() {

    add_filter('wu_settings_section_tools', array($this, 'add_settings'), 20);

    // Only for super admins
    if (!current_user_can('manage_network') || !WU_Settings::get_setting('enable_jumper') ) return;

    // Adds the search block
    add_action('admin_footer', array($this, 'render_jumper_block'));

    // Add Footer message
    add_filter('update_footer', array($this, 'add_jumper_footer_message'), 200);

    // Add extra WP Ultimo links
    add_filter('wu_link_list', array($this, 'add_wp_ultimo_extra_links'));
    add_filter('wu_link_list', array($this, 'add_user_custom_links'));

    // Refresh cache
    add_action('wu_after_save_settings', array($this, 'clear_jump_cache_on_save'));
    add_action('admin_init', array($this, 'rebuild_menu'));

  } // end construct;

  /**
   * Reset the menu cache on save
   *
   * @param array $settings
   * @return void
   */
  public function clear_jump_cache_on_save($settings) {

    if (isset($settings['jumper_custom_links'])) {

      delete_site_transient('wu-link-list-timestamp');

    } // end if;

  } // end clear_jump_cache_on_save;

  /**
   * Force rebuild the menu
   *
   * @return void
   */
  public function rebuild_menu() {

    if (isset($_GET['wu-rebuild-jumper']) && current_user_can('manage_network')) {

      /**
       * TODO: Maybe add a nonce check here?
       */

      delete_site_transient('wu-link-list-timestamp');

      wp_redirect(network_admin_url());

      exit;

    } // end if;

  } // end rebuild_menu;

  /**
   * Get user links added by the user
   *
   * @return array
   */
  public function get_user_custom_links() {

    $treated_lines = array();

    $saved_links = WU_Settings::get_setting('jumper_custom_links');

    $lines = explode(PHP_EOL, $saved_links);

    foreach($lines as $line) {

      $link_elements = explode(':', $line, 2);

      if (count($link_elements) == 2) {

        $treated_lines[ trim($link_elements[1]) ] = trim($link_elements[0]);

      } // end if;

    } // end foreach;

    return $treated_lines;

  } // end get_user_custom_links;

  /**
   * Get the formatted user links and add them to the Jumper
   *
   * @param array $links
   * @return array
   */
  public function add_user_custom_links($links) {

    $custom_links = $this->get_user_custom_links();

    if (!empty($custom_links)) {

      $links[__('Custom Links', 'wp-ultimo')] = $custom_links;

    } // end if;

    return $links;

  } // end add_user_custom_links;

  /**
   * Adding the custom settings to enable or disable the jumper
   *
   * @param array $settings
   * @return array
   */
  public function add_settings($settings) {

    $new_fields = array(
      'tools'    => array(
        'title'         => __('Tools', 'wp-ultimo'),
        'desc'          => __('Turn on and off the extra tools available on WP Ultimo.', 'wp-ultimo'),
        'type'          => 'heading',
      ),

      'enable_jumper'   => array(
        'title'         => __('Enable Jumper', 'wp-ultimo'),
        'desc'          => __('This will enable the Jumper, a feature that let you jump between pages by using a keyboard shortcut.', 'wp-ultimo'),
        'tooltip'       => '',
        'type'          => 'checkbox',
        'default'       => 1,
      ),

      'jumper_key'   => array(
        'title'         => __('Trigger Key', 'wp-ultimo'),
        'desc'          => __('Change the keyboard key used in conjuction with ctrl + alt (or cmd + option), to trigger the Jumper box.', 'wp-ultimo'),
        'tooltip'       => '',
        'type'          => 'text',
        'default'       => 'g',
        'require'       => array('enable_jumper' => 1),
      ),

      'jumper_custom_links' => array(
        'title'         => __('Custom Links', 'wp-ultimo'),
        'desc'          => __('Use this textarea to add custom links to the Jumper. Add one per line, with the format "Title : url".', 'wp-ultimo'),
        'placeholder'   => __('Tile of Custom Link : http://link.com', 'wp-ultimo'),
        'tooltip'       => '',
        'type'          => 'textarea',
        'default'       => '',
        'require'       => array('enable_jumper' => 1),
      ),

      'jumper_display_tip'   => array(
        'title'         => __('Display the Jumper "Quick Tip" on the Footer', 'wp-ultimo'),
        'desc'          => __('Uncheck this checbox to remove the Quick Tip line from the WP Admin Panel footer.', 'wp-ultimo'),
        'tooltip'       => '',
        'type'          => 'checkbox',
        'default'       => 1,
        'require'       => array('enable_jumper' => 1),
      ),
    );

    return array_merge($settings, $new_fields);

  } // end add_settings;

  /**
   * Add the WP Ultimo links to the block
   *
   * @param array $links
   * @return array
   */
  public function add_wp_ultimo_extra_links($links) {

    if (isset($links['WP Ultimo'])) {

      $settings_tabs = array(
        'general'        => __('General', 'wp-ultimo'),
        'network'        => __('Network Settings', 'wp-ultimo'),
        'gateways'       => __('Payment Gateways', 'wp-ultimo'),
        'domain_mapping' => __('Domain Mapping & SSL', 'wp-ultimo'),
        'emails'         => __('Emails', 'wp-ultimo'),
        'styling'        => __('Styling', 'wp-ultimo'),
        'tools'          => __('Tools', 'wp-ultimo'),
        'advanced'       => __('Advanced', 'wp-ultimo'),
        'activation'     => __('Activation & Support', 'wp-ultimo'),
      );

      foreach ($settings_tabs as $tab => $tab_label) {

        $url = network_admin_url('admin.php?page=wp-ultimo&wu-tab='.$tab);

        $links['WP Ultimo'][$url] = sprintf(__('Settings: %s', 'wp-ultimo'), $tab_label);

      } // end foreach;

      $links['WP Ultimo'][ network_admin_url('admin.php?page=wp-ultimo&wu-tab=tools') ] = __('Settings: Webhooks', 'wp-ultimo');

      $links['WP Ultimo'][ network_admin_url('admin.php?page=wp-ultimo-system-info&wu-tab=logs') ] = __('System Info: Logs', 'wp-ultimo');

      /**
       * Adds Main Site Dashboard
       */
      if (isset($links[__('Sites')])) {

        $links[__('Sites')][ get_admin_url( get_current_site()->blog_id ) ] = __('Main Site Dashboard', 'wp-ultimo');

      } // end if;

    } // end if;

    return $links;

  } // end add_wp_ultimo_extra_links;

  /**
   * Get the keys for each OS
   *
   * @param string $os
   * @return array
   */
  function get_keys($os = 'win') {

    $keys = array(
      'win' => array('ctrl', 'alt', $this->get_defined_trigger_key()),
      'osx' => array('command', 'option', $this->get_defined_trigger_key()),
    );

    return isset($keys[$os]) ? $keys[$os] : $keys['win'];

  } // end get_keys;

  /**
   * Get the defined trigger key
   *
   * @return string
   */
  function get_defined_trigger_key() {
    
    return substr( WU_Settings::get_setting('jumper_key'), 0, 1 ); 

  } // end get_defined_trigger_key;

  /**
   * Adds the quick tip text for the jumper
   *
   * @param string $text
   * @return string
   */
  public function add_jumper_footer_message($text) {

    if (!WU_Settings::get_setting('jumper_display_tip')) return $text;

    $os = stristr($_SERVER['HTTP_USER_AGENT'], 'mac') ? 'osx' : 'win';

    $keys = $this->get_keys($os);

    $html = '';

    foreach($keys as $key) {

      $html .= '<span class="wu-keys-key">'. $key .'</span>+';

    } // end foreach;

    $html = trim($html, '+');

    return '<span class="wu-keys">' . sprintf(__('<strong>Quick Tip:</strong> Use %s to jump between pages.', 'wp-ultimo'), $html) . '</span>' . $text;

  } // end add_jumper_footer_message;

  /**
   * Adds the block in the footer of the admin page
   */
  public function render_jumper_block() {

    $suffix = WP_Ultimo()->min;

    wp_register_script('wu-jumper', WP_Ultimo()->get_asset("wu-jumper$suffix.js", 'js'), array('jquery'), WP_Ultimo()->version, true);

    wp_localize_script('wu-jumper', 'wu_jumper_vars', array(
      'not_found_message' => __('Nothing found for', 'wp-ultimo'),
      'trigger_key'       => WU_Settings::get_setting('jumper_key'),
      'network_base_url'  => network_admin_url(),
      'base_url'          => get_admin_url( get_current_site()->blog_id ),
    ));

    wp_enqueue_script('wu-jumper');
    
    // render the view block
    WP_Ultimo()->render('meta/jumper', array(
      'module' => $this,
    ));
    
  } // end render_jumper_block;

  /**
   * Get menu page URL, based on the type of the link
   *
   * @param string $url
   * @return string
   */
  public function get_menu_page_url($url) {

    $final_url = menu_page_url($url, false);

    return str_replace( admin_url(), network_admin_url(), $final_url);

  } // end get_menu_page_url;
  
  /**
   * Get the target URL
   *
   * @param string $url
   * @return string
   */
  public function get_target_url($url) {

    if (strpos($url, 'http') !== false) {
      return $url;
    } 

    if (strpos($url, '.php') !== false) {
      return network_admin_url($url);
    } 

    return $this->get_menu_page_url($url);

  } // end get_target_url;

  /**
   * Build Link List based on the Menu and Submenu globals, cache it for 10 minutes
   *
   * @return array
   */
  public function build_link_list() {

    global $menu, $submenu;

    // This variable is going to carry our options
    $choices = array();
    
    // Prevent first run bug
    if (!is_array($menu) || !is_array($submenu)) return array();
    
    // Loop all submenus so que can get our final
    foreach ($submenu as $menu_name => $submenu_items) {
      
      // Add title
      $title = $this->search_recursive($menu_name, $menu);
      $title = preg_replace('/[0-9]+/', '', strip_tags($title[0]));
      
      // If parent does not exists, skip
      if (!empty($title) && is_array($submenu_items)) {
        
        // We have to loop now each submenu
        foreach ($submenu_items as $submenu_item) {

          $url = $this->get_target_url($submenu_item[2]);
          
          // Add to our choiches the admin urls
          $choices[$title][$url] = preg_replace('/[0-9]+/', '', strip_tags($submenu_item[0]));
          
        } // end foreach;
        
      } // end if;
      
    } // end foreach;

    /**
     * Allow Filtering
     */
    $choices = apply_filters('wu_link_list', $choices);

    // Save for later use
    update_network_option(null, 'wu-link-list', $choices);

    // Save the timestamp, so we now when to rebuild the menu
    set_site_transient('wu-link-list-timestamp', true, 5 * MINUTE_IN_SECONDS);

    // Return our choices
    return $choices;

  } // end build_link_list;

  /**
   * Get the saved menu
   *
   * @return array
   */
  public function get_saved_menu() {

    $saved_menu = get_network_option(null, 'wu-link-list');

    return $saved_menu ?: array();

  } // end get_saved_menu;
  
  /**
   * Get Internal Links choice
   */
  public function get_link_list() {
    
    $should_rebuild_menu = !get_site_transient('wu-link-list-timestamp');

    return $should_rebuild_menu && is_network_admin() ? $this->build_link_list() : $this->get_saved_menu();
    
  } // end getLinks
  
  /**
   * Search a array recursivelly
   *
   * @param string $needle
   * @param array  $haystack
   * @return mixed
   */
  public function search_recursive($needle, $haystack) {
    
    // Begin search
    foreach($haystack as $key => $value) {

      $current_key = $key;

      if ($needle === $value OR (is_array($value) && $this->search_recursive($needle, $value) !== false)) return $value;

    } // end foreach;
    
    // Not found
    return false;
    
  } // end search_recursive;

} // end class WU_Jumper;

/**
 * Returns the singleton
 */
function WU_Jumper() {
  
  return WU_Jumper::get_instance();
  
}

// Initialize
WU_Jumper();

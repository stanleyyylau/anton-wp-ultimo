<?php
/**
 * Pages About
 *
 * Handles the addition of the About Page
 *
 * @author      WPUltimo
 * @category    Admin
 * @package     WPUltimo/Pages
 * @version     0.0.1
*/

if (!defined('ABSPATH')) {
  exit;
}

class WU_Page_Settings extends WU_Page {

  /**
   * Overrides the parent init to add additional actions
   *
   * @since 1.8.2
   * @return void
   */
  public function init() {

    parent::init();

    add_action('network_admin_menu', array($this, 'add_separator'), 100);

  } // end init;

  /**
   * Adds a menu separator before WP Ultimo so it has its own distinctive section on the menu
   *
   * @since 1.8.2
   * @return void
   */
  public function add_separator() {

    $this->add_admin_menu_separator(10101009, true);

  } // end add_separator;

  /**
   * Add new admin menu separator
   * 
   * @param integer $position       Position to put the separator
   * @param boolean [$after = true] If it is to be added after
   */
  public function add_admin_menu_separator($position, $after = true) {
    
    global $menu;
    
    $index = 0;
    
    foreach($menu as $offset => $section) {
      
      if (substr($section[2], 0, 9) == 'separator') $index++;
      
      if ($offset >= $position && $after) {
        
        $menu[$position] = array('', 'read', "separator{$index}", '', 'wp-menu-separator');

        break;

      } else if ($offset <= $position && !$after) {
        
        $menu[$position] = array('', 'read', "separator{$index}", '', 'wp-menu-separator');

        break;

      } // end if;
      
    } // end foreach;
    
    // Resort
    ksort($menu);
    
  } // end add_admin_menu_separator;

  /**
   * Register widgets for this particular page
   *
   * @since 1.8.2
   * @return void
   */
  public function register_widgets() {

    add_meta_box('wu-forum', __('WP Ultimo - Links', 'wp-ultimo'), array($this, 'output_widget_help_links'), get_current_screen()->id, 'normal');

  } // end register_widgets;

  /**
   * Outputs the help links widgets of the settings page
   *
   * @since 1.8.2
   * @return void
   */
  public function output_widget_help_links() {

    WP_Ultimo()->render('widgets/settings/help-links');

  } // end output_widget_help_links;
  
  /**
   * Sets the output template for this particular page
   *
   * @since 1.8.2
   * @return void
   */
  public function output() {

    wp_enqueue_script('dashboard');

    /**
     * Fix the GET variable tab
     */
    $_GET['wu-tab'] = isset($_GET['wu-tab']) ? $_GET['wu-tab'] : 'general';

    // Handles saving
    if (isset($_POST['wu_action']) && $_POST['wu_action'] == 'save_settings') {

      if (wp_verify_nonce($_POST['_wpnonce'], 'wu_settings')) {

        WU_Settings::save_settings();

        wp_redirect(network_admin_url('admin.php?page=wp-ultimo&updated=1&wu-tab='.$_GET['wu-tab']));

        exit;

      } // end if;

    } // end if;
    
    // Render the Page
    WP_Ultimo()->render('settings/base');

  } // end output;
  
} // end class WU_Page_Settings;

new WU_Page_Settings(true, array(
  'id'            => 'wp-ultimo',
  'type'          => 'menu',
  'title'         => __('WP Ultimo', 'wp-ultimo'),
  'menu_title'    => __('WP Ultimo', 'wp-ultimo'),
  'submenu_title' => __('Settings', 'wp-ultimo'),
  'menu_icon'     => 'dashicons-wpultimo',
  'position'      => 10101010,
));

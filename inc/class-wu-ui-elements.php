<?php
/**
 * UI Elements
 *
 * Adds UI elements with important triggers for API calls, actions and more.
 *
 * @category   WP Ultimo
 * @package    WP_Ultimo
 * @author     Arindo Duque <arindo@wpultimo.com>
 * @since      1.8.2
 */

if (!defined('ABSPATH')) {
  exit; // Exit if accessed directly.
}

if (!class_exists('WU_UI_Elements')) :

class WU_UI_Elements {

  /**
   * Makes sure we are only using one instance of the plugin
   * 
   * @since 1.8.2
   * @var WU_UI_Elements
   */
  public static $instance;

  /**
   * Holds the branded pages
   * This was moved here from the main class
   *
   * @since 1.8.2
   * @var array
   */
  public $pages = array();

  /**
   * Returns the instance of WU_UI_Elements
   * 
   * @since 1.8.2
   * @return object A WU_UI_Elements instance
   */
  public static function get_instance() {

    if (null === self::$instance) self::$instance = new self();

    return self::$instance;
    
  } // end get_instance;

  /**
   * Adds the hooks and actions
   *
   * @since 1.0.0
   * @return void
   */
  public function __construct() {

    add_action('admin_bar_menu', array($this, 'add_top_bar_menus'), 50);

    add_action('current_screen', array($this, 'add_help_tabs'));
    
    add_action('in_admin_header', array($this, 'display_header'));
    
    add_action('in_admin_header', array($this, 'display_messages'));

    add_filter('admin_footer_text', array($this, 'brand_footer'));

    add_action('wp_head', array($this, 'add_wp_ultimo_version_metatag'));

    $plugin_basename = plugin_basename( WP_Ultimo()->get_config('file') );

    add_filter('network_admin_plugin_action_links_' . $plugin_basename, array($this, 'plugin_add_links'));

  } // end construct;

  /**
   * Checks if this there is a admin theme actiove in here. 
   * If that's the case, we limit the branding we do on WordPress admin pages
   *
   * @since 1.9.2
   * @return boolean
   */
  public function has_admin_theme_active() {

    $has_admin_theme_active = false;

    $admin_theme_classes = apply_filters('wu_admin_theme_classes', array(
      'MaterialWP',
      'PROTheme',
      'WP_Admin_Theme_CD_Options',
      'Clientside',
    ));

    foreach ($admin_theme_classes as $admin_theme_class) {

      if (class_exists($admin_theme_class)) {

        $has_admin_theme_active = true;

      } // end if;

    } // end foreach;

    return apply_filters('wu_has_admin_theme_active', $has_admin_theme_active, $admin_theme_classes);

  } // end has_admin_theme_active;

  /**
   * All pages added to this pages array will get our branding top and help tabs
   * 
   * @since 1.8.2
   * @param string $page
   */
  public function add_page_to_branding($page) {

    $this->pages[] = $page;

  } // end add_page_to_branding;

  /**
   * Checks if a given page is one of out branded pages
   *
   * @since 1.8.2
   * @return boolean
   */
  public function is_branded_page() {

    $screen = get_current_screen();

    return apply_filters('wu_is_branded_page', in_array(str_replace('-network', '', $screen->id), $this->pages), $screen->id, $this->pages);

  } // end is_branded_page;

  /**
   * Add meta tag to tell us which WP Ultimo version is being used
   *
   * @since 1.7.4
   * @return void
   */
  public function add_wp_ultimo_version_metatag() {

    $version = WP_Ultimo()->version;

    if (!defined('WP_ULTIMO_DISPLAY_VERSION_META') || true === WP_ULTIMO_DISPLAY_VERSION_META) {
      
      echo "<meta name='generator' content='WP Ultimo $version'>";

    } // end if;
    
  } // end add_wp_ultimo_version_metatag;

  /**
   * Adds custom links to our plugin list table
   * 
   * @since  1.1.4
   * @param  array $links Links added by WordPress by default
   * @return array        
   */
  public function plugin_add_links($links) {

    $docs_link = WU_Links()->get_link('documentation');

    $settings_link = sprintf('<a href="%s">%s</a>', network_admin_url('admin.php?page=wp-ultimo'), __('Settings', 'wp-ultimo'));
    
    $docs_link = sprintf('<a target="_blank" href="%s">%s</a>', $docs_link, __('Documentation', 'wp-ultimo'));
    
    array_push($links, $settings_link, $docs_link);
    
    return $links;

  } // end plugin_add_links;

  /**
   * Fires on the admin header to display messages
   */
  public function display_messages() {

    $messages = WP_Ultimo()->get_messages(is_network_admin());

    WP_Ultimo()->render('notices/notices', array(
      'messages' => $messages
    ));

  } // end display_messages;

  /**
   * Adds our branding to the footer
   *
   * @since 1.8.2
   * @param string $footer
   * @return string
   */
  public function brand_footer($footer) {

    if (! $this->is_branded_page()) return $footer;

    return sprintf('%s'.__('Thanks for using <strong>WP Ultimo</strong>. Developed by <a terget="_blank" href="%s">NextPress</a>', 'wp-ultimo').'%s', '<span id="footer-thankyou">', 'https://nextpress.co?utm-source=wu-footer', '</span>');

  } // end brand_footer;

  /**
   * Displays the small header of WP Ultimo on all of our registered pages.
   * Includes server clock to help super admmins manage subscriptions
   *
   * @since 1.8.2
   * @return void
   */
  public function display_header() {
      
    if (!$this->is_branded_page() || $this->has_admin_theme_active()) return;
      
    WP_Ultimo()->render('meta/header');
      
  } // end display_header;

  /**
   * Add the help tabs
   *
   * @since 1.9.0
   * @param WP_Screen $screen
   * @return void
   */
  public function add_help_tabs($screen) {

    if (!$this->is_branded_page()) return;

    $wu_help_tabs = array(
      'wu-intro'        => __('Introduction', 'wp-ultimo'),
      'wu-shortcodes'   => __('Shortcodes', 'wp-ultimo'),
      'wu-setup'        => __('Setup Wizard', 'wp-ultimo'),
      'wu-notes'        => __('Important Notes', 'wp-ultimo'),
    );
    
    foreach($wu_help_tabs as $template => $title) {

      ob_start();

        WP_Ultimo()->render("help/tab-$template");
      
      $content = ob_get_clean();

      $screen->add_help_tab(array( 
        'id'       => $template,
        'title'    => $title,
        'content'  => $content,
      ));

    } // end foreach;
    
    /**
     * Help Sidebar
     */
    ob_start();

      WP_Ultimo()->render('help/help-sidebar');

    $content = ob_get_clean();
    
    $screen->set_help_sidebar($content);

  } // end add_help_tabs;

  /**
   * Adds the WP Ultimo topbar shortcut menu
   *
   * @since 1.1.0
   * @param WP_Admin_Bar $wp_admin_bar
   * @return void
   */
  public function add_top_bar_menus($wp_admin_bar) {

    // Onlt for super admins
    if (!current_user_can('manage_network')) return;

    // Add Parent element
    $parent = array(
      'id'      => 'wp-ultimo',
      'title'   => __('WP Ultimo', 'wp-ultimo'),
      'href'    => network_admin_url('admin.php?page=wp-ultimo'),
      'meta'    => array(
        'class' => 'wp-ultimo-top-menu', 
        'title' => __('Go to the settings page', 'wp-ultimo'),
    ));

    // Subscriptions
    // @since 1.1.2
    $subscriptions = array(
      'id'      => 'wp-ultimo-subscriptions',
      'parent'  => 'wp-ultimo',
      'title'   => __('Manage Subscriptions', 'wp-ultimo'),
      'href'    => network_admin_url('admin.php?page=wp-ultimo-subscriptions'), 
      'meta'    => array(
        'class' => 'wp-ultimo-top-menu', 
        'title' => __('Go to the subscriptions page', 'wp-ultimo'),
    ));

    // Settings
    $settings = array(
      'id'      => 'wp-ultimo-settings',
      'parent'  => 'wp-ultimo',
      'title'   => __('Settings', 'wp-ultimo'),
      'href'    => network_admin_url('admin.php?page=wp-ultimo'), 
      'meta'    => array(
        'class' => 'wp-ultimo-top-menu', 
        'title' => __('Go to the settings page', 'wp-ultimo'),
    ));

    // Plans
    $plans = array(
      'id'      => 'wp-ultimo-plans',
      'parent'  => 'wp-ultimo',
      'title'   => __('Plans', 'wp-ultimo'),
      'href'    => network_admin_url('admin.php?page=wp-ultimo-plans'), 
      'meta'    => array(
        'class' => 'wp-ultimo-top-menu', 
        'title' => __('Go to the plans page', 'wp-ultimo'),
    ));

    // Coupons
    $coupons = array(
      'id'      => 'wp-ultimo-coupons',
      'parent'  => 'wp-ultimo',
      'title'   => __('Coupons', 'wp-ultimo'),
      'href'    => network_admin_url('admin.php?page=wp-ultimo-coupons'), 
      'meta'    => array(
        'class' => 'wp-ultimo-top-menu', 
        'title' => __('Go to the coupons page', 'wp-ultimo'),
    ));
    
    // Add it to the top bar  
    $wp_admin_bar->add_node($parent);
    $wp_admin_bar->add_node($subscriptions);
    $wp_admin_bar->add_node($settings);
    $wp_admin_bar->add_node($plans);
    $wp_admin_bar->add_node($coupons);

    /**
     * Add the submenus
     */
    $settings_tabs = array(
      'general'        => __('General', 'wp-ultimo'),
      'network'        => __('Network Settings', 'wp-ultimo'),
      'gateways'       => __('Payment Gateways', 'wp-ultimo'),
      'domain_mapping' => __('Domain Mapping & SSL', 'wp-ultimo'),
      'emails'         => __('Emails', 'wp-ultimo'),
      'styling'        => __('Styling', 'wp-ultimo'),
      'tools'          => __('Tools', 'wp-ultimo'),
      'advanced'       => __('Advanced', 'wp-ultimo'),
      'export-import'  => __('Export & Import', 'wp-ultimo'),
      'activation'     => __('Activation & Support', 'wp-ultimo'),
    );

    foreach ($settings_tabs as $tab => $tab_label) {

      $settings_tab = array(
        'id'      => 'wp-ultimo-settings-' . $tab,
        'parent'  => 'wp-ultimo-settings',
        'title'   => $tab_label,
        'href'    => network_admin_url('admin.php?page=wp-ultimo&wu-tab=') . $tab, 
        'meta'    => array(
          'class' => 'wp-ultimo-top-menu', 
          'title' => __('Go to the settings page', 'wp-ultimo'),
      ));

      $wp_admin_bar->add_node($settings_tab);

    } // end foreach;

  } // end add_billwerk_topbar_menu;

} // end WU_UI_Elements;

/**
 * Returns the singleton
 */
function WU_UI_Elements() {

  return WU_UI_Elements::get_instance();

} // end WU_Links;

WU_UI_Elements();

endif;
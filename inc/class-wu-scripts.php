<?php
/**
 * Links Class
 *
 * This helper class allow us to keep our external link references in one place for better control;
 * Links are also filterable;
 * 
 * @since       1.7.0
 * @author      Arindo Duque
 * @category    Admin
 * @package     WP_Ultimo/Links
 * @version     0.0.1
 */

if (!defined('ABSPATH')) {
  exit;
}

class WU_Scripts {

  /**
   * Makes sure we are only using one instance of the class
   *
   * @since 1.8.2
   * @var WU_Scripts
   */
  public static $instance;

  /**
   * Keeps a copy of the plugin version for caching purposes
   *
   * @since 1.8.2
   * @var string
   */
  public $version = '1.0.0';

  /**
   * Returns the instance of WP_Ultimo
   * 
   * @return object A WU_Scripts instance
   */
  public static function get_instance() {

    if (null === self::$instance) self::$instance = new self();

    return self::$instance;

  } // end get_instance;

  /**
   * Initializes the class
   */
  public function __construct() {

    $this->version = WP_Ultimo()->version;

    add_action('init', array($this, 'register_scripts'), 20);

    add_action('init', array($this, 'register_styles'), 20);
    
    add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'), 1);

    add_action('admin_enqueue_scripts', array($this, 'enqueue_styles'), 1);
    
  } // end cosntruct;

  /**
   * Returns the suffix .min if the debugging of scripts is not enabled
   *
   * @since 1.8.2
   * @return string
   */
  public static function suffix() {

    return defined('SCRIPT_DEBUG') && SCRIPT_DEBUG ? '' : '.min';

  } // end if;

  /**
   * We basically send all setting information to the front-end of the admin panel, as it might be useful
   *
   * @since 1.8.2
   * @return array
   */
  public function get_localization_variables() {

    /**
     * Fetch all the plugin settings
     * TODO: We need to change this in the future, as I think this is quite resource intensive for such a simple task
     */
    $plugin_settings = WU_Settings::get_settings();

    /**
     * Aditional settings needed by various parts of the front-end code
     */
    $additional_settings = array(
      'datepicker_locale'    => strtok(get_locale(), '_'),
      'server_clock_start'   => current_time( 'timestamp', true),
      'server_clock_offset'  => (current_time( 'timestamp' ) - current_time( 'timestamp', true )) / 60 / 60,
      'currency_symbol'      => get_wu_currency_symbol($plugin_settings['currency_symbol']),
      'currency_placeholder' => wu_format_currency(0),
    );

    $settings = array_merge(array(
      'currency_symbol'        => $plugin_settings['currency_symbol'],
      'currency_position'      => $plugin_settings['currency_position'],
      'decimal_separator'      => $plugin_settings['decimal_separator'],
      'thousand_separator'     => $plugin_settings['thousand_separator'],
      'precision'              => $plugin_settings['precision'],
    ), $additional_settings);

    /**
     * Allow plugin developers to extend the list of variables we send to our JSON variable
     * 
     * @since 1.0.0
     * @param array List of all settings
     */
    return apply_filters('wu_js_variables', $settings);

  } // end get_localization_variables;

  /**
   * Registers the globally necessary scripts
   *
   * @since 1.8.2
   * @return void
   */
  public function register_scripts() {

    $suffix = self::suffix();
    
    /**
     * Register Block UI
     */
    wp_register_script('jquery-blockui', WP_Ultimo()->url('/inc/setup/js/jquery.blockUI.js'), array('jquery'), $this->version);

    /**
     * Register our main script
     */
    wp_register_script('wp-ultimo', WP_Ultimo()->get_asset('scripts.min.js', 'js'), array('jquery', 'backbone', 'masonry', 'jquery-ui-sortable', 'jquery-ui-datepicker'), $this->version, false);

    /**
     * Pricing tables
     * @since 1.9.1
     */
     wp_register_script('wu-pricing-table', WP_Ultimo()->get_asset("wu-pricing-table$suffix.js", 'js'), array('jquery', 'wp-ultimo'), $this->version, false);

    /**
     * Load variables to localized it
     */
    wp_localize_script('wp-ultimo', 'wpu', $this->get_localization_variables());

  } // end register_scripts;

  /**
   * Register the globally needed styles
   *
   * @since 1.8.2
   * @return void
   */
  public function register_styles() {

    $suffix = self::suffix();

    /**
     * General WP Ultimo styles
     */
    wp_register_style('wp-ultimo', WP_Ultimo()->get_asset("wp-ultimo$suffix.css", 'css'), array(), $this->version);
    
    /**
     * Additional Styles
     */
    wp_register_style('wp-ultimo-app', WP_Ultimo()->get_asset("app$suffix.css", 'css'), array(), $this->version);

    /**
     * Custom icon font
     */
    wp_register_style('wu-icons', WP_Ultimo()->get_asset("wu-icons$suffix.css", 'css'), array(), $this->version);

    /**
     * WP Ultimo version of Bootstrap
     */
    wp_register_style('wu-grid', WP_Ultimo()->get_asset("wu-grid$suffix.css", 'css'), array(), $this->version);

    /**
     * Shortcodes
     */
    wp_register_style('wu-shortcodes', WP_Ultimo()->get_asset("wu-shortcodes$suffix.css", 'css'), array('wu-grid', 'themes'), $this->version);

    /**
     * Pricing Tables
     */
    wp_register_style('wu-pricing-table', WP_Ultimo()->get_asset("wu-pricing-table$suffix.css", 'css'), array('wu-grid'), $this->version);

    /**
     * Login Styles
     */
    wp_register_style('wu-login', WP_Ultimo()->get_asset("wu-login$suffix.css", 'css'), array(), $this->version);
    
  } // end register_styles;

  /**
   * Enqueue the globally necessary scripts
   *
   * @since 1.8.2
   * @return void
   */
  public function enqueue_scripts() {

    if (!is_network_admin()) return;

    wp_enqueue_script('wp-ultimo');

  } // end enqueue_scripts;

  /**
   * Enqueue the globally necessary styles
   *
   * @since 1.8.2
   * @return void
   */
  public function enqueue_styles() {

    wp_enqueue_style('wp-ultimo');

    wp_enqueue_style('wp-ultimo-app');

    wp_enqueue_style('wu-icons');

  } // end enqueue_styles;

} // end class WU_Scripts;

/**
 * Returns the singleton
 */
function WU_Scripts() {

  return WU_Scripts::get_instance();

} // end WU_Scripts;

// Initialize
WU_Scripts();
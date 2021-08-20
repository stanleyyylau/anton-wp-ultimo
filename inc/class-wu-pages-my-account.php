<?php
/**
 * Page My Account
 *
 * Adds the my Account page to sub-sites
 *
 * @author      Arindo Duque
 * @category    Admin
 * @package     WP_Ultimo/Pages
 * @version     0.0.1
*/

if (!defined('ABSPATH')) {
  exit;
}

class WU_Page_My_Account extends WU_Page {

  /**
   * In this particular case, we need to override the construct to only add this when needed
   *
   * @since 1.8.2
   * @param boolean $network
   * @param array $atts
   */
  public function __construct($network = true, $atts = array()) {

    /**
     * Checks if we need a Account page
     */
    if (wu_get_current_site()->get_plan()) {

      /**
       * Call the constructor of the parent class
       */
      parent::__construct($network, $atts);

      /**
       * This page needs custom capabilities, so we just show it to the site owner
       */
      $this->capability = sprintf('manage_wu_%s_account', get_current_blog_id());

    } // end if;
    
  } // end construct;

  /**
   * Initializes the page
   *
   * @since 1.8.2
   * @return void
   */
  public function init() {

    add_action('admin_bar_menu', array($this, 'add_my_accounts_to_toolbar'));

  } // end init;

  /**
   * Adds the screen options for the transactions table
   *
   * @since 1.8.2
   * @return void
   */
  public function screen_options() {

    $args = array(
      'label'   => __('Transactions', 'wp-ultimo'),
      'option'  => 'transactions_per_page',
      'default' => 5,
    );

    add_screen_option('per_page', $args);

  } // end screen_options;

  /**
   * Add the Account item to the right side of the top-bar
   *
   * @param WP_Admin_Bar $wp_admin_bar
   * @return void
   */
  public function add_my_accounts_to_toolbar($wp_admin_bar) {

    $args = array(
      'id'     => 'wu-my-account',
      'parent' => 'top-secondary',
      'title'  => apply_filters('wu_my_accounts_page_title', __('Account', 'wp-ultimo')),
      'href'   => admin_url('admin.php?page=wu-my-account'),
      'meta'   => array('class' => 'wu-my-account'),
	   );
    
    $wp_admin_bar->add_node($args);
    
  } // end add_my_accounts_to_toolbar;

  /**
   * Allow child classes to add further initializations, but only after the page is loaded
   *
   * @since 1.8.2
   * @return void
   */
  public function page_loaded() {

    parent::page_loaded();

    add_action('admin_enqueue_scripts', array($this, 'load_main_script'));

  } // end page_loaded;

  /**
   * Loads the WP Ultimo main scripts
   *
   * @since 1.9.4
   * @return void
   */
  public function load_main_script() {

    wp_enqueue_script('wp-ultimo');

  } // end load_main_scripts;

  /**
   * Register the widgets of the Account page
   *
   * @since 1.8.2
   * @return void
   */
  public function register_widgets() {

    /**
     * Current plan and change plan
     */
    add_meta_box('wp-ultimo-change-plan', __('Plan', 'wp-ultimo'), array($this, 'output_widget_change_plan'), $this->id, 'normal', 'high');
    
    /**
     * Account Status
     */
    add_meta_box('wp-ultimo-status', __('Account Status', 'wp-ultimo'), array($this, 'output_widget_account_status'), $this->id, 'normal', 'high');
    
    /**
     * Account Actions
     */
    add_meta_box('wp-ultimo-actions', __('Account Actions', 'wp-ultimo'), array($this, 'output_widget_account_actions'), $this->id, 'normal', 'high');

  } // end my_account_widgets;
  
  /**
   * Sets the output template for this particular page
   *
   * @since 1.8.2
   * @return void
   */
  public function output() {
    
    WP_Ultimo()->render('account/my-account');
    
  } // end output;

  /**
   * Renders the Account Actions widgets
   *
   * @since 1.8.2
   * @return void
   */
  public function output_widget_account_actions() {

    WP_Ultimo()->render('widgets/account/account-actions', array(
      'subscription' => wu_get_current_site()->get_subscription()
    ));

  } // end output_widget_account_actions;

  /**
   * Renders the Change Plan widget
   *
   * @since 1.8.2
   * @return void
   */
  public function output_widget_change_plan() {

    WP_Ultimo()->render('widgets/account/change-plan');

  } // end output_widget_change_plan;
  
  /**
   * Renders the Account Status widget
   *
   * @since 1.8.2
   * @return void
   */
  public function output_widget_account_status() {

    WP_Ultimo()->render('widgets/account/account-status');

  } // end output_widget_account_status;
  
} // end class WU_Page_My_Account;

new WU_Page_My_Account(false, array(
  'id'         => 'wu-my-account',
  'type'       => 'menu',
  'title'      => apply_filters('wu_my_accounts_page_title', __('Account', 'wp-ultimo')),
  'menu_title' => apply_filters('wu_my_accounts_page_title', __('Account', 'wp-ultimo')),
  'menu_icon'  => apply_filters('wu_my_accounts_page_icon', 'dashicons-id'),
  'position'   => apply_filters('wu_my_account_menu_position', 999999),
));

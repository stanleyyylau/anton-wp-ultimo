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

class WU_Page_List_Coupons extends WU_Page_List {
  
  /**
   * Adds the hooks necessary for this page
   *
   * @since 1.8.2
   * @return void
   */
  public function hooks() {

    /**
     * Display warning messages in some cases
     * @since  1.1.2 messages that coupon codes are not supported by paypal standard
     */
    if (WU_Settings::get_setting('paypal_standard')) {

      WP_Ultimo()->add_message(__('PayPal Subscription Buttons (PayPal Standard) does not support coupon codes. They will only work with alternative methods or PayPal Express Checkout API'), 'warning', true);

    } // end if;

  } // end hooks;

  /**
   * Holds the list of action links. 
   * These are the ones displayed next to the title of the page. e.g. Add New
   *
   * @since 1.8.2
   * @var array
   */
  public function action_links() {

    return array(
      __('Add new Coupon', 'wp-ultimo') => network_admin_url('admin.php?page=wu-edit-coupon')
    );

  } // end action_links;

  /**
   * Returns an array with the labels for the edit page
   *
   * @since 1.8.2
   * @return array
   */
  public function get_labels() {

    return array(
      'deleted_message' => sprintf(__('%s plan(s) deleted successfully!', 'wp-ultimo'), isset($_GET['deleted']) ? $_GET['deleted'] : 0),
      'search_label'    => __('Search Subscriptions', 'wp-ultimo'),
    );

  } // end get_labels;

  /**
   * Adds the screen option to filter the coupons table
   *
   * @since 1.8.2
   * @return void
   */
  public function screen_options() {

    $args = array(
      'default' => 25,
      'label'   => __('Coupons', 'wp-ultimo'),
      'option'  => 'coupons_per_page'
    );

    add_screen_option('per_page', $args);

  } // end screen_options;

  /**
   * Get the list table for the coupons
   *
   * @since 1.8.2
   * @return WU_Coupons_List_Table
   */
  public function get_table() {

    require_once WP_Ultimo()->path('inc/class-wu-coupons-list-table.php');

    return new WU_Coupons_List_Table();

  } // end get_table;

  /**
   * We don't need the search bar on this page
   *
   * @since 1.8.2
   * @return boolean
   */
  public function has_search() {

    return false;

  } // end has_search;
  
} // end class WU_Page_List_Coupons;

new WU_Page_List_Coupons(true, array(
  'id'            => 'wp-ultimo-coupons',
  'type'          => 'menu',
  'title'         => __('Coupons', 'wp-ultimo'),
  'menu_title'    => __('Coupons', 'wp-ultimo'),
  'submenu_title' => __('All Coupons', 'wp-ultimo'),
  'menu_icon'     => 'dashicons-tickets',
  'position'      => 10101030,
));

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

class WU_Page_List_Subscription extends WU_Page_List {

  /**
   * Set the action links for this list page
   *
   * @since 1.8.2
   * @return array
   */
  public function action_links() {

    return array(
      __('Add New', 'wp-ultimo')         => network_admin_url('admin.php?page=wu-add-new-subscription'),
      __('Export CSV File', 'wp-ultimo') => admin_url('admin-ajax.php?action=wu_generate_subscriptions_csv'),
    );

  } // end action_links;

  /**
   * Set the screen options to allow users to set the pagination options of the subscriptions list
   *
   * @since 1.8.2
   * @return void
   */
  public function screen_options() {

    $args = array(
      'default' => 100,
      'label'   => __('Subscriptions', 'wp-ultimp'),
      'option'  => 'subscriptions_per_page'
    );

    add_screen_option('per_page', $args);

  } // end screen_options;

  /**
   * Returns an array with the labels for the edit page
   *
   * @since 1.8.2
   * @return array
   */
  public function get_labels() {

    return array(
      'search_label'    => __('Search Subscriptions', 'wp-ultimo'),
      'deleted_message' => __('Subscription successfully deleted!', 'wp-ultimo'),
    );

  } // end get_labels;

  /**
   * Returns the subscriptions list table
   * 
   * @since 1.8.2
   * @return WU_Subscriptions_List_Table
   */
  public function get_table() {

    require_once WP_Ultimo()->path('inc/class-wu-subscriptions-list-table.php');

    return new WU_Subscriptions_List_Table();

  } // end get_table;
  
} // end class WU_Page_List_Subscription;

new WU_Page_List_Subscription(true, array(
  'id'            => 'wp-ultimo-subscriptions',
  'type'          => 'menu',
  'title'         => __('Subscriptions', 'wp-ultimo'),
  'menu_title'    => __('Subscriptions', 'wp-ultimo'),
  'submenu_title' => __('All Subscriptions', 'wp-ultimo'),
  'menu_icon'     => 'dashicons-update',
  'position'      => 10101015,
));

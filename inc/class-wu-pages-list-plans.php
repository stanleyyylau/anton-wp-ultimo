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

class WU_Page_List_Plans extends WU_Page_List {

  /**
   * Adds the hooks necessary for this page
   *
   * @since 1.8.2
   * @return void
   */
  public function hooks() {

    /**
     * Includes the guided tour
     */
    $this->add_guided_tour();

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
      __('Add new Plan', 'wp-ultimo') => network_admin_url('admin.php?page=wu-edit-plan')
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
   * Adds the screen option to filter the plans table
   *
   * @since 1.8.2
   * @return void
   */
  public function screen_options() {} // end screen_options;

  /**
   * Get the list table for the plans
   *
   * @since 1.8.2
   * @return WU_Plans_List_Table
   */
  public function get_table() {

    require_once WP_Ultimo()->path('inc/class-wu-plans-list-table.php');

    return new WU_Plans_List_Table();

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

  /**
   * Add the guided tours to plans and coupons page
   * 
   * @since 1.8.2
   * @return void
   */
  public function add_guided_tour() {

    // Require the necessary script
    require_once (WP_Ultimo()->path('inc/class-wu-pointers.php'));

    $screen_id = get_current_screen()->id;

    $pointers = array();

    $pointers[] = array(
       'id'       => 'plan_reorder',   // unique id for this pointer
       'screen'   => $screen_id, // this is the page hook we want our pointer to show on
       'target'   => '#the-list tr:first-child .dashicons.dashicons-menu', // the css selector for the pointer to be tied to, best to use ID's
       'title'    => __('Plan Reordering', 'wp-ultimo'),
       'content'  => __('You can reorder the plans by dragging and dropping them in the desired order. The saved order will be reflected on the pricing tables.', 'wp-ultimo'),
       'position' => array( 
           'edge'     => 'right', //top, bottom, left, right
           'align'    => 'right' //top, bottom, left, right, middle
        )
    );

    //Now we instantiate the class and pass our pointer array to the constructor 
    $guided_tour = new WU_Help_Pointers($pointers);
     
  } // end add_guided_tour;
  
} // end class WU_Page_List_Plans;

new WU_Page_List_Plans(true, array(
  'id'            => 'wp-ultimo-plans',
  'type'          => 'menu',
  'title'         => __('Plans', 'wp-ultimo'),
  'menu_title'    => __('Plans', 'wp-ultimo'),
  'submenu_title' => __('All Plans', 'wp-ultimo'),
  'menu_icon'     => 'dashicons-money',
  'position'      => 10101020,
));

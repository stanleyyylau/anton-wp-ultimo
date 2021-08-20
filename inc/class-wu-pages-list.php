<?php
/**
 * Class WU_Page_List
 *
 * This class extends the base WU_Page class to help create edit pages on the network level
 * 
 * Most of WP Ultimo pages edit are implemented using this class, which means that the filters and hooks
 * listed below can be used to append content to all of our pages at once
 *
 * @author      Arindo Duque
 * @category    Admin
 * @package     WP_Ultimo/Pages
 * @version     0.0.1
*/

if (!defined('ABSPATH')) {
  exit;
}

class WU_Page_List extends WU_Page {

  /**
   * The id/name/slug of the object being edited/created. e.g: plan
   *
   * @since 1.8.2
   * @var object
   */
  public $object_id;
  
  /**
   * Keep the labels
   *
   * @since 1.8.2
   * @var array
   */
  public $labels = array();

  /**
   * Holds the WP_List_Table instance to be used on the list
   *
   * @since 1.8.2
   * @var WP_List_Table
   */
  public $table;

  /**
   * Defines if we need to display the search bar or not
   *
   * @since 1.8.2
   * @var boolean
   */
  public $has_search = true;

  /**
   * Holds the list of action links. 
   * These are the ones displayed next to the title of the page. e.g. Add New
   *
   * @since 1.8.2
   * @var array
   */
  public $action_links = array();

  /**
   * Sets the default labels and get the object
   *
   * @since 1.8.2
   * @return void
   */
  public function page_loaded() {

    /**
     * Loads the list table
     */
    $this->table = $this->get_table();

    /**
     * Gets the base labels
     */
    $this->labels = $this->get_labels();

    /**
     * Loads if we need to get the search
     */
    $this->has_search = $this->has_search();

    /**
     * Get the action links
     */
    $this->action_links = $this->action_links();

    /**
     * Adds the process for process actions
     */
    $this->process_bulk_action();

  } // end setup;

  /**
   * Initializes the class
   *
   * @since 1.8.2
   * @return void
   */
  public function init() {

    /**
     * Runs the parent init functions
     */
    parent::init();

    /**
     * Adds the Ajax Response
     */
    add_action("wp_ajax_{$this->id}_fetch_ajax_results", array($this, 'process_ajax_filter'));

  } // end init;

  /**
   * Process bulk actions of the tables
   *
   * @since 1.8.2
   * @return void
   */
  public function process_bulk_action() {

    if ($this->table) {

      $this->table->process_bulk_action();

    } // end if;

  } // end process_bulk_action;

  /**
   * Process Ajax Filters for the tables, if one is set
   *
   * @since 1.8.2
   * @return void
   */
  public function process_ajax_filter() {

    $this->table = $this->get_table();

    if ($this->table) {

      $this->table->ajax_response();

    } // end if;

  } // end process_ajax_filter;

  /**
   * Returns an array with the labels for the edit page
   *
   * @since 1.8.2
   * @return array
   */
  public function get_labels() {

    return array(
      'deleted_message' => __('Object removed successfully.', 'wp-ultimo'),
      'search_label'    => __('Search Object', 'wp-ultimo'),
    );

  } // end get_labels;

  /**
   * Get the title links. Allow users to filter the action links
   *
   * @since 1.8.2
   * @return array
   */
  public function get_title_links() {

    /**
     * Allow plugin developers, and ourselves, to add action links to our list pages
     * 
     * @since 1.8.2
     * @param array List of action links
     * @param WU_Page_Edit $this This instance
     * @return array
     */
    return apply_filters('wu_page_list_get_title_links', $this->action_links, $this);

  } // end get_title_links;

  /**
   * Returns the action links for that page
   *
   * @since 1.8.2
   * @return array
   */
  public function action_links() {

    return array();

  } // end action_links;

  /**
   * Sets the default list template
   *
   * @since 1.8.2
   * @return void
   */
  public function output() {

    /**
     * Renders the base list page layout, with the columns and everything else =)
     */
    WP_Ultimo()->render('base/list', array(
      'page'  => $this,
      'table' => $this->table,
    ));

  } // end output;

  /**
   * Child classes can to implement to hide the sarch field
   *
   * @since 1.8.2
   * @return boolean
   */
  public function has_search() {

    return true;

  } // end has_title;

  /**
   * Dumb function. Child classes need to implement this to set the table that WP Ultimo will use
   *
   * @since 1.8.2
   * @return WP_List_Table
   */
  public function get_table() {} // end get_table;
  
} // end class WU_Page_List;
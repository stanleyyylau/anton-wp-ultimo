<?php
/**
 * Class WU_Page_Edit
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

class WU_Page_Edit extends WU_Page {
  
  /**
   * Checks if we are adding a new object or if we are editing one
   *
   * @since 1.8.2
   * @var boolean
   */
  public $edit = false;
  
  /**
   * Checks if we need the title field or not
   *
   * @since 1.8.2
   * @var boolean
   */
  public $has_title = true;

  /**
   * The id/name/slug of the object being edited/created. e.g: plan
   *
   * @since 1.8.2
   * @var object
   */
  public $object_id;
  
  /**
   * The base object being edited/created
   *
   * @since 1.8.2
   * @var object
   */
  public $object = false;
  
  /**
   * Keep the labels
   *
   * @since 1.8.2
   * @var array
   */
  public $labels = array();

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
     * Gets the base object
     */
    $this->object = $this->get_object();

    /**
     * Loads if we need to get the title
     */
    $this->has_title = $this->has_title();

    /**
     * Get the action links
     */
    $this->action_links = $this->action_links();

    /**
     * Gets the base labels
     */
    $this->labels = $this->get_labels();

    /**
     * Process save, if necessary
     */
    $this->process_save();

  } // end setup;

  /**
   * Process saving
   *
   * @since 1.8.2
   * @return void
   */
  public function process_save() {

    $saving_tag = "saving_{$this->object_id}";

    if (isset($_REQUEST[ $saving_tag ])) {

      check_admin_referer($saving_tag, '_wpultimo_nonce');

      /**
       * Allow plugin developers to add actions to the saving process
       * 
       * @since 1.8.2
       */
      do_action("wu_{$this->object_id}");
      
      /**
       * Calls the saving funtion
       */
      $this->handle_save();

    } // end if;

  } // end process_save;

  /**
   * Returns an array with the labels for the edit page
   *
   * @since 1.8.2
   * @return array
   */
  public function get_labels() {

    return array(
      'edit_label'        => __('Edit Object', 'wp-ultimo'),
      'add_new_label'     => __('Add New Object', 'wp-ultimo'),
      'updated_message'   => __('Object updated with success!', 'wp-ultimo'),
      'title_placeholder' => __('Enter Object Name', 'wp-ultimo'),
      'title_description' => '',
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
     * Allow plugin developers, and ourselves, to add action links to our edit pages
     * 
     * @since 1.8.2
     * @param object $this->object The object being edited. e.g. Plan, Coupon, Subscription, etc
     * @param WU_Page_Edit $this This instance
     * @return array
     */
    return apply_filters('wu_page_edit_get_title_links', $this->action_links, $this->object, $this);

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
   * Sets the output template for edit plan
   *
   * @since 1.8.2
   * @return void
   */
  public function output() {

    /**
     * Enqueue the base Dashboard Scripts
     */
    wp_enqueue_script('dashboard');

    /**
     * Renders the base edit page layout, with the columns and everything else =)
     */
    WP_Ultimo()->render('base/edit', array(
      'screen' => get_current_screen(),
      'page'   => $this,
      'object' => $this->object,
    ));

  } // end output;

  /**
   * Child classes can to implement to hide the title field
   *
   * @since 1.8.2
   * @return boolean
   */
  public function has_title() {

    return true;

  } // end has_title;

  /**
   * Child classes need to implement this to retrieve the base object
   *
   * @since 1.8.2
   * @return object
   */
  public function get_object() {} // end get_object;

  /**
   * Child classes can implement this to save the edit page
   *
   * @since 1.8.2
   * @return object
   */
  public function handle_save() {} // end handle_save;
  
} // end class WU_Page_Edit;
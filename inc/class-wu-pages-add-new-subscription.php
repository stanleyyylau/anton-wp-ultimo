<?php
/**
 * Pages Edit Plan
 *
 * Handles the addition of the About Page
 *
 * @author      WPU_ltimo
 * @category    Admin
 * @package     WP_Ultimo/Pages
 * @version     0.0.1
*/

if (!defined('ABSPATH')) {
  exit;
}

class WU_Page_Add_New_Subscription extends WU_Page_Edit {

  /**
   * Initializes the page
   *
   * @since 1.8.2
   * @return void
   */
  public function init() {

    /**
     * Set the object slug being edited
     */
    $this->object_id = 'new_subscription';

  } // end init:

  /**
   * Handles saving of a new subscription
   *
   * @since 1.8.2
   * @return void
   */
  public function handle_save() {
    
    WU_Subscriptions::get_instance()->save_new_subscription();

  } // end handle_save;

  /**
   * We don't need the has title
   *
   * @since 1.8.2
   * @return boolean
   */
  public function has_title() {

    return false;

  } // end has_title;

  /**
   * Returns the labels for the plan edit page
   *
   * @since 1.8.2
   * @return array
   */
  public function get_labels() {

    return array(
      'edit_label'        => __('Edit Subscription', 'wp-ultimo'),
      'add_new_label'     => __('Add New Subscription', 'wp-ultimo'),
      'updated_message'   => __('Subscription updated with success!', 'wp-ultimo'),
      'title_placeholder' => '',
      'title_description' => '',
    );

  } // end get_labels;
  
  public function register_scripts() {

    $suffix = WP_Ultimo()->min;

    WP_Ultimo()->enqueue_select2();

    wp_enqueue_script('wu-subscription-add-new', WP_Ultimo()->get_asset("wu-subscription-add-new$suffix.js", 'js'), array('jquery', 'jquery-blockui'), WP_Ultimo()->version);

  } // end register_scripts;

  /**
   * Returns the plan object, to be used by the class
   *
   * @since 1.8.2
   * @return WU_Plan
   */
  public function get_object() {

    $subscription = isset($_GET['user_id']) ? wu_get_subscription($_GET['user_id']) : false;

    $this->edit = $subscription;

    return $subscription;

  } // end get_object;

  /**
   * Register the widgets of the edit plan page
   *
   * @since 1.8.2
   * @return void
   */
  public function register_widgets() {

    $screen = get_current_screen();

    /**
     * User Selector
     * @since  1.2.0
     */
    add_meta_box('wu-mb-user-selector', __('User', 'wp-ultimo'), array($this, 'output_widget_user_selector'), $screen->id, 'normal', null);

    /**
     * Site Selector
     * @since  1.2.0
     */
    add_meta_box('wu-mb-site-selector', __('Sites', 'wp-ultimo'), array($this, 'output_widget_site_selector'), $screen->id, 'normal', null);

    /**
     * Submitter (Actions)
     * @since  1.2.0
     */
    add_meta_box('wu-mb-add-new-actions', __('Actions', 'wp-ultimo'), array($this, 'output_widget_add_new_actions'), $screen->id, 'side', null);

  } // end register_widgets;

  /**
   * Displays the user selector for our add new page
   * 
   * @since 1.2.0
   * @return void
   */
  public function output_widget_user_selector() {
    
    WP_Ultimo()->render('widgets/subscriptions/add-new/user-selector', array()); 

  } // end output_widget_user_selector;

  /**
   * Displays the site selector for our add new page
   * 
   * @since 1.2.0
   * @return void
   */
  public function output_widget_site_selector() {
    
    WP_Ultimo()->render('widgets/subscriptions/add-new/site-selector', array()); 

  } // end output_widget_user_selector;

  /**
   * Displays the actions widget for our add new page
   * 
   * @since 1.2.0
   * @return void
   */
  public function output_widget_add_new_actions() {
    
    WP_Ultimo()->render('widgets/subscriptions/add-new/actions', array()); 

  } // end output_widget_add_new_actions;
  
} // end class WU_Page_Add_New_Subscription;

new WU_Page_Add_New_Subscription(true, array(
  'id'         => 'wu-add-new-subscription',
  'parent'     => 'wp-ultimo-subscriptions',
  'type'       => 'submenu',
  'capability' => 'manage_network',
  'title'      => __('Add New Subscrition', 'wp-ultimo'),
  'menu_title' => __('Add New', 'wp-ultimo'),
));

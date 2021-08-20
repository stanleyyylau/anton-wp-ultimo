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

class WU_Page_Edit_Plan extends WU_Page_Edit {

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
    $this->object_id = 'plan';

  } // end init:

  /**
   * Initializes the page
   *
   * @return void
   */
  public function hooks() {

    /**
     * Includes the Shareable Link
     */
    add_action('wu_page_edit_after_title', array($this, 'add_shareable_link'), 10);

    /**
     * Adds the guided tours
     */
    $this->add_guided_tour_plan_new();

  } // end hooks;

  /**
   * Handles the saving of plans
   *
   * @since 1.8.2
   * @return void
   */
  public function handle_save() {

    WU_Plans::get_instance()->save_plan();

  } // end handle_save;

  /**
   * Returns the labels for the plan edit page
   *
   * @since 1.8.2
   * @return array
   */
  public function get_labels() {

    return array(
      'edit_label'        => __('Edit Plan', 'wp-ultimo'),
      'add_new_label'     => __('Add New Plan', 'wp-ultimo'),
      'updated_message'   => __('Plan updated with success!', 'wp-ultimo'),
      'title_placeholder' => __('Enter Plan Name', 'wp-ultimo'),
      'title_description' => '',
    );

  } // end get_labels;

  /**
   * Returns the plan object, to be used by the class
   *
   * @since 1.8.2
   * @return WU_Plan
   */
  public function get_object() {

    if (isset($_GET['plan_id'])) {

      $plan = new WU_Plan($_GET['plan_id']);

    } else {

      $plan = new WU_Plan();

      $plan->set_attributes($_POST);

    } // end if;

    $this->edit = $plan->id > 0;

    $this->object = $plan;

    return $plan;

  } // end get_object;

  /**
   * Displays the shareable link for plans already created
   *
   * @since 1.8.2
   * @param WU_Plan $plan
   * @return void
   */
  public function add_shareable_link($plan) {

    if ($this->edit) {

      $shareable_link = do_shortcode(sprintf("[wu_plan_link plan_id='%s' plan_freq='%s']", $plan->id, WU_Settings::get_setting('default_pricing_option', 1)));
    
      $tooltip = __('This uses the default billing frequency option to build the link', 'wp-ultimo');

      printf("<a href='#' title='%s' class='wu-copy wu-tooltip page-title-action' data-copy='%s'>%s</a>", 
        $tooltip, 
        $shareable_link, 
        __('Click to copy the Shareable Link', 'wp-ultimo')
      );
    
    } // end if;

  } // end add_shareable_link;

  /**
   * Register the widgets of the edit plan page
   *
   * @since 1.8.2
   * @return void
   */
  public function register_widgets() {

    $screen = get_current_screen();

    add_meta_box('postexcerpt', __('Plan Description', 'wp-ultimo'), array($this, 'output_widget_description'), $screen->id, 'normal', 'high');
    
    add_meta_box('wu-product-data', __('Plan Settings', 'wp-ultimo'), array($this, 'output_widget_advanced_option'), $screen->id, 'advanced', 'high');
    
    add_meta_box('submitdiv', __('Prices', 'wp-ultimo'), array($this, 'output_widget_prices'), $screen->id, 'side', 'high');

    add_meta_box('wu-plan-featured', __('Featured Plan', 'wp-ultimo'), array($this, 'output_widget_featured'), $screen->id, 'side', 'high');

    add_meta_box('wu-plan-hidden', __('Hidden Plan', 'wp-ultimo'), array($this, 'output_widget_hidden'), $screen->id, 'side', 'high');
    
    if ($this->edit) {

      add_meta_box('wp-plan-delete', __('Delete this Plan', 'wp-ultimo'), array($this, 'output_widget_delete'), $screen->id, 'side', 'high');

    } // end if

  } // end register_widgets;

  /**
   * Added the Add New plan guided tour
   *
   * @since 1.8.2
   * @return void
   */
  public function add_guided_tour_plan_new() {

    // Require the necessary script
    require_once (WP_Ultimo()->path('inc/class-wu-pointers.php'));

    $screen_id = get_current_screen()->id;

    $pointers = array();

    /**
     * Pointers for Dashboard
     */
    $pointers[] = array(
       'id'       => 'plan_title',   // unique id for this pointer
       'screen'   => $screen_id, // this is the page hook we want our pointer to show on
       'target'   => '#title-prompt-text', // the css selector for the pointer to be tied to, best to use ID's
       'title'    => __('Plan Title', 'wp-ultimo'),
       'content'  => __('Enter the plan name here. This will be displayed in the pricing tables.', 'wp-ultimo'),
       'position' => array( 
           'edge'     => 'left', //top, bottom, left, right
           'align'    => 'left' //top, bottom, left, right, middle
        )
    );

    $pointers[] = array(
       'id'       => 'plan_desc',   // unique id for this pointer
       'screen'   => $screen_id, // this is the page hook we want our pointer to show on
       'target'   => '#excerpt', // the css selector for the pointer to be tied to, best to use ID's
       'title'    => __('Plan Description', 'wp-ultimo'),
       'content'  => __('If you want to add a short description to this plan on the pricing tables, use this field. You can leave it blank, if you prefer.', 'wp-ultimo'),
       'position' => array( 
           'edge'     => 'top', //top, bottom, left, right
           'align'    => 'left' //top, bottom, left, right, middle
        )
    );

    $pointers[] = array(
       'id'       => 'plan_prices',   // unique id for this pointer
       'screen'   => $screen_id, // this is the page hook we want our pointer to show on
       'target'   => '#price_1', // the css selector for the pointer to be tied to, best to use ID's
       'title'    => __('Plan Prices', 'wp-ultimo'),
       'content'  => __('Here you\'ll need to enter the plan prices for each of the allowed billing intervals (which you can change on the WP Ultimo Settings). Use the checkbox above if you want to mark this plan as free.', 'wp-ultimo'),
       'position' => array( 
           'edge'     => 'top', //top, bottom, left, right
           'align'    => 'bottom' //top, bottom, left, right, middle
        )
    );

    $pointers[] = array(
       'id'       => 'plan_featured',   // unique id for this pointer
       'screen'   => $screen_id, // this is the page hook we want our pointer to show on
       'target'   => '#wu-plan-featured', // the css selector for the pointer to be tied to, best to use ID's
       'title'    => __('Featured Plan', 'wp-ultimo'),
       'content'  => __('If you want to highlight this plan on the pricing tables, adding the label "Featured" to it, check this box.', 'wp-ultimo'),
       'position' => array( 
           'edge'     => 'top', //top, bottom, left, right
           'align'    => 'bottom' //top, bottom, left, right, middle
        )
    );

    $pointers[] = array(
       'id'       => 'plan_hidden',   // unique id for this pointer
       'screen'   => $screen_id, // this is the page hook we want our pointer to show on
       'target'   => '#wu-plan-hidden', // the css selector for the pointer to be tied to, best to use ID's
       'title'    => __('Hidden Plan', 'wp-ultimo'),
       'content'  => __('Alternatively, if you want to hide this plan from the pricing tables altogether (transforming it into a "secret" plan) use this option!', 'wp-ultimo'),
       'position' => array( 
           'edge'     => 'top', //top, bottom, left, right
           'align'    => 'bottom' //top, bottom, left, right, middle
        )
    );

    $pointers[] = array(
       'id'       => 'wu-product-data',   // unique id for this pointer
       'screen'   => $screen_id, // this is the page hook we want our pointer to show on
       'target'   => '#wu-product-data', // the css selector for the pointer to be tied to, best to use ID's
       'title'    => __('Advanced Options', 'wp-ultimo'),
       'content'  => __('Use this block to change the restrictions imposed to clients under this plan. You can select different quotas for different post types, add custom lines to the pricing tables, and even limit which plugins and themes will be available for those users!', 'wp-ultimo'),
       'position' => array( 
           'edge'     => 'bottom', //top, bottom, left, right
           'align'    => 'top' //top, bottom, left, right, middle
        )
    );

    //Now we instantiate the class and pass our pointer array to the constructor 
    $guided_tour = new WU_Help_Pointers($pointers);
     
  } // end add_guided_tour_plan_new;

  /**
   * Renders the description widget
   *
   * @since 1.8.2
   * @param WU_Plan $object
   * @return void
   */
  public function output_widget_description($object) {

    WP_Ultimo()->render('widgets/common/description', array(
      'object'      => $object,
      'title'       => __('Plan Description', 'wp-ultimo'),
      'description' => __('Enter a brief description of this plan. This will be displayed in the pricing tables.', 'wp-ultimo'),
    ));

  } // end output_widget_description;

  /**
   * Renders the advanced options widget for plans
   *
   * @since 1.8.2
   * @param WU_Plan $object
   * @return void
   */
  public function output_widget_advanced_option($object) {

    WP_Ultimo()->render('widgets/plan/advanced-options', array(
      'plan' => $object,
      'edit' => $this->edit,
    ));

  } // end output_widget_advanced_option;

  /**
   * Renders the prices + submit widget for plans
   *
   * @since 1.8.2
   * @param WU_Plan $object
   * @return void
   */
  public function output_widget_prices($object) {

    WP_Ultimo()->render('widgets/plan/prices', array(
      'plan' => $object,
      'edit' => $this->edit,
    ));

  } // end output_widget_prices;

  /**
   * Renders the featured plan widget
   *
   * @since 1.8.2
   * @param WU_Plan $object
   * @return void
   */
  public function output_widget_featured($object) {

    WP_Ultimo()->render('widgets/plan/featured', array(
      'plan' => $object,
      'edit' => $this->edit,
    ));

  } // end output_widget_featured;

  /**
   * Renders the hidden plan widget
   *
   * @since 1.8.2
   * @param WU_Plan $object
   * @return void
   */
  public function output_widget_hidden($object) {

    WP_Ultimo()->render('widgets/plan/hidden', array(
      'plan' => $object,
      'edit' => $this->edit,
    ));

  } // end output_widget_hidden;

  /**
   * Renders the delete plan widget
   *
   * @since 1.8.2
   * @param WU_Plan $object
   * @return void
   */
  public function output_widget_delete($object) {

    WP_Ultimo()->render('widgets/plan/delete', array(
      'plan' => $object,
      'edit' => $this->edit,
    ));

  } // end output_widget_delete;
  
} // end class WU_Page_Edit_Plan;

new WU_Page_Edit_Plan(true, array(
  'id'         => 'wu-edit-plan',
  'parent'     => 'wp-ultimo-plans',
  'type'       => 'submenu',
  'capability' => 'manage_network',
  'title'      => __('Add New Plan', 'wp-ultimo'),
  'menu_title' => __('Add New', 'wp-ultimo'),
));

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

class WU_Page_Edit_Subscription extends WU_Page_Edit {

  static $transactions_table;

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
    $this->object_id = 'subscription';

  } // end init:

  /**
   * We need to check if there is an user checked, otherwise we redirect them
   *
   * @since 1.8.2
   * @return void
   */
  public function hooks() {

    /**
     * If no subscription is present, move to the all subscription list page
     */
    if (!isset($_GET['user_id']) || !wu_get_subscription($_GET['user_id'])) {

      wp_redirect(network_admin_url('admin.php?page=wp-ultimo-subscriptions'));

      exit;

    } // end if;

  } // end hooks;

  /**
   * Handles saving of subscription
   *
   * @since 1.8.2
   * @return void
   */
  public function handle_save() {

    WU_Subscriptions::get_instance()->save_subscription();

  } // end handle_save;

  /**
   * We don't need the title field for this page
   *
   * @since 1.8.2
   * @return boolean
   */
  public function has_title() {

    return false;

  } // end has_title;

  public function register_scripts() {

    $suffix = WP_Ultimo()->min;

    // @since 1.4.2 Add the JS
    wp_register_script('wu-add-payment-to-subscription', WP_Ultimo()->get_asset("wu-add-payment-to-subscription$suffix.js", 'js'), array('jquery'), false);

  } // end register_scripts;

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

  /**
   * Returns the plan object, to be used by the class
   *
   * @since 1.8.2
   * @return WU_Plan
   */
  public function get_object() {

    $this->edit = true;

    return wu_get_subscription($_GET['user_id']);

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
     * Subscription Details
     * 
     * @since 1.1.0
     */
    add_meta_box('wu-mb-subscriptions-details', __('Subscription Details', 'wp-ultimo'), array($this, 'output_widget_subscription_details'), $screen->id, 'normal', null, $this->object);

    /**
     * Subscription Actions
     * 
     * @since 1.1.0
     */
    add_meta_box('wu-mb-actions', __('Actions', 'wp-ultimo'), array($this, 'output_widget_actions'), $screen->id, 'side', null, $this->object);

    /**
     * Payment Integrations
     * 
     * @since 1.1.0
     */
    add_meta_box('wu-mb-integration', __('Payment Integration', 'wp-ultimo'), array($this, 'output_widget_integration'), $screen->id, 'side', null, $this->object);

    /**
     * Coupon Code
     * 
     * @since 1.1.0
     */
    add_meta_box('wu-mb-coupon-code', __('Coupon Code', 'wp-ultimo'), array($this, 'output_widget_coupon_code'), $screen->id, 'side', null, $this->object);

    /**
     * Setup Fee Status Control
     * 
     * @since 1.7.0
     */
    add_meta_box('wu-mb-setup-fee', __('Setup Fee', 'wp-ultimo'), array($this, 'output_widget_setup_fee'), $screen->id, 'side', null, $this->object);

  } // end register_widgets;

  /**
   * Renders the subscription details widget
   * 
   * @since 1.1.0
   * @param WU_Subscription $subscription Subscription instance
   * @return void
   */
  public function output_widget_subscription_details($subscription) {
    
    WP_Ultimo()->render('widgets/subscriptions/edit/details', array(
      'subscription' => $subscription,
      'user_id'      => $subscription->user_id,
    )); 

  } // end output_widget_subscription_details;

  /**
   * Displays the actions widget
   * 
   * @since 1.1.0
   * @param  WU_Subscription $subscription Subscription instance
   * @return void
   */
  public function output_widget_actions($subscription) {
    
    WP_Ultimo()->render('widgets/subscriptions/edit/actions', array(
      'subscription' => $subscription,
      'user_id'      => $subscription->user_id,
      'user'         => get_user_by('id', $subscription->user_id),
    )); 

  } // end output_widget_actions;

  /**
   * Displays the integration widget
   * 
   * @since 1.0.0
   * @param WU_Subscription $subscription Subscription instance
   * @return void
   */
  public function output_widget_integration($subscription) {
    
    WP_Ultimo()->render('widgets/subscriptions/edit/integration', array(
      'subscription' => $subscription,
      'user_id'      => $subscription->user_id,
      'user'         => get_user_by('id', $subscription->user_id),
    )); 

  } // end output_widget_integration;

  /**
   * Displays the setup-fee status control widget
   * 
   * @since 1.7.0
   * @param  WU_Subscription $subscription Subscription instance
   * @return void
   */
  public function output_widget_setup_fee($subscription) {
    
    WP_Ultimo()->render('widgets/subscriptions/edit/setup-fee', array(
      'subscription' => $subscription,
      'user_id'      => $subscription->user_id,
      'user'         => get_user_by('id', $subscription->user_id),
    )); 

  } // end output_widget_setup_fee;

  /**
   * Displays the Coupon Code widget
   * 
   * @since 1.7.0
   * @param WU_Subscription $subscription Subscription instance
   * @return void
   */
  public function output_widget_coupon_code($subscription) {

    WP_Ultimo()->render('widgets/subscriptions/edit/coupon-code', array(
      'subscription' => $subscription,
      'user_id'      => $subscription->user_id,
      'user'         => get_user_by('id', $subscription->user_id),
    )); 

  } // end output_widget_coupon_code;
  
} // end class WU_Page_Edit_Subscription;

new WU_Page_Edit_Subscription(true, array(
  'id'         => 'wu-edit-subscription',
  'parent'     => 'wp-ultimo-subscriptions',
  'type'       => 'submenu',
  'capability' => 'manage_network',
  'title'      => __('Edit Subscription', 'wp-ultimo'),
  'menu_title' => __('Edit Subscription', 'wp-ultimo'),
));

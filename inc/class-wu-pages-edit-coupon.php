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

class WU_Page_Edit_Coupon extends WU_Page_Edit {

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
    $this->object_id = 'coupon';

  } // end init:

  /**
   * Initializes the page
   *
   * @since 1.8.2
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
    $this->add_guided_tour_coupon_new();

    /**
     * Display warning messages in some cases
     * 
     * @since 1.1.2 messages that coupon codes are not supported by paypal standard
     */
    if (WU_Settings::get_setting('paypal_standard')) {

      WP_Ultimo()->add_message(__('PayPal Subscription Buttons (PayPal Standard) does not support coupon codes. They will only work with alternative methods or PayPal Express Checkout API', 'wp-ultimo'), 'warning', true);

    } // end if;

  } // end hooks;

  public function register_scripts() {

    $coupon = $this->get_object();

    $suffix = WP_Ultimo()->min;

    wp_register_script('wu-coupon-edit', WP_Ultimo()->get_asset("wu-coupon-edit$suffix.js", 'js'), array('jquery', 'jquery-blockui'), WP_Ultimo()->version);

    wp_localize_script('wu-coupon-edit', 'wu_coupon_edit', array(
			'applies_to_setup_fee' => $coupon->applies_to_setup_fee,
		));

    wp_enqueue_script('wu-coupon-edit');

  } // end register_scripts;

  /**
   * Handles the saving of coupons
   *
   * @since 1.8.2
   * @return void
   */
  public function handle_save() {

    WU_Plans::get_instance()->save_coupon();

  } // end handle_save;

  /**
   * Returns the labels for the plan edit page
   *
   * @since 1.8.2
   * @return array
   */
  public function get_labels() {

    $shareable_url = $this->edit 
    ? sprintf("%s: <code>%s?coupon=%s</code>", __('Shareable URL', 'wp-ultimo'), WU_Signup()->get_signup_url(), $this->object->title) : '';

    return array(
      'edit_label'        => __('Edit Coupon', 'wp-ultimo'),
      'add_new_label'     => __('Add New Coupon', 'wp-ultimo'),
      'updated_message'   => __('Coupon updated with success!', 'wp-ultimo'),
      'title_placeholder' => __('Coupon Code', 'wp-ultimo'),
      'title_description' => __('Use a unique value. This is what your users will enter during checkout.', 'wp-ultimo') . ' ' . $shareable_url,
    );

  } // end get_labels;

  /**
   * Returns the plan object, to be used by the class
   *
   * @since 1.8.2
   * @return WU_Coupon
   */
  public function get_object() {

    if (isset($_GET['coupon_id'])) {

      $coupon = new WU_Coupon($_GET['coupon_id']);

    } else {

      $coupon = new WU_Coupon();

      $coupon->set_attributes($_POST);

    } // end if;

    $this->edit = $coupon->id > 0;

    $this->object = $coupon;

    return $coupon;

  } // end get_object;

  /**
   * Displays the shareable link for coupons already created
   *
   * @since 1.8.2
   * @param WU_Coupon $coupon
   * @return void
   */
  public function add_shareable_link($coupon) {

    if ($this->edit) {

      printf("<a href='#' class='wu-copy page-title-action' data-copy='%s?coupon=%s'>%s</a>", WU_Signup()->get_signup_url(), $coupon->title, __('Click to copy the Shareable Link', 'wp-ultimo'));
    
    } // end if;

  } // end add_shareable_link;

  /**
   * Register the widgets of the edit coupon page
   *
   * @since 1.8.2
   * @return void
   */
  public function register_widgets() {

    $screen = get_current_screen();

    add_meta_box('postexcerpt', __('Coupon Description', 'wp-ultimo'), array($this, 'output_widget_description'), $screen->id, 'normal', 'high');
    
    add_meta_box('wu-product-data', __('Coupon Settings', 'wp-ultimo'), array($this, 'output_widget_advanced_option'), $screen->id, 'advanced', 'high');
    
    add_meta_box('submitdiv', __('Coupon Value', 'wp-ultimo'), array($this, 'output_widget_value'), $screen->id, 'side', 'high');
    
    if ($this->edit) {

      add_meta_box('wp-coupon-delete', __('Delete this Coupon', 'wp-ultimo'), array($this, 'output_widget_delete'), $screen->id, 'side', 'high');

    } // end if

  } // end register_widgets;

  /**
   * Added the Add New coupon guided tour
   *
   * @since 1.8.2
   * @return void
   */
  public function add_guided_tour_coupon_new() {

    require_once (WP_Ultimo()->path('inc/class-wu-pointers.php'));

    $screen_id = get_current_screen()->id;

    $pointers = array();

    /**
     * Pointers for Dashboard
     */
    $pointers[] = array(
       'id'       => 'coupon_title',   // unique id for this pointer
       'screen'   => $screen_id, // this is the page hook we want our pointer to show on
       'target'   => '#title-prompt-text', // the css selector for the pointer to be tied to, best to use ID's
       'title'    => __('Coupon Code', 'wp-ultimo'),
       'content'  => __('Enter the code for the Coupon. This is the code your users will have to input to receive the discount during the signup flow.', 'wp-ultimo'),
       'position' => array( 
           'edge'     => 'left', //top, bottom, left, right
           'align'    => 'left' //top, bottom, left, right, middle
        )
    );

    $pointers[] = array(
       'id'       => 'coupon_desc',   // unique id for this pointer
       'screen'   => $screen_id, // this is the page hook we want our pointer to show on
       'target'   => '#excerpt', // the css selector for the pointer to be tied to, best to use ID's
       'title'    => __('Coupon Description', 'wp-ultimo'),
       'content'  => __('Use this field to add a short description for your reference. This description won\'t be displayed anywhere else.', 'wp-ultimo'),
       'position' => array( 
           'edge'     => 'top', //top, bottom, left, right
           'align'    => 'left' //top, bottom, left, right, middle
        )
    );

    $pointers[] = array(
       'id'       => 'coupon_prices',   // unique id for this pointer
       'screen'   => $screen_id, // this is the page hook we want our pointer to show on
       'target'   => '#minor-publishing select', // the css selector for the pointer to be tied to, best to use ID's
       'title'    => __('Coupon Types and Values', 'wp-ultimo'),
       'content'  => __('You have two options for coupon types, which change the way WP Ultimo processes the value on the field below.', 'wp-ultimo').sprintf('<br><br><strong>%s</strong>:%s<br><br><strong>%s</strong>:%s', __('Subscription Discount', 'wp-ultimo'), __('This option offers an absolute value discount.', 'wp-ultimo'), __('Subscription % Discount', 'wp-ultimo'), __('This option offers a percent discount. The value added on the field below will be used as a percentage instead of an absolute value.', 'wp-ultimo')),
       'position' => array( 
           'edge'     => 'top', //top, bottom, left, right
           'align'    => 'bottom' //top, bottom, left, right, middle
        )
    );

    $pointers[] = array(
       'id'       => 'coupon_allowed_uses',   // unique id for this pointer
       'screen'   => $screen_id, // this is the page hook we want our pointer to show on
       'target'   => '#allowed_uses', // the css selector for the pointer to be tied to, best to use ID's
       'title'    => __('Allowed Uses', 'wp-ultimo'),
       'content'  => __('You can use this option to limit the number of usages of this particular coupon code. Leaving 0 will allow this coupon to be used unlimited times.', 'wp-ultimo'),
       'position' => array( 
           'edge'     => 'left', //top, bottom, left, right
           'align'    => 'left' //top, bottom, left, right, middle
        )
    );

    $pointers[] = array(
       'id'       => 'coupon_expiring_date',   // unique id for this pointer
       'screen'   => $screen_id, // this is the page hook we want our pointer to show on
       'target'   => '#expiring_date', // the css selector for the pointer to be tied to, best to use ID's
       'title'    => __('Expiring Date', 'wp-ultimo'),
       'content'  => __('Use this option to impose a temporal limitation on the use of this coupon. Useful for time-limited promotions.', 'wp-ultimo'),
       'position' => array( 
           'edge'     => 'left', //top, bottom, left, right
           'align'    => 'left' //top, bottom, left, right, middle
        )
    );

    $pointers[] = array(
       'id'       => 'coupon_cycles',   // unique id for this pointer
       'screen'   => $screen_id, // this is the page hook we want our pointer to show on
       'target'   => '#cycles', // the css selector for the pointer to be tied to, best to use ID's
       'title'    => __('Billing Cycles', 'wp-ultimo'),
       'content'  => __('This option determines for how many billing cycles the coupon will be valid for. If you enter 3, for example, that means that this discount will be applied to the client\'s subscription fee for the first three billing cycles. After that, the price of his subscription fee would return to its full value. <br><br>Leaving 0 will result on the discount being applied to every billing cycle forever.', 'wp-ultimo'),
       'position' => array( 
           'edge'     => 'left', //top, bottom, left, right
           'align'    => 'left' //top, bottom, left, right, middle
        )
    );

    $guided_tour = new WU_Help_Pointers($pointers);
     
  } // end add_guided_tour_coupon_new;

  /**
   * Renders the description widget
   *
   * @since 1.8.2
   * @param WU_Coupon $object
   * @return void
   */
  public function output_widget_description($object) {

    WP_Ultimo()->render('widgets/common/description', array(
      'object'      => $object,
      'title'       => __('Coupon Description', 'wp-ultimo'),
      'description' => __('Enter a brief description for this coupon.', 'wp-ultimo'),
    ));

  } // end output_widget_description;

  /**
   * Renders the advanced options widget for coupons
   *
   * @since 1.8.2
   * @param WU_Coupon $object
   * @return void
   */
  public function output_widget_advanced_option($object) {

    WP_Ultimo()->render('widgets/coupon/advanced-options', array(
      'coupon' => $object,
      'edit'   => $this->edit,
    ));

  } // end output_widget_advanced_option;

  /**
   * Renders the value + submit widget for coupons
   *
   * @since 1.8.2
   * @param WU_Coupon $object
   * @return void
   */
  public function output_widget_value($object) {

    WP_Ultimo()->render('widgets/coupon/value', array(
      'coupon' => $object,
      'edit'   => $this->edit,
    ));

  } // end output_widget_value;

  /**
   * Renders the delete coupon widget
   *
   * @since 1.8.2
   * @param WU_Coupon $object
   * @return void
   */
  public function output_widget_delete($object) {

    WP_Ultimo()->render('widgets/coupon/delete', array(
      'coupon' => $object,
      'edit'   => $this->edit,
    ));

  } // end output_widget_delete;
  
} // end class WU_Page_Edit_Coupon;

new WU_Page_Edit_Coupon(true, array(
  'id'         => 'wu-edit-coupon',
  'parent'     => 'wp-ultimo-coupons',
  'type'       => 'submenu',
  'capability' => 'manage_network',
  'title'      => __('Add New Coupon', 'wp-ultimo'),
  'menu_title' => __('Add New', 'wp-ultimo'),
));

<?php
/**
 * Coupon Class
 *
 * Handles the addition of new coupons
 *
 * @author      Arindo Duque
 * @category    Admin
 * @package     WP_Ultimo/Model
 * @version     0.0.1
*/

if (!defined('ABSPATH')) {
  exit;
}

/**
 * WU_Coupon class.
 */
class WU_Coupon {

  /**
   * Holds the ID of the WP_Post, to be used as the ID of each plan
   * @var integer
   */
  public $id = 0;

  /**
   * Holds the WP_Post Object of the Coupon
   * @var null
   */
  public $post = null;

  /**
   * The status of the post
   * @var string
   */
  public $post_status = '';

  /**
   * Meta fields contained as attributes of each plan 
   * @var array
   */
  public $meta_fields = array(
    'title',
    'description',
    'expiring_date',
    'allowed_uses',
    'uses',
    'type',
    'value',
    'cycles',
    'allowed_plans', // @since 1.5.5
    'allowed_freqs', // @since 1.5.5
    'applies_to_setup_fee', // @since 1.10.3
    'setup_fee_discount_value', // @since 1.10.3
    'setup_fee_discount_type', // @since 1.10.3
  );

  /**
   * Discount types
   * @var array
   */
  public $types;

  /**
   * Construct our new plan
   */
  public function __construct($cupon = false) {

    // Types of discount
    $this->types = array(
      'absolute' => __('Absolute Discount ($)', 'wp-ultimo'),
      'percent'  => __('Relative Discount (%)', 'wp-ultimo'),
    );

    if ( is_numeric( $cupon ) ) {
      $this->id   = absint( $cupon );
      $this->post = get_post( $cupon );
      $this->get_coupon( $this->id );
    } elseif ( $cupon instanceof WU_Coupon ) {
      $this->id   = absint( $cupon->id );
      $this->post = $cupon->post;
      $this->get_coupon($this->id);
    } elseif ( isset( $cupon->ID ) ) {
      $this->id   = absint( $cupon->ID );
      $this->post = $cupon;
      $this->get_coupon( $this->id );
    }
    // Add the get_by title
    else {

      // Get the id
      switch_to_blog( get_current_site()->blog_id );
      if (!function_exists('post_exists')) {
        require_once(ABSPATH.'wp-admin/includes/post.php');
      }

      $coupon_id = post_exists(sanitize_text_field($cupon));
      
      restore_current_blog();

      if ($coupon_id) {
        $this->id   = absint( $coupon_id );
        $this->post = get_post( $coupon_id );
        $this->get_coupon( $coupon_id );
      }

    }

  } // end construct;

  /**
   * Gets a coupon from the database.
   * @param int  $id (default: 0).
   * @return bool
   */
  public function get_coupon($id = 0) {

    if (!$id) {
      return false;
    }

    if ($result = get_blog_post(1, $id) ) {
      $this->populate( $result );
      return true;
    }

    return false;
  }

  /**
   * Populates an order from the loaded post data.
   * @param mixed $result
   */
  public function populate($result) {

    // Standard post data
    $this->id           = $result->ID;
    $this->post_status  = $result->post_status;

  } // end populate;

  /**
   * __isset function.
   * @param mixed $key
   * @return bool
   */
  public function __isset($key) {
    if (!$this->id) return false;
    // Swicth to main blog
    switch_to_blog( get_current_site()->blog_id );
    $value = metadata_exists('post', $this->id, 'wpu_' . $key);
    restore_current_blog();
    return $value;
  }

  /**
   * __get function.
   * @param mixed $key
   * @return mixed
   */
  public function __get($key) {
    // Swicth to main blog
    switch_to_blog( get_current_site()->blog_id );
    $value = get_post_meta( $this->id, 'wpu_' . $key, true);
    restore_current_blog();
    return $value;
  }

  /**
   * Set attributes in a coupon, based on a array. Useful for validation
   * @param array $atts Attributes
   */
  public function set_attributes($atts) {
    
    foreach($atts as $att => $value) {
      $this->{$att} = $value;
    }
    
    return $this;

  } // end set_attributes;

  /**
   * Save the current Plan
   */
  public function save() {

    // Swicth to main blog
    switch_to_blog( get_current_site()->blog_id );

    $this->title = wp_strip_all_tags($this->title);

    $cuponPost = array(
      'post_type'     => 'wpultimo_coupon',
      'post_title'    => $this->title,
      'post_content'  => '',
      'post_status'   => 'publish',
    );

    if ($this->id !== 0 && is_numeric($this->id)) $cuponPost['ID'] = $this->id;

    // Insert Post
    $this->id = wp_insert_post($cuponPost);

    // Add the meta
    foreach ($this->meta_fields as $meta) {
      update_post_meta($this->id, 'wpu_'.$meta, $this->{$meta});
    }

    // Do action after save
    do_action('wu_save_coupon', $this);

    // Do something
    restore_current_blog();
    
    // Return the id of the new post
    return $this->id;

  } // end save;
  
  /**
   * Add uses to this coupon
   * @param integer $uses Number of uses to add
   */
  public function add_use($uses = 1) {

    $this->uses = (int) $this->uses + (int) $uses;

    $this->save();

  } // end add_use;

  /**
   * Check if a given plan is among the allowed list of plans
   *
   * @since 1.5.5
   * @param integer $plan_id
   * @return boolean
   */
  public function is_plan_allowed($plan_id) {

    return !is_array($this->allowed_plans) || in_array($plan_id, $this->allowed_plans);

  } // end is_plan_allowed;

  /**
   * Check if a given frequency is allowed on this coupon
   *
   * @since 1.5.5
   * @param integer $freq
   * @return boolean
   */
  public function is_freq_allowed($freq) {

    return !is_array($this->allowed_freqs) || in_array($freq, $this->allowed_freqs);

  } // end is_freq_allowed;

  /**
   * Returns the formated expiring date
   *
   * @since 1.5.5
   * @param string|boolean $format
   * @return string|bool
   */
  public function get_expiring_date($format = false) {

    // Sets the format
    $format = $format ? $format : get_option('date_format');

    $expiring_date = $this->expiring_date;

    if (!$expiring_date) return false;

    $datetime = new DateTime($expiring_date);

    return date_i18n($format, $datetime->format('U'));

  }

} // end Class WU_Coupon;

/**
 * Get a coupon based on the text, and check validation
 * @param  string $coupon_code String of the coupon code
 * @return boolean|WU_Coupon   WU_Coupon object on success, false on not valid
 */
function wu_get_coupon($coupon_code) {

  // Get the id
  switch_to_blog( get_current_site()->blog_id );

  if (!function_exists('post_exists')) {
    require_once(ABSPATH.'wp-admin/includes/post.php');
  }

  $coupon_id = post_exists(sanitize_text_field($coupon_code));

  /**
   * @since 1.4.0 Checks for right post_type
   */
  if (get_post_type($coupon_id) !== 'wpultimo_coupon') {

    return false;

  } // end if;

  restore_current_blog();

  // Coupon exists, we need to check if it is valid
  if ($coupon_id > 0) {

    $coupon        = new WU_Coupon($coupon_id);
    $now           = new DateTime();
    $expiring_date = new DateTime($coupon->expiring_date);

    // Compare dates
    if ($coupon->expiring_date && $now > $expiring_date) return false;
    
    // Compare uses
    else if ($coupon->allowed_uses != 0 && $coupon->uses >= $coupon->allowed_uses) return false;
    
    // Return the coupon
    else return $coupon;

  } // end if;

  // Coupon does not exist, return false
  else return false;

} // end wu_get_coupon;

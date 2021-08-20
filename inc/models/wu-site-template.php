<?php
/**
 * Site Template Class
 *
 * Model of SIte Template data
 *
 * @author      Arindo Duque
 * @category    Admin
 * @package     WP_Ultimo/Model
 * @version     1.2.0
*/

if (!defined('ABSPATH')) {
  exit;
}

/**
 * WU_Site_Template class.
 */
class WU_Site_Template {

  /**
   * Holds the ID of the blog
   * @var integer
   */
  public $id = 0;

  /**
   * Checks if this is a template
   * @var boolean
   */
  public $is_template = true;

  /**
   * Meta fields contained as attributes of each plan
   * @var array
   */
  public $meta_fields = array(
    'blogname',
    'blogdescription',
    'wu_categories',
    'template_img',
  );

  /**
   * Construct our new site template
   */
  public function __construct($site_template = false) {

    global $wpdb;
    
    // Load the id  
    $this->id = $site_template;

    $blog_info = get_blog_details(array('blog_id' => $site_template));

    if ($blog_info) {

      $data = $blog_info->to_array();

      $this->set_attributes($data);

      $table_name = WU_Site_Owner::get_table_name();

      $sql = "SELECT id, user_id from $table_name WHERE site_id = $site_template";

      $result = $wpdb->get_row($sql);

      if ( ( $result !== null && !user_can($result->user_id, 'manage_network') ) || $site_template == 1) {
        
        $this->is_template = false;

      } // end if;

    } else {

      $this->is_template = false;

    } // end if;

  } // end construct;

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
    
    return get_blog_option($this->id, $key, false) != false;

  }

  /**
   * __get function.
   * @param mixed $key
   * @return mixed
   */
  public function __get($key) {

    if (!$this->id) return false;
    
    return get_blog_option($this->id, $key, false);

  }

  /**
   * Set attributes in a plan, based on a array. Useful for validation
   * @param array $atts Attributes
   */
  public function set_attributes($atts) {

    foreach($atts as $att => $value) {
      $this->{$att} = $value;
    }

    return $this;

  } // end set_attributes;

  /**
   * Converts this into a array
   * @return array object elements
   */
  public function to_array() {
      return get_object_vars( $this );
  }

  /**
   * Save the current Plan
   */
  public function save() {

    if (!$this->id) return false;

    foreach($this->meta_fields as $field) {
      
      update_blog_option($this->id, $field, $this->{$field});

    }
    
    // Return the id of the new post
    return $this->id;

  } // end save;

  /**
   * Get Thumbnail for the given site
   * @param  string $size
   * @return 
   */
  public function get_thumbnail($size = 'full') {

    $image_id = get_blog_option($this->id, 'template_img');

    // Check for image URL
    if (filter_var($image_id, FILTER_VALIDATE_URL)) {

      return $image_id;

    } // end if;

    switch_to_blog( get_current_site()->blog_id );

    $image_obj = wp_get_attachment_image_url($image_id, $size);

    restore_current_blog();

    return $image_url = $image_obj ?: WU_Screenshot::get_image($this->id); 

  } // end get_thumbnail;

  public function get_categories() {

    return implode(', ', explode(',', $this->wu_categories));

  }

  public function add_categories($categories = '') {

    // Categories
    $cats = explode(',', trim($categories));
    
    $cats = array_map(function($value) {

      return ucfirst(trim($value)); 

    }, $cats);

    $cats = implode(',', array_unique($cats));

    $this->wu_categories = $cats;

    return $this;

  } // end add_categories;

} // end Class WU_Site_Template


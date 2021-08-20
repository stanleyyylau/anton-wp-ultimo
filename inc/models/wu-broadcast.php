<?php
/**
 * Broadcast Class
 *
 * Handles the addition of new broadcasts
 *
 * @author      Arindo Duque
 * @category    Admin
 * @package     WP_Ultimo/Model
 * @version     1.1.5
*/

if (!defined('ABSPATH')) {
  exit;
}

/**
 * WU_Broadcast class.
 */
class WU_Broadcast {

  /**
   * Holds the ID of the WP_Post, to be used as the ID of each broadcast
   * @var integer
   */
  public $id = 0;

  /**
   * Holds the WP_Post Object of the Broadcast
   * @var null
   */
  public $post = null;

  /**
   * The status of the post
   * @var string
   */
  public $post_status = '';

  /**
   * Meta fields contained as attributes of each broadcast
   * @var array
   */
  public $meta_fields = array(
    'type',
    'style',
    'target_plans',
    'target_users',
  );

  /**
   * Construct our new broadcast
   */
  public function __construct($broadcast = false) {

    switch_to_blog( get_current_site()->blog_id );

    if ( is_numeric( $broadcast ) ) {
      $this->id   = absint( $broadcast );
      $this->post = get_post( $broadcast );
      $this->get_broadcast( $this->id );
    } elseif ( $broadcast instanceof WU_Broadcast ) {
      $this->id   = absint( $broadcast->id );
      $this->post = $broadcast->post;
      $this->get_broadcast($this->id);
    } elseif ( isset( $broadcast->ID ) ) {
      $this->id   = absint( $broadcast->ID );
      $this->post = $broadcast;
      $this->get_broadcast( $this->id );
    }

    restore_current_blog();

  } // end construct;

  /**
   * Gets a broadcast from the database.
   * @param int  $id (default: 0).
   * @return bool
   */
  public function get_broadcast($id = 0) {

    if (!$id) {
      return false;
    }

    if ($result = get_blog_post( (int) get_current_site()->blog_id, $id) ) {
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

    if ($key == 'post_content') return $this->post->post_content;
    if ($key == 'post_title') return $this->post->post_title;
    if ($key == 'post_date') return $this->post->post_date;

    // Swicth to main blog
    switch_to_blog( get_current_site()->blog_id );
    $value = get_post_meta( $this->id, 'wpu_' . $key, true);
    restore_current_blog();
    return $value;

  }

  /**
   * Set attributes in a broadcast, based on a array. Useful for validation
   * @param array $atts Attributes
   */
  public function set_attributes($atts) {
    
    foreach($atts as $att => $value) {
      $this->{$att} = $value;
    }

    $this->post_title   = wp_strip_all_tags($this->post_title);
    $this->post_content = wp_kses_stripslashes(wp_filter_kses($this->post_content));

    return $this;

  } // end set_attributes;

  /**
   * Return all target emails of this message
   * @return array List with all email addresses
   */
  public function get_recipients_list() {

    $recipient_list = array();

    /**
     * Get Plans
     */
    if (is_array($this->target_plans) && !empty($this->target_plans)) {

      foreach ($this->target_plans as $plan_id) {

        $plan          = new WU_Plan($plan_id);
        $subscriptions = $plan->get_subscriptions();

        foreach($subscriptions as $sub) {

          $user = get_user_by('ID', $sub->user_id);

          if (!$user) continue;

          $recipient_list[] = $user->user_email;

        } // end foreach;

      } // end foreach;

    } // end if;

    /**
     * Get Users
     */
    if (is_array($this->target_users) && !empty($this->target_users)) {

      foreach ($this->target_users as $user_id) {

        $user = get_user_by('ID', $user_id);

        if (!$user) continue;

        $recipient_list[] = $user->user_email;

      } // end foreach;

    } // end if;

    return $recipient_list;

  } // end get_recipients_list;

  /**
   * Save the current Broadcast
   */
  public function save() {

    // Switch to main blog
    switch_to_blog( get_current_site()->blog_id );

    $this->post_title   = wp_strip_all_tags($this->post_title);
    $this->post_content = wp_filter_kses($this->post_content);

    $broadcastPost = array(
      'post_type'     => 'wpultimo_broadcast',
      'post_title'    => $this->post_title,
      'post_content'  => $this->post_content,
      'post_status'   => 'publish',
    );

    if ($this->id !== 0 && is_numeric($this->id)) $broadcastPost['ID'] = $this->id;

    // Insert Post
    $this->id = wp_insert_post($broadcastPost);

    // Add the meta
    foreach ($this->meta_fields as $meta) {
      update_post_meta($this->id, 'wpu_'.$meta, $this->{$meta});
    }

    // Do something
    restore_current_blog();
    
    // Return the id of the new post
    return $this->id;

  } // end save;

} // end Class WU_Broadcast


<?php
/**
 * WebHook Model Class
 *
 * Models the database implementation of our webhooks
 *
 * @since       1.6.0
 * @author      Arindo Duque
 * @category    Admin
 * @package     WP_Ultimo/Model
 * @version     1.0.0
*/

if (!defined('ABSPATH')) {
  exit;
}

/**
 * WU_Webhook class.
 */
class WU_Webhook {

  /**
   * Holds the ID of the WP_Post, to be used as the ID of each webhook
   * @var integer
   */
  public $id = 0;

  /**
   * Holds the WP_Post Object of the Webhook
   * @var null
   */
  public $post = null;

  /**
   * The status of the post
   * @var string
   */
  public $post_status = '';

  /**
   * Meta fields contained as attributes of each webhook
   * @var array
   */
  public $meta_fields = array(
    'name',
    'url',
    'event',
    'sent_events_count',
    'active',
    'hidden',
    'integration',
  );

  /**
   * Construct our new webhook
   */
  public function __construct($webhook = false) {

    switch_to_blog( get_current_site()->blog_id );

    if ( is_numeric( $webhook ) ) {
      $this->id   = absint( $webhook );
      $this->post = get_post( $webhook );
      $this->get_webhook( $this->id );
    } elseif ( $webhook instanceof WU_Webhook ) {
      $this->id   = absint( $webhook->id );
      $this->post = $webhook->post;
      $this->get_webhook($this->id);
    } elseif ( isset( $webhook->ID ) ) {
      $this->id   = absint( $webhook->ID );
      $this->post = $webhook;
      $this->get_webhook( $this->id );
    }

    restore_current_blog();

  } // end construct;

  /**
   * Gets a webhook from the database.
   * @param int  $id (default: 0).
   * @return bool
   */
  public function get_webhook($id = 0) {

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

    if ($key == 'name') return $this->post->post_content;
    if ($key == 'post_title') return $this->post->post_title;
    if ($key == 'post_date') return $this->post->post_date;

    // Swicth to main blog
    switch_to_blog( get_current_site()->blog_id );
    $value = get_post_meta( $this->id, 'wpu_' . $key, true);
    restore_current_blog();
    return $value;

  }

  /**
   * Set attributes in a webhook, based on a array. Useful for validation
   * @param array $atts Attributes
   */
  public function set_attributes($atts) {
    
    foreach($atts as $att => $value) {
      $this->{$att} = $value;
    }

    $this->post_title   = wp_strip_all_tags($this->name);
    $this->post_content = wp_kses_stripslashes(wp_filter_kses($this->name));

    return $this;

  } // end set_attributes;

  /**
   * Save the current Webhook
   */
  public function save() {

    // Switch to main blog
    switch_to_blog( get_current_site()->blog_id );

    $this->post_title   = wp_strip_all_tags($this->name);
    $this->post_content = wp_filter_kses($this->name);

    $webhookPost = array(
      'post_type'     => 'wpultimo_webhook',
      'post_title'    => $this->name,
      'post_content'  => $this->post_content,
      'post_status'   => 'publish',
    );

    if ($this->id !== 0 && is_numeric($this->id)) $webhookPost['ID'] = $this->id;

    // Insert Post
    $this->id = wp_insert_post($webhookPost);

    // Add the meta
    foreach ($this->meta_fields as $meta) {
      update_post_meta($this->id, 'wpu_'.$meta, $this->{$meta});
    }

    // Do something
    restore_current_blog();
    
    // Return the id of the new post
    return $this->id;

  } // end save;

  /**
   * Delete the model
   *
   * @return void
   */
  public function delete() {

    // Switch to main blog
    switch_to_blog( get_current_site()->blog_id );

      $result = wp_delete_post($this->id, true);

    restore_current_blog();

    return $result;

  } // end delete;

  public function get_logs() { } // end get_logs;

  /**
   * Get all Webhooks
   *
   * @return array;
   */
  public static function get_webhooks($args = array()) {

    switch_to_blog( get_current_site()->blog_id );

      $webhooks = array_merge(array(
        'posts_per_page' => -1,
        'post_type'      => 'wpultimo_webhook',
        'post_status'    => 'publish',
        'orderby'        => 'ID',
        'order'          => 'ASC',
      ), $args);

      $webhooks_found = array_map(function($item) {

        return new WU_Webhook($item);

      }, get_posts($webhooks));

    restore_current_blog();

    return $webhooks_found;

  } // end get_webhooks;

  /**
   * Get webhooks for JS
   *
   * @return array
   */
  public static function get_webhooks_for_js($args = array()) {

    $webhooks = self::get_webhooks($args);

    return array_map(function($item) {

      return array(
        'id'                => $item->id,
        'name'              => $item->name ? $item->name : __('No Name', 'wp-ultimo'),
        'url'               => $item->url,
        'event'             => $item->event,
        'sent_events_count' => (int) $item->sent_events_count,
        'active'            => (int) $item->active,
        'integration'       => $item->integration ? ucfirst($item->integration) : __('Manual', 'wp-ultimo'),
      );

    }, $webhooks);

  } // end get_webhooks_for_js;

  // public function 

} // end Class WU_Webhook

<?php
/**
 * Pages About
 *
 * Handles the addition of the About Page
 *
 * @author      WPUltimo
 * @category    Admin
 * @package     WPUltimo/Pages
 * @version     0.0.1
*/

if (!defined('ABSPATH')) {
  exit;
}

class WU_Page_About extends WU_Page {

  /**
   * Calls the parent consteruct to create the page, but adds the ajax listener that serves the Changelog content
   *
   * @since 1.9.4
   * @param boolean $network
   * @param array $atts
   */
  public function __construct($network = true, $atts = array()) {

    parent::__construct($network, $atts);

    add_action('wp_ajax_wu_serve_changelogs', array($this, 'serve_changelogs'));

  } // end construct;

  /**
   * Gets the changelog page and strip the changelog contents
   *
   * @since 1.9.4
   * @return void
   */
  public function serve_changelogs() {

    if (!wp_verify_nonce($_REQUEST['_wpnonce'], 'serve_changelogs')) {
      
      echo __('Error retrieving the changelog.', 'wp-ultimo');

      die;

    } // end if;

    $response = wp_remote_get(WU_Links()->get_link('changelog'));

    $body = json_decode(wp_remote_retrieve_body($response));

    echo $body;

    exit;

  } // end serve_changelogs;
  
  /**
   * Sets the output template for this particular page
   *
   * @since 1.8.2
   * @return void
   */
  public function output() {

    WP_Ultimo()->render('meta/about');

  } // end output;
  
} // end class WU_Page_About;

new WU_Page_About(true, array(
  'id'         => 'wp-ultimo-about',
  'type'       => 'submenu',
  'capability' => 'manage_network',
  'title'      => __('About', 'wp-ultimo'),
  'menu_title' => __('About', 'wp-ultimo'),
));

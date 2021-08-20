<?php
/**
 * Page Webhooks
 *
 * Moving the page Webhooks to a separate file, to make things organized
 *
 * @author      WP_Ultimo
 * @category    Admin
 * @package     WP_Ultimo/Pages
 * @version     0.0.1
*/

if (!defined('ABSPATH')) {
  exit;
}

class WU_Page_Webhooks extends WU_Page {

  /**
   * Initializes the page
   *
   * @return void
   */
  public function init() {

    add_action('admin_bar_menu', array($this, 'add_top_bar_menu'), 200);

  } // end init;

  /**
   * Adds the hooks we only need to run inside this particular page
   *
   * @return void
   */
  public function hooks() {

    /**
     * Add notice explaining how to integrate with Zapier
     */
    $this->add_notices();

  } // end hooks;

  /**
   * Register the scripts we will need for this page
   *
   * @return void
   */
  public function register_scripts() {

    $suffix = WP_Ultimo()->min;

    wp_register_script('wu-webhooks-page', WP_Ultimo()->get_asset("wu-webhooks-page$suffix.js", 'js'), array('jquery'), WP_Ultimo()->version, true);

    wp_localize_script('wu-webhooks-page', 'wu_webhook_page_vars', array(
      'error_title'          => __('An unexpected error happened', 'wp-ultimo'),
      'error_message'        => __('Try again later', 'wp-ultimo'),
      'new_message'          => __('New Webhook', 'wp-ultimo'),
      'test_title'           => __('Test Event Sent!', 'wp-ultimo'),
      'test_message'         => __('Response got from the remote server:', 'wp-ultimo'),
      'are_webhooks_enabled' => WU_Settings::get_setting('enable-webhooks', true),
      'webhooks_list'        => WU_Webhook::get_webhooks_for_js(),
      'integrations'         => WU_Webhooks()->get_all_filters(),
    ));

  } // end register_scripts;

  /**
   * Adds the notices for the page, like the Zapier tutorial link and the enable message.
   * We also check if webhooks are enabled, otherwise we add an error message
   *
   * @return void
   */
  public function add_notices() {

    $message = sprintf(
      '%s <br><a target="_blank" href="%s">%s</a>', 
      __('Want to integrate WP Ultimo with Zapier?', 'wp-ultimo'), 
      WU_Links()->get_link('webhooks-tutorial'), 
      __('Read the Tutorial &rarr;', 'wp-ultimo')
    );

    WP_Ultimo()->add_message($message, 'success zapier-notice', true);

    /**
     * Check for the enable state of webhooks
     */
    if (!WU_Settings::get_setting('enable-webhooks', true)) {

      $message_webhook = sprintf(
        '%s <a style="text-decoration: none;" href="%s">%s</a>', 
        __('Webhooks are currently disabled and will not be triggered.', 'wp-ultimo'), 
        network_admin_url('admin.php?page=wp-ultimo&wu-tab=advanced'), 
        __('Enable Webhook on the Settings page &rarr;', 'wp-ultimo')
      );

      WP_Ultimo()->add_message($message_webhook, 'error', true);

    } // end if;

  } // end add_notices;

  /**
   * Add the submenu to the tools webhook
   *
   * @param WP_Admin_Bar $wp_admin_bar
   * @return void
   */
  public function add_top_bar_menu($wp_admin_bar) {

    $webhooks = array(
      'id'      => 'wp-ultimo-settings-tools-webhook',
      'parent'  => 'wp-ultimo-settings-tools',
      'title'   => __('Webhooks', 'wp-ultimo'),
      'href'    => network_admin_url('admin.php?page=wp-ultimo-webhooks'),
      'meta'    => array(
        'class' => 'wp-ultimo-top-menu', 
        'title' => __('Go to the settings page', 'wp-ultimo'),
    ));

    $wp_admin_bar->add_node($webhooks);
  
  } // end add_top_bar_menu;
  
  /**
   * Sets the output template for this particular page
   *
   * @since 1.8.2
   * @return void
   */
  public function output() {

    add_thickbox();

    wp_enqueue_script('wu-webhooks-page');

    WP_Ultimo()->render('webhooks/list');

  } // end output;
  
} // end class WU_Page_Webhooks;

new WU_Page_Webhooks(true, array(
  'id'         => 'wp-ultimo-webhooks',
  'type'       => 'submenu',
  'capability' => 'manage_network',
  'title'      => __('Webhooks', 'wp-ultimo'),
  'menu_title' => __('Webhooks', 'wp-ultimo'),
));

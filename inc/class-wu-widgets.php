<?php
/**
 * Admin Widgets
 *
 * Adds our admin widgets to different parts of the blog
 *
 * @author      WPUltimo
 * @category    Admin
 * @package     WPUltimo/Pages
 * @version     0.0.1
*/

if (!defined('ABSPATH')) {
  exit;
}

class WU_Widgets {

  /**
   * Holds the transactions table
   *
   * @since 1.8.2
   * @var WU_Transactions_List_Table
   */
  public $transactions_list = false;

  /**
   * Defines and set our hooks for the widgets
   * @private
   */
  public function __construct() {

    add_action("load-index.php", array($this, 'screen_option_transactions'));

    add_action('wp_ajax_wu_get_activity', array($this, 'server_transactions'));

    add_action("wp_ajax_wu_transactions_fetch_ajax_results", array($this, 'process_ajax_filter'));

    add_action("wp_ajax_wu_fetch_rss", array($this, 'process_ajax_fetch_rss'));

    add_action('wp_network_dashboard_setup', array($this, 'network_admin_widgets'));
    
    add_action('wp_dashboard_setup', array($this, 'admin_widgets'));

    add_filter('set-screen-option', array($this, 'save_screen_option'), 8, 3);

    add_action('wu_page_load', array($this, 'account_widgets'), 20, 2);

    add_action('wu_page_load', array($this, 'edit_subscription_widgets'), 20, 2);
    
  } // end construct;

  /**
   * We need to allow WordPress to save our custom screen options if they are valid
   *
   * @param mixed $value
   * @param string $option
   * @param int $other_value
   * @return void
   */
  public function save_screen_option($value, $option, $other_value) {

    return $value === false && is_numeric($other_value) ? (int) $other_value : $value;

  } // end save_screen_option;

  /**
   * Loads the transactions table and sets the screen options
   *
   * @since 1.8.2
   * @return void
   */
  public function get_transactions_table() {

    require_once WP_Ultimo()->path('inc/class-wu-transactions-list-table.php'); // @since 1.1.0

    $GLOBALS['hook_suffix'] = '';

    if (is_network_admin() || wp_doing_ajax()) {

      $user_id = isset($_REQUEST['user_id']) ? $_REQUEST['user_id'] : false;
      
    } else {

      $subscription = wu_get_current_site()->get_subscription();
      
      $user_id = $subscription->user_id;

    } // end if;

    $this->transactions_list = new WU_Transactions_List_Table($user_id);

  } // end get_transactions_table;

    /**
   * Process Ajax Filters for the tables, if one is set
   *
   * @since 1.8.2
   * @return void
   */
  public function process_ajax_filter() {

    $this->get_transactions_table();

    if ($this->transactions_list) {

      $this->transactions_list->ajax_response();

    } // end if;

  } // end process_ajax_filter;

  public function process_ajax_fetch_rss() {

    $atts = wp_parse_args($_GET, array(
      'url'          => WU_Links()->get_link('rss-feed'),
      'title'        => __('Forum Discussions', 'wp-ultimo'),
      'items'        => 3,
      'show_summary' => 1,
      'show_author'  => 0,
      'show_date'    => 1,
    ));

    wp_widget_rss_output($atts);

    exit;

  } // end process_ajax_fetch_rss;

  /**
   * Adds the common widgets (Billing History and Sites List) to the Account screen
   *
   * @since 1.8.2
   * @param string $page_id
   * @param string $page_hook
   * @return void
   */
  public function account_widgets($page_id, $page_hook) {

    if ($page_id == 'wu-my-account') {

      /**
       * Load Transactions table
       */
      $this->get_transactions_table();

      /**
       * Billing History
       * 
       * @since 1.1.0
       */
      add_meta_box('wu-mb-billing-history', __('Billing History', 'wp-ultimo'), array($this, 'output_widget_billing_history'), $page_id, 'side');

      /**
       * Sites List
       * 
       * @since 1.2.0
       */
      add_meta_box('wu-mb-sites-lists', __('Sites List', 'wp-ultimo'), array($this, 'output_widget_sites_list'), $page_id, 'normal');

    } // end if;

  } // end account_widgets;

  /**
   * Adds the common widgets (Billing History and Sites List) to the edit Subscription screen
   *
   * @since 1.8.2
   * @param string $page_id
   * @param string $page_hook
   * @return void
   */
  public function edit_subscription_widgets($page_id, $page_hook) {

    if ($page_id == 'wu-edit-subscription') {

      /**
       * Load Transactions table
       */
      $this->get_transactions_table();

      /**
       * Get current screen
       */
      $screen = get_current_screen();

      /**
       * Sites List
       * 
       * @since 1.2.0
       */
      add_meta_box('wu-mb-sites-lists', __('Sites List', 'wp-ultimo'), array($this, 'output_widget_sites_list'), $screen->id, 'advanced');

      /**
       * Billing History
       * 
       * @since 1.1.0
       */
      add_meta_box('wu-mb-billing-history', __('Billing History', 'wp-ultimo'), array($this, 'output_widget_billing_history'), $screen->id, 'advanced');


    } // end if;

  } // end edit_subscription_widgets;
  
  /**
   * Adds the network admin widgets
   */
  public function network_admin_widgets() {
    
    // Ultimo Stats
    add_meta_box('wp-ultimo-status', __('WP Ultimo - Summary', 'wp-ultimo'), array('WU_Widgets', 'output_widget_stats'), 'dashboard-network', 'normal', 'high');

    // Ultimo Stats
    add_meta_box('wp-ultimo-news', __('WP Ultimo - News & Discussions', 'wp-ultimo'), array('WU_Widgets', 'output_widget_news'), 'dashboard-network', 'side', 'low');

    /**
     * Adding Activity Stream
     * @since 1.5.4
     */
    add_meta_box('wp-ultimo-activity-stream', __('WP Ultimo - Activity Stream', 'wp-ultimo'), array('WU_Widgets', 'output_widget_activity_stream'), 'dashboard-network', 'normal', 'low');
    
  } // end network_admin_widgets;

  /**
   * Checks if we should add the limits and quotas widget
   *
   * @since 1.7.0
   * @return boolean
   */
  public function should_display_limits_and_quotas() {

    $elements = WU_Settings::get_setting('limits_and_quotas');

    if ($elements !== false && !empty($elements)) {

      return current_user_can('manage_network') || current_user_can('edit_posts');

    } // end if;

    return false;

  } // end should_display_limits_and_quotas;
  
  /**
   * Adds the normal dashboard widgets (for my account, and limit status)
   */
  public function admin_widgets() {

    if (!apply_filters('wu_should_display_admin_widgets', true)) return;
    
    // // Get the plan and check it
    $subscription = wu_get_current_site()->get_subscription();

    if (!$subscription || !$subscription->has_plan()) return;
    
    // Ultimo Stats
    add_meta_box('wp-ultimo-account', __('Account Statistics', 'wp-ultimo'), array('WU_Widgets', 'output_widget_account_statistics'), 'dashboard', 'normal', 'high');

    if ($this->should_display_limits_and_quotas()) {

      add_meta_box('wp-ultimo-quotas', __('Limits and Quotas', 'wp-ultimo'), array('WU_Widgets', 'output_widget_limits_and_quotas'), 'dashboard', 'side', 'high');

    } // end if;
    
  } // end admin_widgets; 
  
  /**
   *
   * OUTPUT STATIC FUNCTIONS
   * Now we define the output function of each widget
   *
   */
  
  /**
   * Outputs the wp ultimo news widget
   * 
   * @since 1.9.8 Uses ajax to load, fetches news from the WP Ultimo blog as well
   * @return void
   */
  public static function output_widget_news($user) { ?>

    <div class="rss-widget">
      <div class='wu-rss-widget-title'><?php _e('From the Blog', 'wp-ultimo'); ?></div>
      <div id='wp-ultimo-blog-feed'><?php _e('Loading...', 'wp-ultimo'); ?></div>
    </div>

    <div id="major-publishing-actions" style="margin: 12px -12px -12px;">
      <a target="_blank" href="<?php echo WU_Links()->get_link('facebook-group'); ?>" class="button button-primary button-streched"><?php _e('Join our Facebook Group!', 'wp-ultimo'); ?> &rarr;</a>
    </div>

    <script>
      (function($) {
        $(document).ready(function() {

          /** WP Ultimo Blog */
          $.get({
            url: ajaxurl,
            data: <?php echo json_encode(array(
              'action'       => 'wu_fetch_rss',
              'url'          => WU_Links()->get_link('blog-rss-feed'),
              'title'        => __('WP Ultimo Blog', 'wp-ultimo'),
              'items'        => 3,
              'show_summary' => 1,
              'show_author'  => 0,
              'show_date'    => 1,
            )); ?>,
            success: function(response) {
              $('#wp-ultimo-blog-feed').html(response);
            },
            error: function() {
              $('#wp-ultimo-blog-feed').html('<?php echo __("Error loading external feed.", "wp-ultimo"); ?>');
            }
          }); // end get;

        });
      })(jQuery);
    </script>

    <?php

  } // end output_widget_news;

  /**
   * Screen options for the Edit Subscription Page, loads the list table of transactions
   * 
   * @return void
   */
  public function screen_option_transactions() {

    $args = array(
      'label'   => __('Transactions', 'wp-ultimo'),
      'default' => 5,
      'option'  => 'transactions_index_per_page'
    );

    add_screen_option('per_page', $args);
    
  } // end screen_option_transactions;

  /**
   * Serve transactions to the activity stream
   * TODO: Add site and account creation as well.
   *
   * @since 1.5.4
   * @return void
   */
  public function server_transactions() {

    if (!current_user_can('manage_network')) {
      
      wp_send_json(array());

    } // end if;

    $this->get_transactions_table();

    $page = isset($_GET['page']) ? $_GET['page'] : 1;

    $per_page = get_user_option('transactions_index_per_page') ?: 10;

    $transactions = WU_Transactions::get_transactions(false, (int) $per_page, $page);

    /**
     * Map to get difference
     */
    $transactions = array_map(function($item) {

      $time = date_i18n('U', strtotime($item->time));

      $item->from_now = sprintf(__('%s ago', 'wp-ultimo'), human_time_diff($time, current_time('timestamp')));

      $item->amount = $item->amount != '--' ? wu_format_currency($item->amount) : ''; // Format money

      // Get users
      if ($item->user_id) {

        $user = get_user_by('ID', $item->user_id);

        $item->user = $user ? $user->data : (object) array();
        
        $item->user->avatar = get_avatar($item->user_id, 50, 'identicon', '', array(
          'force_display' => true,
        ));

      } // end if;

      // Get icon
      $item->type_icon = WU_Transactions_List_Table::column_type($item);

      // Get Label
      $item->type_label = ucwords(str_replace('_', ' ', $item->type));

      return $item;

    }, $transactions);

    wp_send_json($transactions);

  } // end server_transactions;

  /**
   * Outputs the network activity stream widget
   * 
   * @since 1.5.4
   */
  public static function output_widget_activity_stream($user) {

    WP_Ultimo()->render('widgets/network/activity-stream', array(
      'user' => $user,
    ));

  } // end output_widget_activity_stream;

  /**
   * Outputs the network stats widget
   */
  public static function output_widget_stats($user) {
    WP_Ultimo()->render('widgets/network/network-stats', array(
      'user' => $user,
    ));
  } // end output_widget_stats;
  
  /**
   * Outputs the account status widget for the user
   */
  public static function output_widget_account_statistics($user) {
    WP_Ultimo()->render('widgets/account/account-statistics', array(
      'user' => $user,
    ));
  } // end output_widget_account_statistics;

  /**
   * Outputs the account status widget for the user
   */
  public static function output_widget_limits_and_quotas($user) {
    WP_Ultimo()->render('widgets/account/limits-and-quotas', array(
      'user' => $user,
    ));
  } // end output_widget_limits_and_quotas;


  /**
   * Displays the billing history
   * 
   * @since 1.1.0
   * @param WU_Subscription $subscription Subscription instance
   * @return void
   */
  public function output_widget_billing_history($subscription) {

    if ($subscription == null) $subscription = wu_get_current_site()->get_subscription();

    wp_enqueue_script('wu-add-payment-to-subscription');
    
    WP_Ultimo()->render('widgets/subscriptions/edit/billing-history', array(
      'subscription'       => $subscription,
      'user_id'            => $subscription->user_id,
      'transactions_list'  => $this->transactions_list,
      'transactions_count' => WU_Transactions::get_transactions_count($subscription->user_id),
    )); 

  } // end output_widget_billing_history;

  /**
   * Displays the Sites List
   * 
   * @since 1.2.0
   * @param WU_Subscription $subscription Subscription instance
   * @return void
   */
  public function output_widget_sites_list($subscription) {
    
    if (!$subscription) $subscription = wu_get_current_site()->get_subscription();

    WP_Ultimo()->render('widgets/subscriptions/edit/sites-list', array(
      'user_id' => $subscription->user_id,
      'sites'   => WU_Site_Owner::get_user_sites($subscription->user_id)
    )); 

  } // end output_widget_sites_lists;
  
} // end class WU_Widgets;

new WU_Widgets;

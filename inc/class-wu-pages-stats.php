<?php
/**
 * Pages Statistics
 *
 * Handles the addition of the Statistics Page
 * 
 * @since 1.8.1 It now uses the new WU_Page
 *
 * @author      WPUltimo
 * @category    Admin
 * @package     WPUltimo/Pages
 * @version     0.0.1
*/

if (!defined('ABSPATH')) {
  exit;
}

class WU_Page_Statistics extends WU_Page {

  /**
   * Register the scripts we will need for this page
   *
   * @return void
   */
  public function register_scripts() {

    $suffix = WP_Ultimo()->min;

    wp_enqueue_script('dashboard');

    wp_enqueue_style('genericons', '//cdn.jsdelivr.net/genericons/3.4.1/genericons/genericons.css');

    wp_register_script('wu-stats', WP_Ultimo()->get_asset("wu-stats$suffix.js", 'js'), array('jquery'), WP_Ultimo()->version, true);

  } // end register_scripts;

  /**
   * Create our widgets
   */
  public function register_widgets() {

    // Return if not necessary
    $screen = get_current_screen();

    // MRR
    add_meta_box('wp-ultimo-mrr', __('Monthly Recurring Revenue', 'wp-ultimo'), array('WU_Page_Statistics', 'output_widget_mrr'), $screen->id, 'normal', 'high');

    // General Data
    add_meta_box('wp-ultimo-general', __('General Data', 'wp-ultimo'), array('WU_Page_Statistics', 'output_widget_general_stats'), $screen->id, 'side', 'high');

    // Users
    add_meta_box('wp-ultimo-users', __('Users', 'wp-ultimo'), array('WU_Page_Statistics', 'output_widget_users_stats'), $screen->id, 'side', 'high');

  } // end register_widgets;

  /**
   * Output the widget of our graphs for user growth
   */
  public static function output_widget_mrr() {
    
    // Render the page
    WP_Ultimo()->render('widgets/statistics/mrr', array(
      'mrr' => self::get_stat('mrr'),
    ));

  } // end output_widget_mrr;

  /**
   * Output the widget of our graphs for user growth
   */
  public static function output_widget_users_stats() {
    
    // Render the page
    WP_Ultimo()->render('widgets/statistics/users', array(
      'users' => self::get_stat('users'),
    ));

  } // end output_widget_users_stats;

  /**
   * Output the widget of our graphs for general data
   */
  public static function output_widget_general_stats() {
    
    // Render the page
    WP_Ultimo()->render('widgets/statistics/general', array(
      'total_revenue' => self::get_total_revenue(),
    ));

  } // end output_widget_general_stats;

  /**
   * Returns the current numeric value of MRR
   * @return float MRR
   */
  public static function get_current_mrr() {
    
    $data = self::get_stat('mrr');

    return $data['total_value'];

  } // end get_current_mrr;

  /**
   * Get total revenue earned in the network
   * @return string Total revenue
   */
  public static function get_total_revenue() {

    global $wpdb;

    $table_name = WU_Transactions::get_table_name();

    $query = "SELECT SUM(amount) AS total FROM $table_name WHERE type = 'payment'";

    $results = $wpdb->get_row($query);

    return $results->total;

  } // end get_total_revenue;

  /**
   * Returns important data about the state of the network
   * @param  string  $stat       Statistic to get
   * @param  boolean $categories Either or not to add the values in columns with year/month
   * @param  integer $limit      Number of months to get
   * @param  string  $format     Date format
   * @return array               Array contianign the returned data, including graph data
   */
  public static function get_stat($stat = 'mrr', $categories = true, $limit = 6, $format = 'Y/m') {

    global $wpdb;

    /**
     * Check for cache
     */
    $data = wp_cache_get($stat, 'wu_stats');

    if ($data) return $data;

    $table_name = WU_Subscription::get_table_name();

    /**
     * Get MRR
     */
    if ($stat == 'mrr') {

      $query = "SELECT 
        YEAR(created_at) as year,
        MONTH(created_at) AS month, 
        SUM(price/freq) AS value 
        FROM $table_name
        WHERE integration_status = 1 
        GROUP BY YEAR(created_at), MONTH(created_at) 
        ORDER BY YEAR(created_at), MONTH(created_at)";
        
    } // end if;

    /**
     * Get Users
     */
    else if ($stat == 'users') {

      $query = "SELECT 
        YEAR(created_at) as year,
        MONTH(created_at) AS month, 
        1 AS value 
        FROM $table_name
        GROUP BY YEAR(created_at), MONTH(created_at) 
        ORDER BY YEAR(created_at), MONTH(created_at)";
        // LIMIT %d;";

    } // end if;

    $results = $wpdb->get_results(($query));

    /**
     * Mount results data
     */
    $data = array('pairs' => array(), 'total_value' => 0, 'growth_sign' => 'positive', 'growth' => 0);

    /**
     * Load data with all the months
     */
    $now = new DateTime();

    for ($i = 1; $i <= $limit; $i++) {

      if ($i != 1) {
        
        $now->sub(DateInterval::createFromDateString("1 Month"));

      } // end if;

      $data['pairs'][$now->format('Y/m')] = array($now->format('Y/m'), 0);

    } // end for;

    // Set previous month
    $previous_month = 0;

    // Loop results
    foreach ($results as $index => $date_range) {

      $previous_month = $previous_month == 0 ? 1 : $data['total_value'];

      $xaxis = $categories ? "$date_range->year/$date_range->month" : $index;
      $data['total_value']  += $date_range->value;

      // Only add if its in the limit
      // if (isset($data['pairs'][$xaxis]))
      $data['pairs'][$xaxis] = array($xaxis, (float) $data['total_value']);

      // Calculate Growth
      if ($previous_month) {
        $data['growth'] = (($data['total_value'] - $previous_month) / $previous_month) * 100;
      } else {
        $data['growth'] = 0;
      }
      $data['growth_sign'] = $data['growth'] < 0 ? 'negative' : 'positive';
      $data['growth']      = abs($data['growth']);

    } // end foreach;

    $data['pairs'] = array_values($data['pairs']);

    // Set cache
    wp_cache_set($stat, $data, 'wu_stats', 1 * HOUR_IN_SECONDS);

    return $data;

  } // end get_stat;

  /**
   * Displays the page content
   * 
   * @return void
   */
  public function output() {

    wp_enqueue_script('wu-stats');

    // Render the page
    WP_Ultimo()->render('meta/statistics');

  } // end output;
  
} // end class WU_Page_Statistics;

new WU_Page_Statistics(true, array(
  'id'         => 'wp-ultimo-stats',
  'type'       => 'submenu',
  'capability' => 'manage_network',
  'title'      => __('Statistics', 'wp-ultimo'),
  'menu_title' => __('Statistics', 'wp-ultimo'),
));

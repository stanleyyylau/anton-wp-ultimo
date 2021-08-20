<?php
/**
 * Shortcode class
 *
 * Defines the plugin shortcodes available for use in the sites
 *
 * @author      Arindo Duque
 * @category    Admin
 * @package     WP_Ultimo/Shortcodes
 * @version     0.0.1
*/

if (!defined('ABSPATH')) {
  exit;
}

class WU_Shortcodes {

  /**
   * Control array for the template list shortcode
   *
   * @since 1.7.4
   * @var array|boolean
   */
  public $templates = false;

  /**
   * Defines all the shortcodes
   */
  public function __construct() {

    // Pricing Table
    add_shortcode('wu_pricing_table', array($this, 'pricing_table'));

    // Paying users
    add_shortcode('wu_paying_users', array($this, 'paying_users'));

    /**
     * @since  1.2.1 Adds Plan Link Shortcode
     */
    add_shortcode('wu_plan_link', array($this, 'plan_link'));

    /**
     * @since  1.2.2 Templates Display
     */
    add_shortcode('wu_templates_list', array($this, 'templates_list'));

    /**
     * @since  1.4.0 User meta getter
     */
    add_shortcode('wu_user_meta', array($this, 'user_meta'));

    /**
     * @since 1.5.0 Restricted content
     */
    add_shortcode('wu_restricted_content', array($this, 'restricted_content'));

  } // end construct;

  /**
   * Return the value of a user meta on the database.
   * This is useful to fetch data saved from custom sign-up fields during sign-up
   *
   * @param array $atts
   * @return void
   */
  public function user_meta($atts) {

    // Chain of user_id
    $site = wu_get_current_site();

    if ($site && $site->site_owner) {

      $user_id = $site->site_owner_id;

    } else {

      $user_id = get_current_user_id();

    } // end if;

    $atts = shortcode_atts(array(
      'user_id'   => $user_id,
      'meta_name' => 'first_name',
      'default'   => false,
      'unique'    => true
    ), $atts, 'wu_user_meta');

    $value = get_user_meta($atts['user_id'], $atts['meta_name'], $atts['unique']);

    return $value ?: '--';

  } // end user_meta;

  /**
   * Makes sure we don't return any invalid values
   *
   * @since  1.7.4
   * @param  string $templates
   * @return array
   */
  public function treat_template_list($templates) {

    $list = array_map('trim', explode(',', $templates));

    return array_filter($list);

  } // end treat_template_list;

  /**
   * Filter the template list for shortcodes
   *
   * @since  1.7.4
   * @param  array $templates
   * @return array
   */
  public function filter_template_list($templates) {

    return is_array($this->templates) ? $this->templates : $templates;

  } // end filter_template_list;

  /**
   * Display the Templates List
   * 
   * @param  array $atts
   * @return array
   */
  public function templates_list($atts) {

    wp_enqueue_style('wu-shortcodes');

    $atts = shortcode_atts(array(
      'show_filters' => true,
      'show_title'   => true,
      'templates'    => false,
      'cols'         => 3,
    ), $atts, 'wu_templates_list');

    /**
     * Hide header, if necessary
     */
    add_filter('wu_step_template_display_header', $atts['show_title'] ? '__return_true' : '__return_false');

    /**
     * Filters the template list to be used
     * @since 1.7.4
     */
    $templates = $atts['templates'] ? $this->treat_template_list($atts['templates']) : false;

    $this->templates = $templates;
    
    add_filter('all_site_templates', array($this, 'filter_template_list'), 300);

    // Render the selector
    ob_start();

      WP_Ultimo()->render('signup/steps/step-template', array(
        'signup'       => WU_Signup(),
        'is_shortcode' => true,
        'show_filters' => (bool) $atts['show_filters'],
        'cols'         => $atts['cols'],
      ));

    $templates_list = ob_get_clean();

    return $templates_list;

  } // end templates_list;

  /**
   * Get the number
   *
   * @param string $str
   * @return void
   */
  static function get_numbers($str) {
    preg_match_all('/\d+/', $str, $matches);
    return isset($matches[0][0]) ? (int) $matches[0][0] : $str;
  }

  /**
   * Plan Link
   * @param  array $atts
   * @return array
   */
  public function plan_link($atts) {

    $atts = shortcode_atts(array(
      'plan_id'   => 0,
      'skip_plan' => 1, 
      'plan_freq' => WU_Settings::get_setting('default_pricing_option'),
      'action'    => 'wu_process_plan_select',
    ), $atts, 'wu_plan_link');

    /**
     * Treat the results to make sure we are getting numbers out of it
     * @since 1.5.1
     */
    foreach(array('plan_id', 'plan_freq') as $att) {

      $atts[$att] = (int) self::get_numbers($atts[$att]);

    } // end foreach;
    
    $plan = wu_get_plan($atts['plan_id']);

    if ($plan) {

      return $plan->get_shareable_link($atts['plan_freq']);

    } // end if;

    $url = add_query_arg($atts, admin_url('admin-ajax.php'));

    return $url;

  }// end plan_link;

  /**
   * Display the pricing Tables of WP Ultimo
   * 
   * @param  array $atts Attributes of the shortcode
   * @return null
   */
  public function pricing_table($atts) {

    $suffix = WP_Ultimo()->min;

    $atts = shortcode_atts(array(
      'primary_color'          => WU_Settings::get_setting('primary-color', '#00a1ff'),
      'accent_color'           => WU_Settings::get_setting('accent-color', '#78b336'),
      'default_pricing_option' => WU_Settings::get_setting('default_pricing_option', 1),
      'plan_id'                => false,
      'show_selector'          => true,
    ), $atts, 'wu_pricing_table');

    // wp_enqueue_script('wp-ultimo');    
    // wp_enqueue_script('wu-pricing-table');
    // wp_enqueue_style('wu-pricing-table');
    // wp_enqueue_style('wu-pricing-table', WP_Ultimo()->url("assets/css/wu-pricing-table$suffix.css"));
    // wp_register_script($handle, $src, $deps, $in_footer)

    wp_localize_script('wu-pricing-table', 'wpu', array(
      'default_pricing_option' => WU_Settings::get_setting('default_pricing_option', 1),
    ));

    wp_enqueue_script('wu-pricing-table');
    wp_enqueue_style('wu-pricing-table');

    // wp_enqueue_style('admin-bar');
    // wp_enqueue_style('wu-signup', WP_Ultimo()->url("assets/css/wu-signup$suffix.css"));

    // Our custom CSS
    wp_enqueue_style('wu-login');

    $plan_ids = is_string($atts['plan_id']) && $atts['plan_id'] !== 'all' ? explode(',', $atts['plan_id']) : false;

    // Get all available plans
    $plans = WU_Plans::get_plans($plan_ids === false);

    $plans = array_filter($plans, function($plan) use ($plan_ids) {

      return $plan_ids === false || in_array($plan->get_id(), $plan_ids);

    });

    $html = '<div class="wu-content-plan">';

    // Render the selector
    ob_start();

    WP_Ultimo()->render('signup/pricing-table/pricing-table', array(
      'plans'        => $plans,
      'signup'       => WU_Signup(),
      'current_plan' => wu_get_current_site()->get_plan(),
      'is_shortcode' => true,
      'atts'         => $atts,
    ));

    $pricing_table = ob_get_clean();

    // Replace form
    $url           = apply_filters('wp_signup_location', network_site_url('wp-signup.php')); //wp_registration_url();
    $url           = add_query_arg(array('skip_plan' => 1), $url);
    $pricing_table = str_replace('<form', "<form action='$url'", $pricing_table);
    $html         .= $pricing_table;

    $html .= '</div>';

    return $html;

  } // end pricing_table;

  /**
   * Returns the number of paying users on the platform
   *
   * @param array $atts
   * @return void
   */
  public function paying_users($atts) {

    global $wpdb;

    $atts = shortcode_atts(array(), $atts, 'wu_pricing_table');

    $table_name = WU_Subscription::get_table_name();

    $result = $wpdb->get_row("SELECT count(id) as count FROM $table_name WHERE integration_status = 1");

    return $result->count;

  } // end paying_users;

  /**
   * Restricted Content Shortcode
   *
   * @param array $atts
   * @param string $content
   * @return void
   */
  public function restricted_content($atts, $content) {

    $atts = shortcode_atts(array(
      'plan_id'         => false,
      'only_active'     => true,
      'only_logged'     => false,
      'exclude_trials'  => false,
    ), $atts, 'wu_restricted_content');

    if (empty($atts) || !$atts['plan_id']) echo __('You need to pass a valid plan ID.', 'wp-ultimo');

    // Add support to arrays
    $plan_ids = explode(',', $atts['plan_id']);
    
    $plan_ids = array_map(function($item) {
      return trim($item);
    }, $plan_ids);
     
    $else = '[wu_default_content]';

    if (strpos($content, $else) !== false) {

      list($if, $else) = explode($else, $content,2);

    } else {

      $if   = $content;
      $else = "";

    } // end if;

    /**
     * Support to Gutenberg
     * @since 1.9.7
     */
    $else = trim($else, '</div>');

    /**
     * Check Condition
     */
    $condition = false;

    $subscription = (bool) $atts['only_logged'] ? wu_get_subscription(get_current_user_id()) : wu_get_current_site()->get_subscription();

    if ($subscription) {

      $condition = in_array($subscription->plan_id, $plan_ids) || in_array('all', $plan_ids);

      if ((bool) $atts['only_active']) {

        $condition = $condition && $subscription->is_active();

      } // end if;

      if ((bool) $atts['exclude_trials']) {

        $condition = $condition && !$subscription->get_trial();

      } // end if;

    } // end if;
          
    return do_shortcode($condition ? $if : $else);

  } // end restricted_content;

} // end class WU_Shortcodes

new WU_Shortcodes;
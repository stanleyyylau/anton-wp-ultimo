<?php
/**
 * Sign-up Class
 *
 * Here we replace the default WordPress sign-up page with our own custom page
 * divided by steps
 *
 * @since  1.4.0 Clean Up, Steps and Fields API, extendable registering using meta-fields on the user;
 * @since  1.2.0 Template selection with filtering;
 *
 * Want to add custom steps to the Sign-up? Read this:
 * @link http://docs.wpultimo.com/docs/adding-steps-to-the-signup-flow/
 *
 * @author      Arindo Duque
 * @category    Admin
 * @package     WP_Ultimo/Signup
 * @version     0.0.1
*/

if (!defined('ABSPATH')) {
  exit;
}

class WU_Signup {

  // class instance
  static $instance;

  /** Holds the Steps that can not be removed from the sing-up process */
  public $core_steps = array('begin_signup', 'plan', 'domain', 'account', 'create_account');

  /** @var $results Holds the results of the page */
  public $results;

  /**
   * Singleton
   */
  public static function get_instance() {

    if (!isset(self::$instance)) {
      self::$instance = new self();
    }

    return self::$instance;

  } // end get_instance;

  /**
   * Adds our main hooks, replacing the default sign-up
   * @private
   */
  public function __construct() {

    /**
     * @since  1.1.4 Adds the current screen script to avoid plugin conflicts
     */
    if (!function_exists('get_current_screen')) {

      require_once(ABSPATH . 'wp-admin/includes/screen.php');

    } // end if;

    // Add the login styles to the login styles
    add_action('login_enqueue_scripts', array($this, 'login_scripts'));

    // Filter the replace_signup
    add_filter('wu_replace_signup_urls', array($this, 'changed_register_url'));
    add_filter('wp_signup_location', array($this, 'get_register_url'));

    // Replace signup
    add_action('init', array($this, 'replace_signup'), 1);
    add_action('parse_request', array($this, 'check_auto_select_plan'), 1);

    // Set active signup for 'user' mandatory
    // That way we can just create the blog after the user signup, using our custom duplication tool
    add_filter('wu_active_signup', array($this, 'set_mandatory_blog'));

    /**
     * @since  1.2.1 Redirects for the plan_link shortcode
     */
    add_action('wp_ajax_nopriv_wu_process_plan_select', array($this, 'process_plan_select'));

    add_action('wp_ajax_wu_process_plan_select', array($this, 'process_plan_select'));

    /**
     * Adds the filter for plans, when they are selected
     * @since 1.5.4
     */
    add_filter('all_site_templates', array($this, 'filter_plan_templates'), 9);

    /**
     * Adds the filter for role, when they are selected
     */
    add_filter('wu_register_default_role', array($this, 'filter_plan_role'), 9);

    /**
     * Adds the filter for role, when they are selected
     * @since 1.6.0
     */
    add_filter('wu_register_trial_value', array($this, 'filter_plan_trial'), 9, 2);

    /**
     * Adds support to multiple domains
     */
    add_filter('init', array($this, 'maybe_add_domain_selection_field'));

    /**
     * Redirect Login fix
     * @since 1.7.3
     */
    add_action('init', array($this, 'redirect_after_login'), 1);

    /**
     * Remove Auth from the Signup
     * @since 1.9.1
     */
    add_action('admin_enqueue_scripts', array($this, 'remove_admin_scripts'), 999);

    /**
     * Transfer posts
     * @since 1.9.2
     */
    add_action('wu_signup_after_create_site', array($this, 'transfer_posts_to_new_user'), 10, 2);

    /**
     * Coupon code change price list values by url
     * @since 1.9.11
     */
    add_action('wu_after_signup_form', array($this, 'render_coupon_code_file_price_table'));


  } // end construct;

  public function render_coupon_code_file_price_table() {

    WP_Ultimo()->render('signup/pricing-table/coupon-code',
    array()
    );

  } // end render_coupon_code_file_price_table;

  /**
   * Remove unwanted scripts
   *
   * @since 1.9.1
   * @return void
   */
  public function remove_admin_scripts() {

    if (!$this->is_register_page()) return;

    wp_dequeue_script('wp-auth-check');

  } // end remove_admin_scripts;

  /**
   * Maybe add the domain selection field
   *
   * @since 1.6.3
   * @return void
   */
  public function maybe_add_domain_selection_field() {

    /**
     * Adding domain options
     */
    if (WU_Settings::get_setting('enable_multiple_domains') && WU_Settings::get_domain_option()) {

      wu_add_signup_field('domain', 'domain_option', 25, array(
        'name'    => apply_filters('wu_domain_step_title', __('Domain', 'wp-ultimo')),
        'tooltip' => apply_filters('wu_domain_step_tooltip', ''),
        'type'    => 'select',
        'options' => WU_Settings::get_domain_option()
      ));

    } // end if;

  } // end maybe_add_domain_selection_field;

  /**
   * Filters the trial value based on the plan
   *
   * @param integer $trial_days
   * @param WU_Plan $plan
   * @return integer
   */
  public function filter_plan_trial($trial_days, $plan) {

    $trial_for_plan = $plan->get_trial();

    return $trial_for_plan ? $trial_for_plan : $trial_days;

  } // end filter_plan_trial;

  /**
   * Filter the templates on the sign-up based on the plan selected previously
   *
   * @since 1.5.4
   * @param array $templates
   * @return array
   */
  public function filter_plan_templates($templates) {

    $transient = $this->get_transient(false);

    $site = wu_get_subscription( get_current_user_id() );

    /**
     * Get the plan id either from the transient OR via the currenct logged user
     */
    $plan_id = $site ? $site->get_plan_id() : false;

    if (isset($transient['plan_id'])) {

      $plan_id = $transient['plan_id'];

    } // end if;

    if (! $plan_id) {

      return $templates;

    } // end if;

    $plan = wu_get_plan($plan_id);

    return $plan->templates && $plan->override_templates ? array_keys($plan->templates) : $templates;

  } // end filter_plan_templates;

  /**
   * Filter the role based on the plan_id selection
   *
   * @param string $role
   * @return string
   */
  public function filter_plan_role($role) {

    $transient = $this->get_transient(false);

    if (!isset($transient['plan_id'])) return $role;

    $plan = wu_get_plan($transient['plan_id']);

    return $plan->role ?: $role;

  } // end filter_plan_role;

  /**
   * Get the signup URL
   *
   * @return string
   */
  public function get_signup_url() {

    return apply_filters('wp_signup_location', network_site_url('wp-signup.php'));

  } // end get_signup_url;

  /**
   * Set the mandatory policy after our plugin is activated to allow user register
   * @return string The new mandatory policy
   */
  function set_mandatory_blog() {
    return 'user';
  }


  /**
   * Process plan select
   * @since  1.2.1
   * @return
   */
  public function process_plan_select() {

    $atts = shortcode_atts(array(
      'plan_id'      => 0,
      'plan_freq'    => 0,
      'skip_plan'    => true,
      'coupon'       => '',
    ), $_REQUEST);

    /**
     * Check if we are settings the template via the URL
     * @since 1.7.3
     */
    if (isset($_REQUEST['template_id']) && WU_Settings::get_setting('allow_template')) {

      // Check if the template is valid
      $site = new WU_Site_Template( $_REQUEST['template_id'] );

      if ($site->is_template) {

        $atts['template'] = $_REQUEST['template_id'];
        $atts['skip_template_selection'] = true;

      } // end if;

    } // end check template;

    if (!isset($atts['plan_id']) || !$atts['plan_id']) {

      wp_die(__('Invalid Plan.', 'wp-ultimo'));

    } // end if;

    if (!isset($atts['plan_freq']) || !$atts['plan_freq'] || !WU_Gateway::check_frequency($atts['plan_freq'])) {

      wp_die(__('Invalid Frequency.', 'wp-ultimo'));

    } // end if;

    // Save this for later use
    $uniqid = uniqid('', true);

    $key = self::get_transient_key($uniqid);

    set_site_transient($key, $atts, apply_filters('wu_signup_transiente_lifetime', 40 * MINUTE_IN_SECONDS, $this));

    // Signup URL
    $signup_url = apply_filters('wp_signup_location', network_site_url('wp-signup.php'));

    /**
     * Passed the rest of the parameters
     * @since 1.7.4
     */
    $url_args = array(
      'cs'   => $uniqid,
    );

    /**
     * Set the step
     * @var string
     */
    wp_redirect(esc_url_raw( add_query_arg($url_args, $signup_url) ));

    exit;

  } // end process_plan_select;

  /**
   * Prints the Login Scripts
   *
   * @return void
   */
  public function login_scripts() {

    $suffix = WP_Ultimo()->min;

    wp_enqueue_script('wp-ultimo');

    wp_enqueue_style('wu-pricing-table', WP_Ultimo()->get_asset("wu-pricing-table$suffix.css", 'css'));

    if (!WU_UI_Elements()->has_admin_theme_active()) {

      wp_enqueue_style('wu-login');

    }

    $this->print_logo();

    wp_site_icon(); // @since 1.8.2

  } // end login_scripts;

  /**
   * Get the logo for the login page
   *
   * @since 1.7.0
   * @return string
   */
  public function get_login_page_logo() {

    $login_logo = WU_Settings::get_logo('full', false, 'logo-login');

    if (WU_Settings::get_setting('use-logo-login') && $login_logo) {

      return $login_logo;

    } // end if;

    return WU_Settings::get_logo();

  } // end get_login_page_logo;

  /**
   * Prints the Logo
   *
   * @return void
   */
  public function print_logo() {

    /**
     * Allow plugin developers to prevent WP Ultimo from overriding the WordPress logo.
     *
     * @param boolean
     * @since 1.9.0
     */
    if (apply_filters('wu_signup_display_logo', true) === false) return;

    echo "<style>
      .login h1 a {
        background-image: url('". $this->get_login_page_logo() ."') !important;
        width: 320px !important;
        background-size: 70% !important;
      }
    </style>";

  } // end print_logo;

  /**
   * Tells WordPress to redirect users to our custom sign-up page instead of the default wp-signup.php
   * Requires Pretty Permalinks to be activated and configured
   *
   * @param string $register_url
   * @return string
   */
  public function get_register_url($register_url) {

    $registration_url = trim(WU_Settings::get_setting('registration_url', false), '/');

    if ($registration_url && get_blog_option(get_current_site()->blog_id, 'permalink_structure', false)) {

      return network_site_url($registration_url);

    } // end if;

    return $register_url;

  } // end get_register_url;

  /**
   * Change the register URLs to be used on our replace function
   *
   * @param array $urls
   * @return array
   */
  public function changed_register_url($urls) {

    $registration_url = trim(WU_Settings::get_setting('registration_url', false), '/');

    if ($registration_url && get_blog_option(get_current_site()->blog_id, 'permalink_structure', false)) {

      return array_merge(array($registration_url), $urls);

    } // end if;

    return $urls;

  } // end changed_register_url;

  /**
   * Handles the new pretty permalinks for plans
   *
   * @since 1.9.0
   * @param WP_Query $wp_query
   * @return void
   */
  public function check_auto_select_plan($wp_query) {

    $parsed_request = $this->parse_register_request($wp_query->request);

    if ($parsed_request['path'] == trim(WU_Settings::get_setting('registration_url', false), '/')) {

      $plan = isset($parsed_request['plan_slug']) ? wu_get_plan_by_slug($parsed_request['plan_slug']) : false;

      if ($plan) {

        $atts = array();

        if (isset($_REQUEST['template_id']) && WU_Settings::get_setting('allow_template')) {

          // Check if the template is valid
          $site = new WU_Site_Template( $_REQUEST['template_id'] );

          if ($site->is_template) {

            $atts['template_id'] = $_REQUEST['template_id'];

          } // end if;

        } // end check template;

        $url = $plan->get_legacy_shareable_link($parsed_request['plan_freq']);

        wp_redirect( add_query_arg($atts, $url) );

        exit;

      } // end if;

    } // end foreach;

  } // end check_auto_select_plan;

  /**
   * Parse the request
   *
   * @since 1.9.0
   * @param string $request
   * @return array
   */
  public function parse_register_request($request) {

    $parsed_request = explode('/', $request);

    if (count($parsed_request) != 3) {

      return array(
        'path'      => 'none',
        'plan_freq' => 1,
        'plan_slug' => '',
      );

    } // end if;

    return array(
      'path'      => $parsed_request[0],
      'plan_freq' => $parsed_request[1],
      'plan_slug' => $parsed_request[2],
    );

  } // end parse_register_request;

  /**
   * Exclude page from the caching
   *
   * @since 1.9.3
   * @return void
   */
  public static function exclude_from_caching() {

    if (class_exists('LiteSpeed_Cache_API') && method_exists('LiteSpeed_Cache_API', 'set_nocache')) {

      /**
       * Removes the signup page from the cache
       */
      LiteSpeed_Cache_API::set_nocache();

    } // end if;

  } // end exclude_from_caching;

  /**
   * Checks if the current page is a register page
   *
   * @since 1.5.0 Checks for exact match
   * @return boolean
   */
  public function is_register_page() {

    $found = false;

    $replace_list = apply_filters('wu_replace_signup_urls', array('wp-signup', 'wp-signup.php'));

    $parsed_url = parse_url($_SERVER['REQUEST_URI']);

    if (!isset($parsed_url['path'])) {

      return $found;

    } // end if;

    $exploded = explode('/', trim($parsed_url['path'], '/'));

    array_map(function($item) use ($exploded, &$found) {

      $check_against = array_pop($exploded);

      if ($item == trim($check_against, '/')) {

        if (strpos($check_against, 'wp-signup') == 0 && !is_main_site()) {

          return;

        };

        $found = true;

        self::exclude_from_caching();

        return;

      } // end array_map

    }, $replace_list);

    return $found;

  } // end is_register_page;

  /**
   * Check if the current page is a customizer page
   *
   * @return boolean
   */
  public static function is_customizer() {

    $exclude_list = apply_filters('wu_replace_signup_urls_exclude', array('wu-signup-customizer-preview'));

    foreach($exclude_list as $replace_word) {

      if (isset($_GET[$replace_word])) return true;

    }

    return false;

  } // end is_customizer;

  /**
   * Replace the default WordPress signup Page with our own
   */
  public function replace_signup() {

    /**
     * Check if we need to display the signup
     */
    if (!$this->is_register_page() && !$this->is_customizer()) {
      return;
    }

    /**
     * if it is not multi-site, we redirect
     */
    if (!is_multisite()) {

      wp_redirect(wp_registration_url());

      die();

    } // end if;

    /**
     * If the user is already logged in, we redirect to homepage
     *
     * @since  1.2.0 Allows for more sites to be added;
     * @since  1.1.4 Redirects back to home only after displaying a error message;
     */
    if (is_user_logged_in() && !$this->is_customizer()) {

      /**
       * If this option is available, redirect to the new sites
       */
      if (WU_Settings::get_setting('enable_multiple_sites')) {

        $default_site = get_active_blog_for_user(get_current_user_id());
        $url          = get_admin_url($default_site->blog_id, 'index.php?page=wu-new-site');

        if (isset($_GET['new'])) {

          $url = add_query_arg( array('new' => $_GET['new']), $url);

        } // end if;

        wp_redirect($url);

        exit;

      } else {

        WU_Util::wp_die(__('You are already logged in.', 'wp-ultimo'), __('You are already logged in', 'wp-ultimo'), home_url(), 4000);

      } // end if;

    } // end if;

    // Check if we registration is open
    if (WU_Settings::get_setting('enable_signup')) {

      // Render our new signup
      add_action('wp', array($this, 'setup_signup'), 999);

    } // end if;

    // Or else, we die
    else {

      wp_die(__('Registration is closed at this time.', 'wp-ultimo'), __('Registration is Closed!', 'wp-ultimo'));

    }

  } // end replace_signup;

  public function filter_customizer_setting($setting_value, $setting, $default) {

    if ($this->is_customizer()) {

      if ($setting == 'primary-color') {

        return get_site_option($setting, $default);

      }

    } // end if;

    return $setting_value;

  } // end filter_customizer_setting;

  /**
   * Check if we should or should not add the template selection step
   *
   * @since 1.7.3
   * @return boolean
   */
  public function should_add_template_selection_step() {

    $transient = $this->get_transient(false);

    if (isset($transient['skip_template_selection']) && $transient['skip_template_selection']) return false;

    $site_templates = WU_Settings::get_setting('templates');

    return WU_Settings::get_setting('allow_template') && !empty($site_templates);

  } // end should_add_template_selection_step;

  /**
   * Set and return the steps and fields of each step
   * @return array The array containing steps and fields for registration
   */
  public function get_steps($include_hidden = true, $filtered = true) {

    // Set the Steps
    $steps = array();

    // Plan Selector
    $steps['plan'] = array(
      'name'    => __('Pick a Plan', 'wp-ultimo'),
      'desc'    => __('Which one of our amazing plans you want to get?', 'wp-ultimo'),
      'view'    => 'step-plans',
      'handler' => array($this, 'plans_save'),
      'order'   => 10,
      'fields'  => false,
      'core'    => true,
    );

    /**
     * Check for the Template Step
     */
    $site_templates = WU_Settings::get_setting('templates');

    // We add template selection if this has template
    if ($this->should_add_template_selection_step()) {

      // Select template
      $steps['template'] = array(
        'name'    => __('Template Selection', 'wp-ultimo'),
        'desc'    => __('Select the base template of your new site.', 'wp-ultimo'),
        'view'    => 'step-template',
        'order'   => 20,
        'handler' => false,
        'core'    => true,
      );

    } // end if;

    // Domain registering
    $steps['domain'] = array(
      'name'    => __('Site Details', 'wp-ultimo'),
      'desc'    => __('Ok, now it\'s time to pick your site url and title!', 'wp-ultimo'),
      'handler' => array($this, 'domain_save'),
      'view'    => false,
      'order'   => 30,
      'core'    => true,
      'fields'  => apply_filters('wu_signup_fields_domain', array(

        'blog_title' => array(
          'order'         => 10,
          'name'          => apply_filters('wu_signup_site_title_label', __('Site Title', 'wp-ultimo')),
          'type'          => 'text',
          'default'       => '',
          'placeholder'   => '',
          'tooltip'       => apply_filters('wu_signup_site_title_tooltip', __('Select the title your site is going to have.', 'wp-ultimo')),
          'required'      => true,
          'core'          => true,
        ),

        'blogname' => array(
          'order'         => 20,
          'name'          => apply_filters('wu_signup_site_url_label', __('URL', 'wp-ultimo')),
          'type'          => 'text',
          'default'       => '',
          'placeholder'   => '',
          'tooltip'       => apply_filters('wu_signup_site_url_tooltip', __('Site urls can only contain lowercase letters (a-z) and numbers and must be at least 4 characters. .', 'wp-ultimo')),
          'required'      => true,
          'core'          => true,
        ),

        'url_preview' => array(
          'order'         => 30,
          'name'          => __('Site URL Preview', 'wp-ultimo'),
          'type'          => 'html',
          'content'       => wu_get_template_contents('signup/steps/step-domain-url-preview'),
        ),

        'submit' => array(
          'order'         => 100,
          'type'          => 'submit',
          'name'          => __('Continue to the next step', 'wp-ultimo'),
          'core'          => true,
        ),

      )),
    );

    /**
     * Since there are some conditional fields on the accounts step, we need to declare the variable before
     * so we can append items and filter it later
     */
    $account_fields = array(

        'user_name' => array(
          'order'         => 10,
          'name'          => apply_filters('wu_signup_username_label', __('Username', 'wp-ultimo')),
          'type'          => 'text',
          'default'       => '',
          'placeholder'   => '',
          'tooltip'       => apply_filters('wu_signup_username_tooltip', __('Username must be at least 4 characters.', 'wp-ultimo')),
          'required'      => true,
          'core'          => true,
        ),

        'user_email' => array(
          'order'         => 20,
          'name'          => apply_filters('wu_signup_email_label', __('Email', 'wp-ultimo')),
          'type'          => 'email',
          'default'       => '',
          'placeholder'   => '',
          'tooltip'       => apply_filters('wu_signup_email_tooltip', ''),
          'required'      => true,
          'core'          => true,
        ),

        'user_pass' => array(
          'order'         => 30,
          'name'          => apply_filters('wu_signup_password_label', __('Password', 'wp-ultimo')),
          'type'          => 'password',
          'default'       => '',
          'placeholder'   => '',
          'tooltip'       => apply_filters('wu_signup_password_tooltip', __('Your password should be at least 6 characters long.', 'wp-ultimo')),
          'required'      => true,
          'core'          => true,
          // 'display_force' => true,
        ),

        'user_pass_conf' => array(
          'order'         => 40,
          'name'          => apply_filters('wu_signup_password_conf_label', __('Confirm Password', 'wp-ultimo')),
          'type'          => 'password',
          'default'       => '',
          'placeholder'   => '',
          'tooltip'       => apply_filters('wu_signup_password_conf_tooltip', ''),
          'required'      => true,
          'core'          => true,
        ),

        /**
         * HoneyPot Field
         */
        'site_url' => array(
          'order'         => rand(1, 59), // Use random order for Honeypot
          'name'          => __('Site URL', 'wp-ultimo'),
          'type'          => 'text',
          'default'       => '',
          'placeholder'   => '',
          'tooltip'       => '',
          'core'          => true,
          'wrapper_attributes' => array(
            'style'            => 'display: none;',
          ),
          'attributes'     => array(
            'autocomplete' => 'nope',
          )
        ),

    ); // end first account fields;

    /**
     * Check and Add Coupon Code Fields
     * @since 1.4.0
     */
    if (WU_Settings::get_setting('enable_coupon_codes', 'url_and_field') == 'url_and_field') {

      /**
       * Test default state, if we have a coupon saved
       */
      $coupon = $this->has_coupon_code();

      $account_fields['has_coupon'] = array(
        'order'         => 50,
        'type'          => 'checkbox',
        'name'         => __('Have a coupon code?', 'wp-ultimo'),
        'core'          => true,
        'check_if'      => 'coupon', // Check if the input with this name is selected
        'checked'       => $coupon ? true : false,
      );

      $account_fields['coupon'] = array(
        'order'         => 60,
        'name'         => __('Coupon Code', 'wp-ultimo'),
        'type'          => 'text',
        'default'       => '',
        'placeholder'   => '',
        'tooltip'       => __('The code should be an exact match. This field is case-sensitive.', 'wp-ultimo'),
        'requires'      => array('has_coupon' => true),
        'core'          => true,
      );

    } // end if;

    /**
     * Check and Add the Terms field
     * @since 1.0.4
     */
    if (WU_Settings::get_setting('enable_terms')) {

      $account_fields['agree_terms'] = array(
        'order'         => 70,
        'type'          => 'checkbox',
        'checked'       => false,
        'name'         => sprintf(__('I agree with the <a href="%s" target="_blank">Terms of Service</a>', 'wp-ultimo'), $this->get_terms_url()),
        'core'          => true,
      );

    } // end if;

    /**
     * Submit Field
     */
    $account_fields['submit'] = array(
      'order'         => 100,
      'type'          => 'submit',
      'name'         => __('Create Account', 'wp-ultimo'),
      'core'          => true,
    );

    // Account registering
    $steps['account'] = array(
      'name'    =>  __('Account Details', 'wp-ultimo'),
      'view'    => false,
      'handler' => array($this, 'account_save'),
      'order'   => 40,
      'core'    => true,
      'fields'  => apply_filters('wu_signup_fields_account', $account_fields),
    );

    /**
     * Add additional steps via filters
     */
    $steps = $filtered ? apply_filters('wp_ultimo_registration_steps', $steps) : $steps;

    // Sort elements based on their order
    uasort($steps, array($this, 'sort_steps_and_fields'));

    // Sorts each of the fields block
    foreach ($steps as &$step) {

      if (isset($step['fields']) && is_array($step['fields'])) {

        // Sort elements based on their order
        uasort($step['fields'], array($this, 'sort_steps_and_fields'));

      } // end if;

    } // end foreach;

    /**
     * Adds the hidden step now responsible for validating data entry and the actual account creation
     * @since  1.4.0
     */
    $begin_signup = array(
      'begin-signup' => array(
        'name'    =>  __('Begin Signup Process', 'wp-ultimo'),
        'handler' => array($this, 'begin_signup'),
        'view'    => false,
        'hidden'  => true,
        'order'   => 0,
        'core'    => true,
      ),
    );

    /**
     * Adds the hidden step now responsible for validating data entry and the actual account creation
     * @since  1.4.0
     */
    $create_account = array(
      'create-account' => array(
        'name'    =>  __('Creating Account', 'wp-ultimo'),
        'handler' => array($this, 'create_account'),
        'view'    => false,
        'hidden'  => true,
        'core'    => true,
        'order'   => 1000000000,
      ),
    );

    /**
     * Glue the required steps together with the filterable ones
     */
    $steps = array_merge($begin_signup, $steps, $create_account);

    /**
     * Filter the hidden ones, if we need to...
     * @var array
     */
    if (!$include_hidden) {

      $steps = array_filter($steps, function($step) {

        return !(isset($step['hidden']) && $step['hidden']);

      });

    } // end if;

    // If we need to add that
    if (!$this->has_plan_step()) {

      unset($steps['plan']);

    } // end if;

    // Steps
    return $steps;

  } // end get_steps;

  /**
   * Gets the terms of service URL
   *
   * @since 1.9.0
   * @return string
   */
  public function get_terms_url() {

    $url = WU_Settings::get_setting('terms_content_url', false);

    if (WU_Settings::get_setting('terms_type', 'content') == 'external_url' && $url) {

      return $url;

    } // end if;

    return admin_url('admin-ajax.php?action=wu-terms');

  } // end get_terms_url;

  /**
   * Checks transient data to see if the plan step is necessary
   *
   * @return boolean
   */
  public function has_plan_step() {

    if (isset($_GET['cs'])) {

      $transient = $this->get_transient();

      if ($transient && isset($transient['skip_plan']) && isset($transient['plan_id']) && isset($transient['plan_freq'])) {

       return false;

      } // end if;

    } // end if;

    if (isset($_REQUEST['skip_plan'])) {

      return false;

    } // end if;

    return true;

  } // end if;


  /**
   * Checks transient data to see if the template step is necessary
   *
   * @return boolean
   */
  public function has_template_step() {

    if (isset($_GET['cs'])) {

      $transient = $this->get_transient();

      if ($transient && isset($transient['skip_template_selection']) && isset($transient['template']) ) {

       return false;

      } // end if;

    } // end if;

    if (isset($_REQUEST['skip_template'])) {

      return false;

    } // end if;

    return true;

  } // end if;

  /**
   * Checks if the transient data have a coupon code that should be used
   *
   * @return boolean
   */
  public function has_coupon_code() {

    if (isset($_GET['cs'])) {

      $transient = $this->get_transient();

      if ($transient && isset($transient['coupon']) && $transient['coupon']) {

        return $transient['coupon'];

      } // end if;

    } // end if;

  } // end if;

  /**
   * Returns the first step of the signup process
   *
   * @return string
   */
  public function get_first_step() {

    $keys = array_keys($this->get_steps());

    if (isset($keys[1])) {

      return $keys[1];

    } else {

      return false;

    } // end if;

  } // end get_first_step;

  /**
   * Adds a new Step to the sign-up flow
   *
   * @since 1.4.0
   * @param string $id
   * @param integer $order
   * @param array $step
   * @return void
   */
  public function add_signup_step($id, $order, $step) {

    add_filter('wp_ultimo_registration_steps', function($steps) use ($id, $order, $step) {

      // Save new order
      $step['order'] = $order;

      // mark as not core
      $step['core'] = false;

      $steps[$id] = $step;

      return $steps;

    });

  } // end add_signup_step;

  /**
   * Adds a new field to a step the sign-up flow
   *
   * @since 1.4.0
   * @param string $step
   * @param string $id
   * @param integer $order
   * @param array $step
   * @return void
   */
  public function add_signup_field($step, $id, $order, $field) {

    add_filter("wp_ultimo_registration_steps", function($steps) use ($step, $id, $order, $field) {

      // Checks for honey-trap id
      if ($id === 'site_url') {

        wp_die(__('Please, do not use the "site_url" as one of your custom fields\' ids. We use it as a honeytrap field to prevent spam registration. Consider alternatives such as "url" or "website".', 'wp-ultimo'));

      } // end if;

      // Saves the order
      $field['order'] = $order;

      // mark as not core
      $field['core'] = false;

      $steps[$step]['fields'][$id] = $field;

      return $steps;

    });

  } // end add_signup_step;

  /**
   * Sorts the steps
   *
   * @param array $a
   * @param array $b
   * @return void
   */
  public function sort_steps_and_fields($a, $b) {

    $a['order'] = isset($a['order']) ? (int) $a['order'] : 50;

    $b['order'] = isset($b['order']) ? (int) $b['order'] : 50;

    return $a['order'] - $b['order'];

  } // end sort_steps_and_fields;

  /**
   * Get the current step
   *
   * @return string
   */
  public function get_current_step() {

    $current_step = isset($_GET['step']) ? sanitize_key($_GET['step']) : current(array_keys($this->steps));

    /** Always get the first step for the customizer */
    if ($this->is_customizer()) {

      $current_step = $this->get_first_step();

    } // end if;

    return apply_filters('wu_current_step', $current_step);

  } // end get_current_step;

  /**
   * Show the setup wizard.
   */
  public function setup_signup() {

    global $wp_query;

    /**
     * Set the current screen
     */
    if (!class_exists('WP_Screen')) {

      require_once (ABSPATH . 'wp-admin/includes/class-wp-screen.php');

    } // end if;

    set_current_screen('wu-signup');

    // Fix for page title
    $wp_query->is_404 = false;

    status_header(200);

    // Apply a filter so we can add steps in the future
    $this->steps = $this->get_steps();

    // Set the current step based on the get
    $this->step = $this->get_current_step();

    /**
     * If we are in the middle of a saving request, we need to call the handler
     */
    if (!empty($_POST['save_step']) || (isset($this->steps[$this->step]['hidden']) && $this->steps[$this->step]['hidden']) ) {

      /** Checks if the view has a handler of its own */
      if (isset($this->steps[$this->step]['handler']) && $this->steps[$this->step]['handler']) {

        $handler_function = $this->steps[$this->step]['handler'];

      } else {

        $handler_function = array($this, 'default_save');

      } // end else;

      /** Allows for handler rewrite */
      $handler_function = apply_filters("wu_signup_step_handler_$this->step", $handler_function);

      call_user_func($handler_function);

    } // end if;

    /**
     * Fires before the Site Signup page is loaded.
     */
    do_action('wu_before_signup_header');

    /**
     * Fix for BB Pro 2.2.4.2
     */
    if (!function_exists('remove_meta_box')) {

      function remove_meta_box() {}

    } // end if;

    /**
     * Fix for WP Time Capsule
     */
    if (!function_exists('use_block_editor_for_post_type')) {

      require ABSPATH . 'wp-admin/includes/post.php';

    } // end if;

    /** Displays */
    WP_Ultimo()->render('signup/signup-main', array(
      'signup' => $this,
    ));

    exit;

  } // end setup_signup;

  /**
   * Includes the template for that particular step; If none is set (false), includes the default template
   *
   * @param string $step
   * @return void
   */
  public function get_step_view($step) {

    /**
     * Only attempts to get transient if we are no on the first step
     */
    if (array_search($step, array_keys($this->steps)) !== 0) {

      $transient = $this->get_transient();

    } else {

      $transient = array();

    } // end if;

    /**
     * Set the errors
     */
    if ($this->results === null) {

      $this->results = array('errors' => new WP_Error);

    }

    /**
     * @since  1.1.5 Load the variables of the user is returning to that step
     */
    if (empty($_POST)) {

      $this->results = array_merge($this->results, $transient);

    }

    /**
     * Builds the array containing the available elements inside the template
     */
    $args = array(
      'signup'    => $this,
      'transient' => $transient,
      'fields'    => isset($this->steps[$step]['fields']) ? $this->steps[$step]['fields'] : array(),
      'results'   => $this->results,
    );

    /**
     * Checks if anything is passed to the view element
     */
    if (isset($this->steps[$step]['view']) && $this->steps[$step]['view']) {

      wu_get_template("signup/steps/" . $this->steps[$step]['view'], $args);

    } else {

      /**
       * Let's try to locate a custom template on the user's theme. If it's there, we use it instead
       */
      if ($found = locate_template("wp-ultimo/signup/steps/step-$step.php")) {

        wu_get_template("signup/steps/step-$step", $args);

      } else {

        wu_get_template("signup/steps/step-default", $args);

      } // end if;

    } // end if;

  } // end get_step_view;

  /**
   * Returns any error message related to that particular field slug
   *
   * @param string $field_slug
   * @return void
   */
  public function get_error($field_slug) {

    return $this->results['errors']->get_error_message($field_slug);

  } // end get_error_message;

  /**
   * Get the link for the next step
   * @return string The link for the next step
   */
  public function get_next_step_link($params = array()) {

    // Add CS
    if (isset($_GET['cs'])) {

      $params['cs'] = $_GET['cs'];

    } // end if;

    if (isset($_REQUEST['customized'])) {

      $params['customized'] = $_REQUEST['customized'];

    } // end if;

    if (isset($_REQUEST['skip_plan']) && $_REQUEST['skip_plan'] == 1) {

      unset($this->steps['plan']);
      unset($params['skip_plan']);

    } // end if;

    if (isset($_REQUEST['template_id'])) {

      $plan = false;

      if (isset($_REQUEST['plan_id'])) {

        $plan = wu_get_plan($_REQUEST['plan_id']);

      } // end if;

      $templates = array_keys((array) WU_Settings::get_setting('templates'));

      if ( ($plan && $plan->is_template_available($_REQUEST['template_id'])) || in_array($_REQUEST['template_id'], $templates)) {

        unset($this->steps['template']);
        unset($params['skip_template_selection']);

      } // end if;

    } // end if;

    $keys = array_keys($this->steps);
    $url  = add_query_arg('step', $keys[ array_search($this->step, array_keys($this->steps)) + 1 ]);

    foreach ($params as $param => $value) {

      $url = add_query_arg($param, $value, $url);

    } // end foreach;

    return $url;

  } // end get_next_step_link;

  /**
   * Get the link for the previous step
   * @return string The link for the previous step
   */
  public function get_prev_step_link($params = array()) {

    // Add CS
    if (isset($_GET['cs'])) {

      $params['cs'] = $_GET['cs'];

    }

    if (isset($_REQUEST['customized'])) {

      $params['customized'] = $_REQUEST['customized'];

    }

    $keys = array_keys($this->steps);
    $search_key = array_search($this->step, array_keys($this->steps)) - 1 >= 0 ? array_search($this->step, array_keys($this->steps)) - 1 : false;
    $key = $search_key === false ? '' : $keys[$search_key];

    if (!$key || $key == 'begin-signup') {

      return false;

    }

    $url = add_query_arg('step', $key);

    foreach ($params as $param => $value) {

      $url = add_query_arg($param, $value, $url);

    }

    return $url;

  } // end get_prev_step_link;

  /**
   * Redirects the user to the next step on the signup flow
   *
   * @param array $args
   * @return void
   */
  public function next_step($args = array()) {

    /** Redirect the user to the next step */
    wp_redirect(esc_url_raw($this->get_next_step_link( $args )));

    /** Kill the execution after the redirect */
    exit;

  } // end next_step;

  /**
   * Redirects the user to the previous step on the signup flow
   *
   * @param array $args
   * @return void
   */
  public function prev_step($args = array()) {

    /** Redirect the user to the next step */
    wp_redirect(esc_url_raw($this->get_prev_step_link( $args )));

    /** Kill the execution after the redirect */
    exit;

  } // end next_step;

  /**
   * Filters the input variables and sanitizes its contents
   *
   * @param array $post
   * @return array
   */
  public function filter_post_array($post, $exclude_list = false) {

    $exclude_list = $exclude_list ?: array('_signup_form', '_wp_http_referer');

    /** Filter Array */
    $post = WU_Util::array_filter_key($post, function($element_key) use ($exclude_list) {

      return !in_array($element_key, $exclude_list);

    });

    /** Sanitizes the input */
    $post = array_map(function($element) {

      return sanitize_text_field($element);

    }, $post);

    return $post;

  } // end filter_post_array;

  /**
   * Default save handler for steps that don't have one.
   * It simply sanitizes the contents of the POST array and saves it to the transient.
   *
   * @since 1.4.0
   * @return void
   */
  public function default_save() {

    // Get transient
    $transient = $this->get_transient();

    // Set Errors
    $this->results = array('errors' => new WP_Error);

    // Check referer
    check_admin_referer('signup_form_1', '_signup_form');

    /** Sanitizes Input */
    $transient = array_merge($transient, $this->filter_post_array($_POST));

    // Action hook for users
    do_action("wp_ultimo_registration_step_{$this->step}_save", $transient);

    // Stay on the form if we get any errors
    if ($this->results['errors']->get_error_code()) {

      $this->results = array_merge($this->results, $_POST);

      return;

    } // end if;

    // Re-saves the transient
    $this->update_transient($transient);

    /** Go to the next step **/
    $this->next_step();

  } // end default_save;

  /**
   * Check Geolocation
   *
   * @return void
   */
  public function check_geolocation() {

    $location = WU_Geolocation::geolocate_ip();

    $allowed_countries = WU_Settings::get_setting('allowed_countries');

    if (isset($location['country']) && $location['country'] && $allowed_countries) {

      if (!in_array($location['country'], $allowed_countries)) {

        wp_die(apply_filters('wu_geolocation_error_message', __('Sorry. Our service is not allowed in your country.', 'wp-ultimo')));

      } // end if;

    } // end if;

  } // end check_geolocation;

  /**
   * The first invisible step, handles the creation of the transient saver
   *
   * @since 1.4.0
   * @return void
   */
  public function begin_signup() {

    /**
     * Check Geo-location
     */
    $this->check_geolocation();

    /** Create the unique ID we well use from now on */
    $uniqid = uniqid('', true);

    /** Initializes the content holder with the honeypot unique id */
    $content = array(
      'honeypot_id' => uniqid(''),
    );

    /**
     * Saves the coupon code in the request, only if that option is available
     */
    if (isset($_REQUEST['coupon']) && $_REQUEST['coupon'] && WU_Settings::get_setting('enable_coupon_codes', 'url_and_field') != 'disabled') {

      // Adds to the payload
      $content['coupon'] = $_REQUEST['coupon'];

    } // end if;

    /**
     * Check if user came from a pricing select table
     */
    if (isset($_REQUEST['plan_id']) && isset($_REQUEST['plan_freq']) && WU_Gateway::check_frequency($_REQUEST['plan_freq'])) {

      $content['plan_id']   = $_REQUEST['plan_id'];
      $content['plan_freq'] = $_REQUEST['plan_freq'];
      $content['skip_plan'] = true;

    } // end if;

    /**
     * Check if we only have one plan and the skip_plan enabled
     */

    $plans = WU_Plans::get_plans(true);

    if (WU_Settings::get_setting('skip_plan', false) && count($plans) === 1) {

      $billing_frequency = WU_Settings::get_setting('default_pricing_option', 1);

      $plan = reset($plans);

      // Append that to the content
      $content['plan_id']   = $plan->id;
      $content['plan_freq'] = $billing_frequency;
      $content['skip_plan'] = true;

      $_REQUEST['skip_plan'] = 1;

    } // end if;

    /**
     * Check if we are settings the template via the URL
     * @since 1.7.3
     */
    if (isset($_REQUEST['template_id']) && WU_Settings::get_setting('allow_template')) {

      // Check if the template is valid
      $site = new WU_Site_Template( $_REQUEST['template_id'] );

      if ($site->is_template) {

        $content['template'] = $_REQUEST['template_id'];
        $content['skip_template_selection'] = true;

      } // end if;

    } // end check template;

    /** Saves Transient **/
    $key = self::get_transient_key($uniqid);

    set_site_transient($key, $content, apply_filters('wu_signup_transiente_lifetime', 40 * MINUTE_IN_SECONDS, $this));

    /** Go to the next step **/
    $this->next_step(array('cs' => $uniqid));

  } // end begin_signup;

  /**
   * We pass the following info
   */
  public function plans_save() {

    // Get transient
    $transient = $this->get_transient();

    // Check referer
    check_admin_referer('signup_form_1', '_signup_form');

    // Errors
    $this->results['errors'] = new WP_Error;

    // We need now to check for plan
    if (!isset($_POST['plan_id'])) {
      $this->results['errors']->add('plan_id', __('You don\'t have any plan selected.', 'wp-ultimo'));
    }

    else {
      // We need now to check if the plan exists
      $plan = new WU_Plan($_POST['plan_id']);

      if (!$plan->plan_exists()) {
        $this->results['errors']->add('plan_id', __('The plan you\'ve selected doesn\'t exist.', 'wp-ultimo'));
      }
    }

    $transient = apply_filters('wp_ultimo_registration_step_plans_save_transient', $transient);

    // Action hook for users
    do_action('wp_ultimo_registration_step_plans_save', $transient);

    // Stay on the form if we get any errors
    if ($this->results['errors']->get_error_code()) return;

    /** Update Transient Content **/
    $transient['plan_freq'] = $_POST['plan_freq'];
    $transient['plan_id']   = $_POST['plan_id'];

    /** Update Data **/
    $this->update_transient($transient);

    /** Go to the next step **/
    $this->next_step();

  } // end plans_save;

  /**
   * Personal Info Settings.
   */
  public function domain_save() {

    // Get transient
    $transient = $this->get_transient();

    // Check referer
    check_admin_referer('signup_form_1', '_signup_form');

    /**
     * Make sure we trim() the contents of the form
     * @since 1.9.0
     */
    $_POST = array_map('trim', $_POST);

    // Get validation errors
    $this->results = validate_blog_form();

    /** Sanitizes Input */
    $transient = array_merge($transient, $this->filter_post_array($_POST));

    // Action hook for users
    do_action('wp_ultimo_registration_step_domain_save', $transient);

    // Stay on the form if we get any errors
    if ($this->results['errors']->get_error_code()) {

      $this->results = array_merge($this->results, $_POST);

      return;

    } // end if;

    // Re-saves the transient
    $this->update_transient($transient);

    /** Go to the next step **/
    $this->next_step();

  } // end domain_save;

  /**
   * Returns the setting for trial days, making sure we are not returning negative values
   *
   * @since 1.7.3
   * @param WU_Plan $plan
   * @return int
   */
  public function get_trial_value($plan) {

    $default_trial_value = WU_Settings::get_setting('trial', 0);

    if ($default_trial_value < 0 || !is_numeric($default_trial_value)) $default_trial_value = 0;

    return apply_filters('wu_register_trial_value', $default_trial_value, $plan);

  } // end get_trial_value;

  /**
   * Validates the Account step of the signup
   *
   * @return void
   */
  public function account_save() {

    // Get transient
    $transient = $this->get_transient();

    // Check referer
    check_admin_referer('signup_form_1', '_signup_form');

    /**
     * Check HoneyPot to block Spam registrations
     */
    if (isset($_POST['site_url']) && $_POST['site_url']) {

      wp_die(__('You are not welcomed here, Mr. Spammer!', 'wp-ultimo'));

    } // end if;

    /**
     * Make sure we trim() the contents of the form
     * @since 1.9.0
     */
    $_POST = array_map('trim', $_POST);

    // Get validation errors
    $this->results = validate_user_form();

    // @since 1.0.4
    // Validate terms of service
    if (WU_Settings::get_setting('enable_terms') && !isset($_POST['agree_terms'])) {
      $this->results['errors']->add('agree_terms', __('You need to agree to our terms of service.', 'wp-ultimo'));
    }

    /**
     * Allow admins to filter the min lengh of a new password
     *
     * @since  1.7.3
     * @param  int Min length
     * @return int New min length
     */
    $password_min_length = apply_filters('wu_password_min_length', 6);

    // Verify passwords length
    if (!isset($_POST['user_pass']) || strlen($_POST['user_pass']) < $password_min_length) {
      $this->results['errors']->add('user_pass', sprintf(__('Your password should be at least %d characters long.', 'wp-ultimo'), $password_min_length));
    }

    // Verify passwords, if they match
    if ($_POST['user_pass'] != $_POST['user_pass_conf']) {
      $this->results['errors']->add('user_pass_conf', __('The passwords don\'t match.', 'wp-ultimo'));
    }

    /**
     * Add the coupon code
     */
    if (isset($_POST['coupon']) && $_POST['coupon'] && isset($_POST['has_coupon'])) {

      $this->results['coupon'] = $_POST['coupon'];

      $coupon = wu_get_coupon($_POST['coupon']);

      if (!$coupon) {

        $this->results['errors']->add('coupon', __('The coupon code you entered is not valid or is expired.', 'wp-ultimo'));

      } else {

        /**
         * Test for plan_id and plan_freq
         * @since 1.5.5
         */
        if (!$coupon->is_plan_allowed( $transient['plan_id'] ) || !$coupon->is_freq_allowed( $transient['plan_freq'] )) {

          $this->results['errors']->add('coupon', __('This coupon is not allowed for this plan or billing frequency.', 'wp-ultimo'));

        } else {

          $transient['coupon'] = $_POST['coupon'];

        }


      } // end if;

    } else {

      $coupon = false;

    } // end else;

    /** Sanitizes Input */
    $transient = array_merge($transient, $this->filter_post_array($_POST));

    // Action hook for users
    do_action('wp_ultimo_registration_step_account_save', $transient);

    // Stay on the form if we get any errors
    if ($this->results['errors']->get_error_code()) {

      $this->results = array_merge($this->results, $_POST);

      return;

    } // end if;

    // Re-saves the transient
    $this->update_transient($transient);

    /** Go to the next step **/
    $this->next_step();

  } // end account_save;

  /**
   * Actually creates the account and site for the user
   *
   * @since 1.4.0
   * @return void
   */
  public function create_account() {

    // Get transient
    $transient = $this->get_transient();

    /**
     * Sign the user up and generate his blog
     */
    $user_data = array(
      'user_login'  => $transient['user_name'],
      'user_email'  => $transient['user_email'],
      'user_pass'   => $transient['user_pass'],
    );

    // Clean the transient data to get the user meta-data
    $user_meta = $this->filter_post_array($transient, array(
      'honeypot_id', 'template', 'save_step', 'blog_title', 'blogname', 'signup_form_id', 'user_name', 'user_email', 'user_pass', 'user_pass_conf', 'coupon_code', 'agree_terms', 'site_id', 'user_id',
    ));

    // We need now to check for plan
    if (!isset($transient['plan_id'])) {

      wp_die(__('You don\'t have any plan selected.', 'wp-ultimo'));

    } else {

      // We need now to check if the plan exists
      $plan = wu_get_plan($transient['plan_id']);

      if (!$plan) {

        wp_die(__('The plan you\'ve selected doesn\'t exist.', 'wp-ultimo'));

      } // end if;

    } // end else;

    // Get the new user ID
    $user_id = $this->create_user($user_data, array(
      'plan_id'   => (int) $transient['plan_id'],
      'plan_freq' => (int) $transient['plan_freq'],
    ), $user_meta);

    /**
     * Check for errors in the user creation
     */
    if (is_wp_error($user_id)) {

      wp_die($user_id->get_error_message());

    } // end if;

    /**
     * Now we move on to creating the site
     */

    // Get the template
    $template_id = apply_filters('wu_site_template_id', $plan->site_template, $user_id);

    // Set template, if user passed one
    if (isset($transient['template'])) $template_id = (int) $transient['template'];

    $role = apply_filters('wu_register_default_role', WU_Settings::get_setting('default_role', 'administrator'), $transient);

    // Save the site
    $site_id = $this->create_site($user_id, array(
      'blog_title'    => $transient['blog_title'],
      'blogname'      => $transient['blogname'],
      'domain_option' => isset($transient['domain_option']) ? $transient['domain_option'] : '',
      'role'          => $role,
    ), $template_id, apply_filters('wu_create_site_meta', array(
      'blog_upload_space' => $plan->quotas['upload'],
    ), $transient));

    /**
     * Check for errors in the site creation
     */
    if (is_wp_error($site_id)) {

      wp_die($site_id->get_error_message());

    } // end if;

    $transient['site_id'] = $site_id;
    $transient['user_id'] = $user_id;

    // Re-save the transient
    $this->update_transient($transient);

    /**
     * @since  1.1.0 lets get coupons to work, shall we?
     */
    if (isset($transient['coupon'])) {

      $coupon = wu_get_coupon($transient['coupon']);

      if ($coupon) {

        wu_get_subscription($user_id)->apply_coupon_code($coupon->title);

        $coupon->add_use(1);

      } // end if;

    } // end if coupon;

    // // Set storage space
    update_blog_option($site_id, 'blog_upload_space', $plan->quotas['upload']);

    // Admin
    WU_Mail()->send_template('account_created', get_network_option(null, 'admin_email'), array(
      'date'               => WU_Transactions::get_current_time('mysql'),
      'user_name'          => $transient['user_name'],
      'user_site_id'       => $site_id,
      'user_site_name'     => $transient['blog_title'],
      'user_account_link'  => get_admin_url($site_id, 'admin.php?page=wu-my-account'),
    ));

    // User
    WU_Mail()->send_template('account_created_user', $transient['user_email'], array(
      'date'               => WU_Transactions::get_current_time('mysql'),
      'user_name'          => $transient['user_name'],
      'user_site_name'     => $transient['blog_title'],
      'admin_panel_link'   => get_admin_url($site_id, ''),
      'user_site_home_url' => get_home_url($site_id, ''),
    ));

    // Log Event
    $log = sprintf(__('A new site of title %s and id %s was created, owned by user %s of id %s.', 'wp-ultimo'), $transient['blog_title'], $site_id, $transient['user_name'], $user_id);

    WU_Logger::add('signup', $log);

    // Add action to allow developers to add custom actions
    do_action('wp_ultimo_registration', $site_id, $user_id, $transient, $plan);

    // Redirect to the homepage, after loging in the user
    $creds = array(
      'user_login'    => $transient['user_name'],
      'user_password' => $transient['user_pass'],
      'remember'      => true
    );

    /**
     * We need to switch to the site before clearing the cache.
     */
    switch_to_blog($site_id);

      // Clear the cache after the first instantiation
      wp_cache_delete($site_id, 'wu_site');

    restore_current_blog();

    /**
     * Set the redirect URL
     * @since  1.1.3 Let developers filter the redirect URL
     */
    $url = apply_filters('wp_ultimo_redirect_url_after_signup', get_admin_url($site_id), $site_id, $user_id, $transient);

    if (WU_Settings::get_setting('auto_login_users_after_registration', true)) {

      wp_clear_auth_cookie();

      wp_signon($creds);

    } // end if;

    // Redirect
    wp_redirect($url);

    exit;

  } // end create_account;

  /**
   * Handles the redirect login after a new sign-up
   *
   * @since 1.7.3
   * @return void
   */
  public function redirect_after_login() {

    if (isset($_GET['wu_login_redirect']) && isset($_REQUEST['_wpnonce'])) {

      if (!WU_Settings::get_setting('add_users_to_main_site', false)) {

        $removed = remove_user_from_blog(get_current_user_id(), get_network()->blog_id);

      } // end if;

      wp_redirect(urldecode($_GET['wu_login_redirect']));

      exit;

    } // end if;

  } // end redirect_after_login;

  /**
   * Creates a new WP Ultimo User with the appropriate Subscription attached to it.
   *
   * User Data should contain: user_login, user_email, user_pass;
   * Plan Data should contain: plan_id, plan_freq;
   * User Meta is an associative array containing key => value pairs to be saved as meta fields on that user.
   *
   * @since 1.4.0
   * @param array $user_data
   * @param array $plan_data
   * @param array $user_meta
   * @return void
   */
  public function create_user(array $user_data, array $plan_data, array $user_meta = array()) {

    global $current_site;

    /**
     * Check the Plan sent
     */
    $plan = wu_get_plan($plan_data['plan_id']);

    if (!$plan) {

      return WP_Error('invalid_plan', __('There is no plan with that ID on our databases.', 'wp-ultimo'));

    } // end if;

    /** Inserts the user on our database */
    $user_id = wp_insert_user($user_data);

    /** Remove from main site */
    remove_user_from_blog($user_id, $current_site->blog_id);

    /** Check for errors in the user creation */
    if (is_wp_error($user_id)) {

      return $user_id;

    } // end if;

    /** Saves Metadata sent to be saved */
    foreach($user_meta as $meta_slug => $meta_value) {

      update_user_meta($user_id, $meta_slug, $meta_value);

    } // end foreach;

    $plan = wu_get_plan($plan_data['plan_id']);

    /**
     * @since 1.2.0 Create the subscription here and not on the wu_site initialization
     */
    $now = WU_Transactions::get_current_time('mysql');

    $trial = $this->get_trial_value($plan);

    $should_pay_setup_fee = apply_filters('wu_register_should_pay_setup_fee', $plan && $plan->has_setup_fee(), $plan);

    $subscription = new WU_Subscription((object) array(
      'user_id'          => $user_id,
      'trial'            => $trial,
      'created_at'       => $now, // date('Y-m-d H:i:s'),
      'last_plan_change' => date('Y-m-d H:i:s', 0),
      'plan_id'          => (int) $plan_data['plan_id'],
      'freq'             => (int) $plan_data['plan_freq'],
      'price'            => $plan->get_price($plan_data['plan_freq']),
      'active_until'     => apply_filters('wu_set_active_until', WU_Signup::get_active_until_with_trial($now, $trial), $plan_data['plan_id'], $plan_data['plan_freq']),

      'paid_setup_fee'   => ! $should_pay_setup_fee,
    ));

    /** Saves it */
    $subscription = $subscription->save();

    return $user_id;

  } // end create_user;

  /**
   * Get the active until + trial days, to allow for putting subscription on hold
   *
   * @since 1.5.5
   * @param string $now
   * @param integer $trial_days
   * @return string
   */
  public static function get_active_until_with_trial($now, $trial_days) {

    $active_until = new DateTime($now);

    $active_until->add(new DateInterval("P". $trial_days ."D"));

    return $active_until->format('Y-m-d H:i:s');

  } // end get_active_until_with_trial;

  /**
   * Transfers the posts created on the template sites to the newly created user
   *
   * @since  1.9.2
   * @param  int $user_id
   * @param  int $site_id
   * @return int
   */
  public function transfer_posts_to_new_user($user_id, $site_id) {

    global $wpdb;

    /**
     * Allow developers to change the user that will be used to assign the posts to.
     * Returning falsy values will short-circuit the code below.
     *
     * @since  1.9.2
     * @param  int $user_id The user id that created the site
     * @param  int $site_id ID of the site that was just created
     * @return int New user ID, to assign to a different user OR false to prevent the code from executing
     */
    $user_id = apply_filters('wu_signup_transfer_posts_to_new_user_id', $user_id, $site_id);

    if (!$user_id) return;

    $table_prefix = $wpdb->get_blog_prefix($site_id);

    /**
     * Run the update query and return rows affected
     */
    $query_status = $wpdb->query(
      $wpdb->prepare( "UPDATE {$table_prefix}posts SET `post_author` = %d", $user_id )
    );

    return $query_status;

  } // end transfer_posts_to_new_user;

  /**
   * Creates a Site in the network for a specific user, creating the additional data entries WP Ultimo requires.
   *
   * Site Data should contain: blog_title, blogname, and role;
   * Site Meta is an associative array containing key => value pairs to be saved as meta fields on that site.
   *
   * @since 1.4.0
   * @param integer $user_id
   * @param array $site_data
   * @param boolean $template_id
   * @param array $site_meta
   * @return void
   */
  public function create_site($user_id, $site_data, $template_id = false, $site_meta = array()) {

    // We need our current site for reference
    global $current_site;

    /**
     * Prevent admin email change email from being send
     * @since 1.7.0
     */
    add_filter('send_network_admin_email_change_email', '__return_false', 10);
    add_filter('send_site_admin_email_change_email', '__return_false', 10);

    // Get the user
    $user = get_user_by('id', $user_id);

    if (!$user) {

      return new WP_Error('invalid_user', __('Invalid User ID passed.', 'wp-ultimo'));

    } // end if;

    // Set constant
    // if (!defined('MUCD_PRIMARY_SITE_ID')) define('MUCD_PRIMARY_SITE_ID', $template_id);
    if (!defined('MUCD_PRIMARY_SITE_ID')) define('MUCD_PRIMARY_SITE_ID', 1);

    // Set our flags for later use
    $use_wordpress_default_site = true;

    // If it's a different value, we need to check if that blog exists
    // if it does not, we use the default WordPress blog
    $site_template = get_blog_details($template_id);

    if ($template_id != 0 && is_object($site_template)) {

      $use_wordpress_default_site = false;

    } // end if;

    // If the value is valid and there's a blog, we use that blog as a template
    // duplicating the new blog
    if ($use_wordpress_default_site) {

      // Set the default domain
      $domain = '';

      if (preg_match('|^([a-zA-Z0-9-])+$|', $site_data['blogname']))
        $domain = strtolower($site_data['blogname']);

      $site_domain = isset($site_data['domain_option']) && $site_data['domain_option'] ? $site_data['domain_option'] : $current_site->domain;

      if (is_subdomain_install()) {
        $newdomain = $domain . '.' . preg_replace( '|^www\.|', '', $site_domain);
        $path      = $current_site->path;
      } else {
        $newdomain = $site_domain;
        $path      = $current_site->path . $domain . '/';
      }

      // Use the default
      $site_id = wpmu_create_blog($newdomain, $path, $site_data['blog_title'], $user_id, '', $current_site->id);

      /**
       * Check for errors in the user creation
       */
      if (is_wp_error($site_id)) {

        return new WP_Error('site_creation_error', $site_id->get_error_message());

      } // end if;

      // Update
      WU_Site_Hooks::search_and_replace_for_new_site($site_id);

    } // end if;

    /**
     * Case Duplication
     */
    else {

      $site_domain = isset($site_data['domain_option']) && $site_data['domain_option'] ? $site_data['domain_option'] : $current_site->domain;

      // SHould copy media?
      $subscription = wu_get_subscription($user_id);
      $copy_files = $subscription && $subscription->get_plan() ? $subscription->get_plan()->should_copy_media() : '';

      // Run the duplicator
      $duplicated = WU_Site_Hooks::duplicate_site($template_id, $site_data['blog_title'], $site_data['blogname'], $user->user_email, $site_domain, $copy_files);

      /**
       * We need to check if everything went right
       */
      if (!isset($duplicated['site_id'])) {

        $message = isset($duplicated['error']) ? $duplicated['error'] : __('Something wrong happened in the duplication process.', 'wp-ultimo');

        return new WP_Error('duplication_error', $message);

      } // end if;

			// Remove user from the template
			if (!is_super_admin($user_id)) {

        /**
         * Check if the user owns the template.
         */
        $user_owned_sites = WU_Site_Owner::get_user_sites($user_id);

        if (!array_key_exists($template_id, $user_owned_sites)) {

          remove_user_from_blog($user_id, $template_id);

        } // end if;

      } // end if;

      // if everything went okay, we set the new site_id
      $site_id = @$duplicated['site_id'];

    } // end else;

    // Set primary blog
    if (!is_super_admin($user_id) && !get_user_option('primary_blog', $user_id)) {

      update_user_option($user_id, 'primary_blog', $site_id, true);

    } // end if;

    if (!is_super_admin($user_id)) {

      // Instantiate wu_site object
      $site = wu_get_site($site_id);

      // Set owner
      $site->set_owner($user_id, $site_data['role']);

      /**
       * Check if we need to add the user to the main site or not
       * @since 1.9.0
       */
      if (WU_Settings::get_setting('add_users_to_main_site', false)) {

        $main_site_role = WU_Settings::get_setting('main_site_default_role', 'subscriber');

        add_user_to_blog($current_site->blog_id, $user_id, $main_site_role);

      } // end if;

      // Remove user from main site
      if (!$use_wordpress_default_site) {

        if (!array_key_exists($template_id, $user_owned_sites)) {

          remove_user_from_blog($user_id, (int) $template_id);

        } // end if;

      } // end if;

    } // end if;

    /** Saves Metadata sent to be saved */
    foreach($site_meta as $meta_slug => $meta_value) {

      update_blog_option($site_id, $meta_slug, $meta_value);

    } // end foreach;

    /**
     * Action
     */
    do_action('wu_signup_after_create_site', $user_id, $site_id);

    /**
     * Return the Site ID, if everything goes fine
     */
    return $site_id;

  } // end create_site;

  /**
   * Returns the transient key to be saved onto the database
   *
   * @since 1.9.3
   * @param string $cs
   * @return string
   */
  public static function get_transient_key($cs) {

    return 'wu_signup_' . $cs;

  } // end get_transient_key;

  /**
   * Check the transient, and if it does not exists, throw fatal
   * @return array The transient information
   */
  public static function get_transient($die = true) {

    if (isset($_GET['cs'])) {

      $key = self::get_transient_key($_GET['cs']);

      $transient = get_site_transient($key);

      if (!$transient && $die) wp_die(__('Try again', 'wp-ultimo'));

    } else {

      /** Customizer Exception */
      return self::is_customizer() || !$die ? array() : wp_die(__('Try again', 'wp-ultimo'));

    } // end if;

    // Return Transient
    return $transient ?: array();

  } // end check_transient;

  /**
   * Update the transient data in out database
   * @param  array $transient Array containing the transient data
   */
  public function update_transient($transient) {

    if (isset($_GET['cs'])) {

      $key = self::get_transient_key($_GET['cs']);

      set_site_transient($key, $transient, apply_filters('wu_signup_transiente_lifetime', 40 * MINUTE_IN_SECONDS, $this));

    } else {

      /** Customizer Exception */
      return self::is_customizer() ? array() : wp_die(__('Try again', 'wp-ultimo'));

    } // end if;

  } // end update_transient;

  /**
   * Delete a signup transient
   *
   * @since 1.9.3
   * @return void
   */
  public function remove_transient() {

    if (isset($_GET['cs'])) {

      $key = self::get_transient_key($_GET['cs']);

      return delete_site_transient($key);

    } // end if;

    return false;

  } // end remove_transient;

  /**
   * Get the primary site URL we will use on the URL previewer, during sign-up
   *
   * @since 1.7.2
   * @return string
   */
  public function get_site_url_for_previewer() {

    $domain_options = WU_Settings::get_domain_option();

    $site = get_current_site();

    $domain = $site->domain;

    if (WU_Settings::get_setting('enable_multiple_domains', false) && $domain_options) {

      $domain = array_shift($domain_options);

    } // end if;

    $domain = rtrim($domain . $site->path, '/');

    /**
     * Allow plugin developers to filter the URL used in the previewer
     *
     * @since 1.7.2
     * @param string  Default domain being used right now, useful for manipulations
     * @param array   List of all the domain options entered in the WP Ultimo Settings -> Network Settings -> Domain Options
     * @return string New domain to be used
     */
    return apply_filters('get_site_url_for_previewer', $domain, $domain_options);

  } // end get_site_url_for_previewer;

  /**
   * Helper Functions to make everything look cleaner
   */

  /**
   * Display the necessary fields for the plan template
   *
   * @since 1.5.0 Takes the frequency parameter
   *
   * @param boolean $current_plan
   * @param string  $step
   * @param integer $freq
   * @return void
   */
  public function form_fields($current_plan = false, $step = 'plan', $freq = false) {

    /** Select the default frequency */
    $freq = $freq ?: WU_Settings::get_setting('default_pricing_option');

    ?>

    <?php if ($step == 'plan') { ?>

      <input type="hidden" name="wu_action" value="wu_new_user">
      <input type="hidden" id="wu_plan_freq" name="plan_freq" value="<?php echo $freq; ?>">

    <?php } ?>

    <input type="hidden" name="save_step" value="1">

    <?php wp_nonce_field('signup_form_1', '_signup_form'); ?>

    <!-- if this is a change plan, let us know -->
    <?php if ($current_plan) : ?>

      <input type="hidden" name="changing-plan" value="1">

    <?php endif; ?>

  <?php } // end form_fields;

} // end class WU_Signup;

// Run our Class
WU_Signup::get_instance();

/**
 * Return the instance of the function
 */
function WU_Signup() {

  return WU_Signup::get_instance();

} // end WU_Signup;

/**
 *
 * We need to load our functions in case people access this from wp-signup without the .php extension
 *
 */

if (!function_exists('validate_blog_form')) {

  function validate_blog_form() {
      $user = '';
      if ( is_user_logged_in() )
          $user = wp_get_current_user();

      return wpmu_validate_blog_signup($_POST['blogname'], $_POST['blog_title'], $user);
  }

}

if (!function_exists('validate_user_form')) {

  function validate_user_form() {
      return wpmu_validate_user_signup($_POST['user_name'], $_POST['user_email']);
  }

}

/**
 * Builds HTML attributes from a PHP array
 *
 * @param array $attributes
 * @return void
 */
function wu_create_html_attributes_from_array($attributes = array()) {

  $output = '';

  foreach ($attributes as $name => $value) {
      if (is_bool($value)) {
          if ($value) $output .= $name . ' ';
      } else {
          $output .= sprintf('%s="%s"', $name, $value);
      }
  }

  return $output;

} // end wu_create_html_attributes_from_array;

/**
 * Display one single option
 *
 * @since 1.7.3
 * @param string $option_value
 * @param string $option_label
 * @return void
 */
function wu_print_signup_field_option($option_value, $option_label, $field = array()) { ?>

  <option <?php selected(isset($field['default']) && $field['default'] == $option_value); ?> value="<?php echo $option_value; ?>"><?php echo $option_label; ?></option>

<?php } // end wu_print_signup_field_option;

/**
 * Displays the option tags of an select field
 *
 * @since 1.7.3
 * @param array $options
 * @return void
 */
function wu_print_signup_field_options($options, $field = array()) {

  foreach($options as $option_value => $option_label) {

    if (is_array($option_label)) {

      echo sprintf('<optgroup label="%s">', $option_value);

      foreach($option_label as $option_value => $option_label) {

        wu_print_signup_field_option($option_value, $option_label, $field);

      } // end foreach;

      echo "</optgroup>";

    } else {

      wu_print_signup_field_option($option_value, $option_label, $field);

    } // end if;

  } // end foreach;

} // end wu_print_signup_field_options;

/**
 * Print sing-up fields
 *
 * @param string $field_slug
 * @param array  $field
 * @param array  $results
 * @return void
 */
function wu_print_signup_field($field_slug, $field, $results) {

  $display = true;

  // Requires Logic
  if (isset($field['requires']) && is_array($field['requires'])) {

    $display = false;

    /**
     * Builds required elements list
     */

    $elements = array_keys($field['requires']);
    array_walk($elements, function(&$value, $key) { $value = '#' . $value; });
    $elements = implode(', ', $elements);

    wp_enqueue_script('jquery'); ?>

    <script type="text/javascript">
    (function($) {
      $(document).ready(function() {

        var requires = <?php echo json_encode($field['requires']); ?>,
            target_field = $('#<?php echo $field_slug; ?>-field');

        var display_field = function(target_field, requires, velocity) {

          var conditions_count = Object.keys(requires).length,
              conditions_met   = 0;

          $.each(requires, function(element, value) {

            var element = $("#" + element),
                element_value = element.val();

            if (element.is(":checkbox")) {

              var is_checked = !!element.is(':checked');

              if (is_checked === value) {
                conditions_met++;
              }

              return true;

            } // end if;

            value = Array.isArray(value) ? value : [value];

            if (value.indexOf(element_value) > -1) {

              conditions_met++;

            } // end if;

          });

          if (conditions_met == conditions_count) {

            target_field.slideDown(velocity);

          } else {

            target_field.slideUp(velocity);

          } // end

        } // end display_field;

        display_field(target_field, requires, 0);

        $('<?php echo $elements; ?>').on('change', function() {
          display_field(target_field, requires, 300);
        });

      });
    })(jQuery);

    </script>

    <?php

  } // end if;

  $wrapper_attributes = '';
  $attributes = '';

  /**
   * Builds Attributes display
   */
  if (isset($field['wrapper_attributes']) && $field['wrapper_attributes']) {

    $wrapper_attributes = wu_create_html_attributes_from_array($field['wrapper_attributes']);

  } // end if;

  if (isset($field['attributes']) && $field['attributes']) {

    $attributes = wu_create_html_attributes_from_array($field['attributes']);

  } // end if;

  /**
   * Switch type for display
   */
  switch($field['type']) {

    /**
     * Normal Text Inputs
     */
    case 'text':
    case 'number':
    case 'email':
    case 'url':
    ?>

    <p <?php echo $wrapper_attributes; ?> id="<?php echo $field_slug; ?>-field" <?php echo $wrapper_attributes; ?> style="<?php echo $display ? '' : "display: none"; ?>" >

      <label for="<?php echo $field_slug; ?>"><?php echo $field['name']; ?> <?php echo WU_Util::tooltip($field['tooltip']); ?><br>
      <input <?php echo $attributes; ?> <?php echo isset($field['required']) && $field['required'] ? 'required' : ''; ?> type="<?php echo $field['type']; ?>" name="<?php echo $field_slug; ?>" id="<?php echo $field_slug; ?>" class="input" value="<?php echo isset($results[$field_slug]) ? $results[$field_slug] : ''; ?>" size="20"></label>


      <?php if ($error_message = $results['errors']->get_error_message($field_slug)) {
        echo '<p class="error">' . $error_message . '</p>';
      } ?>

    </p>

    <?php
    break;

    case 'password':
      wp_enqueue_script('utils');
      wp_enqueue_script('user-profile');
    ?>

    <p <?php echo $wrapper_attributes; ?> id="<?php echo $field_slug; ?>-field" <?php echo $wrapper_attributes; ?> style="<?php echo $display ? '' : "display: none"; ?>" >

      <?php if (isset($field['display_force']) && $field['display_force']) :

        $suffix = WP_Ultimo()->min;

        wp_enqueue_script('wu-password-verify', WP_Ultimo()->get_asset("wu-password-verify$suffix.js", 'js'), array('jquery'), true);

        ?>

      <span class="password-input-wrapper" style="display: block;">
        <label for="<?php echo $field_slug; ?>"><?php echo $field['name']; ?> <?php echo WU_Util::tooltip($field['tooltip']); ?><br>
        <input <?php echo $attributes; ?> <?php echo isset($field['required']) && $field['required'] ? 'required' : ''; ?> type="<?php echo $field['type']; ?>" name="<?php echo $field_slug; ?>" id="<?php echo $field_slug; ?>" class="input" value="<?php echo isset($results[$field_slug]) ? $results[$field_slug] : ''; ?>"  data-reveal="1" data-pw="<?php echo esc_attr( wp_generate_password( 16 ) ); ?>" class="input" size="20" autocomplete="off" aria-describedby="pass-strength-result" />
      </span>

      <span style="display: block; margin-top: -16px; opacity: 1; height: 36px;" id="pass-strength-result" class="hide-if-no-js" aria-live="polite"><?php _e( 'Strength indicator' ); ?></span>

      <script>
        (function($) {
          $(function() {
            //wu_check_pass_strength('#<?php echo $field_slug; ?>', '#<?php echo $field_slug; ?>');
            $('#<?php echo $field_slug; ?>').keyup(function() {
              wu_check_pass_strength('#<?php echo $field_slug; ?>', '#<?php echo $field_slug; ?>');
            });
          });
        })(jQuery);
      </script>

    <?php else: ?>

      <label for="<?php echo $field_slug; ?>"><?php echo $field['name']; ?> <?php echo WU_Util::tooltip($field['tooltip']); ?><br>
      <input <?php echo $attributes; ?> <?php echo isset($field['required']) && $field['required'] ? 'required' : ''; ?> type="<?php echo $field['type']; ?>" name="<?php echo $field_slug; ?>" id="<?php echo $field_slug; ?>" class="input" value="<?php echo isset($results[$field_slug]) ? $results[$field_slug] : ''; ?>" size="20"></label>

    <?php endif; ?>

    <?php if ($error_message = $results['errors']->get_error_message($field_slug)) {
        echo '<p class="error">' . $error_message . '</p>';
      } ?>

    </p>

    <?php
    break;

    /**
     * Case HTML
     */
    case 'html': ?>

      <div <?php echo $wrapper_attributes; ?> id="<?php echo $field_slug; ?>-field">
        <?php echo $field['content']; ?>
      </div>

      <?php
      break;

    /**
     * Case Submit Button
     */
    case 'submit':

    ?>

    <p class="submit">

      <input name="signup_form_id" type="hidden" value="1">

      <button id="wp-submit" <?php echo $attributes; ?> type="submit" class="button button-primary button-large button-next" value="1" name="save_step">
        <?php esc_attr_e($field['name'], 'wp-ultimo'); ?>
      </button>

      <?php wp_nonce_field('signup_form_1', '_signup_form'); ?>

    </p>

    <?php
    break;

    /**
     * Case Select
     */
    case 'select':

    ?>

    <p <?php echo $wrapper_attributes; ?> id="<?php echo $field_slug; ?>-field" style="<?php echo $display ? '' : "display: none"; ?>">

      <label for="<?php echo $field_slug; ?>"><?php echo $field['name']; ?> <?php echo WU_Util::tooltip($field['tooltip']); ?><br>

      <select <?php echo $attributes; ?> <?php echo isset($field['required']) && $field['required'] ? 'required' : ''; ?> name="<?php echo $field_slug; ?>" id="<?php echo $field_slug; ?>" class="input" value="<?php echo isset($results[$field_slug]) ? $results[$field_slug] : ''; ?>">

        <?php wu_print_signup_field_options($field['options'], $field); ?>

      </select>

      </label>

      <?php if ($error_message = $results['errors']->get_error_message($field_slug)) {
        echo '<p class="error">' . $error_message . '</p>';
      } ?>

    </p>

    <?php
    break;

    /**
     * Case Checkbox
     */
    case 'checkbox':

      $checked =     isset($field['check_if']) && isset($result[$field['check_if']])
                  || (isset($field['check_if']) && isset($_POST[$field['check_if']]) && $_POST[$field['check_if']])
                  || (isset($field['checked']) && $field['checked'])
                  ? true : false;
    ?>

    <p>

      <label for="<?php echo $field_slug; ?>">
        <input type="checkbox" name="<?php echo $field_slug; ?>" value="1" id="<?php echo $field_slug; ?>" <?php echo checked($checked, true); ?>>
        <?php echo $field['name']; ?>
      </label>

      <br>

      <?php if ($error_message = $results['errors']->get_error_message($field_slug)) {
        echo '<p class="error">' . $error_message . '</p>';
      } ?>

      <br>

    </p>

    <?php
    break;

  } // end switch;

} // end wu_print_singup_field;

/**
 * Alias function to allow creation of users for WP Ultimo.
 *
 * User Data should contain: user_login, user_email, user_pass;
 * Plan Data should contain: plan_id, plan_freq;
 * User Meta is an associative array containing key => value pairs to be saved as meta fields on that user.
 *
 * @param array $user_data
 * @param array $plan_data
 * @param array $user_meta
 * @return integer/boolean
 */
function wu_create_user(array $user_data, array $plan_data, array $user_meta = array()) {

  return WU_Signup()->create_user($user_data, $plan_data, $user_meta);

} // end wu_create_user;

/**
 * Alias function to allow creation of sites for WP Ultimo.
 *
 * Site Data should contain: blog_title, blogname, and role;
 * Site Meta is an associative array containing key => value pairs to be saved as meta fields on that site.
 *
 * @param integer $user_id
 * @param array $site_data
 * @param boolean $template_id
 * @param array $site_meta
 * @return void
 */
function wu_create_site($user_id, array $site_data, $template_id = false, $site_meta = array()) {

  return WU_Signup()->create_site($user_id, $site_data, $template_id, $site_meta);

} // end wu_create_site;

/**
 * Alias function that adds a new Step to the sign-up flow
 *
 * @since 1.4.0
 * @param string $id
 * @param integer $order
 * @param array $step
 * @return void
 */
function wu_add_signup_step($id, $order, array $step) {

  return WU_Signup()->add_signup_step($id, $order, $step);

} // end wu_add_signup_step;

/**
 * Alias function that adds a new field to a step the sign-up flow
 *
 * @since 1.4.0
 * @param string $step
 * @param string $id
 * @param integer $order
 * @param array $step
 * @return void
 */
function wu_add_signup_field($step, $id, $order, $field) {

  return WU_Signup()->add_signup_field($step, $id, $order, $field);

} // end wu_add_signup_field;
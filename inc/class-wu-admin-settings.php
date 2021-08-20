<?php
/**
 * Settings Class
 *
 * Handles the settings page of our plugin
 *
 * @author      Arindo Duque
 * @category    Admin
 * @package     WP_Ultimo/Settings
 * @version     0.0.1
 */

if (!defined('ABSPATH')) {
  exit;
}

class WU_Settings {
  
  /**
   * Holds the settings array
   *
   * @var array
   */
  public static $settings = null;

  /**
   * Holds the settings defaults
   *
   * @var array
   */
  public static $defaults = array();

  /**
   * Holds the sections for later use
   *
   * @since 1.5.2
   * @var array
   */ 
  private static $sections = null;

  /**
   * Holds the available templates to prevent unnecessary hits on the database
   *
   * @since 1.5.2
   * @var array
   */
  private static $available_templates = null;

  private static $prevent_expensive_queries = false;
  
  /**
   * Get all the settings from the framework
   * 
   * @return array Array containing all the settings
   */
  public static function get_settings() {

    // Get all the settings
    if (null === self::$settings)  {
      
      self::$settings = WP_Ultimo()->getOption('settings');

    } // end if;
    
    if (self::$settings === false || empty(self::$settings)) {

      self::$settings = self::save_settings(true);

    } // end if;

    return self::$settings;

  } // end get_settings;

  /**
   * Get available templates
   * 
   * Checks if we have the templates loaded, in order to avoid hitting the database on every get_setting call
   *
   * @since 1.5.2
   * @return array
   */
  public static function get_available_templates() {

    if (self::$prevent_expensive_queries) return array();

    self::$available_templates = self::$available_templates ?: WU_Site_Hooks::get_available_templates(false);

    return self::$available_templates;

  } // end get_available_templates;

  /**
   * Get the default value for one of the settings
   *
   * @since 1.5.1
   * @param string $setting
   * @return void
   */
  static function get_setting_default($setting) {

    $default = isset(self::$defaults[$setting]) ? self::$defaults[$setting] : false;
   
    return $default;

  } // end get_setting_default;
  
  /**
   * Get a specific settings from the plugin
   * 
   * @since  1.4.0 Now we can filter settings we get
   * @since  1.1.5 Let's we pass default responses, in case nothing is found
   * @param  string $setting Settings name to return
   * @return string The value of that setting
   */
  public static function get_setting($setting, $default = false) {

    $settings = self::get_settings();

    $setting_value = isset($settings[$setting]) ? $settings[$setting] : $default;

    return apply_filters('wu_get_setting', $setting_value, $setting, $default);
     
  } // end get_setting

  /**
   * Returns the image being used as a logo
   * 
   * @since  1.7.0 Added setting option
   * @since  1.1.5 Return the default in case 
   * @param  string $size The size to retrieve the logo
   * @return string       The Logo URL
   */
  public static function get_logo($size = 'full', $logo = false, $setting_name = 'logo', $fallback = true) {
    
    $logo = $logo ? $logo : self::get_setting($setting_name);
    
    if (is_numeric($logo)) {

      switch_to_blog( get_current_site()->blog_id );

        $attachment_url = wp_get_attachment_image_url($logo, $size);

      restore_current_blog();

      return apply_filters('wu_get_logo', $attachment_url);

    } // end if;

    if (!$logo && $fallback) {

      $logo = WP_Ultimo()->get_asset('logo.png');

    } // end if;

     return apply_filters('wu_get_logo', $logo);

  } // end get_logo;

  /**
   * Saves a specific setting into the database
   *
   * @param string $setting
   * @param mixed $value
   * @return void
   */
  public static function save_setting($setting, $value) {

    $settings = self::get_settings();

    $settings[$setting] = $value;

    WP_Ultimo()->saveOption('settings', $settings);

    self::$settings = $settings;

  } // end save_setting;

  /**
   * Handles the saving of the settings after the save button is pressed
   *
   * @param boolean $first
   * @param boolean $reset
   * @return void
   */
  public static function save_settings($first = false, $reset = false) {

    // The post passed by the form
    $post = $_POST;
    
    // Get all the settings
    $settings = $first ? array() : self::get_settings();

    /**
     * Before saving hook
     */
    do_action('wu_before_save_settings', $post);

    // Saves each setting
    $sections = self::get_sections();
    
    // Loop to save
    foreach ($sections as $section_slug => $section) {
      
      // Loop fields
      foreach ($section['fields'] as $field_slug => $field) {

        // Skip headings
        if ($field['type'] == 'heading') continue;
        
        // Skip checkboxes if this is not the time
        if (!$first && !$reset && isset($_GET['wu-tab']) && $_GET['wu-tab'] == $section_slug) {
          
          // Fix Checkbox
          if ($field['type'] == 'checkbox') $post[$field_slug] = isset($post[$field_slug]);

          // Fix multi_checkbox
          if ($field['type'] == 'multi_checkbox') 
          $post[$field_slug] = empty($post[$field_slug]) ? array() : $post[$field_slug];

          /** @since 1.3.0  Validating WP_Editor fields */
          if ($field['type'] == 'wp_editor') 
            $post[$field_slug] = wp_kses_post( stripslashes($post[$field_slug]) );

          /** @since 1.7.0  Validating Text fields */
          if ($field['type'] == 'textarea') 
            $post[$field_slug] = wp_kses_post( stripslashes($post[$field_slug]) );

          if ($field['type'] == 'text' && isset($post[$field_slug])) 
            $post[$field_slug] = sanitize_text_field($post[$field_slug]);

          /** @since 1.3.0  Validating WP_Editor fields */
          if ($field['type'] == 'select2') 
            $post[$field_slug] = isset($post[$field_slug]) ? $post[$field_slug] : array();

          /** @since 1.7.3 validate min values */
          if ($field['type'] == 'number' && isset($field['html_attr']['min']) && $post[$field_slug] < $field['html_attr']['min']) {
            $post[$field_slug] = $field['html_attr']['min'];
          }

          /** @since 1.7.3 validate max values */
          if ($field['type'] == 'number' && isset($field['html_attr']['max']) && $post[$field_slug] > $field['html_attr']['max']) {
            $post[$field_slug] = $field['html_attr']['max'];
          }

        } // end if;

        // TODO: Validate
        if (isset($post[$field_slug])) {
          $settings[$field_slug] = $post[$field_slug]; 
        }

        else if ($reset && !isset($settings[$field_slug]) && isset($field['default'])) {
          $settings[$field_slug] = $field['default']; 
        }

        else if ( ($first) && isset($field['default'])) {
          $settings[$field_slug] = $field['default'];
        }

        // Do action on field
        do_action('wu_save_setting', $field_slug, $field, $post);
        
      } // end foreach;
      
    } // end foreach;
    
    // Re-save the settings
    WP_Ultimo()->saveOption('settings', $settings);
    
    /**
     * After the form
     */
    do_action('wu_after_save_settings', $post);
    
    return $settings;
    
  } // end save_settings;

  /**
   * Uses hook to overwrite some WordPress network options
   * 
   * @param  string $field_slug ID of the field being saved
   * @param  array  $field      Field settings
   * @param  array  $post       POST array
   */
  public static function wordpress_overwrite($post) {

    if (!isset($_REQUEST['wu-tab']) || $_REQUEST['wu-tab'] != 'network') {

      return;

    } // end if;

    /**
     * Overwrite WordPress Settings
     */
    $menu_items = get_network_option(null, 'menu_items');
    $menu_items['plugins'] = isset($post['menu_items_plugin']);
    update_network_option(null, 'menu_items', $menu_items);
  
    $add_new = isset($post['add_new_users']);
    update_network_option(null, 'add_new_users', $add_new);
  
    $registration = isset($post['enable_signup']) ? 'all' : 'none';
    update_network_option(null, 'registration', $registration);  

  } // end wordpress_overwrite;

  /**
   * Returns a list of valid selectable roles
   * 
   * @since  1.2.0
   * @return array
   */
  public static function get_roles() {

    if (!function_exists('get_editable_roles')) {

      require_once(ABSPATH.'wp-admin/includes/user.php');

    }

    $roles = array();

    $editable_roles = get_editable_roles();

    foreach ($editable_roles as $role => $details) {

      $roles[esc_attr($role)] = translate_user_role($details['name']);

    }

    return $roles;

  } // end get_roles;

  /**
   * Checks if the user has entered a license code from the wp-config file
   *
   * @since 1.7.3
   * @return boolean|string
   */
  public static function get_license_key_from_wp_config() {

    $license_key = defined('WP_ULTIMO_LICENSE_KEY') ? WP_ULTIMO_LICENSE_KEY : false;

    if ($license_key && get_site_transient('wu_validated_license_code_from_wp_config') != 'yes') {

      $_POST['license_key'] = $license_key;

      $success = WP_Ultimo()->checkBuyer();

      set_site_transient( 'wu_validated_license_code_from_wp_config', 'yes', $success ? 30 * DAY_IN_SECONDS : 5 * MINUTE_IN_SECONDS );

    } // end if;

    return $license_key;

  } // end get_license_key_from_wp_config;

  /**
   * Return the support terms content
   * 
   * @since 1.5.4
   * @return string
   */
  public static function get_support_terms() {

    /**
     * @since 1.3.0 Support Terms
     */
    ob_start(); 

      WP_Ultimo()->render('meta/support-terms');

    $support_terms = ob_get_clean();

    return $support_terms;

  } // end get_support_terms;

  /**
   * Return the countries list
   *
   * @since 1.5.4
   * @return void
   */
  public static function get_countries() {
    
    /**
     * Get countries
     * @since 1.5.0
     */
    $countries = include WP_Ultimo()->path('inc/wu-countries.php');

    return $countries;

  } // end get_countries;

  /**
   * Returns the domain options
   *
   * @since 1.7.0
   * @return array
   */
  public static function get_domain_option() {

    $domain_options = self::get_setting('domain_options', '');

    $filtered_array = array_filter(explode(PHP_EOL, $domain_options));

    $filtered_array = array_map(function($item) {
      
      return trim($item);

    }, $filtered_array);

    return array_combine($filtered_array, $filtered_array);

  } // end get_domain_option;
  
  /**
   * Returns all the sections of settings
   * 
   * @return array Sections of settings of the plugin
   */
  public static function get_sections($filters = true) {

    global $current_site;
    
    $currency_code_options = get_wu_currencies();

    foreach ($currency_code_options as $code => $name) {
      $currency_code_options[ $code ] = $name . ' (' . get_wu_currency_symbol( $code ) . ')';
    }
    
    // get the defaults from WordPress to overwrite
    $menu_items_plugin = get_network_option(null, 'menu_items');
    $menu_items_plugin = isset($menu_items_plugin['plugins']) ? $menu_items_plugin['plugins'] : 0;
    $add_new_users = get_network_option(null, 'add_new_users');
    
    // Get gateways and loop them
    $gateways = wu_get_gateways();
    $first_gateway = '';
    foreach ($gateways as $id => $gateway) {
      $first_gateway = $first_gateway != '' ? $first_gateway : $id;
      $gateways[$id] = $gateway['title'];
    }

    // Get license status
    $license_status = get_network_option(null, WP_Ultimo()->slugfy('verified'));

    if (is_object($license_status) && $license_status->success) {

      if ($license_status->purchase->refunded == false)
        $license_status_string = '<span style="color: green">'. __('Status: Activated', 'wp-ultimo') .'</span>';
      else 
        $license_status_string = '<span style="color: red">'. __('Status: License no longer valid - Canceled or Expired', 'wp-ultimo') .'</span>';

    } else {
      $license_status_string = '<span style="color: red">'. __('Status: Not Activated - Invalid License Key', 'wp-ultimo') .'</span>';
    }

    /**
     * @since  1.1.5 Default billing option
     */
    $default_billing_options = array(
      1  => __('Monthly', 'wp-ultimo'), 
      3  => __('Quarterly', 'wp-ultimo'), 
      12 => __('Yearly', 'wp-ultimo'), 
      36 => __('三年', 'wp-ultimo'), 
    );

    /**
     * @since  1.3.3 Limits and Quotas fine tunning
     */
    $post_types = get_post_types(array('public' => true), 'objects');
    $post_types = apply_filters('wu_get_post_types', $post_types);

    $limits_and_quotas_options = array();

    foreach($post_types as $post_type_slug => $post_type) {

      $limits_and_quotas_options[$post_type_slug] = $post_type->label;

    } // end foreach;

    //if (WU_Settings::get_setting('enable_multiple_sites')) {

    $limits_and_quotas_options['sites'] = __('Sites', 'wp-ultimo');
    $limits_and_quotas_options['visits'] = __('Visits', 'wp-ultimo'); // @since 1.6.0

    //} // end if;

    // Set Default
    $default_limits_and_quotas_options = array_fill_keys(array_keys($limits_and_quotas_options), true);

    $gateway_fields = array(

      // @since 1.2.0
      'gateways_general' => array(
        'title'         => __('General Gateways Settings', 'wp-ultimo'),
        'desc'          => __('This section holds settings that are applied to all the gateways selected below.', 'wp-ultimo'),
        'type'          => 'heading',
      ),

      // @since 1.7.0
      'payment_integration_title' => array(
        'title'         => __('Payment Integration Title', 'wp-ultimo'),
        'placeholder'   => __('Payment Integration Needed', 'wp-ultimo'),
        'type'          => 'text',
        'desc'          => '',
        'tooltip'       => '',
        'default'       => '',
      ),

      // @since 1.7.0
      'payment_integration_desc' => array(
        'title'         => __('Payment Integration Description', 'wp-ultimo'),
        'placeholder'   => __('We now need to complete your payment settings, setting up a payment subscription. Use the buttons below to add an integration.', 'wp-ultimo'),
        'type'          => 'textarea',
        'desc'          => '',
        'tooltip'       => '',
        'default'       => '',
      ),

      // @since 1.2.0
      'attach_invoice_pdf' => array(
        'title'         => __('Send Invoice on Payment Confirmation', 'wp-ultimo'),
        'desc'          => __('Enabling this option will attach a PDF invoice (marked paid) with the payment confirmation email. This option does not apply to the Manual Gateway, which sends invoices regardless of this option.', 'wp-ultimo'),
        'tooltip'       => __('The invoice files will be saved on the wp-content/uploads/invc folder.', 'wp-ultimo'),
        'type'          => 'checkbox',
        'default'       => 1,
      ),

      // @since 1.2.0
      'merchant_address' => array(
        'title'         => __('Your Company\'s Address', 'wp-ultimo'),
        'desc'          => __('Enter your company\'s address, to be added to the Invoice.', 'wp-ultimo'),
        'tooltip'       => '',
        'type'          => 'textarea',
        'default'       => '',
        'placeholder'   => get_site_option('site_name'). " \nAddress Line 1\nAddress Line 2\nCity - State\nZip Code\nCountry",
        'require'       => array('attach_invoice_pdf' => 1),
      ),
    
      'gateways_head'   => array(
        'title'         => __('Active Gateway', 'wp-ultimo'),
        'desc'          => __('WP Ultimo supports multiple simultaneous gateways. Select which ones of the registered gateways you want to use.', 'wp-ultimo'),
        'type'          => 'heading',
      ),
      
      'active_gateway'  => array(
        'title'         => __('Active Gateways', 'wp-ultimo'),
        'desc'          => '',
        'type'          => 'multi_checkbox',
        'options'       => $gateways,
        'tooltip'       => ''
      ),
      
    );
    
    // Create our sections
    $sections = array(
      
      // General Settings
      'general' => array(
        'title'  => __('General', 'wp-ultimo'),
        'desc'   => __('General', 'wp-ultimo'),
        'fields' => apply_filters('wu_settings_section_general', array(
          
          'general'        => array(
            'title'         => __('General Options', 'wp-ultimo'),
            'desc'          => __('Here we define some of the fundamental settings of the plugin.', 'wp-ultimo'),
            'type'          => 'heading',
          ),
          
          'trial' => array(
            'title'         => __('Trial Period', 'wp-ultimo'),
            'desc'          => __('Number of days for the trial period. Leave 0 to disable trial.', 'wp-ultimo'),
            'type'          => 'number',
            'default'       => '0',
            'tooltip'       => '',
            'style'         => 'width: 50px;',
            'html_attr'     => array(
              'min'           => 0,
            )
          ),
          
          'currency'        => array(
            'title'         => __('Currency Options', 'wp-ultimo'),
            'desc'          => __('The following options affect how prices are displayed on the frontend, the backend and in reports.', 'wp-ultimo'),
            'type'          => 'heading',
          ),
          
          'currency_symbol' => array(
            'title'         => __('Currency Symbol', 'wp-ultimo'),
            'desc'          => __('Select the currency symbol to be used in WP Ultimo', 'wp-ultimo'),
            'type'          => 'select',
            'default'       => 'USD',
            'options'       => $currency_code_options,
            'tooltip'       => '',
          ),
          
          'currency_position' => array(
            'title'         => __('Currency Position', 'wp-ultimo'),
            'desc'          => '',
            'type'          => 'select',
            'default'       => '%s %v',
            'tooltip'       => '',
            'options'       => array(
              '%s%v'        => sprintf(__('Left (%s99.99)', 'wp-ultimo'), '$'),
              '%v%s'        => sprintf(__('Right (99.99%s)', 'wp-ultimo'), '$'),
              '%s %v'       => sprintf(__('Left with space (%s 99.99)', 'wp-ultimo'), '$'),
              '%v %s'       => sprintf(__('Right with space (99.99 %s)', 'wp-ultimo'), '$'),
            )
          ),
          
          'decimal_separator' => array(
            'title'         => __('Decimal Separator', 'wp-ultimo'),
            'type'          => 'text',
            'default'       => '.',
            'tooltip'       => '',
            'style'         => 'width: 50px;',
          ),
          
          'thousand_separator' => array(
            'title'         => __('Thousand Separator', 'wp-ultimo'),
            'type'          => 'text',
            'default'       => ',',
            'tooltip'       => '',
            'style'         => 'width: 50px;',
          ),
          
          'precision' => array(
            'title'         => __('Number of Decimals', 'wp-ultimo'),
            'type'          => 'number',
            'default'       => '2',
            'tooltip'       => '',
            'style'         => 'width: 50px;',
            'html_attr'     => array(
              'min'           => 0,
            ),
          ),

          /**
           * @since  1.3.3 Options on the Quotes Display
           */
          
          'dashboard_elements' => array(
            'title'         => __('Subscriber Dashboard Options', 'wp-ultimo'),
            'desc'          => __('Control the elements added to the Subscriber\'s Dashboard.', 'wp-ultimo'),
            'type'          => 'heading',
          ),

          'limits_and_quotas' => array(
            'title'         => __('Limits and Quotas', 'wp-ultimo'),
            'desc'          => __('Select which elements you would like to display on the Limits and Quotas Widget.', 'wp-ultimo'),
            'type'          => 'multi_checkbox',
            'tooltip'       => '',
            'options'       => $limits_and_quotas_options,
            'default'       => $default_limits_and_quotas_options,
          ),

          /**
           * @since  1.9.0 Error Reporting
           */
          
          'error_reporting' => array(
            'title'         => __('Error Reporting', 'wp-ultimo'),
            'desc'          => __('Help us make WP Ultimo better by automatically reporting fatal errors and warnings so we can fix them as soon as possible.', 'wp-ultimo'),
            'type'          => 'heading',
          ),

          'enable_error_reporting' => array(
            'title'         => __('Send Error Data to WP Ultimo Developers', 'wp-ultimo'),
            'desc'          => __('With this option enabled, every time your installation runs into an error related to WP Ultimo, that error data will be sent to us. That way we can review, debug, and fix issues without you having to manually report anything. No sensitive data gets collected, only environmental stuff (e.g. if this is this is a subdomain network, etc).', 'wp-ultimo'),
            'tooltip'       => '',
            'type'          => 'checkbox',
            'default'       => 0,
          ),

          /**
           * @since  1.3.2 Options for uninstall
           */
          
          'uninstall'       => array(
            'title'         => __('Uninstall Options', 'wp-ultimo'),
            'desc'          => __('Change the plugin behavior on uninstall.', 'wp-ultimo'),
            'type'          => 'heading',
          ),

          'uninstall_wipe_tables' => array(
            'title'         => __('Remove Data on Uninstall', 'wp-ultimo'),
            'desc'          => __('Remove all saved data for WP Ultimo when the plugin is uninstalled.', 'wp-ultimo'),
            'tooltip'       => '',
            'type'          => 'checkbox',
            'default'       => 0,
          ),
          
        )),
      ),
      
      // Network Settings
      'network' => array(
        'title'  => __('Network Settings', 'wp-ultimo'),
        'desc'   => __('Network Settings', 'wp-ultimo'),
        'fields' => apply_filters('wu_settings_section_network', array(
          
          'network'         => array(
            'title'         => __('Network Options', 'wp-ultimo'),
            'desc'          => __('Basic network settings.', 'wp-ultimo'),
            'type'          => 'heading',
          ),

          // @since 1.0.4
          'block_frontend'  => array(
            'title'         => __('Block Frontend Access', 'wp-ultimo'),
            'desc'          => __('Block the frontend access of network sites after a subscription is no longer active.', 'wp-ultimo'),
            'tooltip'       => __('By default, if a user does not pay and the account goes inactive, only the admin panel will be blocked, but the user\'s site will still be accessible on the frontend. If enabled, this option will also block frontend access in those cases.', 'wp-ultimo'),
            'type'          => 'checkbox',
            'default'       => 0,
          ),

          // @since 1.7.0
          'block_frontend_grace_period'  => array(
            'title'         => __('Frontend Block Grace Period', 'wp-ultimo'),
            'desc'          => __('Select the number of days WP Ultimo should wait after the subscription goes inactive before blocking the frontend access. Leave 0 to block immediately after the subscription becomes inactive.', 'wp-ultimo'),
            'tooltip'       => '',
            'type'          => 'number',
            'default'       => 0,
            'require'       => array('block_frontend' => 1),
          ),

          // @since 1.2.0
          'enable_multiple_sites'  => array(
            'title'         => __('Enable Multiple Sites per User', 'wp-ultimo'),
            'desc'          => __('Enabling this option will allow your users to create more than one site. You can limit how many sites your users can create in a per plan basis.', 'wp-ultimo'),
            'tooltip'       => '',
            'type'          => 'checkbox',
            'default'       => 1,
          ),

          // @since 1.7.3
          'enable_visits_limiting'  => array(
            'title'         => __('Enable Visits Limitation & Counting', 'wp-ultimo'),
            'desc'          => __('Enabling this option will add visits limitation settings to the plans and add the functionality necessary to count site visits on the front-end.', 'wp-ultimo'),
            'tooltip'       => '',
            'type'          => 'checkbox',
            'default'       => 1,
          ),
          
          'pricing'         => array(
            'title'         => __('Pricing Options', 'wp-ultimo'),
            'desc'          => __('Here you can choose to enable monthly, quarterly, and yearly billing options.', 'wp-ultimo'),
            'type'          => 'heading',
          ),

          /**
           * @since  1.1.5 Enable 1 months pricing to be disabled
           */
          'enable_price_1'  => array(
            'title'         => __('Enable Monthly Pricing', 'wp-ultimo'),
            'desc'          => __('Mark this checkbox if you want to enable monthly billing.', 'wp-ultimo'),
            'tooltip'       => '',
            'type'          => 'checkbox',
            'default'       => true,
          ),
          
          'enable_price_3'  => array(
            'title'         => __('Enable Quarterly Pricing', 'wp-ultimo'),
            'desc'          => __('Mark this checkbox if you want to enable trimestral billing.', 'wp-ultimo'),
            'tooltip'       => '',
            'type'          => 'checkbox',
            'default'       => true,
          ),
          
          'enable_price_12' => array(
            'title'         => __('Enable Yearly Pricing', 'wp-ultimo'),
            'desc'          => __('Mark this checkbox if you want to enable yearly billing.', 'wp-ultimo'),
            'tooltip'       => '',
            'type'          => 'checkbox',
            'default'       => true,
          ),

        'enable_price_36' => array(
            'title'         => __('Enable 3 Years Pricing', 'wp-ultimo'),
            'desc'          => __('Mark this checkbox if you want to enable yearly billing.', 'wp-ultimo'),
            'tooltip'       => '',
            'type'          => 'checkbox',
            'default'       => true,
        ),

          'default_pricing_option' => array(
            'title'         => __('Default Billing Option', 'wp-ultimo'),
            'desc'          => __('Select which billing frequency should be highlighted by default.', 'wp-ultimo'),
            'tooltip'       => '',
            'type'          => 'select',
            'default'       => 1,
            'options'       => $default_billing_options,
          ),

          /**
           * Setup Fee - Global. This can be override on each plan
           * @since 1.7.0
           */
          // 'setup_fee'       => array(
          //   'title'         => __('Setup Fee', 'wp-ultimo'),
          //   'desc'          => __('If you want to charge a setup fee on sign-up, enter the amount here. You can override this on each of your plans. Leave 0 for no setup fee.', 'wp-ultimo'),
          //   'type'          => 'number',
          //   'tooltip'       => '',
          //   'default'       => 0,
          // ),

          'sign-up'         => array(
            'title'         => __('Sign up & Login Options', 'wp-ultimo'),
            'desc'          => __('Sign up & Login Options', 'wp-ultimo'),
            'type'          => 'heading',
          ),

          'enable_signup'   => array(
            'title'         => __('Enable Registration', 'wp-ultimo'),
            'desc'          => __('If you un-check this option, registration will be locked to new users.', 'wp-ultimo'),
            'tooltip'       => '',
            'type'          => 'checkbox',
            'default'       => 1,
          ),

          // @since 1.4.0
          'registration_url' => array(
            'title'         => __('Registration URL', 'wp-ultimo'),
            'desc'          => __('Select a custom URL to replace the default wp-signup.php. Leave blank to continue to use wp-signup.php. <br>Requires pretty permalinks enabled on the main site. Do not include leading or trailing slashes.', 'wp-ultimo'),
            'tooltip'       => __('Pretty permalinks must be enabled on your main network\'s site for this to work.', 'wp-ultimo'),
            'type'          => 'text',
            'default'       => '',
            'placeholder'   => 'e.g. register',
            'require'       => array('enable_signup' => 1),
            'disabled'      => !get_blog_option(get_current_site()->blog_id, 'permalink_structure', false),
          ),

          // @since 1.7.0
          'login_url' => array(
            'title'         => __('Login URL', 'wp-ultimo'),
            'desc'          => __('Select a custom URL to replace the default wp-login.php. Leave blank to continue to use wp-login.php. <br>Requires pretty permalinks enabled on the main site. Do not include leading or trailing slashes.', 'wp-ultimo'),
            'tooltip'       => __('Pretty permalinks must be enabled on your main network\'s site for this to work.', 'wp-ultimo'),
            'type'          => 'text',
            'default'       => '',
            'placeholder'   => 'e.g. login',
            'disabled'      => !get_blog_option(get_current_site()->blog_id, 'permalink_structure', false),
          ),
          
          // @since 1.7.0
          'obfuscate_original_login_url' => array(
            'title'         => __('Obfuscate the Original Login URL (wp-login.php)', 'wp-ultimo'),
            'desc'          => __('If this option is enabled, we will display a 404 error when a user tries to access the original wp-login.php link. This is useful to prevent brute-force attacks.', 'wp-ultimo'),
            'tooltip'       => '',
            'type'          => 'checkbox',
            'default'       => 0,
            'disabled'      => !get_blog_option(get_current_site()->blog_id, 'permalink_structure', false),
            // 'require'       => array('login_url' => 1),
          ),

          // @since 1.7.4
          'auto_login_users_after_registration' => array(
            'title'         => __('Auto-login users after Registration', 'wp-ultimo'),
            'desc'          => __('Checking this option will automatically login users after the registration is finished. Disabling it will force your users to login with their recently created credentials.', 'wp-ultimo'),
            'tooltip'       => '',
            'type'          => 'checkbox',
            'default'       => 1,
          ),

          // @since 1.2.0
          'default_role'    => array(
            'title'         => __('Default Role', 'wp-ultimo'),
            'desc'          => __('Set the role to be applied to the user during the signup process.', 'wp-ultimo'),
            'tooltip'       => '',
            'type'          => 'select',
            'default'       => 'administrator',
            'options'       => self::get_roles(),
          ),

          /**
           * @since 1.9.0 Allow you to select if you want to add the user to the main site
           */
          'add_users_to_main_site' => array(
            'title'         => __('Add Users to the Main Site as well?', 'wp-ultimo'),
            'desc'          => __('Enabling this option will also add the user to the main site of your network.', 'wp-ultimo'),
            'tooltip'       => '',
            'type'          => 'checkbox',
            'default'       => 0,
          ),

          'main_site_default_role'    => array(
            'title'         => __('Add to Main Site with Role...', 'wp-ultimo'),
            'desc'          => __('Select the role WP Ultimo should use when adding the user to the main site of your network. Be careful.', 'wp-ultimo'),
            'tooltip'       => '',
            'type'          => 'select',
            'default'       => 'subscriber',
            'options'       => self::get_roles(),
            'require'       => array('add_users_to_main_site' => 1),
          ),

          // @since 1.4.0
          'skip_plan'    => array(
            'title'         => __('Skip Plan Selection Step', 'wp-ultimo'),
            'desc'          => __('Enabling this option will skip the plan selection screen if - AND ONLY IF - there is only one plan available on the platform. The billing frequency selected on the option "Default Billing Option" above will be used.', 'wp-ultimo'),
            'tooltip'       => __('', 'wp-ultimo'),
            'type'          => 'checkbox',
            'default'       => 0,
          ),

          // @since 1.5.0
          'allowed_countries' => array(
            'title'         => __('Limit Registration by Country', 'wp-ultimo'),
            'desc'          => __('Select the countries allowed to register on your network.', 'wp-ultimo'),
            'tooltip'       => __('', 'wp-ultimo'),
            'placeholder'   => __('Leave blank to allow all countries.', 'wp-ultimo'),
            'type'          => 'select2',
            'default'       => '',
            'options'       => self::get_countries(),
          ),

          // @since 1.7.0
          'enable_multiple_domains' => array(
            'title'         => __('Enable Domain Selection on the Sign-up', 'wp-ultimo'),
            'desc'          => __('If you want your users to be able to select between multiple domains, check this option.', 'wp-ultimo'),
            'tooltip'       => __('', 'wp-ultimo'),
            'type'          => 'checkbox',
            'default'       => 0,
          ),

          // @since 1.7.0
          'domain_options' => array(
            'title'         => __('Domain Options', 'wp-ultimo'),
            'desc'          => __('Enter one domain per line.', 'wp-ultimo'),
            'placeholder'   => "testdomain.com
testedomain2.net",
            'type'          => 'textarea',
            'tooltip'       => '',
            'default'       => preg_replace( '|^www\.|', '', $current_site->domain),
            'require'       => array('enable_multiple_domains' => 1),
          ),

          // @since 1.4.0
          'enable_coupon_codes' => array(
            'title'         => __('Enable Coupon Codes', 'wp-ultimo'),
            'desc'          => __('Select which behavior you want WP Ultimo to use regarding coupon codes.', 'wp-ultimo'),
            'tooltip'       => __('', 'wp-ultimo'),
            'type'          => 'select',
            'default'       => 'url_and_field',
            'options'       => array(
              'url_and_field' => __('Enable Coupon Codes via URL and add the Sign-up Field', 'wp-ultimo'),
              'url_only'      => __('Enable Coupon Codes via URL only (hide Sign-up Field)', 'wp-ultimo'),
              'disabled'      => __('Disable Coupon Codes entirely', 'wp-ultimo'),
            )
          ),

          // @since 1.0.4
          // Terms of service
          'enable_terms'    => array(
            'title'         => __('Enable Terms of Service', 'wp-ultimo'),
            'desc'          => __('Allowing this option will display an extra checkbox asking the user to agree to terms of service.', 'wp-ultimo'),
            'tooltip'       => __('', 'wp-ultimo'),
            'type'          => 'checkbox',
            'default'       => 0,
          ),

          // @since 1.4.0
          'terms_type' => array(
            'title'         => __('Terms of Service Type', 'wp-ultimo'),
            'desc'          => __('Content Editor will display an editor where you will be able to add your terms. WP Ultimo will serve them in a custom page. You can also use the External URL option to point the terms link to a specific link of your choosing.', 'wp-ultimo'),
            'tooltip'       => __('', 'wp-ultimo'),
            'type'          => 'select',
            'default'       => 'content',
            'options'       => array(
              'content'      => __('Content Editor', 'wp-ultimo'),
              'external_url' => __('External URL', 'wp-ultimo'),
            ),
            'require'       => array(
              'enable_terms' => 1,
            ),
          ),

          // @since 1.0.4
          // Terms of service Content
          'terms_content'    => array(
            'title'         => __('Terms of Service', 'wp-ultimo'),
            'desc'          => __('The terms of service.', 'wp-ultimo'),
            'tooltip'       => __('Basic HTML supported', 'wp-ultimo'),
            'type'          => 'wp_editor',
            'default'       => '',
            'args'          => array(
              'media_buttons' => false,
              'wpautop'       => true,
              'editor_height' => 300,
            ),
            'require'       => array(
              'enable_terms' => 1,
              'terms_type'   => 'content'
            ),
          ),

          /**
           * @since 1.9.0
           */
          'terms_content_url' => array(
            'title'         => __('Terms External URL', 'wp-ultimo'),
            'desc'          => __('External URL for your Terms aof Services.', 'wp-ultimo'),
            'tooltip'       => '',
            'type'          => 'url',
            'placeholder'   => 'https://externalurl.com/terms',
            'require'       => array(
              'enable_terms' => 1,
              'terms_type'   => 'external_url'
            ),
          ),

          'site-templates'  => array(
            'title'         => __('Site Templates', 'wp-ultimo'),
            'desc'          => __('Template options and settings', 'wp-ultimo'),
            'type'          => 'heading',
          ),

          'allow_template'  => array(
            'title'         => __('Allow Template Selection', 'wp-ultimo'),
            'desc'          => __('Allow users to select templates in the sign up process.', 'wp-ultimo'),
            'tooltip'       => __('You can select the templates the users can select in the next option.', 'wp-ultimo'),
            'type'          => 'checkbox',
            'default'       => 1,
          ),

          /**
           * @since  1.5.6 Adds template switching
           */
          'allow_template_switching'  => array(
            'title'         => __('Allow Template Switching', 'wp-ultimo'),
            'desc'          => __('Enabling this option will add an option on your client\'s dashboard to switch their site template to another one available on the catalog of available templates. The data is lost after a switch as the data from the new template is copied over.', 'wp-ultimo'),
            'tooltip'       => '',
            'type'          => 'checkbox',
            'require'       => array('allow_template' => 1),
            'default'       => 1,
          ),

          /**
           * @since  1.7.4 Allow super admins to decide if they want to allow users to copy their own sites up
           */
          'allow_own_site_as_template'  => array(
            'title'         => __('Allow Users to use their own Sites as Templates', 'wp-ultimo'),
            'desc'          => __('Enabling this option will add the user own sites to the template screen, allowing them to create a new site based on the content and customizations they made previously.' , 'wp-ultimo'),
            'tooltip'       => '',
            'type'          => 'checkbox',
            'require'       => array('allow_template' => 1),
            'default'       => 1,
          ),

          /**
           * @since  1.2.0 Adds filtering to the Template Selection step
           */
          'allow_template_filter'  => array(
            'title'         => __('Allow Template Filtering', 'wp-ultimo'),
            'desc'          => __('Enabling this option will add a dynamic filter on the Template Selector, during the signup flow, allowing your users to sort the templates by category. Useful when your network has a lot of site templates.', 'wp-ultimo'),
            'tooltip'       => '',
            'type'          => 'checkbox',
            'require'       => array('allow_template' => 1),
            'default'       => 1,
          ),

          /**
           * @since  1.5.5 Adds the template top-bar
           */
          'allow_template_top_bar'  => array(
            'title'         => __('Allow Template Selection Top-bar', 'wp-ultimo'),
            'desc'          => __('Enabling this option will add a top-bar to the template preview screen. This can be customized on the Styling tab of WP Ultimo Settings.', 'wp-ultimo'),
            'tooltip'       => '',
            'type'          => 'checkbox',
            'require'       => array('allow_template' => 1),
            'default'       => 1,
          ),

          'templates'       => array(
            'title'         => __('Allowed Templates', 'wp-ultimo'),
            'desc'          => __('Select the templates you want the user to select.', 'wp-ultimo'),
            'tooltip'       => __('You can sort them in the order you prefer, just by dragging them into position.', 'wp-ultimo'),
            'type'          => 'multi_checkbox',
            'default'       => false,
            'require'       => array('allow_template' => 1),
            'options'       => self::get_available_templates(),
            'sortable'      => true,
          ),

          /**
           * @since  1.4.1 Enables the screenshot scraper
           */
          'enable_screenshot_scraper' => array(
            'title'         => __('Enable Screenshot Scraper', 'wp-ultimo'),
            'desc'          => __('Enable the automatic screenshot scraper cron job for the templates on the network. Warning: This can be quite resource-intensive.', 'wp-ultimo'),
            'tooltip'       => '',
            'type'          => 'checkbox',
            'default'       => 0,
            'require'       => array('allow_template' => 1),
          ),

          /**
           * @since  1.1.0 Allows users to manually trigger the screenshot retriever
           */
          'call_screenshot' => array(
            'title'         => __('Screenshot Scraper', 'wp-ultimo'),
            'desc'          => __('Use this button to manually start the screenshot template scraper.', 'wp-ultimo'),
            'label'         => __('Run Screenshot Scraper', 'wp-ultimo'),
            'tooltip'       => __('This process may take a while.', 'wp-ultimo'),
            'type'          => 'ajax_button',
            'action'        => 'wu_screenshot_scraper',
            'require'       => array('allow_template' => 1, 'enable_screenshot_scraper' => 1),
          ),

          /**
           * @since  1.3.0 Select if you want the media to be copied over or not
           */
          'copy_media'      => array(
            'title'         => __('Copy Media on Template Duplication?', 'wp-ultimo'),
            'desc'          => __('Checking this option will copy the media uploaded on the template site to the newly created site. This can be overridden on each of the plans.', 'wp-ultimo'),
            'tooltip'       => '',
            'type'          => 'checkbox',
            'default'       => true,
            // 'require'       => array('allow_template' => 1),
          ),

          /**
           * @since 1.6.0 Give admins the option to stop Search Engines from indexing templates
           */
          'stop_template_indexing' => array(
            'title'         => __('Prevent Search Engines from indexing Site Templates', 'wp-ultimo'),
            'desc'          => __('Checking this option will discourage search engines from indexing all the Site Templates on your network.', 'wp-ultimo'),
            'tooltip'       => '',
            'type'          => 'checkbox',
            'default'       => false,
            // 'require'       => array('allow_template' => 1),
          ),

          /**
           * Downgrade and Upgrade Options
           * @since 1.7.0
           */
          'handle_quotas'   => array(
            'title'         => __('Upgrade & Downgrade', 'wp-ultimo'),
            'desc'          => __('Upgrade & Downgrade', 'wp-ultimo'),
            'type'          => 'heading',
          ),

          'move_posts_on_downgrade' => array(
            'title'         => __('Move Posts on Downgrade', 'wp-ultimo'),
            'desc'          => __('Select how you want to handle the posts above the quota on downgrade. This will apply to all post types with quotas set.', 'wp-ultimo'),
            'tooltip'       => '',
            'type'          => 'select',
            'default'       => 'none',
            'options'       => array(
              'none'        => __('Keep posts as is (do nothing)', 'wp-ultimo'),
              'trash'       => __('Move posts above the new quota to the Trash', 'wp-ultimo'),
              'draft'       => __('Mark posts above the new quota as Drafts', 'wp-ultimo'),
            ),
          ),

          /**
           * @since 1.7.3
           */
          'block_sites_on_downgrade' => array(
            'title'         => __('Block Sites on Downgrade', 'wp-ultimo'),
            'desc'          => __('Choose how WP Ultimo should handle client sites above their plan quota on downgrade.', 'wp-ultimo'),
            'tooltip'       => '',
            'type'          => 'select',
            'default'       => 'none',
            'options'       => array(
              'none'           => __('Keep sites as is (do nothing)', 'wp-ultimo'),
              'block-frontend' => __('Block only frontend access', 'wp-ultimo'),
              'block-backend'  => __('Block only backend access', 'wp-ultimo'),
              'block-both'     => __('Block both frontend and backend access', 'wp-ultimo'),
            ),
          ),

          /**
           * @since 1.7.0
           */
          'search_and_replace_heading' => array(
            'title'         => __('Search & Replace', 'wp-ultimo'),
            'desc'          => __('Enter search and replace pairs to be used while creating new sites.', 'wp-ultimo'),
            'type'          => 'heading',
          ),

          'search_and_replace' => array(
            'title'         => __('Search and Replace', 'wp-ultimo'),
            'type'          => 'note',
            'desc'          => self::get_multiple_lines_field(),
          ),
          
          'wp_overwrite'    => array(
            'title'         => __('WordPress Overwrite', 'wp-ultimo'),
            'desc'          => __('The options below are also present in the Network Settings of WordPress, changing them here will overwrite the settings there and vice-versa. <br>They were placed here just to make your life a little bit easier =D', 'wp-ultimo'),
            'type'          => 'heading',
          ),
          
          'menu_items_plugin' => array(
            'title'         => __('Enable Plugins Menu', 'wp-ultimo'),
            'desc'          => __('Do you want to let users on the network to have access to the Plugins page, activating plugins for their sites?', 'wp-ultimo'),
            'tooltip'       => __('You can select which plugins the user will be able to use for each plan.', 'wp-ultimo'),
            'type'          => 'checkbox',
            'default'       => $menu_items_plugin,
          ),
          
          'add_new_users' => array(
            'title'         => __('Add New Users', 'wp-ultimo'),
            'desc'          => __('Allow site administrators to add new users to their site via the "Users → Add New" page.', 'wp-ultimo'),
            'tooltip'       => __('You can limit the number of users allowed for each plan.', 'wp-ultimo'),
            'type'          => 'checkbox',
            'default'       => $add_new_users,
          ),
          
        )),
      ),

      // Gateways
      'domain_mapping' => array(
        'title'  => __('Domain Mapping & SSL', 'wp-ultimo'),
        'desc'   => __('Domain Mapping & SSL', 'wp-ultimo'),
        'fields' => apply_filters('wu_settings_section_domain_mapping', array(
          
          /**
           * @since  1.2.0 In order to keep things neat, we are moving domain-related options to a separate session
           */
          'domains'         => array(
            'title'         => __('Domain Mapping and SSL Options', 'wp-ultimo'),
            'desc'          => __('General settings on how to handle domain mapping and SSL enforcement.', 'wp-ultimo'),
            'type'          => 'heading',
          ),

          'enable_domain_mapping'  => array(
            'title'         => __('Enable Domain Mapping', 'wp-ultimo'),
            'desc'          => __('Do you want to enable domain mapping?', 'wp-ultimo'),
            'tooltip'       => __('Enabling this will allow you and other super admins to manually add mapped domains to specific sites. To allow your users to setup their own custom domain directly from their panel, you need to enable the Custom Domain options below as well.', 'wp-ultimo'),
            'type'          => 'checkbox',
            'default'       => true,
          ),

          'custom_domains'  => array(
            'title'         => __('Enable Custom Domains', 'wp-ultimo'),
            'desc'          => __('Do you want to enable custom domains for your clients?', 'wp-ultimo'),
            'tooltip'       => __('You can select which plans can have access to this feature.', 'wp-ultimo'),
            'type'          => 'checkbox',
            'default'       => true,
            'require'       => array('enable_domain_mapping' => 1),
          ),

          'domain-mapping-alert-message'  => array(
            'title'         => __('Domain Mapping Alert Message', 'wp-ultimo'),
            'desc'          => __('Display a customized message alerting the end-user of the risks of mapping a misconfigured domain.', 'wp-ultimo'),
            'type'          => 'textarea',
            'default'       => __('This action can make your site inaccessible if your DNS configuration was not properly set up. Please make sure your domain DNS configuration is pointing to the right IP address and that enough time has passed for that change to propagate.'),
            'require'       => array('enable_domain_mapping' => 1),
          ),

          'force_admin_redirect'  => array(
            'title'         => __('Force Admin Redirect', 'wp-ultimo'),
            'desc'          => __('Select how you want your users to access the admin panel if they have mapped domains.', 'wp-ultimo').'<br><br>'.__('Force Redirect to Mapped Domain: your users with mapped domains will be redirected to theirdomain.com/wp-admin, even if they access using yournetworkdomain.com/wp-admin.', 'wp-ultimo').'<br><br>'.__('Force Redirect to Network Domain: your users with mapped domains will be redirect to yournetworkdomain.com/wp-admin, even if they access using theirdomain.com/wp-admin.', 'wp-ultimo'),
            'tooltip'       => '',
            'type'          => 'select',
            'default'       => 'both',
            'require'       => array('enable_domain_mapping' => 1),
            'options'       => array(
              'both'          =>  __('Allow access to the admin by both mapped domain and network domain', 'wp-ultimo'),
              'force_map'     =>  __('Force Redirect to Mapped Domain', 'wp-ultimo'),
              'force_network' =>  __('Force Redirect to Network Domain', 'wp-ultimo'),
            ),
          ),

          'force_admin_https' => array(
            'title'         => __('Force HTTPs for the Admin Panel', 'wp-ultimo'),
            'desc'          => __('This equivalent of adding define("FORCE_SSL_ADMIN", true) on your wp-config.php file.', 'wp-ultimo'),
            'tooltip'       => __('', 'wp-ultimo'),
            'type'          => 'checkbox',
            'default'       => false,
            'require'       => array('enable_domain_mapping' => 1),
          ),

          'force_subdomains_https' => array(
            'title'         => __('Force HTTPS for Subdomains', 'wp-ultimo'),
            'desc'          => __('Forces HTTPS on the front-end and backend of sites if this network uses subdomains. It will require a wildcard SSL certificate for this to work.', 'wp-ultimo'),
            'tooltip'       => __('', 'wp-ultimo'),
            'type'          => 'checkbox',
            'default'       => false,
            'require'       => array('enable_domain_mapping' => 1),
          ),

          'force_mapped_https' => array(
            'title'         => __('Force HTTPS for Custom Domains', 'wp-ultimo'),
            'desc'          => __('Forces HTTPS on the front-end and backend of sites with custom domains. Each custom domain would need a valid SSL certificate.', 'wp-ultimo'),
            'tooltip'       => __('', 'wp-ultimo'),
            'type'          => 'checkbox',
            'default'       => false,
            'require'       => array('enable_domain_mapping' => 1),
          ),

          'allow_page_unmapping'  => array(
            'title'         => __('Allow Page Unmapping', 'wp-ultimo'),
            'desc'          => __('Adds a metabox to your clients\' post type edit pages, giving them the option to unmap particular pages and posts. Usefull for checkout pages and other instances where your client might use your network\'s SSL certificate.', 'wp-ultimo'),
            'tooltip'       => __('', 'wp-ultimo'),
            'type'          => 'checkbox',
            'default'       => true,
            'require'       => array('enable_domain_mapping' => 1),
          ),

          'force_https_unmapped'  => array(
            'title'         => __('Force HTTPs on Unmapped Pages', 'wp-ultimo'),
            'desc'          => __('Enable this option if you want to force HTTPs for pages and posts unmapped by your subsites.', 'wp-ultimo'),
            'tooltip'       => __('', 'wp-ultimo'),
            'type'          => 'checkbox',
            'default'       => !is_subdomain_install(),
            'require'       => array('enable_domain_mapping' => 1, 'allow_page_unmapping' => 1),
          ),

          /**
           * @since 1.5.0 Disable SSO
           */
          'enable_sso'  => array(
            'title'         => __('Enable Single Sign-On', 'wp-ultimo'),
            'desc'          => __('Enables the Single Sign-on functionality between mapped-domains.', 'wp-ultimo'),
            'tooltip'       => __('', 'wp-ultimo'),
            'type'          => 'checkbox',
            'default'       => true,
            'require'       => array('enable_domain_mapping' => 1),
          ),

          'network_ip'      => array(
            'title'         => __('Network IP Address', 'wp-ultimo'),
            'desc'          => sprintf(__('Add this website\'s IP Address. It will be displayed to your users to allow them to setup the correct DNS configurations for domain mapping. Your apparent network IP address is %s. Leave it blank to use it as a default.', 'wp-ultimo'), WU_Domain_Mapping::get_ip_address()),
            'tooltip'       => '', //__('You can select which plugins the user will be able to use for each plan.', 'wp-ultimo'),
            'type'          => 'text',
            'placeholder'   => WU_Domain_Mapping::get_ip_address(),
            'default'       => '',
            'require'       => array('enable_domain_mapping' => 1),
          ),
        )),
      ),

      // Gateways
      'gateways' => array(
        'title'  => __('Payment Gateways', 'wp-ultimo'),
        'desc'   => __('Payment Gateways', 'wp-ultimo'),
        'fields' => $filters ? apply_filters('wu_settings_section_payment_gateways', $gateway_fields) : $gateway_fields,
      ),

      // Emails
      'emails' => array(
        'title'  => __('Emails', 'wp-ultimo'),
        'desc'   => __('', 'wp-ultimo'),
        'fields' => $filters ? apply_filters('wu_settings_section_emails', array(

          'email_headings' => array(
            'title' => __('Email Templates', 'wp-ultimo'),
            'desc'  => __('Use the options below to edit the email templates. Emails with the <code>admin</code> tag are sent to the network admin email address only.', 'wp-ultimo'),
            'type'  => 'heading',
          )

        )) : array(),
      ),

      // Advanced
      // @since 1.0.5
      'styling' => array(
        'title'  => __('Styling', 'wp-ultimo'),
        'desc'   => __('Styling', 'wp-ultimo'),
        'fields' => apply_filters('wu_settings_section_styling', array(

          'customizer'       => array(
            'title'         => __('Edit on Customizer', 'wp-ultimo'),
            'desc'          => __('Since version 1.4.0 it is possible to change the settings below directly from the Customizer with live preview support!', 'wp-ultimo'), // . '<br>' . '<strong>' . __('You can also re-order and add new steps and fields to the Sign-up Flow.', 'wp-ultimo') . '</strong>',
            'type'          => 'heading',
          ),

          'customizer-link' => array(
            'desc'          => '<a style="font-style: normal;" target="_blank" class="button button-primary" href="'. WU_Customizer::get_link() .'">'. __('Launch the Customizer', 'wp-ultimo') .'</a>',
            'type'          => 'note',
          ),

          'logos'           => array(
            'title'         => __('Logos and Images', 'wp-ultimo'),
            'desc'          => __('You can use this section to upload custom logos of your service. They will be displayed in the login page and in other places.', 'wp-ultimo'),
            'type'          => 'heading',
          ),

          'logo'            => array(
            'title'         => __('Logo', 'wp-ultimo'),
            'desc'          => __('Use the button below to upload and set the logo of your service. The optimal size is 320x80px.', 'wp-ultimo'),
            'button'        => __('Upload new Logo', 'wp-ultimo'),

            'default'       => WP_Ultimo()->get_asset('logo.png'),
            'type'          => 'image',
            'width'         => 200,
          ),

          'logo-square'     => array(
            'title'         => __('Logo - Squared version', 'wp-ultimo'),
            'desc'          => __('Upload a squared version of your logo. This is used by some gateways like Stripe.', 'wp-ultimo'),
            'button'        => __('Upload new Logo', 'wp-ultimo'),
            'default'       => WP_Ultimo()->get_asset('badge.png'),
            'type'          => 'image',
            'width'         => 50,
          ),

          // @since 1.7.0
          'use-logo-login' => array(
            'title'         => __('Use a different Logo for Login Page', 'wp-ultimo'),
            'desc'          => __('Toggle this option if you want to use a different logo on the login page. Useful for customized login screens.', 'wp-ultimo'),
            'tooltip'       => '',
            'type'          => 'checkbox',
            'default'       => 0,
          ),

          // @since 1.7.0
          'logo-login'    => array(
            'title'         => __('Logo - Login Page', 'wp-ultimo'),
            'desc'          => __('Upload a logo for the login page.', 'wp-ultimo'),
            'button'        => __('Upload new Logo', 'wp-ultimo'),

            'default'       => WP_Ultimo()->get_asset('logo.png'),
            'type'          => 'image',
            'width'         => 200,
            'require'       => array('use-logo-login' => 1)
          ),

          'colors'          => array(
            'title'         => __('Colors', 'wp-ultimo'),
            'desc'          => __('Change the main colors of the plugin. These are mainly used in the front-end, in the pricing tables, for example.', 'wp-ultimo'),
            'type'          => 'heading',
          ),

          'primary-color'   => array(
            'title'         => __('Primary Color', 'wp-ultimo'),
            'desc'          => __('Select a color to be the primary one of your network. This is used mostly in the front-end, on the pricing tables.', 'wp-ultimo'),
            'default'       => '#00a1ff',
            'type'          => 'color',
          ),

          'accent-color'   => array(
            'title'         => __('Accent Color', 'wp-ultimo'),
            'desc'          => __('Select a color to be the accent one of your network. This is used mostly in the front-end, on the pricing tables.', 'wp-ultimo'),
            'default'       => '#78b336',
            'type'          => 'color',
          ),

          /**
           * Top-bar customizing
           * @since 1.5.5
           */

          'top-bar'          => array(
            'title'         => __('Template Selection Top-bar', 'wp-ultimo'),
            'desc'          => __('Use the options below to customize the top-bar on the template preview screen. The top-bar needs to be activated on the Network Settings tab of the WP Ultimo Settings.', 'wp-ultimo'),
            'type'          => 'heading',
          ),

          'top-bar-bg-color'   => array(
            'title'         => __('Background Color', 'wp-ultimo'),
            'desc'          => __('Select the background color of the top-bar', 'wp-ultimo'),
            'default'       => '#f9f9f9',
            'type'          => 'color',
          ),

          'top-bar-button-bg-color'   => array(
            'title'         => __('Button Background Color', 'wp-ultimo'),
            'desc'          => __('Select the background color of the top-bar action button', 'wp-ultimo'),
            'default'       => '#00a1ff',
            'type'          => 'color',
          ),

          'top-bar-button-text'   => array(
            'title'         => __('Button Text', 'wp-ultimo'),
            'desc'          => __('Customize the call-to-action button label', 'wp-ultimo'),
            'default'       => __('Use this Template', 'wp-ultimo'),
            'type'          => 'text',
          ),

          'top-bar-enable-resize'   => array(
            'title'         => __('Enable Resize Icons', 'wp-ultimo'),
            'desc'          => __('Enable this option if you want to display the resize icons, allowing visitors to change the viewport to different devices', 'wp-ultimo'),
            'tooltip'       => '',
            'type'          => 'checkbox',
            'default'       => 1,
          ),

          'top-bar-use-logo' => array(
            'title'         => __('Use a different Logo', 'wp-ultimo'),
            'desc'          => __('Toggle this option if you want to use a different logo on the top-bar', 'wp-ultimo'),
            'tooltip'       => '',
            'type'          => 'checkbox',
            'default'       => 0,
          ),

          'top-bar-logo'    => array(
            'title'         => __('Logo - Top-bar', 'wp-ultimo'),
            'desc'          => __('Upload a logo for the top-bar.', 'wp-ultimo'),
            'button'        => __('Upload new Logo', 'wp-ultimo'),

            'default'       => WP_Ultimo()->get_asset('logo.png'),
            'type'          => 'image',
            'width'         => 200,
            'require'       => array('top-bar-use-logo' => 1)
          ),

        )),

      ),

      /**
       * Tools Tab
       * @since 1.6.0
       */
      'tools' => array(
        'title'  => __('Tools', 'wp-ultimo'),
        'desc'   => __('Tools', 'wp-ultimo'),
        'fields' => apply_filters('wu_settings_section_tools', array(

        )),
      ),

      // Advanced
      'advanced' => array(
        'title'  => __('Advanced', 'wp-ultimo'),
        'desc'   => __('Advanced', 'wp-ultimo'),
        'fields' => apply_filters('wu_settings_section_advanced', array(

          'advanced'        => array(
            'title'         => __('Advanced Settings', 'wp-ultimo'),
            'desc'          => __('This area contains potentially dangerous settings and actions that could affect your whole network. Be careful!', 'wp-ultimo'),
            'type'          => 'heading',
          ),

          'cleaning'         => array(
            'title'          => sprintf('<strong>%s</strong> <code>%s</code> %s', __('Clean Database Tables and Settings', 'wp-ultimo'), __('Danger Zone', 'wp-ultimo'), WU_Util::tooltip(__('Be careful when using this option since it will delete the selected data and that action cannot be undone.', 'wp-ultimo'))),
            'desc'           => __('', 'wp-ultimo'),
            'type'           => 'heading_collapsible',
          ),

          'cleaning_options' => array(
            'title'          => __('Cleaning Options', 'wp-ultimo'),
            'desc'           => __('Be careful, this cannot be undone!', 'wp-ultimo'),
            'tooltip'        => '',
            'type'           => 'multi_checkbox',
            'default'        => true,
            'options'        => array(
              'settings'     => __('Clean all Settings', 'wp-ultimo'),
              'tables'       => __('Clean all the custom database tables, like transactions and subscriptions', 'wp-ultimo'),
              'cpts'         => __('Remove all Custom Post Types (Plans, Coupons, Broadcasts, etc)', 'wp-ultimo'),
            )
          ),

          'other'        => array(
            'title'         => __('Other', 'wp-ultimo'),
            'desc'          => __('This area contains potentially dangerous settings and actions that could affect your whole network. Be careful!', 'wp-ultimo'),
            'type'          => 'heading',
          ),

         'remove_transients' => array(
            'title'         => __('Remove Transients', 'wp-ultimo'),
            'desc'          => __('Transients are temporary entries on the database. We use them to store the signup information of your users while they are navigating the multi-step signup flow. We only keep them on the databse for a limited amount of time (40 minutes by default), but you can use this to clean them up if you need to.', 'wp-ultimo'),
            'label'         => __('Remove WP Ultimo Transients', 'wp-ultimo'),
            'tooltip'       => __('This process may take a while.', 'wp-ultimo'),
            'type'          => 'ajax_button',
            'action'        => 'wu_remove_transients&_wpnonce=' . wp_create_nonce('wu_remove_transients'),
          ),

        )),
      ),

      /**
       * Handles Export and Import
       * @since 1.7.4
       */
      'export-import' => array(
        'title'  => __('Export & Import', 'wp-ultimo'),
        'desc'   => __('Export & Import', 'wp-ultimo'),
        'fields' => array(

          'export'          => array(
            'title'         => __('Export Settings', 'wp-ultimo'),
            'desc'          => __('Use the button below to generate a bundle .zip file with all your settings from WP Ultimo, including plans, coupons, and broadcasts.', 'wp-ultimo') . '<br>' . __('Subscriptions and Transactions are not included on the export file.', 'wp-ultimo'),
            'type'          => 'heading',
          ),

          'export-button' => array(
            'desc'          => '<a style="font-style: normal;" class="button button-primary" href="'. WU_Exporter_Importer::get_export_link() .'">'. __('Export WP Ultimo Settings', 'wp-ultimo') .'</a>',
            'type'          => 'note',
          ),

          'import'          => array(
            'title'         => __('Import Settings', 'wp-ultimo'),
            'type'          => 'heading',
            'desc'          => WU_Exporter_Importer::get_import_description(),
          ),

          'import-file'    => array(
            'desc'         => '<input style="font-style: normal;" type="file" name="wu-import-file" />',
            'type'         => 'note',
          ),

          'import-partial' => array(
            'title'         => __('Partial Import', 'wp-ultimo'),
            'desc'          => __('If you want to import only some of WP Ultimo\'s data sets, check this box to select which ones.', 'wp-ultimo'),
            'tooltip'       => '',
            'type'          => 'checkbox',
            'default'       => 0,
          ),

          'import-partial-options' => array(
            'title'         => __('Data Sets', 'wp-ultimo'),
            'desc'          => __('Select which data sets you want to import.', 'wp-ultimo'),
            'type'          => 'multi_checkbox',
            'tooltip'       => '',
            'options'       => WU_Exporter_Importer::get_import_options(),
            'default'       => array_keys(WU_Exporter_Importer::get_import_options()),
            'require'       => array('import-partial' => 1)
          ),

        ),
      ),
      
      // Activation
      'activation' => array(
        'title'  => __('Activation & Support', 'wp-ultimo'),
        'desc'   => __('Activation', 'wp-ultimo'),
        'fields' => array(

          'support'         => array(
            'title'         => __('Support', 'wp-ultimo'),
            'desc'          => __('Terms of the Support.', 'wp-ultimo'),
            'type'          => 'heading',
          ),

          'support_terms' => array(
            'type' => 'note',
            'desc' => self::get_support_terms(),
          ),

          'beta-program-heading' => array(
            'title'         => __('Beta Program', 'wp-ultimo'),
            'desc'          => __('Join the beta program to help us test out new features and releases. Only works if your copy is activated with your license code on the "Activate your Plugin" section below.', 'wp-ultimo'),
            'type'          => 'heading',
          ),

          // @since 1.7.0
          'beta-program'    => array(
            'title'         => __('Join the Beta Program?', 'wp-ultimo'),
            'desc'          => __('If you toggle this option, you will be part of our Beta Program. You\'ll be notified whenerver a new alpha, beta or RC release is out so you can test new features as they get implemented. We suggest you only run this on your development environments, not on your production networks, as beta releases WILL contain bugs.', 'wp-ultimo') . '  ' . sprintf('<a target="_blank" href="%s">%s</a>', WU_Links()->get_link('beta-program'), __('Read more about the Beta Program', 'wp-ultimo')),
            'tooltip'       => '',
            'type'          => 'checkbox',
            'default'       => 0,
          ),
          
          'activation'    => array(
            'title'         => __('Activate your Plugin', 'wp-ultimo'),
            'desc'          => __('Use your license key to activate the plugin and get a update notice whenever a new version is available. <br><strong>This will not auto-update your copy, it will only display a notice when a new version is available, with the option to update.</strong>', 'wp-ultimo'),
            'type'          => 'heading',
          ),
          
          'license_key'     => array(
            'title'         => __('License Key', 'wp-ultimo'),
            'desc'          => sprintf(__('Put the code you received alongside the plugin when you finalized your purchase.', 'wp-ultimo').'<br>%s', $license_status_string),
            'tooltip'       => '', //__('You can select which plugins the user will be able to use for each plan.', 'wp-ultimo'),
            'type'          => 'password',
            'placeholder'   => 'xxxx-xxxx-xxxx-xxxx',
            'default'       => '',
            'html_attr'     => self::get_license_key_from_wp_config() ? array(
              'disabled'      => 'disabled',
              'value'         => 'xxxx-xxxx-xxxx-xxxx',
            ) : array(
              'value'         => '',
            )
          ),
          
        )
      ),
      
    );

    self::$sections = apply_filters('wu_settings_sections', $sections);
    
    // Return after running our filter
    return self::$sections;
    
  } // end get_sections;

  /**
   * Get multiple lines
   * 
   * @since 1.7.0
   * @return void
   */
  public static function get_multiple_lines_field($field_name = 'search_and_replace') { 
    
    // Prevent bugs on activation, do not remove
    if ( ! self::$settings ) return;
    
    ob_start(); ?>

    <div id="search-and-replace" v-cloak>

      <div class="line" v-for="(list_item, index) in list">
        <input style="width: 250px;" v-bind:value="list_item.search" v-bind:name="'search_and_replace[' + index + '][search]'" type="text" class="regular-text" v-model="list[index].search" placeholder="<?php _e('Search', 'wp-ultimo'); ?>">
        <input style="width: 250px;" v-bind:value="list_item.replace" v-bind:name="'search_and_replace[' + index + '][replace]'" type="text" class="regular-text" v-model="list[index].replace" placeholder="<?php _e('Replace', 'wp-ultimo'); ?>">
        <a v-if="index != 0" v-on:click="remove($event, index)" href="#" class="delete"><?php _e('Remove', 'wp-ultimo'); ?></a>
      </div>

      <button v-on:click="add_new" class="button" style="margin-top: 10px;"><?php _e('Add new Pair', 'wp-ultimo'); ?></button>

      <p class="description" style="margin-top: 10px;">
        <?php _e('Enter search and replace pairs. WP Ultimo will check for those string and replace them on site creation.', 'wp-ultimo'); ?> <?php printf('<a href="%s" target="_blank">%s</a>', WU_Links()->get_link('search-and-replace'), __('Read about dynamic Search and Replace on the Documentation.', 'wp-ultimo')); ?>
      </p>

    </div>

    <script type="text/javascript">
      (function($) {
        $(document).ready(function() {

          search_and_replace = new Vue({
            el: '#search-and-replace',
            delimiters: ["<%","%>"],
            data: {
              list: <?php echo json_encode(WU_Settings::get_setting('search_and_replace') ?: array(
                array(
                  'search'  => '',
                  'replace' => '',
                )
              )); ?>,
            },
            methods: {
              add_new: function(e) {
                e.preventDefault();
                this.list = this.list.concat([{
                  search: '',
                  replace: ''
                }]);
              },
              remove: function(e, index) {
                e.preventDefault();
                this.list.splice(index, 1);
              }
            }
          });

        });
      })(jQuery);
    </script>

    <?php return ob_get_clean();

  } // end get_multiple_lines_field;

  /**
   * Array Diff
   *
   * @param array $a
   * @param array $b
   * @return void
   */
  public static function array_diff($a, $b) {

    $map = $out = array();
    
    foreach($a as $val) $map[$val] = 1;
    foreach($b as $val) if(isset($map[$val])) $map[$val] = 0;
    foreach($map as $val => $ok) if($ok) $out[] = $val;
    return $out;

  } // end array_diff;

  /**
   * Changes the switch tio prevent expensive queries
   *
   * @since 1.9.3
   * @param bool $value
   * @return void
   */
  public static function set_prevent_expensive_queries($value) {

    self::$prevent_expensive_queries = $value;

  } // end prevent_expensive_queries;

  /**
   * Install defaults
   * TODO: look into ways of optimizing this in the future
   *
   * @since 1.5.4
   * @return void
   */
  public static function install_defaults() {

    self::set_prevent_expensive_queries(true);

    $sections     = self::get_sections();
    $defaults     = self::extract_defaults($sections);
    $settings     = self::get_settings();
    $new_settings = self::array_diff(array_keys($defaults), array_keys($settings));

    self::set_prevent_expensive_queries(false);

    if ($new_settings) {
      
      foreach($new_settings as $setting) {
        
        $settings[$setting] = $defaults[$setting];
        
      } // end foreach;

      WP_Ultimo()->saveOption('settings', $settings);

    } // end if;
  
  } // end install_defaults;

  /**
   * Extracts the defaults for fields from the sections
   *
   * @return void
   */
  public static function extract_defaults($sections) {

    $defaults = array_map(function($section) {

      $section_defaults = array();

      foreach($section['fields'] as $field_name => $field) {

        if (isset($field['default'])) {

          $section_defaults[$field_name] = $field['default'];

        } // end if

      } // end foreach;

      return $section_defaults;

    }, $sections);

    $numbered_defaults = array_values($defaults);

    return array_merge(...$numbered_defaults);

  } // end extract_defaults;

  /**
   * Clean tables & settings and plans and coupons
   * 
   * @since 1.7.4 Moved to here from the core class
   * @return void
   */
  public static function clean_settings($data) {

    global $wpdb;

    $data = $_POST;

    if (!isset($_GET['wu-tab']) || $_GET['wu-tab'] != 'advanced') return;

    if (!isset($data['cleaning_options'])) return;

    if (!current_user_can('manage_network')) {

      wp_die(__('You do not have the necessary permissions to perform this action.', 'wp-ultimo'));

    } // end if;

    $prefix = apply_filters('wu_core_get_table_prefix', $wpdb->base_prefix);

    /**
     * Remove our tables - Nooooo!
     */
    if (isset($data['cleaning_options']['tables'])) {

      unset($_POST['cleaning_options']['tables']);

      // Tables to remove
      $tables = array('wu_transactions', 'wu_site_owner', 'wu_subscriptions');

      foreach($tables as $table) {

        /**
         * Danger! Droping the tables
         */
        $wpdb->query("DROP TABLE IF EXISTS $prefix$table");

      };

      delete_network_option(null, 'wu_transactions_db_version');
      delete_network_option(null, 'wu_site_owner_db_version');
      delete_network_option(null, 'wu_subscription_db_version');

      update_network_option(null, 'wp_ultimo_activated', false);

      // Recreate tables
      WP_Ultimo()->onActivation();

    } // end if;

    /**
     * Remove Settings
     */
    if (isset($data['cleaning_options']['settings'])) {

      /**
       * @since 1.5.4 Makes sure settings gets reset and recreated based on defaults
       */
      unset($_POST['cleaning_options']['settings']);
      
      WU_Settings::save_settings(true);

    } // end if;

    /**
     * Plans and Coupons
     */
    if (isset($data['cleaning_options']['cpts'])) {

      unset($_POST['cleaning_options']['cpts']);

      $cpts = apply_filters('wu_registered_cpts', array(
        'wpultimo_plan',
        'wpultimo_coupon',
        'wpultimo_broadcast',
        'wpultimo_tax_rate',
        'wpultimo_webhook',
      ));

      // Get all plans and coupons
      $sql = "DELETE p, pm FROM {$prefix}posts p LEFT JOIN {$prefix}postmeta pm ON pm.post_id = p.id WHERE p.post_type IN " . "('" . implode("','", $cpts) ."')";

      // Run the query
      $wpdb->query($sql);

    } // end if;

    // Set different message
    wp_redirect(network_admin_url('admin.php?page=wp-ultimo&updated=2&wu-tab='.$_GET['wu-tab']));

    exit;

  } // end clean_settings;
  
} // end class WU_Settings

// new WU_Settings;

/**
 * Handles the cleaning settings
 * 
 * @since 1.7.4 Moved from the core class
 */
add_action('admin_init', array('WU_Settings', 'clean_settings'), 2000);

/**
 * Adds the hook to overwrite settings on save
 */
add_action('wu_before_save_settings', array('WU_Settings', 'wordpress_overwrite'), 10, 3);

/**
 * Adds the default installer to new settings
 * 
 * @since 1.5.4
 */
add_action('admin_init', array('WU_Settings', 'install_defaults'), 10);
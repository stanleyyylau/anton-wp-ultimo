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

class WU_Customizer {

  /**
   * Construct 
   */
  public function __construct() {

    // Customize Register action.
    add_action('customize_register', array($this, 'add_customizer_panel'));

    add_action('customize_preview_init', array($this, 'add_preview_js'));

    add_action('admin_print_footer_scripts', array($this, 'add_customize_preview_init'));

    add_action('wp_ultimo_registration_steps', array($this, 'filter_signup_steps'), 999);

    add_action('wu_get_setting', array($this, 'filter_settings'), 999, 3);

    add_action('login_enqueue_scripts', array($this, 'add_custom_css'));

  } // end __construct;

  /**
   * Custom CSS Option
   *
   * @since 1.7.4
   * @return void
   */
  public function add_custom_css() {

    wp_register_style('wu-dynamic-styles', WP_Ultimo()->get_asset('wu-dynamic-styles.min.css', 'css'));

    wp_add_inline_style('wu-dynamic-styles', WU_Settings::get_setting('custom-css', ' '), 5);

    wp_enqueue_style('wu-dynamic-styles');

  } // end add_custom_css;

  /**
   * Returns the Customizer Link to our step
   *
   * @return string
   */
  public static function get_link() {

    $url        = get_site_url(get_current_site()->blog_id) . '?wu-signup-customizer-preview&step=plan';
    $return_url = network_admin_url('admin.php?page=wp-ultimo&wu-tab=styling');

    return get_admin_url(get_current_site()->blog_id, 'customize.php?autofocus[panel]=wu_customizer_panel&url=' . urlencode($url) . '&return=' . urlencode($return_url) . '&wu-customize=1');

  } // end get_link;

  /**
   * Filter settings for the customizer
   *
   * @param string $setting_value
   * @param string $setting
   * @param string $default
   * @return void
   */
  public function filter_settings($setting_value, $setting, $default) {

    $replaceable_settings = array('primary-color', 'accent-color', 'logo', 'logo-login', 'use-logo-login', 'custom-css');

    if (in_array($setting, $replaceable_settings) && isset($_POST['customized'])) {

      // Treaty the customized values;
      $customized = json_decode(stripslashes($_REQUEST['customized']));

      if ($customized && property_exists($customized, $setting)) {

        return $customized->{$setting};

      } // end if;

    } // end if;

    // Return the normal value
    return $setting_value;

  } // end filter_settings;

  public function array_map_assoc($func, $ar) {

    $rv = array();

    foreach($ar as $key => $val) {
      $func($key, $val);
      $rv[$key] = $val;
    }

    return $rv;

  } // end array_map_assoc;

  /**
   * Replaces the Sign-up With the new order and add new fields and well
   *
   * @param array $steps
   * @return void
   */
  public function filter_signup_steps($steps) {

    /**
     * Check for customizer input
     */
    
    if (isset($_REQUEST['customized'])) {

      $customized = json_decode(stripslashes($_REQUEST['customized']), true);

      $mod_steps = current($customized);

      if (is_string($mod_steps)) {

        parse_str($mod_steps, $mod_steps);

      } // end if;

      $mod_steps = is_array($mod_steps) ? $mod_steps : array();

    } else {

      $mod_steps = WU_Settings::get_setting('steps', array());

    } // end if;

    if (isset($mod_steps['wu_sortable'])) {

      $mod_steps = $mod_steps['wu_sortable'];

    } // end if;

    /**
     * Loop the Mod Steps
     */
    foreach ($mod_steps as $mod_step_index => $mod_step) {

      if (isset($steps[$mod_step_index])) {

        foreach ($mod_step as $change_id => $change_value) {

          if ($change_value) {

            if (is_numeric($change_value)) $change_value = (int) $change_value;

            // Save the old value for reference and restore buttons
            if (isset($steps[$mod_step_index][$change_id])) {

              $steps[$mod_step_index]['old_values'][$change_id] = $steps[$mod_step_index][$change_id];

            } // end if;

            // Change for the new value
            $steps[$mod_step_index][$change_id] = $change_value;

          } // end if;

        } // end foreach;

      } else {

        // Add the new step using our function
        // wu_add_signup_step($mod_step_index, (int) $mod_step['order'], $mod_step);
        // $steps[$mod_step_index] = $mod_step;

      } // end else;

    } // end foreach;

    /**
     * Map the settings so we have the old values
     */
    foreach($steps as $step_index => &$step) {

      $step['old_values'] = array(
        'id'    => $step_index,
        'name'  => isset($step['name']) ? $step['name'] : '',
        'order' => isset($step['order']) ? $step['order'] : 0, 
      );

      if (isset($step['fields']) && is_array($step['fields']) && !empty($step['fields'])) {

        foreach($step['fields'] as $field_index => &$field) {

          $field['old_values'] = array(
            'id'    => $field_index,
            'name'  => isset($field['name']) ? $field['name'] : '',
            'order' => isset($field['order']) ? $field['order'] : 0, 
          );

        } // end foreach;

      } // end if;

    } // end foreach;

    return $steps;

  } // end filter_signup_steps;

  /**
   * Deprecated: Function used to add the customizer scripts
   *
   * @return void
   */
  public function add_customize_preview_init() { } // end add_customize_preview_init;

  /**
   * Add the preview JS
   *
   * @return void
   */
  public function add_preview_js() { } // end add_preview_js;

  /**
   * Sanitizes the checkbox input
   *
   * @since  1.7.0
   * @param  boolean $checked
   * @return boolean
   */
  public function sanitize_checkbox($checked) {

    return ( ( isset( $checked ) && true == $checked ) ? true : false );

  } // end sanitize_checkbox;

  /**
   * Add Customizer options
   *
   * @param WP_Customizer $wp_customize
   * @return void
   */
  public function add_customizer_panel($wp_customize) {

    // Requires Custom Control
    require_once WP_Ultimo()->path('inc/customizer/class-wu-customize-control-steps.php');

    /**
    * Handle saving of settings with "wu_option" storage type.
    *
    * @param string $value Value being saved
    * @param WP_Customize_Setting|onj $WP_Customize_Setting The WP_Customize_Setting instance when saving is happening.
    */ 
    add_action('customize_update_wu_option', function($value, $WP_Customize_Setting) {

      /** Parse arguments if this is a step **/
      if ($WP_Customize_Setting->id === 'steps') {
        
        parse_str($value, $value);

      } // end if;

      WU_Settings::save_setting($WP_Customize_Setting->id, $value);
      
    }, 10, 2);

    /**
     * Settings
     */

    $steps = WU_Signup()->get_steps(false, true);

    $wp_customize->add_setting('logo', array(
      'default'    => WU_Settings::get_setting('logo'),
      'type'       => 'wu_option',
      'transport'  => 'refresh',
      'capability' => 'manage_network',
    ));

    $wp_customize->add_setting('logo-square', array(
      'default'    => WU_Settings::get_setting('logo-square'),
      'type'       => 'wu_option',
      'transport'  => 'refresh',
      'capability' => 'manage_network',
    ));

    $wp_customize->add_setting('use-logo-login', array(
      'default'    => WU_Settings::get_setting('use-logo-login'),
      'type'       => 'wu_option',
      'transport'  => 'refresh',
      'capability' => 'manage_network',
      'sanitize_callback' => array($this, 'sanitize_checkbox'),
    ));

    $wp_customize->add_setting('custom-css', array(
      'default'    => WU_Settings::get_setting('custom-css', ' '),
      'type'       => 'wu_option',
      'transport'  => 'refresh',
      'capability' => 'manage_network',
    ));

    $wp_customize->add_setting('logo-login', array(
      'default'    => WU_Settings::get_setting('logo-login'),
      'type'       => 'wu_option',
      'transport'  => 'refresh',
      'capability' => 'manage_network',
    ));

    $wp_customize->add_setting('primary-color', array(
      'default'    => WU_Settings::get_setting('primary-color'),
      'type'       => 'wu_option',
      'transport'  => 'refresh',
      'capability' => 'manage_network',
    ));

    $wp_customize->add_setting('accent-color', array(
      'default'    => WU_Settings::get_setting('accent-color'),
      'type'       => 'wu_option',
      'transport'  => 'refresh',
      'capability' => 'manage_network', 
    ));

    $wp_customize->add_setting('steps', array(
      'default'    => WU_Settings::get_setting('steps', $steps),
      'type'       => 'wu_option',
      'capability' => 'manage_network',
    ));

    // Only in the main site
    if (is_main_site()) {

      /**
       * Create the Main WP Ultimo Customizer Panel
       */
      $wp_customize->add_panel('wu_customizer_panel', array(
        'priority'     => 10,
        'title'        => __('WP Ultimo Registration', 'wp-ultimo'),
        'description'  => __('This panel offers options to customize your WP Ultimo sign up flow.', 'wp-ultimo'),
        'capability'   => 'manage_network',
        'panel'        => 'wu_customizer_panel',
      ));

      /**
       * Adding the main sections
       */
      $wp_customize->add_section('wu_section_color_and_logo' , array(
        'title'      => __('Colors & Logo', 'wp-ultimo'),
        'priority'   => 2,
        'panel'      => 'wu_customizer_panel',
        'capability' => 'manage_network',
      ));

      $wp_customize->add_section('wu_section_steps' , array(
        'title'      => __('Registration Steps', 'wp-ultimo'),
        'priority'   => 3,
        'panel'      => 'wu_customizer_panel', 
        'capability' => 'manage_network',
      ));

      $wp_customize->add_section('wu_section_fields' , array(
        'title'      => __('Signup Fields', 'wp-ultimo'),
        'priority'   => 4,
        'panel'      => 'wu_customizer_panel',
        'capability' => 'manage_network',
      ));

      $wp_customize->add_section('wu_section_custom_css' , array(
        'title'      => __('Custom CSS', 'wp-ultimo'),
        'priority'   => 5,
        'panel'      => 'wu_customizer_panel',
        'capability' => 'manage_network',
      ));

      /**
       * Controls
       */
    
      $wp_customize->add_control(new WP_Customize_Code_Editor_Control($wp_customize, 'custom-css',
				array(
          'label'       => __('Custom CSS', 'wp-ultimo'),
          'description' => __('Enter custom CSS you want to include on the sign-up and login screens of WP Ultimo.', 'wp-ultimo'),
					'section'     => 'wu_section_custom_css',
					'settings'    => 'custom-css',
					'code_type'   => 'text/css',
					'input_attrs' => array(
						'aria-describedby' => 'editor-keyboard-trap-help-1 editor-keyboard-trap-help-2 editor-keyboard-trap-help-3 editor-keyboard-trap-help-4',
					),
				)
			)
      );

      $wp_customize->add_control(new WP_Customize_Media_Control($wp_customize,
        'logo', array(
          'label'       => __('Upload a Logo', 'wp-ultimo'),
          'description' => __('Use the button below to upload and set the logo of your service. The optimal size is 320x80px.', 'wp-ultimo'),
          'section'     => 'wu_section_color_and_logo',
          'settings'    => 'logo',
        ))
      );

      $wp_customize->add_control(new WP_Customize_Media_Control($wp_customize,
        'logo-square', array(
          'label'       => __('Upload a Logo - Squared version', 'wp-ultimo'),
          'description' => __('Upload a squared version of your logo. This is used by some gateways like Stripe.', 'wp-ultimo'), 
          'section'     => 'wu_section_color_and_logo',
          'settings'    => 'logo-square',
        ))
      );

      $wp_customize->add_control('use-logo-login', array(
        'type'        => 'checkbox',
        'label'       => __('Use a different Logo for Login Page', 'wp-ultimo'),
        'description' => __('Toggle this option if you want to use a different logo on the login page. Useful for customized login screens.', 'wp-ultimo'),
        'section'     => 'wu_section_color_and_logo',
        'settings'    => 'use-logo-login',
      ));

      $wp_customize->add_control(new WP_Customize_Media_Control($wp_customize,
        'logo-login', array(
          'label'       => __('Upload a Logo for the Login Page', 'wp-ultimo'),
          'description' => __('Use the button below to upload and set the logo of your service. The optimal size is 320x80px.', 'wp-ultimo'),
          'section'     => 'wu_section_color_and_logo',
          'settings'    => 'logo-login',
        ))
      );

      $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'primary-color', array(
        'label'       => __('Primary Color', 'wp-ultimo'),
        'description' => __('Select a color to be the primary one of your network. This is used mostly in the front-end, on the pricing tables.', 'wp-ultimo'),
        'section'     => 'wu_section_color_and_logo',
        'settings'    => 'primary-color',
      )));

      $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'accent-color', array(
        'label'       => __('Accent Color', 'wp-ultimo'),
        'description' => __('Select a color to be the accent one of your network. This is used mostly in the front-end, on the pricing tables.', 'wp-ultimo'),
        'section'     => 'wu_section_color_and_logo',
        'settings'    => 'accent-color',
      )));

      $wp_customize->register_control_type('Customize_Control_Steps_Field');

      $wp_customize->add_control(new Customize_Control_Steps_Field($wp_customize, 'steps',
        array(
          'section'        => 'wu_section_steps',
          'button_message' => __('Add new Steps', 'wp-ultimo'),
          'label'          => __('Steps', 'wp-ultimo'),
          'description'    => __('Reorder and disable specific sign-up steps or add your own using the sorter below.', 'wp-ultimo'),
          'choices'        => $steps
        ))
      );

    } // end if;

  } // end add_customizer_panel;

}; // end class WU_Customizer;

new WU_Customizer;
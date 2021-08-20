<?php
/**
 * View Overwrite
 *
 * Allows users to override WP Ultimo views with their own HTML markup
 *
 * @author      Arindo Duque
 * @category    Admin
 * @package     WP_Ultimo/Views
 * @version     1.4.0
*/

if (!defined('ABSPATH')) {
  exit;
}

class WU_View_Override {

  /**
   * Adds the construct, hooks the main filter to override
   */
  public function __construct() {

    // Overwrite
    add_filter('wu_view_override', array($this, 'view_override'), 10, 3);

  } // end construct;

  /**
   * Allows us to search templates when we are not in the main site environment.
   * TODO: Can this be improved? Do we need to re-check the Template Path in here? Not sure...
   * 
   * @since 1.9.0
   * @param string|array $template_names Template file(s) to search for, in order.
	 * @param bool         $load           If true the template file will be loaded if it is found.
	 * @param bool         $require_once   Whether to require_once or require. Default true. Has no effect if $load is false.
	 * @return string The template filename if one is located.
   */
  public function custom_locate_template($template_names, $load = false, $require_once = true) {
    
    switch_to_blog(get_current_site()->blog_id);

      $stylesheet_path = get_stylesheet_directory();

    restore_current_blog();
    
    $located = '';

    foreach ((array) $template_names as $template_name) {

      if (!$template_name)
          continue;

      if ( file_exists( $stylesheet_path . '/' . $template_name)) {

        $located =  $stylesheet_path . '/' . $template_name;
        break;

      } elseif ( file_exists(TEMPLATEPATH . '/' . $template_name) ) {

        $located = TEMPLATEPATH . '/' . $template_name;
        break;

      } elseif ( file_exists( ABSPATH . WPINC . '/theme-compat/' . $template_name ) ) {

        $located = ABSPATH . WPINC . '/theme-compat/' . $template_name;
        break;

      } // end if;

    } // end if;
 
    if ($load && '' != $located) {

      load_template($located, $require_once);

    } // end if;
 
    return $located;

  } // end custom_locate_template;

  /**
   * Check if an alternative view exists and override
   * 
   * @param  string  $original_path         The original path of the view
   * @param  string  $view                  View path
   * @param  boolean $relative_to_framework Relative to framework
   * @return string                         The new path
   */
  public function view_override($original_path, $view, $relative_to_framework) {

    if (is_main_site()) {

      $found = locate_template("wp-ultimo/$view.php");

    } else {

      $found = $this->custom_locate_template("wp-ultimo/$view.php");

    } // end if;

    return $found && !$relative_to_framework ? $found : $original_path;

  } // end view_override;

} // end class WU_View_Override;

/**
 * Instantiate the class!
 */
new WU_View_Override;

/**
 * Helper Functions
 *
 * We will create some helper functions just to make the whole rendering syntax more similar to 
 * existing WordPress Plugins, like WooCommerce and etc.
 *
 * @author      Arindo Duque
 * @category    Admin
 * @package     WP_Ultimo/Views
 * @version     1.4.0
 */

/**
 * Alias function to be used on the templates
 * 
 * @param  string $view Template to be get
 * @param  array  $args Arguments to be parsed and made available inside the template file
 * @return
 */
function wu_get_template($view, $args = array()) {

  // Pass it to our core render function
  WP_Ultimo()->render($view, $args);

} // end wu_get_template;

/**
 * Alias function to be used on the templates; 
 * Rather than directly including the template, it returns the contents inside a variable
 * 
 * @param  string $view Template to be get
 * @param  array  $args Arguments to be parsed and made available inside the template file
 * @return string
 */
function wu_get_template_contents($view, $args = array()) {

  // Pass it to our core render function
  ob_start();

  WP_Ultimo()->render($view, $args);

  return ob_get_clean();

} // end wu_get_template_contents;


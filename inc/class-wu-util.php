<?php
/**
 * Ultils Class
 *
 * Contains a myriad of helper functions that we are going to use across the plugin
 *
 * @author      Arindo Duque
 * @category    Admin
 * @package     WP_Ultimo/Util
 * @version     0.0.1
*/

if (!defined('ABSPATH')) {
  exit;
}

class WU_Util {

  /**
   * Check if a given page is the login page for WordPress
   * Takes into account if plugins or themes change the login URL
   * @return boolean True if this is a login page, false if not
   */
  public static function is_login_page() {

    $ABSPATH_MY = str_replace(array('\\','/'), DIRECTORY_SEPARATOR, ABSPATH);

    return ((in_array($ABSPATH_MY.'wp-login.php', get_included_files()) || in_array($ABSPATH_MY.'wp-register.php', get_included_files()) ) || $GLOBALS['pagenow'] === 'wp-login.php' || $_SERVER['PHP_SELF']== '/wp-login.php');

  } // end is_login_page;

  /**
   * Format megabytes to clean display in pricing tables and stuuf like that
   * @param  interger $size       Number of megabytes
   * @param  string $after_suffix Postsuffix to add after the suffix
   * @return string               Number of bytes, formated in a nice way
   */
  public static function format_megabytes($size, $after_suffix = 'B') {

    $size   = $size == 0 ? 1 : $size;
    $size   = $size * 1024 * 1024;
    $base   = log($size) / log(1024);
    $suffix = array("", "k", "M", "G", "T");
    $suffix = $suffix[floor($base)];
    return round(pow(1024, $base - floor($base))) . $suffix . $after_suffix;

  } // end format_megabytes;

  /**
   * Float extractor
   * @since  1.1.5
   * @param  [type] $num [description]
   * @return [type]      [description]
   */
  public static function to_float($num) {

      $dotPos   = strrpos($num, '.');
      $commaPos = strrpos($num, ',');
      
      $sep = (($dotPos > $commaPos) && $dotPos) ? $dotPos : 
          ((($commaPos > $dotPos) && $commaPos) ? $commaPos : false);
     
      if (!$sep) {

        return floatval(preg_replace("/[^0-9]/", "", $num));

      } 

      return floatval(
        preg_replace("/[^0-9]/", "", substr($num, 0, $sep)) . '.' .
        preg_replace("/[^0-9]/", "", substr($num, $sep + 1, strlen($num)))
      );

  } // end to_float;
  
  /**
   * Generates the html markup for our tooltips
   * @param  string $text                             Text of the message to be displayed
   * @param  string [$icon                            = 'dashicons-editor-help'] Icon to be used as the tooltip icon
   * @return string HTML markup
   */
  public static function tooltip($text, $icon = 'dashicons-editor-help') {

    add_action('admin_enqueue_script', function() {
      
      wp_enqueue_style('dashicons');
      
    });

    $text = htmlspecialchars($text, ENT_QUOTES);

    return empty($text) ? '' : "<span title='$text' class='wu-tooltip dashicons $icon'></span>";

  } // end tooltip;

  /**
   * Replace the default wp_die with our version, with more options
   * @param  string  $message  Message to be displayed
   * @param  string  $title    Title of the page
   * @param  string  $redirect URL to redirect to
   * @param  integer $time     Time the redirect should take to redirect
   */
  public static function wp_die($message, $title, $redirect = false, $time = 5000, $args = array()) {

    $args = wp_parse_args($args, array(
      'response' => 200,
    ));

    if ($redirect) {
      $script = "<script type='text/javascript'>
        setInterval(function() {
          window.location.href = '$redirect';
        }, $time);
      </script>";
    } else {
      $script = '';
    }

    // Display the wp_die
    $display_title   = "<h1>$title</h1>";
    $display_message = $message;

    wp_print_scripts('jquery');

    wp_die($display_title . $display_message.$script, $title, $args);

  } // end wp_die;

  /**
   * Display Alert message to be displayed.
   *
   * @since  1.1.0
   * 
   * @param  string  $title     Title to be displayed
   * @param  string  $message   Message to be displayed
   * @param  string  $type      Type of the message
   * @param  array   $arguments Arguments to be passed to the Alert plugin
   */
  public static function display_alert($title, $message, $type = 'success', $arguments = false) {

    /** Lets set some defaults */
    $arguments = shortcode_atts(array(
      "title"             => $title,
      "text"              => $message,   
      "type"              => $type,
      "showCancelButton"  => false, 
      "confirmButtonText" => __('Ok', 'wp-ultimo'),
      "closeOnConfirm"    => true
    ), $arguments);

    // Print the code
    add_action('admin_footer', function() use($arguments) {

      printf("<script type='text/javascript'>wuswal(%s, function() {});</script>", wp_json_encode($arguments));

    });

  } // end display_alert;
  
  /**
   * Return the number of users registered today
   * TODO: generalize this for data ranges
   * @return integer User count
   */
  public static function registers_today() {
    
    $args = array(
      'date_query' => array(
        array(
          'after'  => 'today', 
          'inclusive' => true
        )
      )
    );

    $query = new WP_User_Query($args);

    return $query->get_total();
    
  } // end registers_today;

  /**
   * Return the number of users on trial
   * @return integer Number of users on trial
   */
  public static function users_on_trial() {

    _deprecated_function(__FUNCTION__, '1.5.3');

    return 100;
    
  } // end users_on_trial;

  /**
   * Filtering a array by its keys using a callback.
   * 
   * @param $array array The array to filter
   * @param $callback Callback The filter callback, that will get the key as first argument.
   * 
   * @return array The remaining key => value combinations from $array.
   */
  public static function array_filter_key(array $array, $callback) {

    $matched_keys = array_filter(array_keys($array), $callback);

    return array_intersect_key($array, array_flip($matched_keys));

  } // end array_filter_key;

  /**
   * Generate CSV file
   * @param  string $file_name
   * @param  array  $data
   * @return
   */
  public static function generate_csv($file_name, $data = array()) {

    $fp = fopen('php://output', 'w');

    if ($fp && $data) {

      header('Content-Type: text/csv');
      header('Content-Disposition: attachment; filename="'.$file_name.'.csv"');
      header('Pragma: no-cache');
      header('Expires: 0');

      foreach($data as $data_line) {

        if (is_array($data_line)) {

          fputcsv($fp, array_values($data_line));

        } else if (is_object($data_line)) {

          fputcsv($fp, array_values(get_object_vars($data_line)));

        }

      } // end while;

    } // end if;

  } // end generateCSV;

  /**
   * Handles Color management using PHP Colors
   * @param  string $hex Hexcode of a color
   * @return Color       Color object
   */
  public static function color($hex) {

    require_once WP_Ultimo()->path('inc/wu-colors.php');

    try {

      $color = new WP_Ultimo\Mexitek\PHPColors\Color($hex);

    } catch (Exception $e) {

      $color = new WP_Ultimo\Mexitek\PHPColors\Color('#f9f9f9');

    } // end try;

    return $color;

  } // end wu_color;
  
} // end class WU_Util;

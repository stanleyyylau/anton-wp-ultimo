<?php
/**
 * Login Class
 *
 * Here we replace the default WordPress login page with our own custom page
 * 
 * Reset Password on sub-site code from: https://gist.github.com/lukecav/8531e0859eae028e3c989f0f396441a9
 *
 * @since       1.7.0
 * @author      Arindo Duque
 * @category    Admin
 * @package     WP_Ultimo/Login
 * @version     0.0.1
*/

if (!defined('ABSPATH')) {
  exit;
}

class WU_Login {

  // class instance 
  static $instance;

  /**
   * Singleton
   */
  public static function get_instance() {

    if (!isset(self::$instance)) {

      self::$instance = new self();

    } // end if;

    return self::$instance;

  } // end get_instance;

  /**
   * Adds our main hooks, replacing the default sign-up
   */
  public function __construct() {

    add_action('wp_loaded', array($this, 'redirect_to_new_login_page'), 8);

    add_action('wp_loaded', array($this, 'render_login_page'), 9);

    add_filter('login_url', array($this, 'change_login_url'), 9, 3);

    add_filter('lostpassword_url', array($this, 'replace_terms_in_url'), 9);

    add_filter('lostpassword_redirect', array($this, 'change_redirect_lostpassword'));

    add_filter('wu_retrieve_password_url', array($this, 'replace_terms_in_url'));

    add_filter('site_url', array($this, 'replace_terms_in_url'));
    
    add_filter('network_site_url', array($this, 'replace_terms_in_url'));

    add_filter('wp', array($this, 'render_404'));

    add_filter('logout_url', array($this, 'get_new_logout_url'), 10, 2);

    add_filter('logout_redirect', array($this, 'get_new_logout_redirect_url'), 10, 3);

    /**
     * Reset Password on Subsite
     * These hooks should be called BEFORE our rewrite URL rules.
     */
    add_filter('lostpassword_url', array($this, 'subsite_lostpassword_url'), 9, 2);

    add_filter('network_site_url', array($this, 'replace_lostpassword_urls'), 9, 3);

    add_filter('retrieve_password_message', array($this, 'retrieve_password_message_urls'), 9);

    add_filter('retrieve_password_message', array($this, 'replace_terms_in_url'), 10);

    add_filter('retrieve_password_title', array($this, 'subsite_retrieve_password_title'));

    /**
     * Fix the logo URL
     * Since the WordPress logo redirects to wordpress.org, we need to fix that =)
     */
    add_filter('login_headerurl', array($this, 'fixes_header_url'));

    add_filter('login_headertext', array($this, 'fixes_header_text'));

  } // end construct;

  /**
   * Fixes the logo URL.
   *
   * @since 1.9.11
   * @param string $url URL to filter.
   * @return string
   */
  public function fixes_header_url($url) {

    return get_site_url( get_current_site()->blog_id );

  } // end fixes_header_url;

  /**
   * Fix the text on the logo.
   *
   * @since 1.9.11
   * @param string $text Powered by WordPress.
   * @return string
   */
  public function fixes_header_text($text) {

    return sprintf(__('Powered by %s', 'wp-ultimo'), get_site_option('site_name'));

  } // end fixes_header_text;

  /**
   * Password reset on sub site
   * Replace login page "Lost Password?" urls.
   *
   * @since 1.9.8
   * @param string $lostpassword_url The URL for retrieving a lost password.
   * @param string $redirect The path to redirect to.
   * @return string
   */
  function subsite_lostpassword_url($lostpassword_url, $redirect) {

    $args = array('action' => 'lostpassword');

    if (!empty($redirect)) {

      $args['redirect_to'] = $redirect;

    } // end if;

    return esc_url(add_query_arg($args, site_url('wp-login.php')));

  } // end subsite_lostpassword_url;

  /**
   * Password reset on sub site (2 of 4)
   * Replace other "unknown" password reset urls.
   *
   * @since 1.9.8
   * @param string $url The complete network site URL including scheme and path.
   * @return string
   */
  function replace_lostpassword_urls($url) {

    if (stripos($url, 'action=lostpassword') !== false) {

      return site_url('wp-login.php?action=lostpassword');
      
    } // end if;

    if (stripos($url, 'action=resetpass') !== false) {

      return site_url('wp-login.php?action=resetpass');
      
    } // end if;

    return $url;
    
  } // end replace_lostpassword_urls;

  /**
   * Password reset on sub site (3 of 4)
   * Fixes the URLs in emails that are sent.
   *
   * @since 1.9.8
   * @param string $message Default mail message.
   * @return string
   */
  function retrieve_password_message_urls($message) {

    global $current_site;

    return str_replace(get_site_url($current_site->blog_id), get_site_url(), $message);
    
  } // end retrieve_password_message_urls;        

  /**
   * Password reset on sub site (4 of 4)
   * Fixes the title in emails that are sent.
   *
   * @since 1.9.8
   * @return string
   */
  function subsite_retrieve_password_title() {

    return sprintf(__('[%s] Password Reset'), wp_specialchars_decode(get_option('blogname'), ENT_QUOTES));
  
  } // end subsite_retrieve_password_title;

  /**
   * Replace the redirect action of the change password form
   *
   * @since 1.7.3
   * @param string $url
   * @return string
   */
  public function change_redirect_lostpassword($url) {

    if ($url) return $this->replace_terms_in_url($url);

    return $this->replace_terms_in_url('wp-login.php?checkemail=confirm');

  } // end change_redirect_lostpassword;

  /**
   * Replace the wp-login.php url with the new slug in all links
   *
   * @since 1.7.0
   * @param  string $url
   * @return string
   */
  public function replace_terms_in_url($url) {

    if (!$this->should_replace_login_page()) {

      return $url;

    } // end if;

    $url = str_replace('wp-login.php', $this->get_new_login_slug(), $url);

    return $url;

  } // end replace_terms_in_url;

  /**
   * Changes the login URL to the new URL, if necessary
   *
   * @since 1.7.0
   * @param  string $login_url
   * @param  bool   $redirect
   * @param  bool   $force_reauth
   * @return string
   */
  public function change_login_url($login_url, $redirect, $force_reauth) {

    $should_obfuscate = $this->check_url(array('wp-admin', 'network')) && WU_Settings::get_setting('obfuscate_original_login_url');

    if (!$this->should_replace_login_page() || $should_obfuscate) {

      return $login_url;

    } // end if;
    
    $login_url = site_url($this->get_new_login_slug(), 'login');
	
    if (!empty($redirect)) {

      $login_url = add_query_arg('redirect_to', urlencode($redirect), $login_url);

    } // end if;

    if ($force_reauth) {

      $login_url = add_query_arg('reauth', '1', $login_url);

    } // end if;
    
    return $login_url;

  } // end change_login_url;

  /**
   * Check if the current browser URL has is one of the ones present on the replace list
   *
   * @since 1.7.0
   * @param array $replace_list
   * @return boolean
   */
  public function check_url($replace_list) {

    $found = false;

    $parsed_url = parse_url($_SERVER['REQUEST_URI']);

    if (!isset($parsed_url['path'])) {

      return $found;

    } // end if;

    array_map(function($item) use ($parsed_url, &$found) {

      $exploded = explode('/', trim($parsed_url['path'], '/'));

      $check_against = array_pop($exploded);

      if ($item == trim($check_against, '/')) {

        $found = true;

        return;

      } // end array_map

    }, $replace_list);

    return $found;

  } // end check_url;

  /**
   * Check if this is one of the login pages we want to replace
   *
   * @since 1.7.0
   * @return boolean
   */
  public function is_login_page() {

    $replace_list = apply_filters('wu_replace_login_urls', array('wp-login', 'wp-login.php'));

    return $this->check_url($replace_list);    

  } // end is_register_page;

  /**
   * Check if we are already in the new login page
   *
   * @since 1.7.0
   * @return boolean
   */
  public function is_new_login_page() {

    if (!WU_Settings::get_setting('login_url')) return;

    $replace_list = array($this->get_new_login_slug());

    return $this->check_url($replace_list);

  } // end is_register_page;

  /**
   * Check if we should replace the login page at all
   *
   * @since 1.7.0
   * @return boolean
   */
  public function should_replace_login_page() {

    return WU_Settings::get_setting('login_url');

  } // end should_replace_login_page;

  /**
   * Get the new login URL, pass the new login URL
   *
   * @since 1.7.0
   * @return string
   */
  public function get_new_login_url() {

    $return_url = site_url('/', 'login') . $this->get_new_login_slug();

    $parsed_url = parse_url($_SERVER['REQUEST_URI']);

    if (isset($parsed_url['query'])) {

      $return_url .= "?" . $parsed_url['query'];

    } // end if;

    return $return_url;

  } // end get_new_login_url;

  /**
   * Get new logout URL and replace
   *
   * @since 1.7.3
   * @param string $logout_url
   * @param string $redirect
   * @return void
   */
  public function get_new_logout_url($logout_url, $redirect) {

    if (!$this->should_replace_login_page()) return $logout_url;

    $args = array('action' => 'logout');

    if (!empty($redirect)) {

      $args['redirect_to'] = urlencode($redirect);

    } // end if;

    $logout_url = add_query_arg($args, site_url($this->get_new_login_slug(), 'login'));

    $logout_url = wp_nonce_url($logout_url, 'log-out');

    return $logout_url;

  } // end get_new_logout_url;

  /**
   * Get the new redirect to URL after the signout
   *
   * @since 1.7.3
   * @param string $redirect_to
   * @param string $requested_redirect_to
   * @param WP_User $user
   * @return string
   */
  public function get_new_logout_redirect_url($redirect_to, $requested_redirect_to, $user) {

    if (!$this->should_replace_login_page()) return $redirect_to;

    if ($requested_redirect_to) return $requested_redirect_to;
    
    return site_url($this->get_new_login_slug()) . '?loggedout=true';

  } // end get_new_logout_redirect_url;

  /**
   * Get the new login slug
   *
   * @since 1.7.0
   * @return string
   */
  public function get_new_login_slug() {

    return trim(WU_Settings::get_setting('login_url'), '/');

  } // end get_new_login_slug;

  /**
   * Get the fake 404 URL address
   * 
   * @since 1.7.0
   * @return string
   */
  public function get_404_url() {

    return site_url('/?wu=404', 'login');

  } // end get_404_url;

  /**
   * Sets the WordPress query to 404, if we are in the ?wu=404 page
   *
   * @since 1.7.0
   * @return void
   */
  public function render_404() {
    
    global $wp_query;

    if (!isset($_GET['wu']) || $_GET['wu'] !== '404') return;
  
    status_header(404);

    $wp_query->set_404();
    
  } // end render_404;

  /**
   * Checks if we are in the logout action
   *
   * @since 1.7.3
   * @return boolean
   */
  public function is_logout_action() {

    return isset($_GET['action']) && $_GET['action'] == 'logout';

  } // end is_logout_action;

  /**
   * Redirect the old login to the new login page
   *
   * @since 1.7.0
   * @return void
   */
  public function redirect_to_new_login_page() {

    global $pagenow;

    if ( empty($_POST) && !$this->is_new_login_page() && ($this->is_login_page() && $this->should_replace_login_page()) )  {

      if (WU_Settings::get_setting('obfuscate_original_login_url')) {

        return wp_safe_redirect($this->get_404_url());

        exit;

      } // end if;

      wp_safe_redirect($this->get_new_login_url());

      exit;

    } // end if;

  } // end redirect_to_new_login_page;

  /**
   * Render the login page
   *
   * @since 1.7.0
   * @return void
   */
  public function render_login_page() {

    if ($this->is_new_login_page()) {

      WU_Signup::exclude_from_caching();

      @require_once ABSPATH . 'wp-login.php';

	    die;

    } // end if;

  } // end render_login_page;

} // end class WU_Login;

// Run our Class
WU_Login::get_instance();

/**
 * Return the instance of the function
 */
function WU_Login() {

  return WU_Login::get_instance();

} // end WU_Login;

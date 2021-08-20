<?php
/**
 * Light Ajax Implementation
 *
 * Helper class that mimics the Admin Ajax URL.
 * Based on the code found on: https://coderwall.com/p/of7y2q/faster-ajax-for-wordpress
 *
 * @since       1.9.14
 * @author      Arindo Duque
 * @category    Admin
 * @package     WP_Ultimo/Light_Ajax
 * @version     0.0.1
 */

if (!defined('ABSPATH')) {

	exit;

} // end if;

/**
 * Adds a lighter ajax option to WP Ultimo.
 *
 * @since 1.9.14
 */
class WU_Light_Ajax {

	/**
	 * Makes sure we are only using one instance of the class
	 *
	 * @var object WU_Light_Ajax
	 */
	public static $instance;

	/**
	 * Returns the instance of WP_Ultimo
	 *
	 * @return object A WU_Light_Ajax instance
	 */
	public static function get_instance() {

		if (null === self::$instance) {

			self::$instance = new self();

		} // end if;

		return self::$instance;

	} // end get_instance;

	/**
	 * Set the links
	 */
	public function __construct() {

		if (isset($_REQUEST['wu-ajax'])) {

			$this->process_light_ajax();

		} // end if;

	} // end __construct;

	/**
	 * Adds an wu_ajax handler.
	 *
	 * @since 1.9.14
	 * @return void
	 */
	public function process_light_ajax() {

		// mimic the actuall admin-ajax
		define('DOING_AJAX', true);

		if (!isset($_REQUEST['action'])) {

			die('-1');

		} // end if;

		// Typical headers
		header('Content-Type: text/html');

		send_nosniff_header();

		// Disable caching
		header('Cache-Control: no-cache');

		header('Pragma: no-cache');

		$action = esc_attr(trim($_REQUEST['action']));

		// A bit of security
		$allowed_actions = array(
			'wu_count_visits',
		);

		if (in_array($action, $allowed_actions)) {

			if (is_user_logged_in()) {

				do_action('wp_ajax_' . $action);

			} else {

				do_action('wp_ajax_nopriv_' . $action);

			} // end if;

			die('1');

		} else {

			die('-1');

		} // end if;

	} // end process_light_ajax;

} // end class WU_Light_Ajax;

/**
 * Returns the singleton
 */
function WU_Light_Ajax() { // phpcs:ignore

	return WU_Light_Ajax::get_instance();

} // end WU_Light_Ajax;

// Initialize
WU_Light_Ajax();

<?php
/**
 * Gutenberg Support
 *
 * Allows WP Ultimo to filter Gutenberg thingys.
 *
 * @since       1.9.14
 * @author      Arindo Duque
 * @category    Admin
 * @package     WP_Ultimo/Gutenberg
 * @version     0.0.1
 */

if (!defined('ABSPATH')) {

	exit;

} // end if;

/**
 * Adds support to Gutenberg filters.
 *
 * @since 2.0.0
 */
class WU_Gutenberg_Support {

	/**
	 * Makes sure we are only using one instance of the class
	 *
	 * @var object WU_Gutenberg_Support
	 */
	public static $instance;

	/**
	 * Returns the instance of WU_Gutenberg_Support
	 *
	 * @return object A WU_Gutenberg_Support instance
	 */
	public static function get_instance() {

		if (null === self::$instance) {

			self::$instance = new self();

		} // end if;

		return self::$instance;

	} // end get_instance;

	/**
	 * Filterable function that let users decide if they want to remove
	 * Gutenberg support and modifications by Ultimo.
	 *
	 * @since 1.9.14
	 * @return bool
	 */
	public function should_load() {

		if (function_exists('has_blocks')) {

			return true;

		} // end if;

		return apply_filters('wu_gutenberg_support_should_load', true);

	} // end should_load;

	/**
	 * Add the hooks for Gutenberg
	 */
	public function __construct() {

		if ($this->should_load()) {

			add_action('admin_enqueue_scripts', array($this, 'add_scripts'));

		} // end if;

	} // end __construct;

	/**
	 * Adds the Gutenberg Filters scripts.
	 *
	 * @since 1.9.14
	 * @return void
	 */
	public function add_scripts() {

		$suffix = WP_Ultimo()->min;

		wp_register_script('wu-gutenberg-support', WP_Ultimo()->get_asset("wu-gutenberg-support$suffix.js", 'js'), array('jquery'), WP_Ultimo()->version, true);

		$replacement_preview_message = apply_filters('wu_gutenberg_support_preview_message', sprintf(__('<strong>%s</strong> is generating the preview...', 'wp-ultimo'), get_network_option(null, 'site_name')));

		wp_localize_script('wu-gutenberg-support', 'wu_gutenberg', array(
			'logo'                => esc_url(WU_Settings::get_logo()),
			'replacement_message' => $replacement_preview_message,
		));

		wp_enqueue_script('wu-gutenberg-support');

	} // end add_scripts;

} // end class WU_Gutenberg_Support;

/**
 * Returns the singleton
 */
function WU_Gutenberg_Support() { // phpcs:ignore

	return WU_Gutenberg_Support::get_instance();

} // end WU_Gutenberg_Support;

// Initialize
WU_Gutenberg_Support();

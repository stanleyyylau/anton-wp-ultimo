<?php
/**
 * WPMU DEV Pro Sites Support.
 *
 * Our Pro Sites migrator requires a bit of adjustments to WP Ultimo.
 * This class handles this.
 *
 * @since       1.9.11
 * @author      Arindo Duque
 * @category    Admin
 * @package     WP_Ultimo/Compat/Pro_Sites
 * @version     0.0.1
 */

if (!defined('ABSPATH')) {
	exit;
} // end if;

/**
 * Our Pro Sites migrator requires a bit of adjustments to WP Ultimo.
 * This class handles this.
 */
class WU_Pro_Sites_Support {

	/**
	 * Makes sure we are only using one instance of the class.
	 *
	 * @since 1.9.11
	 * @var WU_Pro_Sites_Support
	 */
	public static $instance;

	/**
	 * Returns the instance of WP_Ultimo
	 *
	 * @return object A WU_Pro_Sites_Support instance
	 */
	public static function get_instance() {

		if (null === self::$instance) {

			self::$instance = new self();

		} // end if;

		return self::$instance;

	} // end get_instance;

	/**
	 * Initializes the class
	 */
	public function __construct() {

		/**
		 * For PayPal, we need to make sure we have a way of identifying the user that is receiving the payment.
		 * This filter will check if no user was found and try to attemp finding it via the Pro Sites method.
		 */
		add_filter('wu_paypal_get_target_subscription', array($this, 'get_paypal_user_id_from_pro_sites_indentifier'), 9, 2);

		/**
		 * WP Ultimo deals with multiple sites differently then Pro Sites.
		 * This filter makes sure we override the sites limits, so we can make sure users' sites won't get blocked after
		 * the migration is finished.
		 */
		add_filter('wu_plan_get_quota', array($this, 'bypass_site_quota_for_prosites'), 9, 4);

		/**
		 * Adds support to WPMU DEV Hosting.
		 */
		add_action('mercator.mapping.created', array($this, 'add_domain_to_hosting'), 22);
		// add_action('mercator.mapping.updated', array($this, 'add_domain_to_wpengine'), 22); // TODO
		// add_action('mercator.mapping.deleted', array($this, 'remove_domain_from_wpengine'), 22); // TODO

	} // end __construct;

	/**
	 * Override the sites quotas for subscriptions that came from Prosites.
	 *
	 * @since 1.9.11
	 * @param int     $quota      The current quota value of the plan.
	 * @param string  $quota_type The quota being retrieved.
	 * @param array   $quotas     The list of all the quotas and values for that plan.
	 * @param WU_Plan $plan       The WP_Plan object being referenced.
	 * @return int
	 */
	public function bypass_site_quota_for_prosites($quota, $quota_type, $quotas, $plan) {

		if ($quota_type !== 'sites' || !is_admin()) {

			return $quota;

		} // end if;

		$subscription = wu_get_current_site()->get_subscription();

		if (!$subscription) {

			return $quota;

		} // end if;

		$sites = (int) $subscription->get_meta('prosites_count');

		return $quota >= $sites ? $quota : $sites;

	} // end bypass_site_quota_for_prosites;

	/**
	 * Gets the Pro Sites user based on the identifier.
	 *
	 * @since 1.9.11
	 * @param mixed  $user_id User ID identified by PayPal.
	 * @param object $ipn IPN call sent by PayPal.
	 * @return mixed
	 */
	public function get_paypal_user_id_from_pro_sites_indentifier($user_id, $ipn) {

		global $wpdb;

		if ($user_id || !property_exists($ipn, 'custom')) {

			return $user_id;

		} // end if;

		/**
		 * Get the identifier from the IPN
		 */
		$reference = end(explode('_', $ipn->custom));

		$query = $wpdb->prepare("SELECT user_id from {$wpdb->base_prefix}wu_subscriptions WHERE meta_object LIKE %s", '%' . $reference . '%');

		return (int) $wpdb->get_var($query);

	} // end get_paypal_user_id_from_pro_sites_indentifier;


	/**
	 * Checks if we are in a WPMU Dev environment.
	 *
	 * @since 1.9.11
	 * @return bool
	 */
	public function uses_wpmudev_hosting() {

		return defined('WPMUDEV_HOSTING_SITE_ID');

	}  // end uses_wpmudev_hosting;

	/**
	 * Adds a mapped domain to WPMU DEV hosting.
	 *
	 * @since 1.9.11
	 * @param Mercator\Mapping $mapping The mapping added.
	 * @return void
	 */
	public function add_domain_to_hosting($mapping) {

		$domain = $mapping->get_domain();

		if (!$this->uses_wpmudev_hosting() || !$domain) {

			return;

		} // end if;

		$site_id = WPMUDEV_HOSTING_SITE_ID;

		$api_key = get_site_option('wpmudev_apikey');

		$domains = array($domain);

		if (strpos($domain, 'www.') !== 0) {

			$domains[] = "www.$domain";

		} // end if;

		foreach ($domains as $_domain) {

			$response = wp_remote_post("https://premium.wpmudev.org/api/hosting/v1/$site_id/domains", array(
				'timeout' => 50,
				'body'    => array(
					'domain'  => $_domain,
					'site_id' => $site_id,
				),
				'headers' => array(
					'Authorization' => $api_key,
				),
			));

			if (is_wp_error($response)) {

				WU_Logger::add('wpmudev-hosting', sprintf(__('An error occurred while trying to add the custom domain %s to WPMU Dev hosting.', 'wp-ultimo'), $_domain));

			} // end if;

			$body = json_decode(wp_remote_retrieve_body($response));

			if ($body->message) {

				WU_Logger::add('wpmudev-hosting', sprintf(__('An error occurred while trying to add the custom domain %1$s to WPMU Dev hosting: %2$s', 'wp-ultimo'), $_domain, $body->message->message));

			} else {

				WU_Logger::add('wpmudev-hosting', sprintf(__('Domain %s added to WPMU Dev hosting successfully.', 'wp-ultimo'), $_domain));

			} // end if;

		} // end foreach;

	} // end add_domain_to_hosting;

} // end class WU_Pro_Sites_Support;

/**
 * Returns the singleton
 */
function WU_Pro_Sites_Support() { // phpcs:ignore

	return WU_Pro_Sites_Support::get_instance();

} // end WU_Pro_Sites_Support;

// Initialize
WU_Pro_Sites_Support();

<?php
/**
 * Notifications Class Init
 *
 * Handles notifications to the user when needed
 *
 * @since       1.5.5
 * @author      Arindo Duque
 * @category    Admin
 * @package     WP_Ultimo/Notifications
*/

if (!defined('ABSPATH')) {
  exit;
}

class WU_Notification {

  /**
   * Adds necessary hooks, mostly to the Ultimo's cron-job
   */
  public function __construct() {

    add_action('wu_cron', array($this, 'notify_expiring_trials'));

    add_action('wu_cron', array($this, 'notify_expiring_subscriptions'));
    
    add_action('wu_cron', array($this, 'notify_expired_trials'));

    add_action('wu_cron', array($this, 'notify_expired_subscriptions'));

    add_filter('wu_subscription_before_save', array($this, 'reset_sent_notification_status'), 10, 2);

    add_filter('wu_settings_section_emails', array($this, 'add_settings'));

  } // end construct;

  /**
   * Add settings for the expiring notification emails
   *
   * @since 1.5.5
   * @param array $settings
   * @return array
   */
  function add_settings($settings) {
    
    $notification_settings = array(

      'expiring_days_header' => array(
        'title'        => __('Expiring Notification Settings', 'wp-ultimo'),
        'desc'         => __('Change the settings for the expiring notification (trials and subscriptions) emails.', 'wp-ultimo'),
        'type'         => 'heading',
      ),

      'expiring_days' => array(
        'title'         => __('Days to Expire', 'wp-ultimo'),
        'desc'          => __('Select when we should send the notification email. If you select 3 days, for example, a notification email will be sent to every subscription (or trial period) expiring in the next 3 days. Subscriptions are checked hourly.', 'wp-ultimo'),
        'type'          => 'number',
        'style'         => 'width: 50px;',
        'tooltip'       => '',
        'default'       => 3,
      ),

    );

    return array_merge($settings, $notification_settings);

  } // end add_settings;

  /**
   * Notify Expiring Trials
   *
   * @since 1.5.5
   * @return void
   */
  public function notify_expiring_trials() {

    return $this->notify_expiring('trial_expiring');

  } // end notify_expiring_trials;

  /**
   * Notify Expired Trials in the last 3 days
   *
   * @since 1.5.5
   * @return void
   */
  public function notify_expired_trials() {

    return $this->notify_expired('trial_expired');

  } // end notify_expired_trials;
  
  /**
   * Notify Expiring Subscriptions
   *
   * @since 1.5.5
   * @return void
   */
  public function notify_expiring_subscriptions() {

    return $this->notify_expiring('subscription_expiring');

  } // end notify_expiring_trials;

  /**
   * Notify Expired Subscriptions in the last 3 days
   *
   * @since 1.5.5
   * @return void
   */
  public function notify_expired_subscriptions() {

    return $this->notify_expired('subscription_expired');

  } // end notify_expired_subscriptions;

  /**
   * Notify EXPIRING subscriptions 
   *
   * @since 1.5.5
   * @return void
   */
  public function notify_expiring($type = 'subscription_expiring') {

    global $wpdb;

    $table_name = WU_Subscription::get_table_name();

    $days_to_check = WU_Settings::get_setting('expiring_days', 3);

    /**
     * SQL Code
     */
    if ($type == 'subscription_expiring') {

      // Subscription Expiring
      $sql = "SELECT sub.user_id FROM $table_name sub 
      WHERE sub.active_until >= NOW()
      AND (sub.integration_status != 1 OR sub.integration_status IS NULL) 
      AND DATE_ADD(NOW(), INTERVAL $days_to_check DAY) >= sub.active_until";

    } 

    else if ($type == 'trial_expiring') {

      // Trial Ending
      $sql = "SELECT sub.user_id FROM $table_name sub 
      WHERE DATE_ADD(sub.created_at, INTERVAL sub.trial DAY) >= NOW() 
      AND DATE_ADD(NOW(), INTERVAL $days_to_check DAY) >= DATE_ADD(sub.created_at, INTERVAL sub.trial DAY)
      AND (sub.integration_status != 1 OR sub.integration_status IS NULL)";

    } // end if;

    $results = $wpdb->get_col($sql);

    // Convert them to Subscription
    $subscriptions = array_map('wu_get_subscription', $results);

    $subscriptions = array_filter($subscriptions, array($this, 'check_if_notify_email_was_sent'));

    /**
     * We need to send mails from the main site.
     */
    switch_to_blog(get_current_site()->blog_id);

    $results = array_map(function($subscription) use ($type, $days_to_check) {

      /**
       * Sends the right type of email
       */
      return $this->send_notification_email($subscription, $type, $days_to_check);

    }, $subscriptions);

    restore_current_blog();

    return $results;

  } // end notify_expiring;

  /**
   * Notify EXPIRED subscriptions 
   *
   * @since 1.5.5
   * @return void
   */
  public function notify_expired($type = 'subscription_expired') {

    global $wpdb;

    $table_name = WU_Subscription::get_table_name();

    $days_to_check = apply_filters('wu_days_to_check_expired', 1);

    /**
     * SQL Code
     */
    if ($type == 'subscription_expired') {

      // Subscription Expiring
      $sql = "SELECT sub.user_id FROM $table_name sub 
      WHERE sub.active_until < DATE_SUB(NOW(), INTERVAL 2 HOUR)
      -- AND (sub.integration_status != 1 OR sub.integration_status IS NULL) 
      AND sub.active_until >= DATE_SUB(NOW(), INTERVAL $days_to_check DAY)";

    } 

    else if ($type == 'trial_expired') {

      // Trial Ending
      $sql = "SELECT sub.user_id FROM $table_name sub 
      WHERE DATE_ADD(sub.created_at, INTERVAL sub.trial DAY) < NOW() 
      AND sub.trial > 0 
      AND DATE_ADD(sub.created_at, INTERVAL sub.trial DAY) >= DATE_SUB(NOW(), INTERVAL $days_to_check DAY)
      AND (sub.integration_status != 1 OR sub.integration_status IS NULL)";

    } // end if;

    $results = $wpdb->get_col($sql);

    // Convert them to Subscription
    $subscriptions = array_map('wu_get_subscription', $results);

    $subscriptions = array_filter($subscriptions, array($this, 'check_if_notify_email_was_sent'));

    /**
     * We need to send mails from the main site.
     */
    switch_to_blog(get_current_site()->blog_id);

    $results = array_map(function($subscription) use ($type, $days_to_check) {

      /**
       * Sends the right type of email
       */
      return $this->send_notification_email($subscription, $type, $days_to_check);

    }, $subscriptions);

    restore_current_blog();
    
    return $results;

  } // end notify_expiring;

  /**
   * Checks if the email for notify was send
   *
   * @since 1.5.5
   * @param object $subscription
   * @return bool
   */
  public function check_if_notify_email_was_sent($subscription) {

    return $subscription && !$subscription->get_meta('sent_notification_email') && !$subscription->is_free();

  } // end check_if_notify_email_was_sent;

  /**
   * Get the email type: Expiring and Expired
   *
   * @since 1.5.5
   * @param string $email_type
   * @return string
   */
  public function get_email_type($email_type) {

    return in_array( $email_type, array('subscription_expiring', 'trial_expiring') ) ? 'expiring' : 'expired';

  } // end get_email_type;

  /**
   * Calculate the days since the subscription expired
   *
   * @since 1.5.5
   * @param WU_Subscription $subscription
   * @return void
   */
  public function calculate_days_expired_since($subscription) {

    $now = WU_Transactions::get_current_time('timestamp');
    
    $active_until = (int) $subscription->get_date('active_until', 'U');

    return absint(ceil(($now - $active_until) / DAY_IN_SECONDS));

  } // end calculate_days_expired_since;

  /**
   * Send the notification email to expiring, expired and trial expiring subscription
   *
   * @since 1.5.5
   * @param array $subscription
   * @param string $notification_type
   * @return bool
   */
  public function send_notification_email($subscription, $notification_type = 'subscription_expiring', $days) {

    $user = get_user_by('id', $subscription->user_id);

    $primary_site = get_active_blog_for_user( $subscription->user_id );

    $email_shortcodes = array(
      'user_name'                    => $user->display_name,
      'user_account_page_link'       => get_admin_url($primary_site->blog_id, 'admin.php?page=wu-my-account'),
      'subscription_management_link' => $subscription->get_manage_url(),
    );

    /**
     * There are different parameters for different types, like expiring and expired
     */
    if ($this->get_email_type($notification_type) == 'expiring') {
      $email_shortcodes['days_to_expire'] = $this->calculate_days_expired_since($subscription);
    } else {
      $email_shortcodes['days_expired_since'] = $this->calculate_days_expired_since($subscription);
    } // end if;

    WU_Mail()->send_template($notification_type, $user->user_email, $email_shortcodes);

    /**
     * If expired, send the admin email as well
     * @since 1.7.3
     */
    if ($notification_type == 'subscription_expired') {

      WU_Mail()->send_template('subscription_expired_admin', get_network_option(null, 'admin_email'), $email_shortcodes);

    } // end if;

    $subscription->set_meta('sent_notification_email', $subscription->get_date('active_until', 'U'));

  } // end send_expiring_email;

  /**
   * Reset the notification status
   *
   * @since 1.5.5
   * @param array $subscription_to_save
   * @param WU_Subscription $subscription
   * @return array
   */
  public function reset_sent_notification_status($subscription_to_save, $subscription) {

    $seconds_to_add = apply_filters('wu_days_to_check_expired', 1) * DAY_IN_SECONDS;

    $reset_date = WU_Transactions::get_current_time('timestamp') + $seconds_to_add;

    if ($subscription->get_meta('sent_notification_email') && $subscription->get_date('active_until', 'U') > $reset_date) {

      $subscription->set_meta('sent_notification_email', 0);

    } // end if;

    return $subscription_to_save;

  } // end reset_sent_notification_status;

} // end class WU_Notification;

new WU_Notification;
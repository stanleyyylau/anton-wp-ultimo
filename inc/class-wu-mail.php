<?php
/**
 * Mailing Class
 *
 * Handles the seding of emails of the platform
 *
 * @author      Arindo Duque
 * @category    Admin
 * @package     WP_Ultimo/Mail
 * @version     0.0.1
 */

if (!defined('ABSPATH')) {
  exit;
}

class WU_Mail {

  /**
   * Hold the templates of emails to be sent
   * @var array
   */
  public $templates = array(); 

  /**
   * Holds the shortcodes
   * @var array
   */
  public $shortcodes = array();

  /**
   * Makes sure we are only using one instance of the plugin
   * @var object WU_Ultimo
   */
  public static $instance;

  /**
   * Returns the instance of WP_Ultimo
   * @return object A WU_Mail instance
   */
  public static function get_instance() {
    if (null === self::$instance) self::$instance = new self();
    return self::$instance;
  } // end get_instance;

  /**
   * Set the important hooks
   */
  function __construct() {

    // Get network name
    $network_name  = get_site_option('site_name');

    $this->shortcodes = apply_filters('wu_email_shortcodes', array(
      'site_url'  => get_site_url(get_current_site()->blog_id),
      'site_name' => $network_name,
      'logo_url'  => WU_Settings::get_logo(),
      'content'   => '',
      'subject'   => '',
    ));

    // Add our settings - including the option to edit email templates
    add_filter('wu_settings_section_emails', array($this, 'add_settings'));

    add_filter('wpmu_signup_user_notification', array($this, 'send_invite_email_new_user'), 1, 4);

    /**
     * @since 1.4.3 reset password email now uses our templates
     */
    add_filter('retrieve_password_message', array($this, 'replace_reset_password_mail'), 10, 4);

    // WooCommerce reset Password
    add_action('woocommerce_email', function($mail_class) {
      
      if (!is_main_site()) return;
      global $wp_filter;
      unset($wp_filter['woocommerce_reset_password_notification']->callbacks[10]);
      
    });

    // Add new email sending for woocommerce
    add_action('woocommerce_reset_password_notification', array($this, 'replace_reset_password_mail_wc'), 100, 2);

    // Bring the invite email over to our templating styles
    add_action('invite_user', array($this, 'send_invite_email'), 1, 3); // @since 1.6.2
    add_filter('wpmu_welcome_user_notification', array($this, 'replace_welcome_email'), 1, 3); // @since 1.6.2

    add_action('wp_mail_failed', array($this, 'log_mailer_failure'));

  } // end construct;

  /**
   * Log failures on the WordPress mailer, just so we have a copy of the issues for debugging.
   *
   * @since 1.9.14
   * @param WP_Error $error The error with the mailer.
   * @return void
   */
  public function log_mailer_failure($error) {

    if (is_wp_error($error)) {

      WU_Logger::add('mailer-errors', $error->get_error_message());

    } // end if;

  } // end log_mailer_failure;

  /**
   * Add Email template settings to the Settings Array
   * @param array $fields Email fields contained already
   */
  function add_settings($fields) {

    // Set the array containing the fields
    $template_fields = array();

    // Load shortcodes
    $shortcodes = '';

    foreach ($this->shortcodes as $shortcode => $shortcode_replace) {
      if ($shortcode == 'content') continue;
      $shortcodes .= sprintf('<code>{{%s}}</code> ', $shortcode);
    }

    foreach ($this->templates as $template_slug => $template) {

      $is_enabled = WU_Settings::get_setting('email_'.$template_slug.'_enabled');

      // Check enable and save it as default
      if ($is_enabled === '') {
        WU_Settings::save_setting('email_'.$template_slug.'_enabled', true);
      }

      // Collapsible Heading
      $template_fields[$template_slug.'_heading'] = array(
        'title'  => sprintf('<strong>%s</strong> <code>%s</code> %s %s', $template['name'], $template_slug, $template['admin'] ? '<code>admin</code>' : '', WU_Util::tooltip(__('Click on this tab to open this template\'s options', 'wp-ultimo'))),
        'desc'   => __('You can make changes to the site templates here.', 'wp-ultimo'),
        'type'   => 'heading_collapsible',
        'active' => $is_enabled
      );

      // Enabled
      $template_fields['email_'.$template_slug.'_enabled'] = array(
        'title'         => __('Enable this Email', 'wp-ultimo'),
        'desc'          => __('Un-check this box if you do not want your users to receive this email.', 'wp-ultimo'),
        'tooltip'       => '',
        'type'          => 'checkbox',
        'default'       => $template['enabled'],
      );

      // Subject
      $template_fields['email_'.$template_slug.'_subject'] = array(
        'title'       => __('Subject', 'wp-ultimo'),
        'desc'        => __('Change the subject of this email.', 'wp-ultimo'),
        'tooltip'     => '',
        'type'        => 'text',
        'placeholder' => $template['subject'], 
        'default'     => $template['subject'],   
      );

      /**
       * Add the shortcodes
       */
      $shortcodes2 = '';
      foreach ($template['shortcodes'] as $shortcode => $shortcode_replace) {
        if ($shortcode == 'subject') continue;
        $shortcodes2 .= sprintf('<code>{{%s}}</code> ', $shortcode);
      }

      $template_fields['email_'.$template_slug.'_content'] = array(
        'title'       => __('Content', 'wp-ultimo'),
        'desc'        => __('Change the content of this email. You can use the shortcodes listed below.', 'wp-ultimo').'<br>'.$shortcodes.$shortcodes2,
        'tooltip'     => '',
        'type'        => 'wp_editor',
        'placeholder' => $template['content'],
        'default'     => $template['content'], 
        'args'          => array(
          'media_buttons' => false,
          'wpautop'       => true,
          'editor_height' => 300,
        ),
      );

    }

    /**
     * Add the sender and from email
     */
    $sender_fields = array();

    $sender_fields['sender_fields'] = array(
      'title'        => __('Sender Settings', 'wp-ultimo'),
      'desc'         => __('Change the settings of the email headers, like from and name.', 'wp-ultimo'),
      'type'         => 'heading',
    );

    // Sender name
    $sender_fields['from_name'] = array(
      'title'         => __('"From" Name', 'wp-ultimo'),
      'desc'          => __('How the sender name will appear in emails sent by WP Ultimo.', 'wp-ultimo'),
      'type'          => 'text',
      'placeholder'   => get_network_option(null, 'site_name'),
      'tooltip'       => '',
      'default'       => get_network_option(null, 'site_name'),
    );

    // Sender name
    $sender_fields['from_email'] = array(
      'title'         => __('"From" Email', 'wp-ultimo'),
      'desc'          => __('How the sender email will appear in emails sent by WP Ultimo.', 'wp-ultimo'),
      'type'          => 'email',
      'placeholder'   => get_network_option(null, 'admin_email'),
      'tooltip'       => '',
      'default'       => get_network_option(null, 'admin_email'),
    );

    /**
     * @since  1.3.4 We add the option - filterable - to how to send emails
     */
    $style_fields = array();

    $style_fields['style_fields'] = array(
      'title'        => __('Template Settings', 'wp-ultimo'),
      'desc'         => __('Change the settings of the email templates.', 'wp-ultimo'),
      'type'         => 'heading',
    );

    // Sender name
    $style_fields['email_template_style'] = array(
      'title'         => __('Email Templates Style', 'wp-ultimo'),
      'desc'          => __('Select the style WP Ultimo should use when sending out emails.', 'wp-ultimo'),
      'type'          => 'select',
      'tooltip'       => '',
      'default'       => 'html',
      'options'       => array(
        'html'        => __('HTML Emails', 'wp-ultimo'),
        'plain'       => __('Plain Emails', 'wp-ultimo'),
      ),
    );

    // Merge all
    return array_merge($fields, $template_fields, $sender_fields, $style_fields);

  } // end add_settings;

  /**
   * Register template of a certain email
   * @param  string $slug    Indentifier of this template
   * @param  string $subject Default subject of the template
   * @param  string $content Default of the content of this particular template
   * @return WU_Mail
   */
  public function register_template($slug, $args) {

    /**
     * @since  1.3.4 Filters for sending style
     */
    $style = apply_filters('wu_email_template_style', WU_Settings::get_setting('email_template_style', 'html') == 'html');
    $style = apply_filters("wu_email_template_style_$slug", $style);

    // Check all the subjects
    $args = shortcode_atts(array(
      'name'       => '',
      'subject'    => '',
      'content'    => '',
      'shortcodes' => array(),
      'admin'      => false,
      'enabled'    => true,
      'html'       => $style,
    ), $args);

    // Add that to our template list
    $this->templates[$slug] = $args;

    // Flip array
    $this->templates[$slug]['shortcodes'] = array_flip($this->templates[$slug]['shortcodes']);

    // Allow Chains
    return $this;

  } // end register template;

  function get_templates() { return $this->templates; }

  /**
   * Send an email template registered in our framework
   * @param  string $slug The slug identifying the template to be sent
   * @param  string $to   Recipient's email address
   */
  public function send_template($slug, $to, $shortcodes, $attachments = array()) {

    /**
     * Allow plugin developers to "hijack" the email sending routines.
     * If the filter returns falsy, Ultimo's email sending will stop here.
     * Returning truthy values will continue the process nomally
     * 
     * @since 1.9.0
     * @return boolean
     */
    if (apply_filters('wu_mail_send_template', true, $slug, $to, $shortcodes, $attachments) == false) {

      return false;

    } // end if;

    // Get the template to send
    if (isset($this->templates[$slug]))
      $template = $this->templates[$slug];
    else return false;

    // Check if the email is enabled
    if (!WU_Settings::get_setting('email_'.$slug.'_enabled')) return false;

    // Replace with modification on the database
    $subject = WU_Settings::get_setting('email_'.$slug.'_subject');
    $subject = $subject ? $subject : $template['subject'];

    $content = WU_Settings::get_setting('email_'.$slug.'_content');
    $content = $content ? $content : $template['content'];

    // Add Pees
    $content = wpautop($content);

    // Send that email
    return $this->send_mail($to, $subject, $content, $template['html'], $shortcodes, $attachments);

  } // end send_template;

  /**
   * Send our mail using WordPress
   * @param  string $to      destinatary email
   * @param  string $subject Subject line
   * @param  string $body    Body of the message
   * @return boolean         Result
   */
  public function send_mail($to, $subject, $content, $html = true, $shortcodes = array(), $attachments = array(), $bcc = '') {

    $headers  = $html ? "Content-Type: text/html; charset=UTF-8\r\n" : '';
    $headers .= $bcc  ? "Bcc: $bcc\r\n" : '';

    // Get admin email and network name
    $network_email = WU_Settings::get_setting('from_email') ? WU_Settings::get_setting('from_email') : get_site_option('admin_email');
    $network_name  = WU_Settings::get_setting('from_name') ? WU_Settings::get_setting('from_name') : get_site_option('site_name');
    $headers      .= "From: $network_name <$network_email>\r\n";
    
    // Replace shortcodes
    $replace = array_merge($this->shortcodes, $shortcodes);

    /**
     * Get the template
     * For now we have just one template, but in the future we will get more options
     */
    ob_start();
    WP_Ultimo()->render($html ? 'emails/base' : 'emails/plain');
    $template = ob_get_clean();

    // Replace the content tag
    foreach($replace as $find => $replace_for) {

      // Replace in the body itself
      $content = str_replace("{{".$find."}}", $replace_for, $content);
      $subject = str_replace("{{".$find."}}", $replace_for, $subject);

      // Replace the template
      if ($find == 'content') $replace_for = $content;
      if ($find == 'subject') $replace_for = $subject;
      $template = str_replace("{{".$find."}}", $replace_for, $template);

    }

    // Send the actual email
    return wp_mail($to, $subject, $template, $headers, $attachments);

  } // end send_email;

  /**
   * This function changes the message body of the reset password mail
   *
   * @since  1.4.3
   * 
   * @param  string $message    Message body
   * @param  string $key        Key used to reset the passsword
   * @param  string $user_login User login
   * @param  array  $user_data  User data
   * @return string
   */
  public function replace_reset_password_mail($message, $key, $user_login, $user_data) {
    
    if (is_main_site()) {

      $url = apply_filters('wu_retrieve_password_url', network_site_url("wp-login.php?action=rp&key=$key&login=" . rawurlencode($user_login), 'login'));

      WU_Mail()->send_template('reset_password', $user_data->user_email, array(
        'reset_password_link' => $url,
        'user_name'           => $user_login,
      ));
      
      // Return nothing to prevent the original email from being sent
      return '';

    } else {

      return $message;

    } // end if;

  } // end replace_reset_password_mail;

  /**
   * Sends WooCommerce reset password email
   *
   * @param string $user_login
   * @param string $key
   * @return void
   */
  public function replace_reset_password_mail_wc($user_login, $key) {

    if ($user_login && $key) {

      $user_data = get_user_by('login', $user_login);

      $this->send_template('reset_password', $user_data->user_email, array(
        'reset_password_link' => network_site_url("wp-login.php?action=rp&key=$key&login=" . rawurlencode($user_login), 'login'),
        'user_name' => $user_login,
      ));

    } // end if;

  } // end replace_reset_password_mail_wc;

  /**
   * Replace the activation email
   *
   * @param integer $user_id
   * @param string  $password
   * @param array   $meta
   * @return void
   */
  public function replace_welcome_email($user_id, $password, $meta) {

    $current_network = get_network();

    $user = get_userdata($user_id);

    if (isset($meta['add_to_blog'])) {
      
      switch_to_blog($meta['add_to_blog']);
      
    } // end if;

      WU_Mail()->send_template('subsite_user_activated', $user->user_email, array(
        'user_name'      => $user->user_login,
        'user_password'  => $password,
        'user_site_name' => wp_specialchars_decode(get_option('blogname')),
        'user_site_url'  => home_url(),
        'login_url'      => wp_login_url(),
      ));

    restore_current_blog();

    return false;

  } // end replace_welcome_email;

  /**
   * Replace the default invite email to use our template
   *
   * @since 1.6.2
   * @param integer $user_id
   * @param string  $role
   * @param string  $newuser_key
   * @return void
   */
  public function send_invite_email($user_id, $role, $newuser_key) {

    $user = get_user_by('id', $user_id);

    WU_Mail()->send_template('subsite_user_invite', $user->user_email, array(
      'role'                    => wp_specialchars_decode(translate_user_role($role['name'])),
      'invite_confirmation_url' => home_url("/newbloguser/$newuser_key/"),
      'user_site_name'          => wp_specialchars_decode(get_option('blogname')),
      'user_site_url'           => home_url(),
    ));

    $redirect = add_query_arg(array('update' => 'add'), 'user-new.php');
    
    wp_redirect($redirect);
  
    die();

  } // end send_invite_email;

  /**
   * Replace the default invite email to use our template
   *
   * @param string $user_login
   * @param string $user_email
   * @param string $key
   * @param array $meta
   * @return boolean
   */
  public function send_invite_email_new_user($user_login, $user_email, $key, $meta) {

    $roles = get_editable_roles();
    $role = $roles[$_REQUEST['role']];

    WU_Mail()->send_template('subsite_user_invite', $user_email, array(
      'role'                    => wp_specialchars_decode(translate_user_role($role['name'])),
      'invite_confirmation_url' => site_url( "wp-activate.php?key=$key"),
      'user_site_name'          => wp_specialchars_decode(get_option('blogname')),
      'user_site_url'           => home_url(),
    ));

    // Short-circuit the default email;
    return false;

  } // end send_invite_email_new_user;

} // end class WU_Mail;

/**
 * Returns the singleton
 */
function WU_Mail() {
  return WU_Mail::get_instance();
}

// Initialize
WU_Mail();

/**
 *
 * Register our templates
 * 
 */

/**
 * Account Created
 */
WU_Mail()->register_template('account_created', array(
  'admin'      => true,
  'name'       => __('Account Created', 'wp-ultimo'),
  'subject'    => __('A new site was created in your network!', 'wp-ultimo'),
  
  'content'    => __("Hi, admin. <br><br>
A new site was created in your network in {{date}}:<br><br>
Site ID: {{user_site_id}}<br>
Site Name: {{user_site_name}}<br>
User Name: {{user_name}}<br>
Manage Account: <a href='{{user_account_link}}' target='_blank'>Go to Panel &rarr;</a>", 'wp-ultimo'),

    'shortcodes' => array(
      'user_name',
      'user_site_id',
      'user_site_name',
      'user_account_link',
      'date',
    )
));

/**
 * Account Created - User
 */
WU_Mail()->register_template('account_created_user', array(
  'admin'      => false,
  'name'       => __('Account Created - User', 'wp-ultimo'),
  'subject'    => __('Welcome to {{site_name}}!', 'wp-ultimo'),
  
  'content'    => __("Hi, {{user_name}}. <br><br>
Thanks for creating an account in our network! Your site {{user_site_name}} is ready to go, and can be accessed <a href='{{admin_panel_link}}' target='_blank'>here</a>.", 'wp-ultimo'),

  'shortcodes' => array(
    'user_name',
    'user_site_name',
    'admin_panel_link',
    'user_site_home_url',
    'date',
  )
));

/**
 * Account Created - User
 */
WU_Mail()->register_template('account_created_user_invite', array(
  'admin'      => false,
  'name'       => __('Site create from admin panel - User Invite', 'wp-ultimo'),
  'subject'    => __('Welcome to {{site_name}}!', 'wp-ultimo'),
  
  'content'    => __("Hi, {{user_name}}. <br><br>
Thanks for creating an account in {{site_name}}! Use the link below to set your password and login.<br><br>{{set_password_url}}", 'wp-ultimo'),

  'shortcodes' => array(
    'date',
    'user_name',
    'set_password_url',
    'login_url',
  )
));

/**
 * Account Removed
 * @since 1.7.0
 */
WU_Mail()->register_template('account_removed', array(
  'admin'      => true,
  'name'       => __('Account Removed', 'wp-ultimo'),
  'subject'    => __('An account was removed from your network', 'wp-ultimo'),
  'content'    => __("Hi, admin<br>
  
  We are contacting you to let you know that a subscription was removed from your network. <br>

  User ID: {{user_id}}<br>
  Username: {{user_name}} - {{user_email}}<br>

  Regards,", 'wp-ultimo'),
  'shortcodes' => array(
    'user_id',
    'user_name',
    'user_email',
  ),
));

/**
 * Account Removed - User
 * @since 1.7.0
 */
WU_Mail()->register_template('account_removed_user', array(
  'admin'      => false,
  'name'       => __('Account Removed - User', 'wp-ultimo'),
  'subject'    => __('Your account was removed from {{site_name}}', 'wp-ultimo'),
  'content'    => __("Hi, {{user_name}}.
  
  Your account ({{user_email}}) was successfully removed from the network. Your sites were removed and your subscription was canceled as well.<br>

  Hope we see you around in the future!<br>

  Regards,", 'wp-ultimo'),
  'shortcodes' => array(
    'user_id',
    'user_name',
    'user_email',
  ),
));

/**
 * Site Removed
 * @since 1.7.0
 */
WU_Mail()->register_template('site_removed', array(
  'admin'      => true,
  'name'       => __('Site Removed', 'wp-ultimo'),
  'subject'    => __('A site was removed from your network', 'wp-ultimo'),
  'content'    => __("Hi, admin<br>
  
  We are contacting you to let you know that a site was removed from your network. <br>

  Site ID: {{user_site_id}}<br>
  Site Title: {{user_site_name}}<br>
  Username: {{user_name}} - <a target='_blank' href='{{user_subscription_link}}'>View Subscription</a><br>

  Regards,", 'wp-ultimo'),
  'shortcodes' => array(
    'user_site_id',
    'user_site_name',
    'user_name',
    'user_subscription_link',
  ),
));

/**
 * Password Reset
 * @since 1.4.3
 */
WU_Mail()->register_template('reset_password', array(
  'admin'      => false,
  'name'       => __('Password Reset', 'wp-ultimo'),
  'subject'    => __('Reset your Password for {{site_name}}', 'wp-ultimo'),
  'content'    => __("Someone requested that the password be reset for the following account: <br><br>
    Username: {{user_name}}<br><br>
    If this was a mistake, just ignore this email and nothing will happen.<br><br>
    To reset your password, visit the following address:<br><br>

    <a href='{{reset_password_link}}'>Click here to reset your password</a>
    ", 'wp-ultimo'),
  'shortcodes' => array(
    'reset_password_link',
    'user_name'
  ),
));

/**
 * Subscription Created
 */
WU_Mail()->register_template('subscription_created', array(
  'admin'      => false,
  'name'       => __('Subscription Created', 'wp-ultimo'),
  'subject'    => __('Thanks for subscribing to our service!', 'wp-ultimo'),

  'content'    => __("Hi, {{user_name}}. <br><br>
Thanks so much for subscribing to our service. Please let us know if you have any questions. You will be billed for the first time on {{billing_start_date}} via {{gateway}}.", 'wp-ultimo'),

  'shortcodes' => array(
    'user_name',
    'date',
    'gateway',
    'billing_start_date'
  )
));

/**
 * Subscription Canceled
 */
WU_Mail()->register_template('subscription_canceled', array(
  'admin'      => false,
  'name'       => __('Subscription Canceled', 'wp-ultimo'),
  'subject'    => __('You canceled your subscription', 'wp-ultimo'),

  'content'    => __("Hi, {{user_name}}. <br><br>
We are sending you this email to confirm that your subscription was canceled. You should receive a email from {{gateway}} shortly as well. You account will be active until {{new_active_until}}.", 'wp-ultimo'),

  'shortcodes' => array(
    'user_name',          
    'date',             
    'gateway',          
    'new_active_until'
  )
));

/**
 * Subscription is about to expire
 * @since 1.5.5
 */
WU_Mail()->register_template('subscription_expiring', array(
  'admin'      => false,
  'name'       => __('Subscription is About to Expire', 'wp-ultimo'),
  'subject'    => __('Your subscription is about to expire!', 'wp-ultimo'),
  'content'    => __("Hi, {{user_name}}. <br><br>
  
We're here to let you know that your subscription on {{site_name}} is {{days_to_expire}} day(s) away from expiring and becoming inactive!<br><br>

Be sure to visit your account page to add a payment option and renew your subscription to keep your sites active: <a href='{{user_account_page_link}}'>Account Page</a>.", 'wp-ultimo'),
  'shortcodes' => array(
    'user_name',
    'days_to_expire',
    'user_account_page_link',
  ),
));

/**
 * Subscription expired
 * @since 1.5.5
 */
WU_Mail()->register_template('subscription_expired', array(
  'admin'      => false,
  'name'       => __('Subscription Expired', 'wp-ultimo'),
  'subject'    => __('Your subscription expired!', 'wp-ultimo'),
  'content'    => __("Hi, {{user_name}}. <br><br>
  
We're here to let you know that your subscription on {{site_name}} expired {{days_expired_since}} day(s) ago and your account is now inactive =(<br><br>

Be sure to visit your account page to add a payment option and renew your subscription to keep your sites active: <a href='{{user_account_page_link}}'>Account Page</a>.", 'wp-ultimo'),
  'shortcodes' => array(
    'user_name',
    'user_site_name',
    'days_to_expire',
    'user_account_page_link',
  ),
));

/**
 * Subscription expired - Admin Version
 * @since 1.7.3
 */
WU_Mail()->register_template('subscription_expired_admin', array(
  'admin'      => true,
  'name'       => __('Subscription Expired - Admin', 'wp-ultimo'),
  'subject'    => __('{{user_name}} subscription expired!', 'wp-ultimo'),
  'content'    => __("Hi, admin. <br><br>
  
We're here to let you know that the subscription of {{user_name}} expired {{days_expired_since}} day(s) ago and that account is now inactive =(<br><br>

You can visit the <a href='{{subscription_management_link}}'>subscription management screen</a> of that account to take action or review the subscription.", 'wp-ultimo'),
  'shortcodes' => array(
    'user_name',
    'user_site_name',
    'days_to_expire',
    'subscription_management_link',
  ),
));

/**
 * Trial is about to expire
 * @since 1.5.5
 */
WU_Mail()->register_template('trial_expiring', array(
  'admin'      => false,
  'name'       => __('Trial is About to Expire', 'wp-ultimo'),
  'subject'    => __('Your trial is about to expire!', 'wp-ultimo'),
  'content'    => __("Hi, {{user_name}}. <br><br>
  
We're here to let you know that your trial period on {{site_name}} is {{days_to_expire}} day(s) away from ending and becoming inactive!<br><br>

Be sure to visit your account page to add a payment option to keep your sites active: <a href='{{user_account_page_link}}'>Account Page</a>.", 'wp-ultimo'),
  'shortcodes' => array(
    'user_name',
    'days_expired_since',
    'user_account_page_link',
  ),
));

/**
 * Trial expired
 * @since 1.5.5
 */
WU_Mail()->register_template('trial_expired', array(
  'admin'      => false,
  'name'       => __('Trial Expired', 'wp-ultimo'),
  'subject'    => __('Your trial period expired!', 'wp-ultimo'),
  'content'    => __("Hi, {{user_name}}. <br><br>
  
We're here to let you know that your trial period on {{site_name}} expired {{days_expired_since}} day(s) ago and your account is now inactive =(<br><br>

Be sure to visit your account page to add a payment option to keep your sites active: <a href='{{user_account_page_link}}'>Account Page</a>.", 'wp-ultimo'),
  'shortcodes' => array(
    'user_name',
    'days_expired_since',
    'user_account_page_link',
  ),
));

/**
 * Payment Received (Receipt)
 */
WU_Mail()->register_template('payment_receipt', array(
  'admin'      => false,
  'name'       => __('Payment Receipt', 'wp-ultimo'),
  'subject'    => __('We received your payment!', 'wp-ultimo'),

  'content'    => __("Hi, {{user_name}}. <br><br>
We are contacting you to let you know that we received your payment of {{amount}} on {{date}} via {{gateway}}. Your account will now be active until {{new_active_until}}.", 'wp-ultimo'),

  'shortcodes' => array(
    'user_name',       
    'amount',           
    'date',             
    'gateway',          
    'new_active_until'
  )
));

/**
 * Payment Failed =(
 */
WU_Mail()->register_template('payment_failed', array(
  'admin'      => false,
  'name'       => __('Payment Failed', 'wp-ultimo'),
  'subject'    => __('Your payment failed', 'wp-ultimo'),

  'content'    => __("Hi, {{user_name}}. <br><br>
We are contacting you to let you know that a payment of {{amount}} on {{date}} via {{gateway}} failed to be processed. To prevent the deactivation of your account, you might need to integrate another form of payment. <a href='{{account_link}}'>Go to your account settings &rarr;</a>", 'wp-ultimo'),

  'shortcodes' => array(
    'amount',           
    'date',         
    'gateway',
    'user_name',    
    'account_link', 
  )
));

/**
 * Payment Failed =( - Admin Version
 * @since 1.7.3
 */
WU_Mail()->register_template('payment_failed_admin', array(
  'admin'      => true,
  'name'       => __('Payment Failed - Admin', 'wp-ultimo'),
  'subject'    => __('A payment for the {{user_name}} subscription failed', 'wp-ultimo'),

  'content'    => __("Hi, admin. <br><br>
We are contacting you to let you know that a payment of {{amount}} on {{date}} via {{gateway}} failed to be processed on the {{user_name}} account. <br><br>

You can visit the <a href='{{subscription_management_link}}'>subscription management screen</a> of that account to take action or review the subscription.", 'wp-ultimo'),

  'shortcodes' => array(
    'amount',           
    'date',         
    'gateway',
    'user_name',    
    'subscription_management_link', 
  )
));

/**
 * Plan Changed
 */
WU_Mail()->register_template('plan_changed', array(
  'admin'      => false,
  'name'       => __('Plan Changed', 'wp-ultimo'),
  'subject'    => __('You changed your subscription plan!', 'wp-ultimo'),

  'content'    => __("Hi, {{user_name}}. <br><br>
We are contacting you to let you know that we changed your subscription plan, as requested! Your new plan is {{new_plan_name}}. <a href='{{account_link}}'>Go to your account settings &rarr;</a>", 'wp-ultimo'),

  'shortcodes' => array(     
    'date',         
    'gateway',
    'user_name',    
    'account_link', 
    'new_plan_name', 
  )
));

/**
 * Refunded Issued
 */
WU_Mail()->register_template('refund_issued', array(
  'admin'      => false,
  'name'       => __('Refund Issued', 'wp-ultimo'),
  'subject'    => __('A refund was issued to your account', 'wp-ultimo'),

  'content'    => __("Hi, {{user_name}}. <br><br>
We are contacting you to let you know that we issued a refund of {{amount}} on {{date}} to your {{gateway}} account. Your account will now be active until {{new_active_until}}.", 'wp-ultimo'),

  'shortcodes' => array(
    'user_name',       
    'amount',           
    'date',             
    'gateway',          
    'new_active_until'
  )
));

/**
 * Domain Mapping
 * @since 1.5.4
 */
WU_Mail()->register_template('domain_mapping', array(
  'admin'      => true,
  'name'       => __('New Domain Mapping', 'wp-ultimo'),
  'subject'    => __('{{user_site_name}} has a new mapped domain', 'wp-ultimo'),
  'content'    => __("Hi, admin. <br><br>
  
The user {{user_name}}, owner of the site {{user_site_name}} (<a href='{{user_site_url}}'>{{user_site_url}}</a>), added a new mapped domain: <a href='{{mapped_domain}}'>{{mapped_domain}}</a>.<br><br>

To visit the site's alias panel, go to the <a href='{{alias_panel_url}}'>Alias Panel</a>.
Check that user's account page on <a href='{{user_account_page_link}}'>Subscription Manager</a>", 'wp-ultimo'),
  'shortcodes' => array(
    'user_name',
    'user_site_name',
    'user_site_url',
    'mapped_domain',
    'alias_panel_url',
    'user_account_page_link',
  ),
));

/**
 * Sub-sites User Invite
 * @since 1.6.2
 */
WU_Mail()->register_template('subsite_user_invite', array(
  'admin'      => false,
  'name'       => __('User Invite', 'wp-ultimo'),
  'subject'    => __('{{user_site_name}} - Joining Confirmation', 'wp-ultimo'),
  'content'    => __("Hi, <br><br>
  
  You've been invited to join <strong>{{user_site_name}}</strong> at
{{user_site_url}} with the role of {{role}}.<br><br>

Please click the following link to confirm the invite:
<a href='{{invite_confirmation_url}}'>Confirm Invite</a>", 'wp-ultimo'),
  'shortcodes' => array(
    'user_site_name',
    'user_site_url',
    'role',
    'invite_confirmation_url',
  ),
));

/**
 * Sub-sites User Activate
 * @since 1.6.2
 */
WU_Mail()->register_template('subsite_user_activated', array(
  'admin'      => false,
  'name'       => __('User Activated', 'wp-ultimo'),
  'subject'    => __('{{user_site_name}} - Account Activated', 'wp-ultimo'),
  'content'    => __("Howdy {{user_name}},<br><br>

Your new account is set up.<br><br>

You can log in with the following information:
Username: {{user_name}}
Password: {{user_password}}<br><br>

<a href='{{login_url}}'>Login</a><br><br>

Thanks!", 'wp-ultimo'),
  'shortcodes' => array(
    'user_name',
    'user_password',
    'user_site_name',
    'user_site_url',
    'login_url',
  ),
));

/**
 * User Visit Limit Approaching
 * @since 1.7.0
 */
WU_Mail()->register_template('visits_limit_approaching', array(
  'admin'      => false,
  'name'       => __('Visits Limit Approaching', 'wp-ultimo'),
  'subject'    => __('Your site {{user_site_name}} is reaching its monthly visits limit soon', 'wp-ultimo'),
  'content'    => __("Hi, {{user_name}}.<br><br>
  
The visits count on your site {{user_site_name}} has reached 80% of your monthly visit quota ({{visits_count}}/{{visits_limit}} monthly visits). Your monthly limit will be reset on {{reset_date}}.<br><br>
  
Visit <a href='{{admin_panel_link}}'>your site dashboard</a> to see more information and upgrade options.<br><br>

Regards,", 'wp-ultimo'),
  'shortcodes' => array(
    'user_name',
    'user_site_name',
    'reset_date',
    'visits_count',
    'visits_limit',
    'admin_panel_link',
  ),
));

/**
 * User Visit Limit Reached
 * @since 1.7.0
 */
WU_Mail()->register_template('visits_limit_reached', array(
  'admin'      => false,
  'name'       => __('Visits Limit Reached', 'wp-ultimo'),
  'subject'    => __('Your site {{user_site_name}} has reached its monthly visits limit', 'wp-ultimo'),
  'content'    => __("Hi, {{user_name}}.<br><br>
  
The visits count on your site {{user_site_name}} has reached your monthly visits quota ({{visits_limit}} monthly visits). <strong>Your site will no longer be available for visitors until your monthly limit is reset on {{reset_date}}</strong>.<br><br>
  
Visit <a href='{{admin_panel_link}}'>your site dashboard</a> to see more information and upgrade options.<br><br>

Regards,", 'wp-ultimo'),
  'shortcodes' => array(
    'user_name',
    'user_site_name',
    'reset_date',
    'visits_count',
    'visits_limit',
    'admin_panel_link',
  ),
));
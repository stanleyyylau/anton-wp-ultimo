<?php

/**
 * Pluggable
 * This file refines some of the WordPress native functions
 */

if (!function_exists('iconv')) :

  /**
   * Prevent fatal errors in envs where iconv is not available
   * 
   * @return string
   */
  function iconv($in_charset, $out_charset, $str ) {
    
    return $str;

  } // end iconv;

endif;

if (!function_exists('wp_new_user_notification')) :

 /**
  * Email login credentials to a newly-registered user.
  *
  * A new user registration notification is also sent to admin email.
  *
  * @since 2.0.0
  * @since 4.3.0 The `$plaintext_pass` parameter was changed to `$notify`.
  * @since 4.3.1 The `$plaintext_pass` parameter was deprecated. `$notify` added as a third parameter.
  * @since 4.6.0 The `$notify` parameter accepts 'user' for sending notification only to the user created.
  *
  * @global wpdb         $wpdb      WordPress database object for queries.
  * @global PasswordHash $wp_hasher Portable PHP password hashing framework instance.
  *
  * @param int    $user_id    User ID.
  * @param null   $deprecated Not used (argument deprecated).
  * @param string $notify     Optional. Type of notification that should happen. Accepts 'admin' or an empty
  *                           string (admin only), 'user', or 'both' (admin and user). Default empty.
  */
function wp_new_user_notification($user_id, $deprecated = null, $notify = '') {
  if ( $deprecated !== null ) {
    _deprecated_argument( __FUNCTION__, '4.3.1' );
  }

  global $wpdb, $wp_hasher;
  $user = get_userdata( $user_id );

  // The blogname option is escaped with esc_html on the way into the database in sanitize_option
  // we want to reverse this for the plain text arena of emails.
  $blogname = wp_specialchars_decode(get_option('blogname'), ENT_QUOTES);

  if ( 'user' !== $notify ) {
    // $message  = sprintf( __( 'New user registration on your site %s:' ), $blogname ) . "\r\n\r\n";
    // $message .= sprintf( __( 'Username: %s' ), $user->user_login ) . "\r\n\r\n";
    // $message .= sprintf( __( 'Email: %s' ), $user->user_email ) . "\r\n";

    // @wp_mail( get_option( 'admin_email' ), sprintf( __( '[%s] New User Registration' ), $blogname ), $message );
  }

  // `$deprecated was pre-4.3 `$plaintext_pass`. An empty `$plaintext_pass` didn't sent a user notifcation.
  if ('admin' === $notify || ( empty( $deprecated ) && empty( $notify ))) {
    return;
  }

  // Generate something random for a password reset key.
  $key = wp_generate_password( 20, false );

  /** This action is documented in wp-login.php */
  do_action( 'retrieve_password_key', $user->user_login, $key );

  // Now insert the key, hashed, into the DB.
  if ( empty( $wp_hasher ) ) {
    require_once ABSPATH . WPINC . '/class-phpass.php';
    $wp_hasher = new PasswordHash( 8, true );
  }
  $hashed = time() . ':' . $wp_hasher->HashPassword( $key );
  $wpdb->update( $wpdb->users, array( 'user_activation_key' => $hashed ), array( 'user_login' => $user->user_login ) );

  // $message = sprintf(__('Username: %s'), $user->user_login) . "\r\n\r\n";
  // $message .= __('To set your password, visit the following address:') . "\r\n\r\n";
  // $message .= '<' . network_site_url("wp-login.php?action=rp&key=$key&login=" . rawurlencode($user->user_login), 'login') . ">\r\n\r\n";

  // $message .= wp_login_url() . "\r\n";

  // wp_mail($user->user_email, sprintf(__('[%s] Your username and password info'), $blogname), $message);

  /**
   *
   * Finally send out the messages
   * 
   */

  // Welcome email to user
  WU_Mail()->send_template('account_created_user_invite', $user->user_email, array(
    'date'               => date(get_option('date_format')),
    'user_name'          => $user->user_login,
    'set_password_url'   => network_site_url("wp-login.php?action=rp&key=$key&login=".rawurlencode($user->user_login), 'login'),
    'login_url'          => wp_login_url(),
  ));

 }
 endif;

/**
 * This is a temporary fix
 * TODO: Check this later
 */
if (!function_exists('check_ajax_referer')) :

  function check_ajax_referer( $action = -1, $query_arg = false, $die = true ) {

    if (isset($_POST['action']) && isset($_POST['fl_upload_type']) && $_POST['action'] == 'upload-attachment') {

      return true; // Temporary fix for Jason

    } // end if;

    if ( -1 == $action ) {
        _doing_it_wrong( __FUNCTION__, __( 'You should specify a nonce action to be verified by using the first parameter.' ), '4.7' );
    }
 
    $nonce = '';
 
    if ( $query_arg && isset( $_REQUEST[ $query_arg ] ) )
        $nonce = $_REQUEST[ $query_arg ];
    elseif ( isset( $_REQUEST['_ajax_nonce'] ) )
        $nonce = $_REQUEST['_ajax_nonce'];
    elseif ( isset( $_REQUEST['_wpnonce'] ) )
        $nonce = $_REQUEST['_wpnonce'];
 
    $result = wp_verify_nonce( $nonce, $action );
 
    /**
     * Fires once the Ajax request has been validated or not.
     *
     * @since 2.1.0
     *
     * @param string    $action The Ajax nonce action.
     * @param false|int $result False if the nonce is invalid, 1 if the nonce is valid and generated between
     *                          0-12 hours ago, 2 if the nonce is valid and generated between 12-24 hours ago.
     */
    do_action( 'check_ajax_referer', $action, $result );
 
    if ( $die && false === $result ) {
        if ( wp_doing_ajax() ) {
            wp_die( -1, 403 );
        } else {
            die( '-1' );
        }
    }
 
    return $result;

  }

endif;
<?php
/**
 * Pages Broadcasts
 *
 * Handles the addition of the Broadcasts Page
 *
 * @author      Arindo Duque
 * @category    Admin
 * @package     WP_Ultimo/Pages/Broadcasts
 * @version     0.0.1
*/

if (!defined('ABSPATH')) {
  exit;
}

class WU_Page_Broadcasts extends WU_Page_List {

  /**
   * Initializes the page
   *
   * @return void
   */
  public function init() {

    require_once WP_Ultimo()->path('inc/class-wu-broadcasts-list-table.php');

    add_action('wp_ajax_wu_broadcast_message', array($this, 'save_broadcast'));

    add_action('wp_ajax_wu_dismiss_broadcast', array($this, 'dismiss_broadcast'));

    add_action('wp_ajax_wu_query_user', array($this, 'query_users'));

    add_action('admin_init', array($this, 'display_admin_messages'), 1);

    add_action('admin_init', array($this, 'register_post_type'));

  } // end init;

  /**
   * Register the page scripts
   *
   * @return void
   */
  public function register_scripts() {

    $suffix = WP_Ultimo()->min;

    wp_register_script('wu-broadcast-sender', WP_Ultimo()->get_asset("wu-broadcast-sender$suffix.js", 'js'), array('jquery'), WP_Ultimo()->version);

    wp_localize_script('wu-broadcast-sender', 'wu_broadcast_sender', array(
      'placeholder_plans' => __('Select the Target Plans'),
    ));

  } // end register_scripts;

  /**
   * Search for users for the targeting of messages
   * 
   * @return void
   */
  public function query_users() {

    if (!current_user_can('manage_network')) {

      wp_send_json(array());

    } // end if;

    $users = new WP_User_Query( array(
      'blog_id'        => 0,
      'search'         => '*'.esc_attr( $_GET['term'] ).'*',
      'search_columns' => array(
        'ID',
        'user_login',
        'user_nicename',
        'user_email',
        'user_url',
      ),
    ));

    $users_found = $users->get_results();

    wp_send_json($users_found);

  } // query_users;

  /**
   * Displayes the messages for the user, it takes into account the targeting options and if the user has dismissed it
   * 
   * @return void;
   */
  public function display_admin_messages() {

    // Only display this for valid users
    $subscription = wu_get_current_site()->get_subscription();

    if (!$subscription) return;

    $user_id = $subscription->user_id;

    // Get the messages
    $messages = WU_Broadcasts_List_Table::get_broadcasts(apply_filters('wu_broadcast_count', 10), 1, 'message', $subscription->get_date('created_at', 'Y-m-d H:i:s'));

    /**
     * Check if the user has dismissed that message in particular
     */
    $dismissed = get_user_meta($user_id, 'wu_dismissed_broadcasts', true);

    $dismissed = $dismissed ?: array();

    // Loop the messages
    foreach($messages as $message) {

      if (in_array($message->id, $dismissed)) continue; // do not display if already dismissed;

      // Do not display if the user is not in the selected group
      $is_target = in_array($user_id, (array) $message->target_users) || in_array(wu_get_current_site()->plan_id, (array) array_filter( (array) $message->target_plans));

      if (!$is_target) continue;

      $content = '';
      
      if ($message->post_title) $content .= sprintf('<strong>%s</strong> - ', $message->post_title);
      
      $content .= nl2br("$message->post_content");

      $nonce = wp_create_nonce('wu-dismiss-broadcast');

      /**
       * Adds the hidden fields for control
       */
      $content .= "<input type='hidden' name='message_id' value='$message->id'>";
      $content .= "<input type='hidden' name='nonce' value='$nonce'>";
      $content .= "<input type='hidden' name='user_id' value='$user_id'>";

      WP_Ultimo()->add_message($content, "{$message->style} wu-broadcast-notice");

    } // end foreach;

    $suffix = WP_Ultimo()->min;

    wp_enqueue_script('wu-dismiss-broadcast', WP_Ultimo()->get_asset("wu-dismiss-broadcast$suffix.js", 'js'), array('jquery'), WP_Ultimo()->version);

  } // end display_admin_messages;

  /**
   * Add a message to the dismissed list of a user
   * 
   * @return void
   */
  public function dismiss_broadcast() {

    $post = $_POST;

    $dismissed = get_user_meta($post['user_id'], 'wu_dismissed_broadcasts', true);

    $dismissed = $dismissed ?: array();

    if (!in_array($post['message_id'], $dismissed)) $dismissed[] = $post['message_id'];

    update_user_meta($post['user_id'], 'wu_dismissed_broadcasts', $dismissed);

    die(1);

  } // end dismiss_broadcast;

  /**
   * Add the screen options
   * 
   * @return void;
   */
  public function screen_options() {

    $args = array(
      'label'   => 'Broadcast',
      'default' => 5,
      'option'  => 'broadcasts_per_page'
    );

    add_screen_option('per_page', $args);

    require_once WP_Ultimo()->path('inc/class-wu-broadcasts-list-table.php');

    $this->broadcasts_list = new WU_Broadcasts_List_Table;

    $this->broadcasts_list->prepare_items();

  } // end screen_options;

  /**
   * Register the Broadcast Post Type
   * 
   * @return void
   */
  public function register_post_type() {

    $args = array(
      'label'              => __('Broadcast Message', 'wp-ultimo'),
      'description'        => __('Description.', 'wp-ultimo'),
      'public'             => false,
      'publicly_queryable' => false,
      'show_ui'            => false,
      'show_in_menu'       => false,
      'query_var'          => true,
      'rewrite'            => array('slug' => 'wpultimo_broadcast'),
      'capability'         => 'manage_network',
      'has_archive'        => true,
      'hierarchical'       => false,
      'can_export'         => false,
      'menu_position'      => null,
      'supports'           => array('title', 'editor', 'custom-fields'),
    );

    register_post_type('wpultimo_broadcast', $args);

  } // end register_post_type;

  /**
   * Save the broadcast
   * 
   * @return void
   */
  public function save_broadcast() {

    check_ajax_referer('wu_save_broadcast');

    $error = false;

    if (!isset($_POST['post_content']) || !$_POST['post_content']) {
      $error = __('The message can not be empty.', 'wp-ultimo');
    }

    if (!isset($_POST['type']) || !$_POST['type']) {
      $error = __('You need to select a type of message.', 'wp-ultimo');
    }

    if ( (isset($_POST['type']) && !$_POST['type'] === 'message') && (!isset($_POST['style']) || !$_POST['style'])) {
      $error = __('You need to select a style when posting a message.', 'wp-ultimo');
    }

    if ( (!isset($_POST['target_users']) || !$_POST['target_users']) && (!isset($_POST['target_plans']) || !$_POST['target_plans'])) {
      $error = __('You need to select at least one recipient for this message.', 'wp-ultimo');
    }

    if ($error) {

      echo json_encode(array(
        'status'  => false,
        'message' => $error,
      )); exit;

    }

    $broadcast = new WU_Broadcast;

    $_POST['post_content'] = str_replace("\'", "'", $_POST['post_content']);

    // Fix on not having extra lines
    $_POST['post_content'] = wpautop($_POST['post_content']);

    $broadcast->set_attributes($_POST);

    /**
     * Target users
     */
    $target_users = explode(',', $_POST['target_users']);

    $broadcast->target_users = $target_users;

    $admin_email = get_site_option('admin_email');

    /**
     * @since  1.3.4 Filters for sending style
     */
    $style = apply_filters('wu_email_template_style', WU_Settings::get_setting('email_template_style', 'html') == 'html');
    $style = apply_filters("wu_email_template_style_$style", $style);

    /**
     * Case Preview
     */
    if ($broadcast->message_action == 'preview') {

      /**
       * If this is an email, we need to send it
       */
      WU_Mail()->send_mail($admin_email, $broadcast->post_title, $broadcast->post_content, $style);

      echo json_encode(array(
        'status'  => true,
        'message' => sprintf(__('A test email was sent to your email address (%s).', 'wp-ultimo'), $admin_email),
      )); exit;

    } // end if;
    
    if ($broadcast->save()) {

      /**
       * If this is an email, we need to send it
       */
      if ($broadcast->type == 'email') {

        $_recipients_list = $broadcast->get_recipients_list();

        $to = array_shift($_recipients_list);

        $recipients_list = implode(',', $_recipients_list);

        /**
         * Send email without TO to hide email addresses, using BCC instead
         */
        WU_Mail()->send_mail($to, $broadcast->post_title, $broadcast->post_content, $style, array(), array(), $recipients_list);

      } // end if;

      echo json_encode(array(
        'status'  => true,
        'message' => __('Message sent successfully', 'wp-ultimo'),
      )); 
      
      exit;

    } else {

       echo json_encode(array(
        'status'  => false,
        'message' => __('An error occurred', 'wp-ultimo'),
      ));
      
      exit;

    } // end if;

  } // end save_broadcast;

  /**
   * Register the widgets for this page
   *
   * @return void
   */
  public function register_widgets() {

    $screen = get_current_screen();

    // Sender
    add_meta_box('wp-ultimo-send-broadcast', __('Send Broadcast', 'wp-ultimo'), array('WU_Page_Broadcasts', 'output_widget_sender'), $screen->id, 'normal', 'high');

    // Preview Block @since 1.6.0
    add_meta_box('wp-ultimo-broadcast-preview', __('Preview', 'wp-ultimo'), array($this, 'output_widget_preview'), $screen->id, 'side', 'high');
    
    // Table
    add_meta_box('wp-ultimo-past-broadcasts', __('Last Messages', 'wp-ultimo'), array($this, 'output_widget_table'), $screen->id, 'side', 'high');

  } // end register_widgets;

  /**
   * Output the widget of our graphs for user growth
   */
  public static function output_widget_sender() {

    wp_enqueue_script('wu-broadcast-sender');
    
    // Render the page
    WP_Ultimo()->render('widgets/broadcasts/sender');

  } // end output_widget_sender;

  /**
   * Outputs the preview block for the Vue preview
   */
  public function output_widget_preview() {
    
    // Render the page
    WP_Ultimo()->render('widgets/broadcasts/preview');

  } // end output_widget_table;

  /**
   * Output the widget of our graphs for user growth
   */
  public function output_widget_table() {
    
    // Render the page
    WP_Ultimo()->render('widgets/broadcasts/list', array(
      'broadcasts_list' => $this->broadcasts_list,
    ));

  } // end output_widget_table;
  
  /**
   * Displays the page content
   * 
   * @return void
   */
  public function output() {

    WP_Ultimo()->render('meta/broadcasts');

  } // end output;
  
} // end class WU_Page_Broadcasts;

new WU_Page_Broadcasts(true, array(
  'id'         => 'wp-ultimo-broadcast',
  'type'       => 'submenu',
  'capability' => 'manage_network',
  'title'      => __('Broadcasts', 'wp-ultimo'),
  'menu_title' => __('Broadcasts', 'wp-ultimo'),
));


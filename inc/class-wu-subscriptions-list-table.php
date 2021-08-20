<?php
/**
 * Subscriptions List Table
 *
 * Extends the Class that handles WordPress lists tables to display our susbscriptions
 *
 * @author      Arindo Duque
 * @category    Admin
 * @package     WP_Ultimo/Subscriptions
 * @version     1.1.0
*/

if (!class_exists('WP_List_Table')) {
  require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
}

class WU_Subscriptions_List_Table extends WP_List_Table {

  /** Class constructor */
  public function __construct() {

    parent::__construct([
      'screen'   => 'subscription-list-table',
      'singular' => __( 'Subscription', 'wp-ultimo'),  // singular name of the listed records
      'plural'   => __( 'Subscriptions', 'wp-ultimo'), // plural name of the listed records
      'ajax'     => true                               // does this table support ajax?
    ] );

    add_filter('wu_subscriptions_get_views', array($this, 'add_plan_views'));

  } // end __construct;

  /**
	 * Retrieve subscriptions data from the database
	 *
	 * @param int $per_page
	 * @param int $page_number
	 *
	 * @return mixed
	 */
  public static function get_subscriptions($per_page = 5, $page_number = 1) {

    $status = isset($_GET['status']) ? $_GET['status'] : 'all';

    $orderby = isset($_GET['orderby']) ? $_GET['orderby'] : false;

    $order = isset($_GET['order']) ? $_GET['order'] : false;
    
    $plan_id = isset($_GET['plan_id']) ? $_GET['plan_id'] : false;

    /**
     * @since 1.2.1 Search string
     * @var
     */
    $search = isset($_GET['s']) ? $_GET['s'] : false;

    $results = WU_Subscription::get_subscriptions($status, $per_page, $page_number, $orderby, $order, false, $search, $plan_id);

    return $results;

  } // end get_subscriptions;

  /**
   * Add the custom filtering options to this list table
   * @since 1.7.0 Added plan filtering
   * @since 1.1.0
   * @return array
   */
  function get_views() {

    $orderby       = isset($_GET['orderby']) ? $_GET['orderby'] : false;
    $order         = isset($_GET['order']) ? $_GET['order'] : false;
    $active_status = isset($_GET['status']) ? $_GET['status'] : false;
    $plan_id       = isset($_GET['plan_id']) ? $_GET['plan_id'] : false;

    /**
     * @since 1.2.1 Search string
     * @var
     */
    $search = isset($_GET['s']) ? $_GET['s'] : false;

    $status = apply_filters('wu_subscriptions_status', array(
      'all'      => __('All', 'wp-ultimo'),
      'active'   => __('Active', 'wp-ultimo'),
      'inactive' => __('Inactive', 'wp-ultimo'),
      'trialing' => __('Trialing', 'wp-ultimo'),
    ));

    $actions = array();

    foreach ($status as $status => $label) {

      $count = WU_Subscription::get_subscriptions($status, false, false, false, false, true);

      if ($count <= 0) continue; 

      $url = network_admin_url('admin.php?page=wp-ultimo-subscriptions&status=').$status;

      // if ($plan_id) $url .= "&plan_id=$plan_id";

      if ($orderby && $order) $url .= "&orderby=$orderby&order=$order";

      $actions[] = $active_status && !$plan_id && $status == $active_status
        ? sprintf("<strong class='wu-sub-view-status'>%s</strong> (%s)", $label, $count)
        : sprintf("<a class='wu-sub-view-status' href='%s'>%s</a> (%s)", $url, $label, $count);

    }

    return apply_filters('wu_subscriptions_get_views', $actions);

  } // end get_views;

  public function add_plan_views($actions) {

    $orderby       = isset($_GET['orderby']) ? $_GET['orderby'] : false;
    $order         = isset($_GET['order'])   ? $_GET['order']   : false;
    $active_status = isset($_GET['status'])  ? $_GET['status']  : false;
    $plan_id       = isset($_GET['plan_id']) ? $_GET['plan_id'] : false;

    $plans = WU_Plans::get_plans();

    foreach($plans as $plan) {

      $count = $plan->get_subscription_count();

      $url = network_admin_url('admin.php?page=wp-ultimo-subscriptions');

      $url .= "&plan_id=$plan->id";

      if ($orderby && $order) $url .= "&orderby=$orderby&order=$order";

      $actions[] = $plan_id && $plan_id == $plan->id
        ? sprintf("<strong class='wu-sub-view-plan'>%s</strong> (%s)", $plan->title, $count)
        : sprintf("<a class='wu-sub-view-plan' href='%s'>%s</a> (%s)", $url, $plan->title, $count);

    } // end foreach;

    return $actions;

  } // end add_plan_views;

  /**
   * Display the table
   * Adds a Nonce field and calls parent's display method
   *
   * @since 3.1.0
   * @access public
   */
  public function display() {

    wp_nonce_field('ajax-subscription-nonce', '_ajax_subscriptions_nonce');

    $order   = isset($this->_pagination_args['order']) ? $this->_pagination_args['order']  : '';
    $orderby = isset($this->_pagination_args['orderby']) ? $this->_pagination_args['orderby']  : '';

    $status = isset($_GET['status']) ? $_GET['status'] : 'all';

    /**
     * @since 1.2.1 Search string
     * @var
     */
    $search = isset($_GET['s']) ? $_GET['s'] : '';

    // echo '<input type="hidden" id="user_id" name="user_id" value="' . self::$user_id . '" />';
    echo '<input type="hidden" id="order" name="order" value="' . $order . '" />';
    echo '<input type="hidden" id="orderby" name="orderby" value="' . $orderby . '" />';
    echo '<input type="hidden" id="orderby" name="status" value="'. $status .'" />';
    echo '<input type="hidden" id="s" name="status" value="'. $search .'" />';

    parent::display();

  }

  /**
   * Render a column when no column specific method exist.
   *
   * @param array $item
   * @param string $column_name
   *
   * @return mixed
   */
  public function column_default($item, $column_name) {
    return $item->{$column_name};
  }


  /**
	 * Returns the count of records in the database.
	 *
	 * @return null|string
	 */
  public static function record_count() {

    $status = isset($_GET['status']) ? $_GET['status'] : 'all';

    $orderby = isset($_GET['orderby']) ? $_GET['orderby'] : false;

    $order = isset($_GET['order']) ? $_GET['order'] : false;

    /**
     * @since 1.2.1 Search string
     * @var
     */
    $search = isset($_GET['s']) ? $_GET['s'] : false;

    $results = WU_Subscription::get_subscriptions($status, false, false, false, false, true, $search);

    return $results;

  } // end record_count;

  /** Text displayed when no plan data is available */
  public function no_items() {
    _e( 'No Subscriptions avaliable.', 'wp-ultimo');
  }

  /**
	 * Render the bulk edit checkbox
	 *
	 * @param array $item
	 *
	 * @return string
	 */
  function column_cb( $item ) {
    return sprintf(
      '<input type="checkbox" name="bulk-delete[]" value="%s" />', $item->ID
    );
  }
  
  /**
   * Method for name column
   * @since  1.1.3 Checks for the existance of the user
   *
   * @param array  $item an array of DB data
   *
   * @return string
   */
  function column_id($item) {

    $subscription = wu_get_subscription($item->user_id);

    // Get user info
    $user = get_user_by('id', $item->user_id);

    // Check if user exists
    if (!$user) {

      $actions = array(
        'delete' => sprintf('<a href="?page=wp-ultimo-subscriptions&action=delete&user_id=%s">%s</a>', absint($item->user_id), __('Delete', 'wp-ultimo')),
      );

      return sprintf('<strong>#%s</strong> - %s', $item->user_id, __('User not found', 'wp-ultimo')) . $this->row_actions($actions);

    }

    $subscription_id = sprintf('<a href="?page=wu-edit-subscription&user_id=%s"><strong>#%s</strong></a>', $item->user_id, $item->user_id);

    $subscription_user = sprintf('<a href="%s%s">%s</a>', network_admin_url('user-edit.php?user_id='), $user->ID, $user->display_name);

    // Add trialing
    $subscription_trial = $subscription->get_trial() ? '<small class="wu-trialing">'. __('Trialing', 'wp-ultimo') .'</small>' : '';

    // Concatenate the two blocks
    $title = "$subscription_id - $subscription_user $subscription_trial<br>";

    $desc = sprintf('<a href="mailto:%s" class="description">%s</a>', $user->user_email, $user->user_email);

    $delete_url = wp_nonce_url(sprintf('?page=wp-ultimo-subscriptions&action=delete&user_id=%s', absint($item->user_id)), 'wp-ultimo-delete-subscription');

    $actions = array(
      'edit' => sprintf('<a href="?page=wu-edit-subscription&user_id=%s">%s</a>', absint($item->user_id), __('See Details', 'wp-ultimo')),
      'delete' => sprintf('<a class="wu-confirm" data-text="%s" href="%s">%s</a>', __('Are you sure you want to delete this subscription? All data linked to this subscription will be removed. The gateway integration will also be revoked, if possible.', 'wp-ultimo'), $delete_url, __('Delete', 'wp-ultimo')),
    );

    return $title . $desc . $this->row_actions($actions);

  } // end column_id;

  /**
   * Displays the status of this subscriptions
   */
  static function column_wu_status($item) {

    $subscription = wu_get_subscription($item->user_id);

    echo '<div class="wu-status-container">';

    if ($subscription->get_trial()) {

      echo WU_Util::tooltip(__('Subscription is currently trialing', 'wp-ultimo'), 'wu-status-icon dashicons-minus');

    } else if ($subscription->is_on_hold()) { // @since 1.2.0

      echo WU_Util::tooltip(__('Subscription is on hold', 'wp-ultimo'), 'wu-status-icon dashicons-arrow-left');

    } else {

      $icon_active   = WU_Util::tooltip(__('Subscription is currently active', 'wp-ultimo'), 'wu-status-icon dashicons-yes');

      $icon_inactive = WU_Util::tooltip(__('Subscription is currently inactive', 'wp-ultimo'), 'wu-status-icon dashicons-no-alt');

      echo $subscription->is_active() ? $icon_active : $icon_inactive;

    }

    /**
     * @since 1.5.4 Displays users avatar to make all more colorful and cool =)
     */
    echo get_avatar($item->user_id, 48, 'identicon', '', array(
      'force_display' => true,
    ));

    echo '</div>';

  } // end column_wu_status;

  /**
   * Returns the name of the subscription plan
   * @param  WU_Subscription $item The instance of a subscription object
   */
  function column_plan($subscription) {

    $plan = wu_get_plan($subscription->plan_id);

    echo $plan 
      ? sprintf(
        '<a class="wu-tooltip" title="%s" href="%s">%s</a>', 
        __('Visit Plan page', 'wp-ultimo'), 
        network_admin_url('admin.php?page=wu-edit-plan&plan_id=' . $plan->id),
        $plan->title
        ) 
      : '-';

  } // end column_plan;

  /**
   * Returns the price of the subscription plan
   * @param  WU_Subscription $item The instance of a subscription object
   */
  public function column_price($item) {

    // Calculate lifetime value
    $lifetime_value = WU_Transactions::get_total_after_refunds($item->user_id);

    echo wu_format_currency($item->price) . '<br><small>' . __('Total:', 'wp-ultimo') . ' ' . wu_format_currency($lifetime_value) . '</small>';

  } // end column_price;

  /**
   * Returns the frequency of the subscription plan
   * @param  WU_Subscription $item The instance of a subscription object
   */
  function column_frequency($item) {

    $prices = array(
      1  => __('Monthly', 'wp-ultimo'), 
      3  => __('Quarterly', 'wp-ultimo'), 
      12 => __('Yearly', 'wp-ultimo'), 
    );

    echo isset($prices[$item->freq]) ? $prices[$item->freq] : '-';

  } // end column_frequency;

  /**
   * Returns the expiring date of that subscription
   * @param  WU_Subscription $item The instance of a subscription object
   */
  function column_active_until($item) {

    $subscription   = wu_get_subscription($item->user_id);
    $format         = get_option('date_format') . ' @ H:i';

    echo $subscription->get_trial() 
      ? $subscription->get_date('trial_end', $format) 
      : $subscription->get_date('active_until', $format);

    echo '<br>';

    echo "<small>". $subscription->get_active_until_string() ."</small>";

  } // end column_active_until;

  /**
   * Returns the last note and a count of the number of notes on that particular subscription
   * @param  WU_Subscription $item The instance of a subscription object
   */
  function column_notes($item) {

    $active = rand(0, 2);

    $active = floor($active);

    echo $active ? WU_Util::tooltip(__('Last message here...'), 'dashicons-admin-comments') : '-';

  } // end column_notes;

  /**
	 * Associative array of columns
	 * @return array
	 */
  function get_columns() {

    $columns = [
      // 'cb'            => '<input type="checkbox" />',
      'wu_status'     => __('Status', 'wp-ultimo'),
      'id'            => __('Subscription', 'wp-ultimo'),
      'plan'          => __('Plan', 'wp-ultimo'),
      'price'         => __('Billing Amount', 'wp-ultimo'),
      'frequency'     => __('Billing Frequency', 'wp-ultimo'),
      'active_until'  => __('Active Until', 'wp-ultimo'),
      // 'notes'         => __('Notes', 'wp-ultimo'),
    ];

    return $columns;

  } // end get_columns;

  /**
	 * Columns to make sortable.
	 * @return array
	 */
  public function get_sortable_columns() {

    $sortable_columns = array(
      'id'           => array('user_id', false),
      'plan'         => array('plan_id', false),
      'price'        => array('price', false),
      'frequency'    => array('freq', false),
      'active_until' => array('active_until', false),
    );

    return $sortable_columns;

  } // end get_sortable_columns;

  /**
	 * Returns an associative array containing the bulk action
	 *
	 * @return array
	 */
  public function get_bulk_actions() {

    return array();

  }

  /**
   * Handle an incoming ajax request (called from admin-ajax.php)
   *
   * @since 3.1.0
   * @access public
   */
  function ajax_response() {

    check_ajax_referer('ajax-subscription-nonce', '_ajax_subscriptions_nonce');

    $this->prepare_items();

    extract( $this->_args );
    extract( $this->_pagination_args, EXTR_SKIP );

    ob_start();

    if ( ! empty( $_REQUEST['no_placeholder'] ) )
      $this->display_rows();
    else
      $this->display_rows_or_placeholder();
    $rows = ob_get_clean();

    ob_start();
    $this->print_column_headers();
    $headers = ob_get_clean();

    ob_start();
    $this->pagination('top');
    $pagination_top = ob_get_clean();

    ob_start();
    $this->pagination('bottom');
    $pagination_bottom = ob_get_clean();

    $response = array( 'rows' => $rows );
    $response['pagination']['top'] = $pagination_top;
    $response['pagination']['bottom'] = $pagination_bottom;
    $response['column_headers'] = $headers;

    if ( isset( $total_items ) )
      $response['total_items_i18n'] = sprintf( _n( '1 item', '%s items', $total_items ), number_format_i18n( $total_items ) );

    if ( isset( $total_pages ) ) {
      $response['total_pages'] = $total_pages;
      $response['total_pages_i18n'] = number_format_i18n( $total_pages );
    }

    die( json_encode( $response ) );

  }

  /**
	 * Handles data query and filter, sorting, and pagination.
	 */
  public function prepare_items() {

    $this->_column_headers = $this->get_column_info();

    $per_page     = $this->get_items_per_page('subscriptions_per_page', 10);
    $current_page = $this->get_pagenum();
    $total_items  = self::record_count();

    $this->set_pagination_args([
      'total_items' => $total_items, // WE have to calculate the total number of items
      'per_page'    => $per_page     // WE have to determine how many items to show on a page
    ]);

    $this->items = self::get_subscriptions($per_page, $current_page);

  } // end prepare_items;

  /**
   * Remove the Subscriptions without user
   * @return
   */
  public function process_bulk_action() {

    if (isset($_GET['action']) && $_GET['action'] == 'delete') {

      // Check Nonce
      check_admin_referer('wp-ultimo-delete-subscription');

      if (!isset($_GET['user_id'])) return;

      // Get the subscription
      $subscription = wu_get_subscription($_GET['user_id']);

      if (!$subscription) {

        WP_Ultimo()->add_message(__('Subscription not found.', 'wp-ultimo'), 'error', true);

      } else {

        $subscription->delete();

        wp_redirect(add_query_arg(array('deleted' => 1), network_admin_url('admin.php?page=wp-ultimo-subscriptions')));
        exit;

      }

    }

  } // end process_bulk_action;

}

/**
 * Callback function for 'wp_ajax__ajax_fetch_subscriptions_list' action hook.
 * 
 * Loads the Custom List Table Class and calls ajax_response method
 */

/**
 * This function adds the jQuery script to the plugin's page footer
 */
function ajax_pagination_subscriptions_table_script() {

  $screen = get_current_screen();
  if (!$screen || 'toplevel_page_wp-ultimo-subscriptions-network' != $screen->id )
    return false;
?>
<script type="text/javascript">
(function($) {

list = {

  /**
   * Register our triggers
   * 
   * We want to capture clicks on specific links, but also value change in
   * the pagination input field. The links contain all the information we
   * need concerning the wanted page number or ordering, so we'll just
   * parse the URL to extract these variables.
   * 
   * The page number input is trickier: it has no URL so we have to find a
   * way around. We'll use the hidden inputs added in TT_Example_List_Table::display()
   * to recover the ordering variables, and the default paged input added
   * automatically by WordPress.
   */
  init: function() {

    // This will have its utility when dealing with the page number input
    var timer;
    var delay = 500;

    // Pagination links, sortable link
    $('.tablenav-pages a, .manage-column.sortable a, .manage-column.sorted a').on('click', function(e) {
      // We don't want to actually follow these links
      e.preventDefault();
      // Simple way: use the URL to extract our needed variables
      var query = this.search.substring( 1 );
      
      var data = {
        paged: list.__query( query, 'paged' ) || '1',
        order: list.__query( query, 'order' ) || 'DESC',
        orderby: list.__query( query, 'orderby' ) || 'user_id',
        status: list.__query( query, 'status' ) || 'all',
        s: list.__query( query, 's' ) || '',
      };
      list.update( data );
    });

    // Page number input
    $('input[name=paged], input[name=s]').on('keyup', function(e) {

      // If user hit enter, we don't want to submit the form
      // We don't preventDefault() for all keys because it would
      // also prevent to get the page number!
      if ( 13 == e.which )
        e.preventDefault();

      // This time we fetch the variables in inputs
      var data = {
        paged: parseInt( $('input[name=paged]').val() ) || '1',
        order: $('input[name=order]').val() || 'DESC',
        orderby: $('input[name=orderby]').val() || 'user_id',
        status: $('input[name=status]').val() || 'all',
        s: $('input[name=s]').val() || '',
      };

      // Fix paged
      if ($('input[name=s]').val() != '') {

        data.paged = '1';

      }

      // Now the timer comes to use: we wait half a second after
      // the user stopped typing to actually send the call. If
      // we don't, the keyup event will trigger instantly and
      // thus may cause duplicate calls before sending the intended
      // value
      window.clearTimeout( timer );
      timer = window.setTimeout(function() {
        list.update( data );
      }, delay);

    });

    $(document).on('click', '#search-submit', function(e) {
      
      e.preventDefault();

      // // This time we fetch the variables in inputs
      // var data = {
      //   paged: parseInt( $('input[name=paged]').val() ) || '1',
      //   order: $('input[name=order]').val() || 'DESC',
      //   orderby: $('input[name=orderby]').val() || 'user_id',
      //   status: $('input[name=status]').val() || 'all',
      //   s: $('input[name=s]').val() || '',
      // };

      // // Now the timer comes to use: we wait half a second after
      // // the user stopped typing to actually send the call. If
      // // we don't, the keyup event will trigger instantly and
      // // thus may cause duplicate calls before sending the intended
      // // value
      // window.clearTimeout( timer );
      // timer = window.setTimeout(function() {
      //   list.update( data );
      // }, delay);

    });

  },

  /** AJAX call
   * 
   * Send the call and replace table parts with updated version!
   * 
   * @param    object    data The data to pass through AJAX
   */
  update: function( data ) {

    $('#the-list').animate({'opacity': 0.4}, 300);

    $.ajax({
      // /wp-admin/admin-ajax.php
      url: ajaxurl,
      // Add action and nonce to our collected data
      data: $.extend(
        {
          _ajax_subscriptions_nonce: $('#_ajax_subscriptions_nonce').val(),
          user_id: $('#user_id').val(),
          action: 'wp-ultimo-subscriptions_fetch_ajax_results',
        },
        data
      ),
      // Handle the successful result
      success: function( response ) {

        // WP_List_Table::ajax_response() returns json
        var response = $.parseJSON( response );

        // Add the requested rows
        if ( response.rows.length ){
          $('#the-list').html( response.rows ); 
        }
        $('#the-list').animate({'opacity': 1}, 300);
        // Update column headers for sorting
        if ( response.column_headers.length )
          $('thead tr, tfoot tr').html( response.column_headers );
        // Update pagination for navigation
        if ( response.pagination.bottom.length )
          $('.tablenav.top .tablenav-pages').html( $(response.pagination.top).html() );
        if ( response.pagination.top.length )
          $('.tablenav.bottom .tablenav-pages').html( $(response.pagination.bottom).html() );

        // Init back our event handlers
        list.init();
      }
    });
  },

  /**
   * Filter the URL Query to extract variables
   * 
   * @see http://css-tricks.com/snippets/javascript/get-url-variables/
   * 
   * @param    string    query The URL query part containing the variables
   * @param    string    variable Name of the variable we want to get
   * 
   * @return   string|boolean The variable value if available, false else.
   */
  __query: function( query, variable ) {

    var vars = query.split("&");
    for ( var i = 0; i <vars.length; i++ ) {
      var pair = vars[ i ].split("=");
      if ( pair[0] == variable )
        return pair[1];
    }
    return false;
  },
}

// Show time!
list.init();

})(jQuery);
</script>
<?php
}
add_action('admin_footer', 'ajax_pagination_subscriptions_table_script');

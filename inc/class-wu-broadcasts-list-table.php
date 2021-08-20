<?php
/**
 * Broadcasts List Table
 *
 * Extends the Class that handles WordPress lists tables to display the broadcasted messages
 *
 * @author      Arindo Duque
 * @category    Admin
 * @package     WP_Ultimo/Broadcasts
 * @version     1.1.5
*/

if (!class_exists('WP_List_Table')) {
  require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
}

class WU_Broadcasts_List_Table extends WP_List_Table {

  /** Class constructor */
  public function __construct() {

    parent::__construct([
      'singular' => __( 'Broadcast', 'wp-ultimo'),  // singular name of the listed records
      'plural'   => __( 'Broadcasts', 'wp-ultimo'), // plural name of the listed records
      'ajax'     => true                              // does this table support ajax?
    ]);

  } // end __construct;

  /**
	 * Retrieve broadcasts data from the database
	 *
	 * @param int $per_page
	 * @param int $page_number
	 *
	 * @return mixed
	 */
  public static function get_broadcasts($per_page = 5, $page_number = 1, $type = false, $time = false) {

    $orderby = isset($_GET['orderby']) ? $_GET['orderby'] : false;

    $order = isset($_GET['order']) ? $_GET['order'] : 'DESC';

    global $wpdb;

    $prefix = apply_filters('wu_core_get_table_prefix', $wpdb->base_prefix);

    $sql  = "SELECT DISTINCT ID FROM {$prefix}posts, {$prefix}postmeta";
    $sql .= " WHERE {$prefix}posts.ID = {$prefix}postmeta.post_id AND post_type = 'wpultimo_broadcast' && post_status = 'publish' ";

    if ($type) {
      $sql .= " AND {$prefix}postmeta.meta_key = 'wpu_type' AND {$prefix}postmeta.meta_value = '$type' ";
    }

    if ($time) {
      $sql .= " AND {$prefix}posts.post_date >= '$time'";
    }

    if ($orderby) {
      $sql .= " ORDER BY {$prefix}posts.post_date {$order}";
    } else {
      $sql .= " ORDER BY {$prefix}posts.post_date DESC";
    }

    $sql .= " LIMIT $per_page";
    $sql .= ' OFFSET ' . ( $page_number - 1 ) * $per_page;

    $results = $wpdb->get_results( $sql, 'ARRAY_A' );
    
    foreach ($results as $index => &$result) {
      $result = new WU_Broadcast($result['ID']);
    }

    return $results;

  } // end get_broadcasts;

  /**
	 * Delete a broadcast record.
	 *
	 * @param int $id plan ID
	 */
  public static function delete_broadcast($id) {

    global $wpdb;

    $prefix = apply_filters('wu_core_get_table_prefix', $wpdb->base_prefix);

    $wpdb->delete(
      "{$prefix}posts",
      [ 'ID' => $id ],
      [ '%d' ]
    );

    $wpdb->delete(
      "{$prefix}postmeta",
      [ 'post_id' => $id ],
      [ '%d' ]
    );

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

  public function column_post_content($item) {

    if ($item->post_title) echo sprintf('<strong>%s</strong><br>', $item->post_title);
    
    if ($item->type == 'message') echo $item->post_content;

    else {

      echo "<div style='display: none;' id='broadcast-content-$item->id'>
        <div style='padding-top: 20px'>$item->post_content</div>
      </div>";

      printf('<a title="%s" href="%s" class="thickbox">%s</a>', $item->post_title, "#TB_inline?width=600&height=400&inlineId=broadcast-content-$item->id", __('View Content', 'wp-ultimo'));

    }

  }

  /**
	 * Returns the count of records in the database.
	 *
	 * @return null|string
	 */
  public static function record_count() {

    global $wpdb;

    $prefix = apply_filters('wu_core_get_table_prefix', $wpdb->base_prefix);

    $sql  = "SELECT COUNT(ID) FROM {$prefix}posts";
    $sql .= " WHERE post_type = 'wpultimo_broadcast' && post_status = 'publish'";
    
    return $wpdb->get_var($sql);

  } // end record_count;

  /** Text displayed when no plan data is available */
  public function no_items() {
    _e( 'No broadcasts found.', 'wp-ultimo');
  }

  /**
	 * Associative array of columns
	 * @return array
	 */
  function get_columns() {

    $columns = apply_filters('wu_get_broadcasts_table_headers', array(
      // 'id'           => __('ID', 'wp-ultimo'),
      'style'        => WU_Util::tooltip(__('Broadcast Style', 'wp-ultimo'), 'dashicons-marker') . sprintf('<span class="wu-broadcast-type-label">%s</span>', __('Style', 'wp-ultimo')),
      'type'         => __('Type', 'wp-ultimo'),
      'post_content' => __('Content', 'wp-ultimo'),
      'targets'      => __('Targets', 'wp-ultimo'),
      'post_date'    => __('Date', 'wp-ultimo'),
      'actions'      => __('Actions', 'wp-ultimo'),
    ));

    return $columns;

  } // end get_columns;

  /**
	 * Columns to make sortable.
	 * @return array
	 */
  public function get_sortable_columns() {

    $sortable_columns = array(
      // 'id'           => array('id', false),
      // 'type'         => array('type', false),
      // 'style'        => array('style', false),
      'post_date'    => array('post_date', false),
      // 'actions'      => array('', false),
      // 'amount'       => array('amount', false),
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
   * Display the table
   * Adds a Nonce field and calls parent's display method
   *
   * @since 3.1.0
   * @access public
   */
  public function display() {

    wp_nonce_field('ajax-broadcast-nonce', '_ajax_broadcast_nonce');

    $order   = isset($this->_pagination_args['order']) ? $this->_pagination_args['order']  : '';
    $orderby = isset($this->_pagination_args['orderby']) ? $this->_pagination_args['orderby']  : '';

    echo '<input type="hidden" id="order" name="order" value="' . $order . '" />';
    echo '<input type="hidden" id="orderby" name="orderby" value="' . $orderby . '" />';

    parent::display();

  }

  /**
   * Column Style
   * @param  object $item
   * @return string
   */
  public function column_style($item) {

    $item->style = $item->style ?: 'email';

    $icon = WU_Util::tooltip(ucfirst($item->style), 'dashicons-marker');

    if ($item->type == 'email') {

      echo "<div class='broadcast-style style-email'>$icon</div>";

    } else {

      echo "<div class='broadcast-style style-$item->style'>$icon</div>";

    }

  } // end column_type;

  /**
   * Post Type
   * @param  object $item
   * @return 
   */
  public function column_type($item) {

    if ($item->type == 'message') {

      echo WU_Util::tooltip(ucfirst($item->type), "dashicons-admin-comments");

    }

    else echo WU_Util::tooltip(ucfirst($item->type), "dashicons-email");

  } // end column_type;

  /**
   * Display the targets of a given email
   * @param  object $item
   * @return 
   */
  public function column_targets($item) {

    $total = 0;

    $content = '';

    /**
     * Get Plans
     */
    if (is_array($item->target_plans) && !empty($item->target_plans)) {

      $content = '<strong>Plans</strong>: <br>';

      foreach ($item->target_plans as $plan_id) {

        $plan   = new WU_Plan($plan_id);
        $people = $plan->get_subscription_count();

        $total += $people;

        $content .= $plan->title ." ($people ". __('users', 'wp-ultimo') .") <br>";

      } // end foreach;

      $content .= '<br>';

    } // end if;

    /**
     * Get Users
     */
    if (is_array($item->target_users) && !empty($item->target_users)) {

      $content .= '<strong>Users</strong>: <br>';

      foreach ($item->target_users as $user_id) {

        $user = get_user_by('ID', $user_id);

        if (!$user) continue;

        $content .= $user->display_name ." ($user->user_email) <br>";

        $total++;

      } // end foreach;

      $content .= '<br>';

    } // end if;

    $content .= "<strong>Total</strong>: $total";

    echo WU_Util::tooltip($content, "dashicons-groups");

  } // end column_targets;

  public function column_actions($item) {

    $delete_nonce = wp_create_nonce('wpultimo_delete_message');

    $delete_link = sprintf('?page=%s&action=%s&message=%s&_wpnonce=%s', 'wp-ultimo-broadcast', 'delete', absint($item->id), $delete_nonce);

    if ($item->type !== 'email') {
      echo sprintf('<a href="%s" class="button">%s</a>', $delete_link, __('Delete'));
    } else {
      echo sprintf('<button class="button wu-tooltip" title="sasa" disabled="disabled">%s</button>', __('Delete'));
    }

  }

  /**
   * Formats the output of the column time, adding a tooltip for the full time, 
   * but using the selected time format on the WordPress panel
   * @param  WU_Broadcast $item A broadcast instance
   * @return string         HTML to be displyed
   */
  public static function column_post_date($item) {

    $full_date        = date('Y-m-d H:i:s', strtotime($item->post_date));
    $nice_format_date = date(get_option('date_format'), strtotime($item->post_date));
    
    return sprintf('<span class="wu-tooltip" title="%s">%s</span>', $full_date, $nice_format_date);

  } // end column_time;

  /**
   * Handle an incoming ajax request (called from admin-ajax.php)
   *
   * @since 3.1.0
   * @access public
   */
  function ajax_response() {

    check_ajax_referer('ajax-broadcast-nonce', '_ajax_broadcast_nonce');

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

    /** Process bulk action */
    $this->process_bulk_action();

    $per_page     = $this->get_items_per_page('broadcasts_per_page', 5);
    $current_page = $this->get_pagenum();
    $total_items  = self::record_count();

    $this->set_pagination_args([
      'total_items' => $total_items, // WE have to calculate the total number of items
      'per_page'    => $per_page     // WE have to determine how many items to show on a page
    ]);

    $this->items = self::get_broadcasts($per_page, $current_page);

  } // end prepare_items;

  /**
   * Process Bulk Actions
   * @return
   */
  public function process_bulk_action() {

    // Detect when a bulk action is being triggered...
    if ( 'delete' === $this->current_action() ) {

      // In our file that handles the request, verify the nonce.
      $nonce = esc_attr( $_REQUEST['_wpnonce'] );

      if ( ! wp_verify_nonce( $nonce, 'wpultimo_delete_message' ) ) {
        die( 'Go get a life script kiddies' );
      }

      else {
        $url = sprintf('?page=%s&deleted=1', 'wp-ultimo-broadcast');
        self::delete_broadcast( absint( $_GET['message'] ) );
        wp_redirect($url);
        exit;

      }

    }

  } // end process_bulk_action;

}

/**
 * Callback function for 'wp_ajax__ajax_fetch_broadcasts_list' action hook.
 * 
 * Loads the Custom List Table Class and calls ajax_response method
 */
function _ajax_fetch_broadcasts_list_callback() {

  $wp_list_table = new WU_Broadcasts_List_Table();
  $wp_list_table->ajax_response();

}

add_action('wp_ajax__ajax_fetch_broadcasts_list', '_ajax_fetch_broadcasts_list_callback');

/**
 * This function adds the jQuery script to the plugin's page footer
 */
function ajax_pagination_broadcasts_table_script() {

  $screen = get_current_screen();

  if (!$screen) return;

  $allowed = array('wp-ultimo_page_wp-ultimo-broadcast-network');

  if (!in_array($screen->id, $allowed)) return;

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
        orderby: list.__query( query, 'orderby' ) || 'time'
      };
      list.update( data );
    });

    // Page number input
    $('input[name=paged]').on('keyup', function(e) {

      // If user hit enter, we don't want to submit the form
      // We don't preventDefault() for all keys because it would
      // also prevent to get the page number!
      if ( 13 == e.which )
        e.preventDefault();

      // This time we fetch the variables in inputs
      var data = {
        paged: parseInt( $('input[name=paged]').val() ) || '1',
        order: $('input[name=order]').val() || 'asc',
        orderby: $('input[name=orderby]').val() || 'title'
      };

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
          _ajax_broadcast_nonce: $('#_ajax_broadcast_nonce').val(),
          action: '_ajax_fetch_broadcasts_list',
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

        // Install Tooltip
        $('.wu-tooltip').tipTip();

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
add_action('admin_footer', 'ajax_pagination_broadcasts_table_script');

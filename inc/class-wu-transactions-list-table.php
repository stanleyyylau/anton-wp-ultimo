<?php
/**
 * Transactions List Table
 *
 * Extends the Class that handles WordPress lists tables to display our susbscriptions
 *
 * @author      Arindo Duque
 * @category    Admin
 * @package     WP_Ultimo/Transactions
 * @version     1.1.0
*/

if (!class_exists('WP_List_Table')) {
  require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
}

class WU_Transactions_List_Table extends WP_List_Table {

  public static $user_id;

  /** Class constructor */
  public function __construct($user_id = false) {

    self::$user_id = $user_id;

    parent::__construct([
      'singular' => __( 'Transaction', 'wp-ultimo'),  // singular name of the listed records
      'plural'   => __( 'Transactions', 'wp-ultimo'), // plural name of the listed records
      'ajax'     => true                              // does this table support ajax?
    ]);

  } // end __construct;

  /**
	 * Retrieve subscriptions data from the database
	 *
	 * @param int $per_page
	 * @param int $page_number
	 *
	 * @return mixed
	 */
  public static function get_transactions($per_page = 5, $page_number = 1) {

    $orderby = isset($_GET['orderby']) ? $_GET['orderby'] : false;

    $order = isset($_GET['order']) ? $_GET['order'] : false;

    $results = WU_Transactions::get_transactions(self::$user_id, $per_page, $page_number, $orderby, $order, false);

    return apply_filters('wu_transactions_list_table_get_transactions', $results, self::$user_id, $per_page, $page_number, $orderby, $order);

  } // end get_transactions;

  /**
	 * Delete a cupon record.
	 *
	 * @param int $id plan ID
	 */
  public static function delete_coupon($id) {

    global $wpdb;

    // TODO: Delete from the database

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

    $results = WU_Transactions::get_transactions(self::$user_id, false, false, false, false, true);

    return apply_filters('wu_transactions_list_table_get_transactions_count', $results, self::$user_id);

  } // end record_count;

  /** Text displayed when no plan data is available */
  public function no_items() {
    _e( 'No transactions found.', 'wp-ultimo');
  }

  /**
	 * Associative array of columns
	 * @return array
	 */
  function get_columns() {

    $columns = array(
      'type'        => WU_Util::tooltip(__('Transaction Type', 'wp-ultimo'), 'dashicons-marker'),
      'id'          => __('ID', 'wp-ultimo'),
      'gateway'     => __('Gateway', 'wp-ultimo'),
      'description' => __('Description', 'wp-ultimo'),
      'time'        => __('Date', 'wp-ultimo'),
      'amount'      => get_wu_currency_symbol(),
      'refund'      => __('Refund', 'wp-ultimo'),
    );

    if (!current_user_can('manage_network')) {

      unset($columns['id']);
      unset($columns['gateway']);
      unset($columns['refund']);

    } // end if;

    return apply_filters('wu_get_transactions_table_headers', $columns);

  } // end get_columns;

  /**
	 * Columns to make sortable.
	 * @return array
	 */
  public function get_sortable_columns() {

    $sortable_columns = array(
      'id'           => array('id', false),
      'type'         => array('type', false),
      'time'         => array('time', false),
      'gateway'      => array('gateway', false),
      'amount'       => array('amount', false),
    );

    return apply_filters('wu_get_transactions_table_sortable_columns', $sortable_columns);

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

    wp_nonce_field('ajax-transaction-nonce', '_ajax_transaction_nonce');

    $order   = isset($this->_pagination_args['order']) ? $this->_pagination_args['order']  : '';
    $orderby = isset($this->_pagination_args['orderby']) ? $this->_pagination_args['orderby']  : '';

    echo '<input type="hidden" id="user_id" name="user_id" value="' . self::$user_id . '" />';
    echo '<input type="hidden" id="order" name="order" value="' . $order . '" />';
    echo '<input type="hidden" id="orderby" name="orderby" value="' . $orderby . '" />';

    parent::display();

  }

  /**
   * Formats the output of the column refund, displaying the refund form
   * @param  WU_Transaction $item A transaction instance
   * @return string         HTML to be displyed
   */
  public static function column_refund($item) {

    if (!current_user_can('manage_network')) return;
  
    ?>
<!-- 
    <div class="refund-fields">
      <label for="refund-value"><?php _e('Refund Value', 'wp-ultimo'); ?></label>
      <input id="refund-value" name="refund-value" value="<?php echo $item->amount; ?>" placeholder="<?php _e('Enter a partial or the total value', 'wp-ultimo'); ?>">

    </div> -->
    
    <div class="refund-actions row">

      <div class="wu-col-sm-5">
        <button class="button wu-close-refund"><?php _e('Close', 'wp-ultimo'); ?></button>
        <span style="line-height: 32px; margin-left: 6px;"><?php printf(__('Transaction #%s - Amount %s', 'wp-ultimo'), $item->id, wu_format_currency( $item->amount )); ?></span>
      </div>

      <div class="wu-col-sm-7 text-right">
        <label for="refund-value"><?php _e('Refund Value', 'wp-ultimo'); ?></label>

        <input class="refund-value" id="refund-value" value="<?php echo $item->amount; ?>" placeholder="<?php _e('Enter a partial or the total value', 'wp-ultimo'); ?>">
        <input class="refund-gateway" value="<?php echo $item->gateway; ?>" type="hidden">

        <input class="refund-transaction-id" value="<?php echo $item->id; ?>" type="hidden">

        <button class="button button-primary wu-send-refund" type="submit"><?php _e('Issue Refund', 'wp-ultimo'); ?></button>
      </div>

    </div>

    <?php

  }

  /**
   * Formats the output of the column type, displaying icons 
   * @param  WU_Transaction $item A transaction instance
   * @return string         HTML to be displyed
   */
  public static function column_type($item) {

    $types = array(

      'recurring_setup' => array(
        'icon'    => 'dashicons-update',
        'tooltip' => __('Setting up a Payment Integration.', 'wp-ultimo'),
      ),
      'payment'   => array(
        'icon'    => 'dashicons-marker',
        'tooltip' => __('Payment Successfully', 'wp-ultimo'),
      ),
      'failed'    => array(
        'icon'    => 'dashicons-marker',
        'tooltip' => __('Payment Failed', 'wp-ultimo'),
      ),
      'pending'   => array(
        'icon'    => 'dashicons-flag',
        'tooltip' => __('Payment Pending', 'wp-ultimo'),
      ),
      'refund'    => array(
        'icon'    => 'dashicons-image-rotate',
        'tooltip' => __('Payment Refunded', 'wp-ultimo'),
      ),
      'cancel'    => array(
        'icon'    => 'dashicons-dismiss',
        'tooltip' => __('Payment Integration Canceled', 'wp-ultimo'),
      )

    );

    return sprintf("<span class='%s'>%s</span>", $item->type, WU_Util::tooltip($types[$item->type]['tooltip'], $types[$item->type]['icon']));

  } // end column_type;

  /**
   * Add custom actions to the description
   * @param  WU_Transaction $item A transaction instance
   * @return string         HTML to be displyed
   */
  public function column_description($item) {

    // return $item->description;

    if (current_user_can('manage_network') && $item->type == 'payment') {
    
      $actions = array(
        'refund' => sprintf('<a href="#" data-transaction="%s" data-gateway="%s" class="wu-refund-trigger" aria-label="%s">%s</a>', $item->id, $item->gateway, __('Refund', 'wp-ultimo'), __('Refund', 'wp-ultimo')),
        'delete' => sprintf('<a href="#" data-transaction="%s" data-gateway="%s" class="wu-delete-trigger" aria-label="%s">%s</a>', $item->id, $item->gateway, __('Delete', 'wp-ultimo'), __('Delete', 'wp-ultimo')),
        //'quickactions' => sprintf('<a href="%s%s">%s</a>', wu_get_active_gateway()->get_url('process-refund&transaction_id='), $transaction->id, __('Full Refund', 'wp-ultimo')),
      );

    } else {

      $actions = array();

    }

    return $item->description . $this->row_actions(apply_filters('wu_transaction_item_actions', $actions, $item));

  } // end column_description;

  public function inline_edit() { ?>

  <form method="get"><table style="display: none">
  <tbody id="inlineedit">
  </tbody>
  </table>
  </form>
  
  <?php }

  /**
   * Formats the output of the column amount, using our helper function to format it as money
   * @param  WU_Transaction $item A transaction instance
   * @return string         HTML to be displyed
   */
  public static function column_amount($item) {

    if ($item->type == 'refund') {

      return is_numeric($item->amount) ? '<span style="color: darkred;">- ' .wu_format_currency($item->amount). '</span>': $item->amount;

    }

    $original_amount = '';

    if ($item->original_amount != $item->amount) {

      $original_amount = is_numeric($item->original_amount) ? '<span style="color: gray; text-decoration: line-through;">'. wu_format_currency($item->original_amount) .'</span><br>' : '';

    }
    
    return is_numeric($item->amount) ? $original_amount . wu_format_currency($item->amount) : $item->amount;

  } // end column_amount;

  /**
   * Formats the output of gateway field, applying an uppercase to the first letter
   * @param  WU_Transaction $item A transaction instance
   * @return string         HTML to be displyed
   */
  public static function column_gateway($item) {

    return ucfirst($item->gateway);

  } // end column_gateway;

  /**
   * Formats the output of the column time, adding a tooltip for the full time, 
   * but using the selected time format on the WordPress panel
   * @param  WU_Transaction $item A transaction instance
   * @return string         HTML to be displyed
   */
  public static function column_time($item) {

    $full_date        = date_i18n('Y-m-d H:i:s', strtotime($item->time));
    $nice_format_date = date_i18n(get_blog_option(1, 'date_format'), strtotime($item->time));
    
    return sprintf('<span class="wu-tooltip" title="%s">%s</span>', $full_date, $nice_format_date);

  } // end column_time;

  /**
   * Handle an incoming ajax request (called from admin-ajax.php)
   *
   * @since 3.1.0
   * @access public
   */
  function ajax_response() {

    check_ajax_referer('ajax-transaction-nonce', '_ajax_transaction_nonce');

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

    $per_page     = $this->get_items_per_page('transactions_per_page', 5);
    $current_page = $this->get_pagenum();
    $total_items  = self::record_count();

    $this->set_pagination_args([
      'total_items' => $total_items, // WE have to calculate the total number of items
      'per_page'    => $per_page     // WE have to determine how many items to show on a page
    ]);

    $this->items = self::get_transactions($per_page, $current_page);

  } // end prepare_items;

  public function process_bulk_action() {}

}

/**
 * Callback function for 'wp_ajax__ajax_fetch_transactions_list' action hook.
 * 
 * Loads the Custom List Table Class and calls ajax_response method
 */
function _ajax_fetch_transactions_list_callback() {

  $user_id = isset($_GET['user_id']) ? $_GET['user_id'] : false;

  $wp_list_table = new WU_Transactions_List_Table($user_id);

  $wp_list_table->ajax_response();
  
}

add_action('wp_ajax__ajax_fetch_transactions_list', '_ajax_fetch_transactions_list_callback');

/**
 * This function adds the jQuery script to the plugin's page footer
 */
function ajax_pagination_transactions_table_script() {

  $screen = get_current_screen();

  if (!$screen) return;
  
  $allowed = array('toplevel_page_wu-my-account');

  if (strpos($screen->id, 'page_wu-edit-subscription-network') === false && !in_array($screen->id, $allowed)) return;

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
          _ajax_transaction_nonce: $('#_ajax_transaction_nonce').val(),
          user_id: $('#user_id').val(),
          action: 'wu_transactions_fetch_ajax_results',
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

  var open_tr = function(target, tds) {

    target.attr('colspan', 6);

    tds.hide(0, function() {
      target.show();
    });

  }

  var close_tr = function(target, tds) {

    tds.show(0, function() {
      target.hide();
    });

  }

  $('body').on('click', '.wu-send-refund', function(e) {

    e.preventDefault();
    
    var _this          = $(this);
    var original_label = _this.html();
    var refund_value   = _this.parents('tr').find('input.refund-value').val();
    var gateway        = _this.parents('tr').find('input.refund-gateway').val();
    var transaction_id = _this.parents('tr').find('input.refund-transaction-id').val();
    var url            = $('.wu-subscription-site-url').val();

    _this.attr('disabled', 'disabled').html('<?php _e('Loading...', 'wp-ultimo'); ?>');

    $.ajax({
      url: ajaxurl,// url + "/<?php echo str_replace(get_admin_url(), '', wu_get_active_gateway()->get_url('process-refund')); ?>",
      data: {
        action: 'wu_process_refund_' + gateway,
        value: refund_value,
        gateway: gateway,
        transaction_id: transaction_id
      },
      dataType: 'json',
      success: function(data) {

        _this.html(data.message);

        setTimeout(function() {
          _this.html(original_label).removeAttr('disabled');
        }, 4000);

      }
    })

  });

  $('body').on('click', '.wu-close-refund', function(e) {

    e.preventDefault();

    var _this  = $(this);
    var tds    = _this.parents('tr').find('td');
    var target = _this.parents('tr').find('.column-refund');

    close_tr(target, tds);

  });

  $('body').on('click', '.wu-refund-trigger', function(e) {

    e.preventDefault();

    var _this  = $(this);
    var tds    = _this.parents('tr').find('td');
    var target = _this.parents('tr').find('.column-refund');

    open_tr(target, tds);

  });

  $('body').on('click', '.wu-delete-trigger', function(e) {

    e.preventDefault();

    var _this = $(this);

    wuswal({
      title: "<?php _e('Are you sure?', 'wp-ultimo'); ?>",
      text: "<?php _e('Are you sure you want to remove this transaction? This can not be undone.', 'wp-ultimo'); ?>",
      type: "warning",
      showCancelButton: true,
      // confirmButtonColor: "#DD6B55",
      confirmButtonText: "<?php _e('Yes, I\'m sure', 'wp-ultimo'); ?>",
      cancelButtonText: "<?php _e('Cancel', 'wp-ultimo'); ?>",
      closeOnConfirm: false,
      closeOnCancel: true,
      showLoaderOnConfirm: true,
      html: true,
    },
    function(isConfirm) {
      if (isConfirm) {
        delete_transaction(_this, function() {
          wuswal.close();
        });
      }
    });

  });

  function delete_transaction(_this, cb) {

    var original_label = _this.html();
    var transaction_id = _this.parents('tr').find('input.refund-transaction-id').val(); 

    _this.html('<?php echo esc_js(__('Deleting...', 'wp-ultimo')); ?>');

    $.ajax({
      url: ajaxurl,
      type: 'post',
      data: {
        action: 'callback_transaction_process_delete',
        transaction_id: transaction_id,
        _wpnonce: '<?php echo esc_js(wp_create_nonce( 'wpultimo_delete_transaction' )); ?>',

      },
      dataType: 'json',
      success: function(response) {

        $('#wu-mb-subscriptions-details').block({
          message: null,
          overlayCSS: {
            background: '#F1F1F1',
            opacity: 0.4
          }
        });

        cb();
        
        _this.html(response.data.message);

        list.update();

        setTimeout(function() {
          _this.html(original_label).removeAttr('disabled');
          $('#wu-mb-subscriptions-details').unblock();
        }, 4000);

      }
    }) // end ajax;

  } // end delete transaction;

  /**
   * @since  1.2.0, Mark as Paid
   */
  $('body').on('click', '.wu-paid-trigger', function(e) {

    e.preventDefault();
    
    var _this          = $(this);
    var original_label = _this.html();
    var gateway        = _this.data('gateway');
    var transaction_id = _this.data('transaction');

    _this.attr('disabled', 'disabled').html('<?php _e('Loading...', 'wp-ultimo'); ?>');

    $.ajax({
      url: ajaxurl,// url + "/<?php echo str_replace(get_admin_url(), '', wu_get_active_gateway()->get_url('process-refund')); ?>",
      data: {
        action: 'wu_process_marked_as_paid_' + gateway,
        gateway: gateway,
        transaction_id: transaction_id
      },
      dataType: 'json',
      success: function(data) {

        $('#wu-mb-subscriptions-details').block({
          message: null,
          overlayCSS: {
            background: '#F1F1F1',
            opacity: 0.4
          }
        });

        _this.html(data.message);

        list.update();

        setTimeout(function() {

          _this.html(original_label).removeAttr('disabled');

          if (data.status) {

            // Change elements
            $('#wu-mb-subscriptions-details').find('.remaining').html(data.remaining_string);
            $('#wu-mb-subscriptions-details').find('.status').removeClass('active').removeClass('on-hold').removeClass('inactive').addClass(data.status).html(data.status_label);
            
            $('#wu-mb-subscriptions-details').find('.active_until_value').html(data.active_until);
            $('#wu-mb-subscriptions-details').find('#active_until').val(data.active_until);

            _this.html(original_label).removeAttr('disabled');

          }

          $('#wu-mb-subscriptions-details').unblock();

        }, 4000);

      }
    })

  });


})(jQuery);
</script>
<?php
}
add_action('admin_footer', 'ajax_pagination_transactions_table_script');

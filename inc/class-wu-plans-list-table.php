<?php

if ( ! class_exists( 'WP_List_Table' ) ) {
  require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class WU_Plans_List_Table extends WP_List_Table {

  /** Class constructor */
  public function __construct() {

    parent::__construct( [
      'singular' => __( 'Plan', 'wp-ultimo'), //singular name of the listed records
      'plural'   => __( 'Plans', 'wp-ultimo'), //plural name of the listed records
      'ajax'     => true // does this table support ajax?
    ] );

  }
  
  /**
	 * Retrieve plans data from the database
	 *
	 * @param int $per_page
	 * @param int $page_number
	 *
	 * @return mixed
	 */
  public static function get_plans( $per_page = 50, $page_number = 1 ) {

    global $wpdb;

    $prefix = apply_filters('wu_core_get_table_prefix', $wpdb->base_prefix);

    $sql  = "SELECT DISTINCT ID FROM {$prefix}posts, {$prefix}postmeta";
    $sql .= " WHERE {$prefix}posts.ID = {$prefix}postmeta.post_id AND {$prefix}postmeta.meta_key = 'wpu_order' AND post_type = 'wpultimo_plan' && post_status = 'publish' ORDER BY CAST({$prefix}postmeta.meta_value as unsigned) ASC";
    
    //$sql .= " ORDER BY $wpdb->postmeta.order ASC";
//    if ( ! empty( $_REQUEST['orderby'] ) ) {
//      $sql .= ' ORDER BY ' . esc_sql( $_REQUEST['orderby'] );
//      $sql .= ! empty( $_REQUEST['order'] ) ? ' ' . esc_sql( $_REQUEST['order'] ) : ' ASC';
//    } 

    $sql .= " LIMIT $per_page";
    $sql .= ' OFFSET ' . ( $page_number - 1 ) * $per_page;

    $results = $wpdb->get_results( $sql, 'ARRAY_A' );
    
    foreach ($results as $index => &$result) {
      $result = new WU_Plan($result['ID']);
    }

    return $results;

  }

  /**
	 * Delete a plan record.
	 *
	 * @param int $id plan ID
	 */
  public static function delete_plan( $id ) {
    global $wpdb;

    $wpdb->delete(
      "{$wpdb->prefix}posts",
      [ 'ID' => $id ],
      [ '%d' ]
    );

    $wpdb->delete(
      "{$wpdb->prefix}postmeta",
      [ 'post_id' => $id ],
      [ '%d' ]
    );
  }

  /**
	 * Duplicate a given plan
	 *
   * @since 1.9.0
	 * @param int $id plan ID
	 */
  public static function duplicate_plan($id) {
    
    global $wpdb;

    $base_plan = wu_get_plan($id);

    if (!$base_plan) return;

    $new_plan = $base_plan;

    foreach($new_plan->meta_fields as $field_name) {

      $new_plan->{$field_name} = $base_plan->{$field_name};

    } // end foreach;

    $new_plan->title = sprintf(__('%s (Copy)', 'wp-ultimo'), $base_plan->title);
    $new_plan->id = 0;

    $new_plan->save();

  } // end duplicate_plan;

  /**
	 * Returns the count of records in the database.
	 *
	 * @return null|string
	 */
  public static function record_count() {
    global $wpdb;

    $sql  = "SELECT COUNT(ID) FROM {$wpdb->prefix}posts";
    $sql .= " WHERE post_type = 'wpultimo_plan' && post_status = 'publish'";
    
    return $wpdb->get_var($sql);
  }


  /** Text displayed when no plan data is available */
  public function no_items() {
    _e( 'No Plans avaliable.', 'wp-ultimo');
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
    
    // Check for price
    if (strpos($column_name, 'price') !== false) {
      
      // Check for free
      if ($item->free) return __('Free', 'wp-ultimo');

      else if ($item->is_contact_us()) return $item->get_contact_us_label();
      
      return wu_format_currency($item->{$column_name});

    } // end if;
    
    else return $item->{$column_name};
    
  }

  /**
   * Displays the Setup Fee
   *
   * @since 1.7.0
   * @param WU_Plan $item
   * @return void
   */
  public function column_setup_fee($item) {

    return $item->has_setup_fee() ? wu_format_currency($item->get_setup_fee()) : __('No Setup Fee', 'wp-ultimo');

  } // end column_setup_fee;

  /**
	 * Render the bulk edit checkbox
	 *
	 * @param array $item
	 *
	 * @return string
	 */
  function column_cb( $item ) {
    return sprintf(
      '<input type="checkbox" name="bulk-delete[]" value="%s" />', $item->id
    );
  }
  
  /**
   * Renders the site count column, displaying how many customers are using that plan
   * @param  object $item The plan being displayed
   * @return string       The html code to be rendered
   */
  function column_customers($item) {
    $count = $item->get_subscription_count();
    return $count == 0 ? __('None', 'wp-ultimo') : $count;
  }

  /**
   * Displays the drag handles, for plan reorder
   * @param  object $item The plan being displayed
   * @return string       The html code to be rendered
   */
  function column_drag($item) {
    return '<span class="dashicons dashicons-menu"></span>';
  }
  
  /**
   * Displays the custom domain option of that plan
   * @param  object $item The plan being displayed
   * @return string       The html code to be rendered
   */
  function column_custom_domain($item) {
    return $item->custom_domain ? __('Yes') : __('No');
  }

  /**
   * Method for name column
   *
   * @param array  $item an array of DB data
   *
   * @return string
   */
  function column_title( $item ) {

    $title = sprintf('<a href="?page=wu-edit-plan&plan_id=%s">%s</a>', $item->id, $item->title);
    
    $top_deal = $item->top_deal ? '<span class="wu-top-deal">'.__('Featured', 'wp-ultimo').'</span>' : '';

    $hidden = $item->hidden ? '<span class="wu-hidden">'.__('Hidden', 'wp-ultimo').'</span>' : '';
    
    $title = sprintf('<strong>%s %s %s</strong>', $title, $top_deal, $hidden);

    $actions = [
      'edit' => sprintf( '<a href="?page=wu-edit-plan&plan_id=%s">%s</a>', absint($item->id), __('Edit', 'wp-ultimo')),

      'duplicate' => sprintf( '<a href="?page=%s&action=%s&plan=%s&_wpnonce=%s">%s</a>', esc_attr($_REQUEST['page']), 'duplicate', absint($item->id), wp_create_nonce( 'wpultimo_duplicate_plan' ), __('Duplicate', 'wp-ultimo')),

      'delete' => sprintf( '<a href="?page=%s&action=%s&plan=%s&_wpnonce=%s">%s</a>', esc_attr($_REQUEST['page']), 'delete', absint($item->id), wp_create_nonce( 'wpultimo_delete_plan' ), __('Delete', 'wp-ultimo')),
      
      //'see-users' => sprintf('<a href="%s">%s</a>', network_admin_url('users.php?wu-plan='.$item->id), __('See Sites', 'wp-ultimo')),
    ];
    
    // Input to save the custom order
    $input = '<input type="hidden" class="plan-order-input" name="plan_order['.$item->id.']">';

    return $title . $this->row_actions($actions) . $input;

  }

  /**
	 *  Associative array of columns
	 *
	 * @return array
	 */
  function get_columns() {

    $columns = [
      'cb'            => '<input type="checkbox" />',
      'title'         => __( 'Name', 'wp-ultimo'),
      'price_1'       => __( 'Monthly Price', 'wp-ultimo'),
      'price_3'       => __( '3 Months Price', 'wp-ultimo'),
      'price_12'      => __( 'Yearly Price', 'wp-ultimo'),
      'setup_fee'     => __( 'Setup Fee', 'wp-ultimo'), // @since 1.7.0
      'customers'     => __( 'Customers', 'wp-ultimo'),
      'custom_domain' => __( 'Custom Domain', 'wp-ultimo'),
      'drag'          => WU_Util::tooltip(__('You can drag plans to the order you like. That order will be applied to the pricing tables and will be used as reference to the plan upgrade order.', 'wp-ultimo'))
    ];

    return $columns;
  }


  /**
	 * Columns to make sortable.
	 *
	 * @return array
	 */
  public function get_sortable_columns() {
    
    $sortable_columns = array(
      // 'title' => array('post_title', true),
      // 'city'  => array('city', false)
    );

    return $sortable_columns;
  }

  /**
	 * Returns an associative array containing the bulk action
	 *
	 * @return array
	 */
  public function get_bulk_actions() {
    $actions = [
      'bulk-delete' => __('Delete')
    ];

    return $actions;
  }


  /**
	 * Handles data query and filter, sorting, and pagination.
	 */
  public function prepare_items() {

    $this->_column_headers = $this->get_column_info();

    /** Process bulk action */
    $this->process_bulk_action();

    $per_page     = 50; //$this->get_items_per_page('plans_per_page', 50);
    $current_page = $this->get_pagenum();
    $total_items  = self::record_count();

    // $this->set_pagination_args( [
    //   'total_items' => $total_items, //WE have to calculate the total number of items
    //   'per_page'    => $per_page //WE have to determine how many items to show on a page
    // ] );

    $this->items = self::get_plans($per_page, $current_page );
  }

  /**
   * Get base URL
   * 
   * @since  1.7.3
   * @return string
   */
  public function get_base_url() {

    return network_admin_url('admin.php?page=wp-ultimo-plans');

  } // end get_base_url;

  public function process_bulk_action() {

    // Detect when a bulk action is being triggered...
    if ( 'delete' === $this->current_action() ) {

      // In our file that handles the request, verify the nonce.
      $nonce = esc_attr( $_REQUEST['_wpnonce'] );

      if ( ! wp_verify_nonce( $nonce, 'wpultimo_delete_plan' ) ) {
        die( 'Go get a life script kiddies' );
      } else {

        self::delete_plan( absint( $_GET['plan'] ) );
        wp_redirect( add_query_arg('deleted', 1, $this->get_base_url()) );
        exit;

      }

    } // end if;

    // Detect when a bulk action is being triggered...
    if ( 'duplicate' === $this->current_action() ) {

      // In our file that handles the request, verify the nonce.
      $nonce = esc_attr( $_REQUEST['_wpnonce'] );

      if ( ! wp_verify_nonce( $nonce, 'wpultimo_duplicate_plan' ) ) {
        die( 'Go get a life script kiddies' );
      } else {

        self::duplicate_plan( absint( $_GET['plan'] ) );
        wp_redirect( add_query_arg('duplicated', 1, $this->get_base_url()) );
        exit;

      }

    } // end if;

    // If the delete bulk action is triggered
    if ( ( isset( $_POST['action'] ) && $_POST['action'] == 'bulk-delete' )
        || ( isset( $_POST['action2'] ) && $_POST['action2'] == 'bulk-delete' )
       ) {

      $delete_ids = esc_sql( $_POST['bulk-delete'] );

      // loop over the array of record IDs and delete them
      foreach ( $delete_ids as $id ) {
        self::delete_plan( $id );

      }
      
      wp_redirect( add_query_arg('deleted', count( $delete_ids ), $this->get_base_url()) );
      exit;

    }
  }

}

<?php

if ( ! class_exists( 'WP_List_Table' ) ) {
  require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class WU_Coupons_List_Table extends WP_List_Table {

  /** Class constructor */
  public function __construct() {

    parent::__construct( [
      'singular' => __( 'Coupon', 'wp-ultimo'), //singular name of the listed records
      'plural'   => __( 'Coupons', 'wp-ultimo'), //plural name of the listed records
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
  public static function get_coupons( $per_page = 5, $page_number = 1 ) {

    global $wpdb;

    $sql  = "SELECT DISTINCT ID FROM {$wpdb->prefix}posts, {$wpdb->prefix}postmeta";
    $sql .= " WHERE $wpdb->posts.ID = $wpdb->postmeta.post_id AND post_type = 'wpultimo_coupon' && post_status = 'publish' ORDER BY $wpdb->postmeta.meta_value ASC";
    
    //$sql .= " ORDER BY $wpdb->postmeta.order ASC";
//    if ( ! empty( $_REQUEST['orderby'] ) ) {
//      $sql .= ' ORDER BY ' . esc_sql( $_REQUEST['orderby'] );
//      $sql .= ! empty( $_REQUEST['order'] ) ? ' ' . esc_sql( $_REQUEST['order'] ) : ' ASC';
//    } 

    $sql .= " LIMIT $per_page";
    $sql .= ' OFFSET ' . ( $page_number - 1 ) * $per_page;

    $results = $wpdb->get_results( $sql, 'ARRAY_A' );
    
    foreach ($results as $index => &$result) {
      $result = new WU_Coupon($result['ID']);
    }

    return $results;

  }


  /**
	 * Delete a cupon record.
	 *
	 * @param int $id plan ID
	 */
  public static function delete_coupon( $id ) {
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
    global $wpdb;

    $sql  = "SELECT COUNT(ID) FROM {$wpdb->prefix}posts";
    $sql .= " WHERE post_type = 'wpultimo_coupon' && post_status = 'publish'";
    
    return $wpdb->get_var($sql);
  }


  /** Text displayed when no plan data is available */
  public function no_items() {
    _e( 'No Coupons avaliable.', 'wp-ultimo');
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
      '<input type="checkbox" name="bulk-delete[]" value="%s" />', $item->id
    );
  }
  
  /**
   * Displays the custom domain option of that plan
   * @param  object $item The plan being displayed
   * @return string       The html code to be rendered
   */
  function column_uses($item) {
    return sprintf(__('This coupon was used %d time(s).', 'wp-ultimo'), $item->uses);
  }

  /**
   * Displays the custom domain option of that plan
   * @param  object $item The plan being displayed
   * @return string       The html code to be rendered
   */
  function column_value($item) {

    $value = '';
    
    $setup_fee_value = '';

    if ($item->type == 'percent') {

      $value = sprintf(__('%s%s OFF on Subscription', 'wp-ultimo'), number_format_i18n($item->value, 2), '%');

    } else if ( $item->type == 'absolute') {

      $value = sprintf(__('%s OFF on Subscription', 'wp-ultimo'), wu_format_currency($item->value));

    } // end if;

    if ($item->applies_to_setup_fee && $item->setup_fee_discount_value && $item->setup_fee_discount_type == 'percent') {

      $setup_fee_value = sprintf(__('%s%s OFF on Setup Fee', 'wp-ultimo'), number_format_i18n($item->setup_fee_discount_value, 2), '%');

    } else if ($item->applies_to_setup_fee && $item->setup_fee_discount_value && $item->setup_fee_discount_type == 'absolute') {

      $setup_fee_value = sprintf(__('%s OFF on Setup Fee', 'wp-ultimo'), wu_format_currency($item->setup_fee_discount_value));

    } else {

      $setup_fee_value = __('Does not apply to Setup Fee', 'wp-ultimo');

    } // end if;

    return $value . '<br />' . $setup_fee_value;
  }


  /**
   * Method for name column
   *
   * @param array  $item an array of DB data
   *
   * @return string
   */
  function column_title( $item ) {

    $delete_nonce = wp_create_nonce( 'wpultimo_delete_coupon' );

    $title = sprintf('<a href="?page=wu-edit-coupon&coupon_id=%s">%s</a>', $item->id, $item->title);
    
    $title = sprintf('<strong>%s</strong>', $title);

    $desc = sprintf('<span class="description">%s</span>', $item->description);

    $actions = [
      'edit' => sprintf( '<a href="?page=wu-edit-coupon&coupon_id=%s">Edit</a>', absint($item->id)),

      'delete' => sprintf( '<a href="?page=%s&action=%s&coupon=%s&_wpnonce=%s">Delete</a>', esc_attr( $_REQUEST['page']), 'delete', absint($item->id), $delete_nonce),
      
      'share' => sprintf("<a href='#' class='wu-copy' data-copy='%s?coupon=%s'>%s</a>", WU_Signup()->get_signup_url(), $item->title, __('Click to copy Shareable Link', 'wp-ultimo')),
    ];
    
    // Input to save the custom order
    $input = '<input type="hidden" class="plan-order-input" name="plan_order['.$item->id.']">';

    return $title . $desc . $this->row_actions($actions) . $input;
  }


  /**
	 *  Associative array of columns
	 *
	 * @return array
	 */
  function get_columns() {
    $columns = [
      'cb'            => '<input type="checkbox" />',
      'title'         => __( 'Coupon Code', 'wp-ultimo'),
      'expiring_date' => __( 'Expires In', 'wp-ultimo'),
      'allowed_uses'  => __( 'Allowed Uses', 'wp-ultimo'),
      'uses'          => __( 'Number of Uses', 'wp-ultimo'),
      'cycles'        => __( 'Billing Cycles', 'wp-ultimo').WU_Util::tooltip(__('This is how many times the discount will be applied.', 'wp-ultimo')),
      'value'         => __( 'Value', 'wp-ultimo'),
      // 'url'           => __( 'URL', 'wp-ultimo'),
    ];

    return $columns;
  }

  /**
   * Displays the expiring date for that coupon
   *
   * @param WU_Coupon $item
   * @return string
   */
  public function column_expiring_date($item) {

    return $item->get_expiring_date( get_blog_option(1, 'date_format') . ' @ H:i' ) ?: __('Never expires', 'wp-ultimo');

  }

  /**
   * Displays the allowed uses for that coupon
   *
   * @param WU_Coupon $item
   * @return string
   */
  public function column_allowed_uses($item) {

    return $item->allowed_uses ?: __('Unlimited uses', 'wp-ultimo');

  }

  /**
   * Displays the billing cycles forthat coupon
   *
   * @param WU_Coupon $item
   * @return string
   */
  public function column_cycles($item) {

    return $item->cycles ?: __('Unlimited cycles', 'wp-ultimo');

  }


  /**
	 * Columns to make sortable.
	 *
	 * @return array
	 */
  public function get_sortable_columns() {
    $sortable_columns = array(
      'title'          => array('post_title', true),
      'expiring_date'  => array('expiring_date', false)
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

    $per_page     = $this->get_items_per_page('coupons_per_page', 10);
    $current_page = $this->get_pagenum();
    $total_items  = self::record_count();

    $this->set_pagination_args( [
      'total_items' => $total_items, //WE have to calculate the total number of items
      'per_page'    => $per_page //WE have to determine how many items to show on a page
    ] );

    $this->items = self::get_coupons($per_page, $current_page );
  }

  public function process_bulk_action() {

    //Detect when a bulk action is being triggered...
    if ( 'delete' === $this->current_action() ) {

      // In our file that handles the request, verify the nonce.
      $nonce = esc_attr( $_REQUEST['_wpnonce'] );

      if ( ! wp_verify_nonce( $nonce, 'wpultimo_delete_coupon' ) ) {
        die( 'Go get a life script kiddies' );
      }
      else {
        self::delete_coupon( absint( $_GET['coupon'] ) );

        wp_redirect( esc_url( add_query_arg() ) );
        exit;
      }

    }

    // If the delete bulk action is triggered
    if ( ( isset( $_POST['action'] ) && $_POST['action'] == 'bulk-delete' )
        || ( isset( $_POST['action2'] ) && $_POST['action2'] == 'bulk-delete' )
       ) {

      $delete_ids = esc_sql( $_POST['bulk-delete'] );

      // loop over the array of record IDs and delete them
      foreach ( $delete_ids as $id ) {
        self::delete_coupon( $id );

      }

      wp_redirect( esc_url( add_query_arg() ) );
      exit;
    }
  }

}

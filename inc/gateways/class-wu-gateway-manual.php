<?php
/**
 * Manual Gateway
 *
 * Handles Manual Payments
 *
 * @author      Arindo Duque
 * @category    Admin
 * @package     WP_Ultimo/Gateways/Manual
 * @since       1.2.0
*/

if (!defined('ABSPATH')) {
  exit;
}

/**
 * WU_Gateway_Manual
 */
class WU_Gateway_Manual extends WU_Gateway {
  
  /**
   * Initialize the Gateway key elements
   */
  public function init() {

    // Change the button value
    add_filter('wu_gateway_integration_button_manual', array($this, 'change_manual_button'));

    // Add new status to the list table
    add_filter('wu_subscriptions_status', array($this, 'add_on_hold_status'));

    // Add Cron Job
    add_action('wu_cron', array($this, 'send_invoice'));

    // When a user just registers, we need to generate the order right way
    add_action('wu_subscription_create_integration', array($this, 'send_invoice_on_integration'), 10, 3);

    // Reset Invoice Sent Status
    add_action('wu_subscription_before_save', array($this, 'reset_invoice_sent_status'));

    // Add new email template
    add_action('init', array($this, 'register_new_email_template'));

    // Add the action
    add_filter('wu_transaction_item_actions', array($this, 'add_action_mark_paid'), 10, 2);

    // @since 1.2.0
    add_action("wp_ajax_wu_process_marked_as_paid_$this->id", array($this, 'process_mark_as_paid'));

    // Add instructions to pay to the bottom of the page
    add_action('admin_footer', array($this, 'render_modal_box'));

    /**
     * Manual supports single payments
     * @since 1.7.0
     */
    add_filter('wu_gateway_supports_single_payments_manual', '__return_true');

    add_action('wu_subscription_charge_manual', array($this, 'create_charge'), 10, 4);

  } // end init;

  /**
   * Create Single Charge for Manual
   *
   * @since 1.7.0 
   * @param integer         $amount
   * @param string          $description
   * @param WU_Subscription $subscription
   * @param string          $type
   * @return mixed
   */
  public function create_charge($amount, $description, $subscription, $nature = "single_charge") {

    $transaction_id = uniqid();

    // Log Transaction and the results
    return WU_Transactions::add_transaction($subscription->user_id, $transaction_id, 'pending', $amount, $this->id, $description, false, false, $nature);

  } // end create_charge;
  
  /**
   * Checks if a given transaction is a subscription payment or not
   * 
   * @since  1.7.0
   * @param  WU_Transaction
   * @return bool
   */
  public function is_transaction_subscription_payment($transaction) {

    $check = $transaction->nature == '' || $transaction->nature == 'normal';

    /**
     * Allow plugin developers to filter either or not a transaction is a subscription payment
     *
     * @since 1.7.0
     * @param bool           Current value of the check
     * @param WU_Transaction The current transaction being analyzed
     * @param WU_Gateway     The manual gateway object
     * @return bool
     */
    return apply_filters('wu_gateway_menual_is_transaction_subscription_payment', $check, $transaction, $this);

  } // end is_transaction_subscription_payment;

  /**
   * Renders the modal box with instructions to pay for invoices
   * @return void
   */
  public function render_modal_box() {
    
    // enqueues the Modal Script
    add_thickbox();

    printf('<div id="instructions-modal" style="display:none;">
      <div>
        <h2>%s</h2>
        %s
      </div>
    </div>', __('Instructions to Pay', 'wp-ultimo'), WU_Settings::get_setting('manual_payment_instructions'));

  } // end render_modal_box;
  
  /**
   * Process the action of Marking a payment as received
   * @return 
   */
  public function process_mark_as_paid() {

    if (!current_user_can('manage_network')) {

      die(json_encode(array(
        'status'  => false,
        'message' => __('You don\'t have permissions to perform that action.', 'wp-ultimo'),
      )));

    }

    // Get Transaction ID
    if (!isset($_GET['transaction_id'])) {

       die(json_encode(array(
         'status'  => false,
         'message' => __('A valid transaction id is necessary.', 'wp-ultimo'),
       )));

    }

    $transaction_id = $_GET['transaction_id'];
    $transaction = WU_Transactions::get_transaction($transaction_id);

    if (!$transaction) {
      
       die(json_encode(array(
         'status'  => false,
         'message' => __('Transaction not found.', 'wp-ultimo'),
       )));

    }

    $subscription = wu_get_subscription($transaction->user_id);

    if (!$subscription) {
      
       die(json_encode(array(
         'status'  => false,
         'message' => __('Subscription not found.', 'wp-ultimo'),
       )));

    }

    // Update the Transaction?
    $result = WU_Transactions::update_transaction($transaction_id, array(
      'type' => 'payment'
    ));

    /**
     * If update was ok
     */
    if ($result) {

      if ($this->is_transaction_subscription_payment($transaction)) {

        $plan = $subscription->get_plan();

        /**
         * Handles setup fee
         * @since 1.7.0
         */
        $setup_fee_value = 0;
        $setup_fee_desc  = '';

        if ($plan->has_setup_fee() && ! $subscription->has_paid_setup_fee()) {

          $setup_fee_desc = sprintf(__('Setup fee for Plan %s', 'wp-ultimo'), $plan->title);
          
          $setup_fee_value = $plan->get_setup_fee();

          /**
           * Add setup fee line
           * @since 1.7.0
           */
          add_filter('wu_subscription_get_invoice_lines', function($lines) use ($setup_fee_desc, $setup_fee_value) {

            $lines[] = array(
              'text'  => $setup_fee_desc,
              'value' => $setup_fee_value,
            );

            return $lines;

          });

        } // end if;

        // Extend Subscription
        $subscription->paid_setup_fee = true;
        $subscription->extend_future();

        // Add transaction in our transactions database
        $message = sprintf(__('Payment for the plan %s - The account will now be active until %s', 'wp-ultimo'), $plan->title, $subscription->get_date('active_until'));

        /**
         * @since  1.2.0 Send the invoice as an attachment
         */
        $invoice     = $this->generate_invoice($this->id, $subscription, $message, $subscription->price);
        $attachments = $invoice ? array($invoice) : array();

        // Send receipt Mail
        WU_Mail()->send_template('payment_receipt', $subscription->get_user_data('user_email'), array(
          'amount'           => wu_format_currency( $subscription->price + $setup_fee_value),
          'date'             => date(get_option('date_format')),
          'gateway'          => $this->title,
          'new_active_until' => $subscription->get_date('active_until'),
          'user_name'        => $subscription->get_user_data('display_name')
        ), $attachments);

        WU_Logger::add('gateway-'.$this->id, sprintf(__('User ID: %s - Manual Payment: %s %s payment received, transaction ID %s', 'wp-ultimo'), $transaction->user_id, $transaction->reference_id, wu_format_currency(  $subscription->price + $setup_fee_value ), $transaction->reference_id));

        /**
         * @since  1.1.2 Hooks for payments and integrations
         */
        do_action('wp_ultimo_payment_completed', $transaction->user_id, $this->id, $subscription->price, $setup_fee_value);

      } // end if;

      /**
       * Display the Results
       */
      $active_until = new DateTime($subscription->active_until);

      die(json_encode(array(
         'status'  => true,
         'message' => __('Transaction updated, sucessfully.', 'wp-ultimo'),
         'remaining_string' => $subscription->get_active_until_string(),
         'status'           => $subscription->get_status(),
         'status_label'     => $subscription->get_status_label(),
         'active_until'     => $active_until->format( get_blog_option(1, 'date_format') . ' @  H:i' ),
      )));

    } else {

      die(json_encode(array(
         'status'  => false,
         'message' => __('An error occurred trying to update this transaction.', 'wp-ultimo'),
      )));

    } // end if;

  } // end mark_as_paid;

  /**
   * Mark the payment as paid
   * @param array $actions
   * @param WU_Transaction $transaction
   */
  public function add_action_mark_paid($actions, $transaction) {

    if ($transaction->type == 'pending' && $transaction->gateway == 'manual' && current_user_can('manage_network')) {

      $actions['paid'] = sprintf('<a href="#" data-transaction="%s" data-gateway="%s" data-subscription="%s" class="wu-paid-trigger" data-text="%s" aria-label="%s">%s</a>', $transaction->id, $transaction->gateway, $transaction->user_id, __('If you have received this payment, use this option to confirm the payment. The user will receive a new invoice marked as paid and an email confirming the payment.', 'wp-ultimo'), __('Mark as Paid', 'wp-ultimo'), __('Mark as Paid', 'wp-ultimo'));

    } // end if;

    if ($transaction->type == 'pending' && $transaction->gateway == 'manual' && !current_user_can('manage_network')) {

      $actions['instructions-to-pay'] = sprintf('<a title="%s" href="#TB_inline?width=600&height=550&inlineId=instructions-modal" class="thickbox" aria-label="%s">%s</a>', __('Instructions to Pay', 'wp-ultimo'), __('Instructions to Pay', 'wp-ultimo'), __('Instructions to Pay', 'wp-ultimo'));

    }

    return $actions;

  } // end add_action_mark_paid;

  /**
   * Register the email template we are going to use
   * @return
   */
  public function register_new_email_template() {

    /**
     * Payment Invoice Sent
     */
    WU_Mail()->register_template('payment_invoice_sent', array(
      'admin'      => false,
      'name'       => __('Payment Invoice', 'wp-ultimo'),
      'subject'    => __('Invoice for Subscription on {{site_name}}', 'wp-ultimo'),

      'content'    => __("Hi, {{user_name}}. <br><br>
    You will find attached the invoice for your subscription on {{site_name}}. The invoice is due on {{due_date}} and contains the instructions for payment. Let us know if you have any questions.", 'wp-ultimo'),

      'shortcodes' => array(
        'user_name',       
        'amount',           
        'date',             
        'gateway',
        'due_date',
      )
    ));

  } // end register_new_email_template;

  public function generate_and_send_invoice($subscription) {

    $plan = $subscription->get_plan();

    /**
     * Handles setup fee
     * @since 1.7.0
     */
    $setup_fee_value = 0;
    $setup_fee_desc  = '';

    if ($plan->has_setup_fee() && ! $subscription->has_paid_setup_fee()) {

      $setup_fee_desc = sprintf(__('Setup fee for Plan %s', 'wp-ultimo'), $plan->title);
      
      $setup_fee_value = $plan->get_setup_fee();

      /**
       * Add setup fee line
       * @since 1.7.0
       */
      add_filter('wu_subscription_get_invoice_lines', function($lines) use($setup_fee_desc, $setup_fee_value) {

        $lines[] = array(
          'text'  => $setup_fee_desc,
          'value' => $setup_fee_value,
        );

        return $lines;

      });

    } // end if;
    
    $message = sprintf(__('Payment for the plan %s', 'wp-ultimo'), $plan->title);

    /**
     * We need to check for coupon codes to update the price
     */
    $coupon_code = $subscription->get_coupon_code();

    $price = $coupon_code ? $subscription->get_price_after_coupon_code() : $subscription->price;

    // Generate Random ID
    $transaction_id = uniqid();

    // Log Transaction and the results
    WU_Transactions::add_transaction($subscription->user_id, $transaction_id, 'pending', $price + $setup_fee_value, $this->id, $message);

    /**
     * @since  1.2.0 Send the invoice as an attachment
     */
    $invoice     = $this->generate_invoice($this->id, $subscription, $message, $price, false);
    $attachments = $invoice ? array($invoice) : array();

    // Send receipt Mail
    WU_Mail()->send_template('payment_invoice_sent', $subscription->get_user_data('user_email'), array(
      'amount'           => wu_format_currency( $price ),
      'date'             => date(get_option('date_format')),
      'gateway'          => $this->title,
      'due_date'         => $subscription->get_date('due_date'),
      'user_name'        => $subscription->get_user_data('display_name')
    ), $attachments);

    // Mark as sent
    $meta               = $subscription->meta;
    $meta->invoice_sent = true;
    $subscription->meta = $meta;

    $subscription->save();

  } // end generate_and_send_invoice;

  /**
   * Send invoice on integration
   *
   * @since 1.5.0
   * @return void
   */
  public function send_invoice_on_integration($subscription) {

    // Check if on hold
    if ($subscription->is_active()) return;

    // If we already sent it, we don't need to send it again
    if ((isset($subscription->meta->invoice_sent) && $subscription->meta->invoice_sent) || $subscription->gateway != $this->id) return;
    
    // Generate and send invoice
    $this->generate_and_send_invoice($subscription);

  } // end send_invoice_on_integration;

  /**
   * Send invoice for that particular subscription
   * @return
   */
  public function send_invoice() {

    // Get List
    $subscriptions = WU_Subscription::get_subscriptions('on-hold', false, false);

    foreach($subscriptions as $sub) {

      $subscription = wu_get_subscription($sub->user_id);

      // If we already sent it, we don't need to send it again
      if ($subscription->meta->invoice_sent || $subscription->gateway != $this->id) return;

      // Generate and send invoice
      $this->generate_and_send_invoice($subscription);

    } // end foreach;

  } // end send_invoice;

  /**
   * Unmark this subscription removing the sent invoice flag from it
   * @param  array $subscription Array data to be saved
   * @return array
   */
  public function reset_invoice_sent_status($subscription) {

    $sub = wu_get_subscription($subscription['user_id']);

    if ($sub && is_a($sub, 'WU_Subscription')) {

      $sub->active_until = $subscription['active_until'];

      if (!$sub->is_on_hold()) {

        $meta                        = (object) unserialize($subscription['meta_object']);
        $meta->invoice_sent          = false;
        $subscription['meta_object'] = serialize($meta);

      } // end if;

    } // end if;

    return $subscription;

  } // end reset_invoice_sent_status;

  /**
   * Add on hold status to subscription tables
   * @param array $status All the current status
   */
  public function add_on_hold_status($status) {

    $gateways = is_array(WU_Settings::get_setting('active_gateway')) ? WU_Settings::get_setting('active_gateway') : array();

    if (!in_array($this->id, array_keys($gateways))) return $status;

    $status['on-hold'] = __('On Hold', 'wp-ultimo');

    return $status;

  } // end add_on_hold_status;

  /**
   * Change the label of the button
   * @param  string $button HTML of the button
   * @return string         
   */
  public function change_manual_button($button) {

    return str_replace(sprintf(__('Add Payment Account - %s', 'wp-ultimo'), $this->title), __('Use Manual Payments', 'wp-ultimo') . WU_Util::tooltip(sprintf(__('By choosing manual payments, you will receive an invoice every billing period with instructions for payment. Then, the %s team will renew your subscription once the payment is confirmed.'), get_network_option(null, 'site_name'))), $button);

  } // end change_manual_button;

  /**
   * Process Refund
   * @return null
   */
  public function process_refund() {

    if (!current_user_can('manage_network')) {

      die(json_encode(array(
        'status'  => false,
        'message' => __('You don\'t have permissions to perform that action.', 'wp-ultimo'),
      )));

    }

    // Get Transaction ID
    if (!isset($_GET['transaction_id'])) {

       die(json_encode(array(
         'status'  => false,
         'message' => __('A valid transaction id is necessary.', 'wp-ultimo'),
       )));

    }

    if (!isset($_GET['value']) || !is_numeric($_GET['value'])) {

       die(json_encode(array(
         'status'  => false,
         'message' => __('A valid amount is necessary.', 'wp-ultimo'),
       )));

    }

    $transaction_id = $_GET['transaction_id'];
    $transaction = WU_Transactions::get_transaction($transaction_id);

    if (!$transaction) {
      
       die(json_encode(array(
         'status'  => false,
         'message' => __('Transaction not found.', 'wp-ultimo'),
       )));

    }

    $subscription = wu_get_subscription($transaction->user_id);

    if (!$subscription) {
      
       die(json_encode(array(
         'status'  => false,
         'message' => __('Subscription not found.', 'wp-ultimo'),
       )));

    }

    /**
     * Everything Worked
     */
    
    $value = $_GET['value'];

    WU_Logger::add('gateway-'.$this->id, sprintf(__('User ID: %s - Manual Payment "%s" received: You refunded the payment with %s.', 'wp-ultimo'), $transaction->user_id, $transaction->reference_id, wu_format_currency($value)) . $transaction->reference_id);

    $message = sprintf(__('A refund was issued to your account. Payment reference %s.', 'wp-ultimo'), $transaction->reference_id);

    // Log Transaction and the results
    WU_Transactions::add_transaction($transaction->user_id, $transaction->reference_id, 'refund', $value, $this->id, $message);

    // Send refund Mail
    WU_Mail()->send_template('refund_issued', $subscription->get_user_data('user_email'), array(
      'amount'           => wu_format_currency($value),
      'date'             => date(get_option('date_format')),
      'gateway'          => $this->title,
      'new_active_until' => $subscription->get_date('active_until'),
      'user_name'        => $subscription->get_user_data('display_name')
    ));

    /**
     * @since  1.1.2 Hooks for payments and integrations
     */
    do_action('wp_ultimo_payment_refunded', $transaction->user_id, $this->id, $value);

    die(json_encode(array(
       'status'  => true,
       'message' => __('Refund issued successfully. It should appear on this panel shortly.', 'wp-ultimo'),
    )));

  } // end process_refund;
  
  /**
   * First step of the payment flow: proccess_form
   */
  public function process_integration() {

    /**
     * Now we get the costumer ID to be saved in the integration
     */
    $this->create_integration($this->subscription, $this->plan, $this->freq, '', array());

    // Redirect and mark as success
    wp_redirect(WU_Gateway::get_url('success'));

    exit;

  } // end process_integration;

  /**
   * Do upgrade or downgrade of plans
   */
  public function change_plan() {

    // Just return in the wrong pages
    if (!isset($_POST['wu_action']) || $_POST['wu_action'] !== 'wu_change_plan') return;

    // Security check
    if (!wp_verify_nonce($_POST['_wpnonce'], 'wu-change-plan')) {
      WP_Ultimo()->add_message(__('You don\'t have permissions to perform this action.', 'wp-ultimo'), 'error');
      return;
    }

    if (!isset($_POST['plan_id'])) {
      WP_Ultimo()->add_message(__('You need to select a valid plan to change to.', 'wp-ultimo'), 'error');
      return;
    }

    // Check frequency
    if (!isset($_POST['plan_freq']) || !$this->check_frequency($_POST['plan_freq'])) {
      WP_Ultimo()->add_message(__('You need to select a valid frequency to change to.', 'wp-ultimo'), 'error');
      return;
    }

    // Get Plans - Current and new one
    $current_plan = $this->plan;
    $new_plan     = new WU_Plan((int) $_POST['plan_id']);

    $new_price = $new_plan->{"price_".$_POST['plan_freq']};
    $new_freq  = (int) $_POST['plan_freq'];

    if (!$new_plan->id) {
      WP_Ultimo()->add_message(__('You need to select a valid plan to change to.', 'wp-ultimo'), 'error');
      return;
    }

    /**
     * Refund the last transaction and create a new one
     * @since 1.5.0
     */
    $credit = $this->subscription->calculate_credit();
    
    $this->subscription->set_credit($credit);

    // We need to take the current subscription time out
    $this->subscription->withdraw();
    
    /**
     * Now we have the new plan and the new frequency
     */
    // Case: new plan if free
    if ($new_plan->free) {
      
      // Set new plan
      $this->subscription->plan_id            = $new_plan->id;
      $this->subscription->freq               = 1;
      $this->subscription->price              = 0;
      $this->subscription->integration_status = false;

      $this->subscription->set_last_plan_change();
      $this->subscription->save();

      $this->subscription->extend();

      // Hooks, passing new plan
      do_action('wu_subscription_change_plan', $this->subscription, $new_plan, $current_plan);
      
      // Redirect to success page
      wp_redirect(WU_Gateway::get_url('plan-changed'));

      exit;

    } // end if free;

    // Update our subscription object now
    $this->subscription->plan_id            = $new_plan->id;
    $this->subscription->freq               = $new_freq;
    $this->subscription->price              = $new_price;
    $this->subscription->integration_status = true;
    
    $this->subscription->set_last_plan_change();
    $this->subscription->save();

    /**
     * Price to pay now, with the new plan
     */
    $price_to_pay_now = $this->subscription->get_outstanding_amount();

    /**
     * Recreate the invoice
     */
    $message = sprintf(__('Payment for the plan %s.', 'wp-ultimo'), $new_plan->title) .' '. $this->subscription->get_formatted_invoice_lines();
    

    /**
     * Sets the credit if the content is negative; and sets the price to zero.
     */
    if ($price_to_pay_now > 0) {

      $transaction_type = 'pending';

      $price = $price_to_pay_now;

      $paid = false;

      $this->subscription->set_credit(0);

    } else {

      $transaction_type = 'payment';

      $price = 0;

      $paid = true;

      $this->subscription->set_credit(abs($price_to_pay_now));

      $this->subscription->extend();

    } // end if;

    // Generate Random ID
    $transaction_id = uniqid();

    // Log Transaction and the results
    WU_Transactions::add_transaction($this->subscription->user_id, $transaction_id, $transaction_type, $price, $this->id, $message, false, $this->subscription->get_price_after_coupon_code());

    /**
     * @since  1.2.0 Send the invoice as an attachment
     */
    $invoice     = $this->generate_invoice($this->id, $this->subscription, $message, $this->subscription->get_price_after_coupon_code(), $paid);
    $attachments = $invoice ? array($invoice) : array();

    // Send receipt Mail
    WU_Mail()->send_template('payment_invoice_sent', $this->subscription->get_user_data('user_email'), array(
      'amount'           => wu_format_currency( $price ),
      'date'             => date(get_option('date_format')),
      'gateway'          => $this->title,
      'due_date'         => $this->subscription->get_date('due_date'),
      'user_name'        => $this->subscription->get_user_data('display_name')
    ), $attachments);

    // Mark as sent
    $meta               = $this->subscription->meta;
    $meta->invoice_sent = true;
    $this->subscription->meta = $meta;

    $this->subscription->save();

    // Hooks, passing new plan
    do_action('wu_subscription_change_plan', $this->subscription, $new_plan, $current_plan);

    // Redirect to success page
    wp_redirect(WU_Gateway::get_url('plan-changed'));

    exit;

  } // end change_plan;

  /**
   * Remove the Manual integration
   */
  public function remove_integration($redirect = true, $subscription = false) {

    // Get the subscription
    if (!$subscription) {

      $subscription = $this->subscription;

    } // end if;

    if ($subscription) {

      // Finally we remove the integration from our database
      $subscription->meta->subscription_id = '';
      $subscription->integration_status    = false;
      $subscription->save();

    }

    if ($redirect) {

      // Redirect and mark as success
      wp_redirect(WU_Gateway::get_url('integration-removed'));

      exit;

    } // end if;

  } // end remove_integration;

  /**
   * Handles the notifications
   */
  public function handle_notifications() {} // end handle_notifications;

  /**
   * Creates the custom fields need to our Gateway
   * @return array Setting fields
   */
  public function settings() {
    
    // Defines this gateway settings field
    return array(
      
      'manual_waiting_days' => array(
        'title'                       => __('Waiting Days', 'wp-ultimo'),
        'desc'                        => __('After this subscription expires, how long should the system wait for the user to pay?', 'wp-ultimo'),
        'tooltip'                     => '', 
        'type'                        => 'number',
        'placeholder'                 => '',
        'default'                     => 5,
        'require'                     => array('active_gateway[manual]' => true),
      ),
        
      'manual_payment_instructions' => array(
        'title'                       => __('Payment Instructions', 'wp-ultimo'),
        'desc'                        => __('Add detailed instructions for the payment. This will be displayed to your users.', 'wp-ultimo'),
        'tooltip'                     => '', 
        'type'                        => 'wp_editor',
        'default'                     => '',
        'args'                        => array(
          'media_buttons'               => false,
          'wpautop'                     => true,
          'editor_height'               => 300,
        ),
        'placeholder'                 => '',
        'default'                     => __('To pay, just send a payment to the WPU Bank and sent the receipt to me@mail.com.', 'wp-ultimo'),
        'require'                     => array('active_gateway[manual]' => true),
      ),

    );
    
  } // end settings;
  
} // end class WU_Gateway_Manual

/**
 * Register the gateway =D
 */
wu_register_gateway('manual', __('Manual', 'wp-ultimo'), __('Use the Manual Gateway to allow users to pay you directly via bank transfers or other channels. You will need to manually renew a subscription once you confirm a payment was received.', 'wp-ultimo'), 'WU_Gateway_Manual');

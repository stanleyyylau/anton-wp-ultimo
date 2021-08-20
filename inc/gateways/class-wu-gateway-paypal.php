<?php
/**
 * Paypal Gateway
 *
 * Handles PayPal subscriptions using the Express Checkout API
 *
 * @author      Arindo Duque
 * @category    Admin
 * @package     WP_Ultimo/Gateways/Paypal
 * @version     0.0.1
*/

if (!defined('ABSPATH')) {
  exit;
}

// Setup Namespaces
use PayPal\CoreComponentTypes\BasicAmountType;
use PayPal\EBLBaseComponents\BillingAgreementDetailsType;
use PayPal\EBLBaseComponents\PaymentDetailsItemType;
use PayPal\EBLBaseComponents\PaymentDetailsType;
use PayPal\EBLBaseComponents\SetExpressCheckoutRequestDetailsType;
use PayPal\PayPalAPI\SetExpressCheckoutReq;
use PayPal\PayPalAPI\SetExpressCheckoutRequestType;
use PayPal\Service\PayPalAPIInterfaceServiceService;
use PayPal\Exception\PPConnectionException;

use PayPal\EBLBaseComponents\ActivationDetailsType;
use PayPal\EBLBaseComponents\BillingPeriodDetailsType;
use PayPal\EBLBaseComponents\CreateRecurringPaymentsProfileRequestDetailsType;
use PayPal\EBLBaseComponents\RecurringPaymentsProfileDetailsType;
use PayPal\EBLBaseComponents\ScheduleDetailsType;
use PayPal\PayPalAPI\CreateRecurringPaymentsProfileReq;
use PayPal\PayPalAPI\CreateRecurringPaymentsProfileRequestType;

use PayPal\EBLBaseComponents\ManageRecurringPaymentsProfileStatusRequestDetailsType;
use PayPal\PayPalAPI\ManageRecurringPaymentsProfileStatusReq;
use PayPal\PayPalAPI\ManageRecurringPaymentsProfileStatusRequestType;

use PayPal\EBLBaseComponents\UpdateRecurringPaymentsProfileRequestDetailsType;
use PayPal\PayPalAPI\UpdateRecurringPaymentsProfileReq;
use PayPal\PayPalAPI\UpdateRecurringPaymentsProfileRequestType;

use PayPal\PayPalAPI\RefundTransactionReq;
use PayPal\PayPalAPI\RefundTransactionRequestType;

use PayPal\IPN\PPIPNMessage;

// Load the PayPal SDK
require_once('paypal-php/PPBootStrap.php');

/**
 * WU_Gateway_Paypal
 */
class WU_Gateway_Paypal extends WU_Gateway {
  
  /**
   * Endpoint used by the PayPal API to process data
   * @var string
   */
  protected $endpoint;

  /**
   * Set the configuration array of our Paypal Object
   * @return array Configurations object
   */
  public static function get_configuration() {

    // Set the config information
    $config = array(
      'mode'            => WU_Settings::get_setting('paypal_sandbox') ? 'sandbox' : 'live',
      'acct1.UserName'  => WU_Settings::get_setting('paypal_username'),
      'acct1.Password'  => WU_Settings::get_setting('paypal_pass'),
      'acct1.Signature' => WU_Settings::get_setting('paypal_signature'),
      'log.LogEnabled'  => true,
      'log.FileName'    => '../paypal-transactions.log',
      'log.LogLevel'    => 'FINE',
      'version'         => 300,

      // These values are defaulted in SDK. If you want to override default values, uncomment it and add your value.
      // "http.ConnectionTimeOut" => "5000",
      // "http.Retry" => "2",
      
    );

    return $config;

  } // end configuration;

  /**
   * Sends the return from paypal to the right place
   * @since 1.1.3 add a check to prevent fatal errors from being thrown
   * @return 
   */
  function fix_return_url_paypal_button() {

    global $wp_query;

    if (!isset($wp_query->query['pagename']) && !isset($wp_query->query['name'])) return;
    
    if (
      (isset($wp_query->query['pagename']) && $wp_query->query['pagename'] == 'paypal-button-success') ||
      (isset($wp_query->query['name']) && $wp_query->query['name'] == 'paypal-button-success')
    ) {

      wp_redirect(WU_Gateway::get_url('success'));

      exit;

    } else if (
      (isset($wp_query->query['pagename']) && $wp_query->query['pagename'] == 'paypal-button-error') ||
      (isset($wp_query->query['name']) && $wp_query->query['name'] == 'paypal-button-error')
    ) {

      wp_redirect(WU_Gateway::get_url('error'));

      exit;

    }

  } // end fix_return_url_paypal_button;
  
  /**
   * Initialize the Gateway key elements
   */
  public function init() {

    // Sends the return from paypal to the right palce
    add_action('wp', array($this, 'fix_return_url_paypal_button'));

    // Get the configuration
    $this->config = self::get_configuration();

    // Set endpoint based on env
    $this->endpoint = WU_Settings::get_setting('paypal_sandbox')
      ? 'https://api-3t.sandbox.paypal.com/nvp'
      : 'https://api-3t.paypal.com/nvp';

    // Set the description
    $this->desc = sprintf(__('%s, billed every %s month(s).', 'wp-ultimo'), wu_format_currency($this->price), $this->freq);

    if ($this->subscription && $this->subscription->get_price_after_coupon_code() == 0) {
      $this->desc .= ' ' . $this->subscription->get_coupon_code_string();
    }

    // Does not display button if there's no settings 
    add_filter('wu_gateway_integration_button_paypal', array($this, 'change_paypal_button'));

    // Add link to see current subscription in Paypal
    add_action('wu_button_subscription_on_site', array($this, 'paypal_links'));

    /**
     * @since  1.1.0 add that to our edit subscription screen as well
     */
    add_action('wu_edit_subscription_integration_meta_box', array($this, 'paypal_links'));

    /**
     * Register handle return
     * @since  1.1.0
     */
    $this->register_gateway_page('paypal-callback', array($this, 'paypal_callback'));
    
    /**
     * Add the switcher for the PayPal Button
     */
    $this->use_button = WU_Settings::get_setting('paypal_standard');

    /**
     * Manual supports single payments
     * @since 1.7.0
     */
    // if (! $this->use_button)
    // add_filter('wu_gateway_supports_single_payments_paypal', '__return_true');

    add_action('wu_subscription_charge_paypal', array($this, 'create_charge'), 10, 4);

    /**
     * Register a gateway page that will redirect to PayPal with for single charge Payment
     */
    $this->register_gateway_page('paypal-pay', array($this, 'redirect_to_payment_for_single_payment'));
    $this->register_gateway_page('paypal-single-callback', array($this, 'paypal_single_callback'));

    // Add the action
    add_filter('wu_transaction_item_actions', array($this, 'add_action_mark_paid'), 10, 2);

    add_action('wu_integration_status_widget_actions', array($this, 'add_view_on_paypal_on_management_screen'), 10, 1);

  } // end init;

  /**
   * Add a link to redirect to Stripe Dashboard
   *
   * @since 1.7.0
   * @param WU_Subscription $subscription
   * @return void
   */
  public function add_view_on_paypal_on_management_screen($subscription) {

    if ($this->id !== $subscription->gateway || ! $subscription->integration_status) return;

    $url = $this->get_paypal_remote_link($subscription->integration_key);

    printf(' <small>- <a href="%s" target="_blank">%s</a></small>', $url, __('View on PayPal Dashboard', 'wp-ultimo'));

  } // end add_view_on_paypal_on_management_screen;

  /**
   * Create Single Charge for PayPal
   * 
   * This method hooks into the subscription->charge method and simply creates a pending transaction with the Pay via PayPal
   * link for the user to use. Once we hear back from PayPal telling us that the payment was successful, we will edit the transaction
   * to add the right PayPal references and etc (see handle_notifications for that).
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
   * Redirect the user to the single payment link over at PayPal
   * 
   * @since 1.7.0
   * @return void
   */
  public function redirect_to_payment_for_single_payment() {

    $subscription = wu_get_current_site()->get_subscription();

    if (!$subscription || !isset($_GET['transaction_id'])) {

      return WP_Ultimo()->add_message(__('Invalid Action.', 'wp-ultimo'), 'error');

    } // end if;

    // Checks for the transaction id on the get parameters
    $transaction_id = $_GET['transaction_id'];

    // Check if transaction can be paid
    $transaction = WU_Transactions::get_transaction($transaction_id);

    if ($transaction && $transaction->nature !== 'normal' && $transaction->type == 'pending') {

      return $this->create_single_charge($transaction->amount, $transaction->description, $subscription, $transaction->nature);

    } // end if;

    return WP_Ultimo()->add_message(__('The transaction you are trying to pay is no longer available.', 'wp-ultimo'), 'error');

  } // end redirect_to_payment_for_single_payment;

  /**
   * Mark the payment as paid
   * 
   * @since 1.7.0
   * @param array $actions
   * @param WU_Transaction $transaction
   */
  public function add_action_mark_paid($actions, $transaction) {

    if ($transaction->type == 'pending' && $transaction->gateway == 'paypal' && current_user_can('manage_network')) {

      $actions['paid'] = sprintf('<a href="#" data-transaction="%s" data-gateway="%s" data-subscription="%s" class="wu-paid-trigger" data-text="%s" aria-label="%s">%s</a>', $transaction->id, $transaction->gateway, $transaction->user_id, __('If you have received this payment, use this option to confirm the payment. The user will receive a new invoice marked as paid and an email confirming the payment.', 'wp-ultimo'), __('Mark as Paid', 'wp-ultimo'), __('Mark as Paid', 'wp-ultimo'));

    } // end if;

    if ($transaction->type == 'pending' && $transaction->gateway == 'paypal' && !current_user_can('manage_network') && $transaction->nature !== 'normal') {

      $actions['pay'] = sprintf('<a href="%s" aria-label="%s">%s</a>', $this->get_url('paypal-pay') . "&transaction_id=$transaction->id", __('Pay via PayPal', 'wp-ultimo'), __('Pay via PayPal', 'wp-ultimo'));

    }

    return $actions;

  } // end add_action_mark_paid;

  /**
   * Displays success message on the return page of PayPal's single payment
   * 
   * @since 1.7.0
   */
  public function paypal_single_callback() {

    $title   = __('Success!', 'wp-ultimo');
    $message = __('Your payment is being processed by PayPal and it can take up to 5 minutes before it shows up as completed on your billing history.', 'wp-ultimo');

    // Display our success message
    WU_Util::display_alert($title, $message);

  } // end paypal_single_callback;

  /**
   * Create Single Charge
   *
   * @since 1.7.0 
   * @param integer         $amount
   * @param string          $description
   * @param WU_Subscription $subscription
   * @param string          $type
   * @return mixed
   */
  public function create_single_charge($amount, $description, $subscription, $nature = "single_charge") {

    $currencyCode = WU_Settings::get_setting('currency_symbol');

    // total shipping amount
    $shippingTotal = new BasicAmountType($currencyCode, 0);
    //total handling amount if any
    $handlingTotal = new BasicAmountType($currencyCode, 0);
    //total insurance amount if any
    $insuranceTotal = new BasicAmountType($currencyCode, 0);

    // details about payment
    $paymentDetails = new PaymentDetailsType();
    $itemTotalValue = 0;
    $taxTotalValue = 0;

    /*
    * iterate trhough each item and add to atem detaisl
    */
    $itemAmount      = new BasicAmountType(WU_Settings::get_setting('currency_symbol'), $amount);	
    $itemTotalValue += $amount;

    // $taxTotalValue += $_REQUEST['itemSalesTax'][$i] * $_REQUEST['itemQuantity'][$i];
    $itemDetails = new PaymentDetailsItemType();
    $itemDetails->Name     = $description;
    $itemDetails->Amount   = $itemAmount;
    $itemDetails->Quantity = 1;
    /*
    * Indicates whether an item is digital or physical. For digital goods, this field is required and must be set to Digital. It is one of the following values:
      Digital
      Physical
    */
    $itemDetails->ItemCategory = 'Digital';
    $itemDetails->Tax = new BasicAmountType(WU_Settings::get_setting('currency_symbol'), 0);	
    
    $paymentDetails->PaymentDetailsItem[] = $itemDetails;	
    
    /*
    * The total cost of the transaction to the buyer. If shipping cost and tax charges are known, include them in this value. If not, this value should be the current subtotal of the order. If the transaction includes one or more one-time purchases, this field must be equal to the sum of the purchases. If the transaction does not include a one-time purchase such as when you set up a billing agreement for a recurring payment, set this field to 0.
    */
    $orderTotalValue = $itemTotalValue;

    $paymentDetails->ItemTotal = new BasicAmountType(WU_Settings::get_setting('currency_symbol'), $itemTotalValue);
    $paymentDetails->TaxTotal = new BasicAmountType(WU_Settings::get_setting('currency_symbol'), $taxTotalValue);
    $paymentDetails->OrderTotal = new BasicAmountType(WU_Settings::get_setting('currency_symbol'), $orderTotalValue);

    /*
    * How you want to obtain payment. When implementing parallel payments, this field is required and must be set to Order. When implementing digital goods, this field is required and must be set to Sale. If the transaction does not include a one-time purchase, this field is ignored. It is one of the following values:

    Sale � This is a final sale for which you are requesting payment (default).

    Authorization � This payment is a basic authorization subject to settlement with PayPal Authorization and Capture.

    Order � This payment is an order authorization subject to settlement with PayPal Authorization and Capture.

    */
    $paymentDetails->PaymentAction = 'Sale';

    $paymentDetails->HandlingTotal  = $handlingTotal;
    $paymentDetails->InsuranceTotal = $insuranceTotal;
    $paymentDetails->ShippingTotal  = $shippingTotal;

    /*
    *  Your URL for receiving Instant Payment Notification (IPN) about this transaction. If you do not specify this value in the request, the notification URL from your Merchant Profile is used, if one exists.
    */
    $paymentDetails->NotifyURL = $this->notification_url;

    $setECReqDetails = new SetExpressCheckoutRequestDetailsType();
    $setECReqDetails->PaymentDetails[0] = $paymentDetails;
    
    // Important URLs
    $setECReqDetails->CancelURL = self::get_url('error');
    $setECReqDetails->ReturnURL = self::get_url('paypal-single-callback');

    /*
    * Determines where or not PayPal displays shipping address fields on the PayPal pages. For digital goods, this field is required, and you must set it to 1. It is one of the following values:

    0 � PayPal displays the shipping address on the PayPal pages.

    1 � PayPal does not display shipping address fields whatsoever.

    2 � If you do not pass the shipping address, PayPal obtains it from the buyer's account profile.

    */
    $setECReqDetails->NoShipping = 1;
    /*
    *  (Optional) Determines whether or not the PayPal pages should display the shipping address set by you in this SetExpressCheckout request, not the shipping address on file with PayPal for this buyer. Displaying the PayPal street address on file does not allow the buyer to edit that address. It is one of the following values:

    0 � The PayPal pages should not display the shipping address.

    1 � The PayPal pages should display the shipping address.

    */
    $setECReqDetails->AddressOverride = 0;

    /*
    * Indicates whether or not you require the buyer's shipping address on file with PayPal be a confirmed address. For digital goods, this field is required, and you must set it to 0. It is one of the following values:

    0 � You do not require the buyer's shipping address be a confirmed address.

    1 � You require the buyer's shipping address be a confirmed address.

    */
    $setECReqDetails->ReqConfirmShipping = 0;

    // Billing agreement details
    $billingAgreementDetails = new BillingAgreementDetailsType('None');
    $billingAgreementDetails->BillingAgreementDescription = '';
    $setECReqDetails->BillingAgreementDetails = array($billingAgreementDetails);

    // Display options
    if ($logo = apply_filters('wu_gateway_paypal_use_logo', WU_Settings::get_logo())) {
      $setECReqDetails->cpplogoimage = $logo;
    } // end if;

    $setECReqDetails->BrandName    = get_network_option(null, 'site_name');

    // Advanced options
    $setECReqDetails->AllowNote = false;

    $setECReqType = new SetExpressCheckoutRequestType();
    $setECReqType->SetExpressCheckoutRequestDetails = $setECReqDetails;
    $setECReq = new SetExpressCheckoutReq();
    $setECReq->SetExpressCheckoutRequest = $setECReqType;

    /*
    * 	 ## Creating service wrapper object
    Creating service wrapper object to make API call and loading
    Configuration::getAcctAndConfig() returns array that contains credential and config parameters
    */
    $paypalService = new PayPalAPIInterfaceServiceService($this->config);
    
    try {
      /* wrap API method calls on the service object with a try catch */
      $setECResponse = $paypalService->SetExpressCheckout($setECReq);
      
      // Redirect in case of success
      if (isset($setECResponse)) {

        if ($setECResponse->Ack =='Success') {
          $this->redirect_to_paypal($setECResponse->Token);
        } else {

          $message = $setECResponse->Errors[0]->LongMessage;

          WP_Ultimo()->add_message(sprintf(__('There was an error when trying to contact PayPal. Contact the Network admin. %s Code: 1', 'wp-ultimo'), $message), 'error');
          return;
        }

      } else {
        WP_Ultimo()->add_message(__('There was an error when trying to contact PayPal. Contact the Network admin. Code: 2', 'wp-ultimo'), 'error');
        return;
      }

    } catch (Exception $ex) {

      WP_Ultimo()->add_message(sprintf(__('There was an error when trying to contact PayPal. Contact the Network admin. %s Code: 3', 'wp-ultimo'), $ex->getMessage()), 'error');
          return;
      
    }

  } // end create_charge;

  /**
   * Change the button to disabled in case of no account integrated
   * @param  string $html HTML of the original Button
   * @return string       HTML modified or not
   */
  public function change_paypal_button($html) {

    /** 
     * Now that we support the subscription button option, we need to divide the button
     */
    if ($this->use_button) { 
      
      /**
       * Check for setup fee
       * @since 1.7.0
       */
      $setup_fee_value = 0;
      $setup_fee_desc  = '';
    
      if ($this->plan->has_setup_fee() && ! $this->subscription->has_paid_setup_fee()) {

        $setup_fee_value = $this->plan->get_setup_fee();

        $setup_fee_desc = sprintf(' - ' . __('Setup fee for %s: %s', 'wp-ultimo'), $this->plan->title, wu_format_currency($setup_fee_value));

      } // end if;

      ob_start();

      ?>

    <form action="https://<?php echo WU_Settings::get_setting('paypal_sandbox') ? 'www.sandbox.' : 'www.'; ?>paypal.com/cgi-bin/webscr" method="post">
         <input type="hidden" name="cmd" value="_xclick-subscriptions">
         <input type="hidden" name="business" value="<?php echo WU_Settings::get_setting('paypal_standard_email'); ?>">
         <input type="hidden" name="item_name" value="<?php echo $this->desc . $setup_fee_desc; ?>">
         <input type="hidden" name="item_number" value="">
         <input type="hidden" name="no_note" value="1">
         <input type="hidden" name="no_shipping" value="1">
         <input type="hidden" name="currency_code" value="<?php echo WU_Settings::get_setting('currency_symbol') ?>">
         <input type="hidden" name="return" value="<?php echo site_url('paypal-button-success'); ?>">
         <input type="hidden" name="cancel" value="<?php echo site_url('paypal-button-error'); ?>">
         <input type="hidden" name="src" value="1">
         <input type="hidden" name="sra" value="1">
         <input type="hidden" name="on0" value="User ID">
         <input type="hidden" name="os0" value="<?php echo $this->subscription->user_id; ?>">
         <input type="hidden" name="notify_url" value="<?php echo $this->notification_url; ?>">

         <!-- <input id="rm" name="rm" type="hidden" value="2"> -->

         <!-- Set the terms of the 1st trial period. -->
         <?php if ($this->subscription->get_trial()): ?>

           <input type="hidden" name="a1" value="0">
           <input type="hidden" name="p1" value="<?php echo $this->subscription->get_trial(); ?>">
           <input type="hidden" name="t1" value="D">
         
         <?php endif; ?>

        <!-- Set the terms of the 1st trial period. -->
         <?php if ($setup_fee_value): $trial_index = $this->subscription->get_trial() ? 2 : 1; ?>

          <input type="hidden" name="a<?php echo $trial_index; ?>" value="<?php echo $this->subscription->get_price() + $setup_fee_value; ?>">
          <input type="hidden" name="p<?php echo $trial_index; ?>" value="1">
          <input type="hidden" name="t<?php echo $trial_index; ?>" value="M">
         
         <?php endif; ?>

         <?php $coupon_code = $this->subscription->get_coupon_code();

         $eternal_coupon = false;

         if (false && $coupon_code && $coupon_code['cycles'] !== false) : // False flag added, because this does not support coupon codes

          // Calculate price
          $price = $this->subscription->get_price_after_coupon_code();

          if ($coupon_code['cycles'] > 0) :

            $trial_index = $this->subscription->get_trial() ? 2 : 1;

           ?>

            <!-- Set the terms of the 2nd trial period. -->
            <input type="hidden" name="a<?php echo $trial_index; ?>" value="<?php echo $price; ?>">
            <input type="hidden" name="p<?php echo $trial_index; ?>" value="<?php echo $coupon_code['cycles']; ?>">
            <input type="hidden" name="t<?php echo $trial_index; ?>" value="M">

          <?php else: $eternal_coupon = true; ?>

            <!-- Set the terms of eternal coupon. -->
            <input type="hidden" name="a3" value="<?php echo $price; ?>">
            <input type="hidden" name="p3" value="<?php echo $this->subscription->freq; ?>">
            <input type="hidden" name="t3" value="M">
         
         <?php endif; ?>
         <?php endif; ?>

         <?php if (!$eternal_coupon) : ?>
           <!-- Set the actual terms. -->
           <input type="hidden" name="a3" value="<?php echo $this->price; ?>">
           <input type="hidden" name="p3" value="<?php echo $this->subscription->freq; ?>">
           <input type="hidden" name="t3" value="M">
         <?php endif; ?>

         <button style="width: 100%; text-align: center; margin-top: 10px;" type="submit" class="button button-primary button-streched">
            <?php echo $this->get_button_label(); ?>
         </button>
    </form>

    <?php 

      return ob_get_clean();

    } else {

      if (WU_Settings::get_setting('paypal_username')) return $html;

      $html = str_replace('<a', '<button disabled="disabled"', $html);
      $html = str_replace('</a', '</button', $html);
      $html = str_replace('button-primary', 'button-disabled', $html);

      return $html;

    } // end else;

  } // end change_paypal_button;

  /**
   * Get the link to visit the subscription on the remote site
   *
   * @since 1.7.0
   * @param string $integration_key
   * @return string
   */
  public function get_paypal_remote_link($integration_key) {

    $sandbox_prefix = WU_Settings::get_setting('paypal_sandbox') ? 'sandbox.' : '';

    return sprintf("https://www.%spaypal.com/us/cgi-bin/webscr?cmd=_profile-recurring-payments&encrypted_profile_id=%s", $sandbox_prefix, $integration_key);

  } // end get_paypal_remote_link;

  /**
   * Add a direct link to check a subscription in paypal's website
   */
  public function paypal_links() {

    // If there's no integration key, there's nothing to do
    if (wu_get_active_gateway()->id != $this->id) return;

    if ($this->subscription->integration_key == '' || !$this->subscription->integration_status) return;

    $url = $this->get_paypal_remote_link($this->subscription->integration_key);

    if ($this->site->is_user_owner() || current_user_can('manage_network')) {
      printf('<li><a href="%s" target="_blank">%s</a></li>', $url, __('Check this subscription at PayPal', 'wp-ultimo'));
    }

  } // end paypal_links;

  /**
   * First step of the payment flow: proccess_form
   */
  public function process_integration() {

    if ($this->use_button) return;

    // Set up the checkout express
    $this->create_express_checkout(array(
      'desc'            => $this->desc,
      'price'           => $this->price,
      'freq'            => $this->freq,
      'plan_name'       => $this->plan->title,
      'redirect_page'   => self::get_url('paypal-callback'),
      'initial_profile' => true,
    ));

  } // end process_integration;

  /**
   * After the request to the remote server we will come back to this page
   * @since  1.1.0 Only pass the start date if we need to
   */
  public function paypal_callback() {

    /**
     * Handles setup_fee
     * @since 1.7.0
     */
    $setup_fee_value = 0;
    
    if ($this->plan->has_setup_fee() && ! $this->subscription->has_paid_setup_fee()) {

      $setup_fee_value = $this->plan->get_setup_fee();

    } // end if;

    // Creation array
    $subscription = array(
      'token' => $_REQUEST['token'],
      'desc'  => $this->desc,
      'site'  => wu_get_current_site(),
      'plan'  => $this->plan,
      'price' => $this->price,
      'freq'  => $this->freq,
      'trial' => array(),
      'charge_initial_amount' => 0,
    );

    /**
     * @since  1.1.0 add coupon code implementation
     */

    // Get the coupon code info
    $coupon_code = $this->subscription->get_coupon_code();

    if ($coupon_code) {

      // Calculate price
      $price = $this->subscription->get_price_after_coupon_code();

      $subscription['trial'] = array(
        'freq'     => $this->freq,
        'price'    => $price + $setup_fee_value,
        'cycles'   => (int) $coupon_code['cycles'],
      );

    }

    // Add the subscription start date
    if ($this->subscription->get_billing_start('c')) {

      $subscription['start_date'] = $this->subscription->get_billing_start('c');

    } else {

      $now                                   = new DateTime();
      $now                                   = new DateTime($now->format('Y-m-d') . ' 23:59:59');
      $subscription['start_date']            = $now->modify("+$this->freq Month")->format('c');
      $subscription['charge_initial_amount'] = $this->price + $setup_fee_value;

    }

    // Create the recurring profile
    $this->create_recurring_profile($subscription);

  } // end success;

  /**
   * Cancel page, to return to
   */
  public function cancel_page() {
    WP_Ultimo()->add_message(__('There was a problem setting up the payment option. Please try again later.', 'wp-ultimo'), 'error');
    return;
  }

  /**
   * Remove a paypal integration
   */
  public function remove_integration($redirect = true, $subscription = false) {
    
    if (!$subscription) {
      $subscription = wu_get_current_site()->get_subscription();
    }

    $this->cancel_recurring_profile($subscription, $redirect);

  } // end remove_integration;

  /**
   * Change plan
   * @return [type] [description]
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

    if (!$new_plan->id) {
      WP_Ultimo()->add_message(__('You need to select a valid plan to change to.', 'wp-ultimo'), 'error');
      return;
    }

    /**
     * Really start to process things =D
     * We need to decide if we need to pro-rate this or no
     */

    $new_price = $new_plan->{"price_".$_POST['plan_freq']};
    $new_freq  = (int) $_POST['plan_freq'];

    // Otherwise, we need to pro-rate this, and here things get interesting

    // First we need to define the previous starting date, which is our active until - frequency
    $active_until = new DateTime($this->subscription->active_until);
    $created_at   = new DateTime($this->subscription->created_at);
    $start_date   = $active_until->modify("-$this->freq Months");

    // But we also need to compare that to our created date
    if ($start_date < $created_at) {
      $start_date = $created_at;
    }

    $now = new DateTime();

    // Calculate the difference
    $diff = $now->diff($start_date, true);

    $days_used = $diff->days;

    $divisor = ((int) $now->format('t')) * $this->subscription->freq;
    $value_used = $days_used * ((float) $this->subscription->get_price_after_coupon_code() / $divisor);

    $value_to_refund = (float) $this->subscription->get_price_after_coupon_code() - $value_used;

    // Get last relevant transaction
    $transaction = $this->get_last_relevant_transaction($this->subscription->user_id);

    // die;

    // If found something
    if ($transaction) {

      $this->paypal_refund_transaction($transaction, $value_to_refund);

      // Re-add one cycle
      $this->subscription->remove_cycles_from_coupon_code(-1);

    } // end if;

    // Just remove the integration
    $this->cancel_recurring_profile($this->subscription, false);

    // Case: new plan if free
    if ($new_plan->free) {

      // Send email confirming things
      // TODO: send email
      
      // Set new plan
      $this->subscription->plan_id = $new_plan->id;
      $this->subscription->freq    = 1;
      $this->subscription->price   = 0;
      $this->subscription->integration_status = 0;

      $this->subscription->gateway = '';

      $this->subscription->save();

      // Hooks, passing new plan
      do_action('wu_subscription_change_plan', $this->subscription, $new_plan, $current_plan);
      
      // Redirect to success page
      wp_redirect(WU_Gateway::get_url('plan-changed'));

      exit;

    } // end if free;

    // Changes to the subscription
    $this->subscription->active_until = $now->format('Y-m-d H:i:s');
    $this->subscription->price        = $new_price;
    $this->subscription->freq         = $new_freq;
    $this->subscription->plan_id      = $new_plan->id;

    $this->subscription->save();

    // Hooks, passing new plan
    do_action('wu_subscription_change_plan', $this->subscription, $new_plan, $current_plan);

    wp_redirect(WU_Gateway::get_url('process-integration'));
    
    exit;

  } // end change_plan;

  /**
   * Refund a transaction
   * @param  [type] $transaction [description]
   * @param  [type] $value       [description]
   * @return [type]              [description]
   */
  public function paypal_refund_transaction($transaction, $value) {

    $value = number_format($value, 2);

    $refundReqest                = new RefundTransactionRequestType();
    $refundReqest->TransactionID = $transaction->reference_id;

    // If this is a total refund
    if ($value == $transaction->amount) {

      $refundReqest->RefundType = 'Full';
      $partial = false;

    } else {

      $refundReqest->Amount     = new BasicAmountType(WU_Settings::get_setting('currency_symbol'), $value);
      $refundReqest->RefundType = 'Partial';
      $partial = true;

    }

    $refundReq = new RefundTransactionReq();
    $refundReq->RefundTransactionRequest = $refundReqest;

    $paypalService = new PayPalAPIInterfaceServiceService($this->config);

    // Let's Try =D
    try {

      /* wrap API method calls on the service object with a try catch */
      $refundResponse = $paypalService->RefundTransaction($refundReq);

    } catch (PPConnectionException $ex) {

      $message = $ex->getMessage();

      WU_Logger::add('gateway-'.$this->id, sprintf(__('User ID: %s - Paypal: We tried to issue a refund of %s for a change plan, but something went wrong: %s. Code: 1', 'wp-ultimo'), $this->subscription->user_id, $value, $message));

      return array(
        'status'  => false,
        'message' => $message
      );

    }

    // All right with the request, check for the status
    if ($refundResponse->Ack =='Success') {

      return array(
        'status'  => true,
        'message' => __('Refund issued successfully. It should appear on this panel shortly.', 'wp-ultimo'),
      );

    } else {

      $message = $refundResponse->Errors[0]->LongMessage;

      WU_Logger::add('gateway-'.$this->id, sprintf(__('User ID: %s - Paypal: We tried to issue a refund of %s for a change plan, but something went wrong: %s. Code: 2', 'wp-ultimo'), $this->subscription->user_id, $value, $message));

      return array(
        'status'  => false,
        'message' => $message
      );

    }

  } // end refund_transaction;
  
  /**
   * Create the express checkout request to PayPal
   * @param  array $args Arguments necessary to create o express checkout
   */
  public function create_express_checkout($args) {

    /**
     * Arguments Array
     * - desc
     * - price
     * - freq
     * - plan_name
     */
    $args = (object) $args;
    
    // Price, keeps the value of coupon code. Needs refactoring
    $price = 0;

    // Set the Express Checkout
    $setECReqDetails = new SetExpressCheckoutRequestDetailsType();

    // Set Data
    $billingAgreementDetails                              = new BillingAgreementDetailsType('RecurringPayments');
    $billingAgreementDetails->BillingAgreementDescription = substr(strip_tags($args->desc), 0, 126);

    $setECReqDetails->BillingAgreementDetails             = array($billingAgreementDetails);

    // Set Styling things
    // $setECReqDetails->cppheaderimage       = WU_Settings::get_logo();
    // $setECReqDetails->cppheaderbordercolor = $_REQUEST['cppheaderbordercolor'];
    // $setECReqDetails->cppheaderbackcolor   = $_REQUEST['cppheaderbackcolor'];
    // $setECReqDetails->cpppayflowcolor      = $_REQUEST['cpppayflowcolor'];
    // $setECReqDetails->cppcartbordercolor   = $_REQUEST['cppcartbordercolor'];
    // $setECReqDetails->PageStyle            = $_REQUEST['pageStyle'];
    
    if ($logo = apply_filters('wu_gateway_paypal_use_logo', WU_Settings::get_logo('medium'))) {
      $setECReqDetails->cpplogoimage = $logo;
    } // end if;
    
    $setECReqDetails->BrandName    = get_network_option(null, 'site_name');
    
    $setECReqDetails->AllowNote = false;

    // Important URLs
    $setECReqDetails->CancelURL = self::get_url('error');
    $setECReqDetails->ReturnURL = $args->redirect_page;

    // Load the details
    $setECReqType = new SetExpressCheckoutRequestType();
    $setECReqType->SetExpressCheckoutRequestDetails = $setECReqDetails;

    $setECReq = new SetExpressCheckoutReq();
    $setECReq->SetExpressCheckoutRequest = $setECReqType;

    /**
     * Add the desc if the we have subscription
     * @since 1.7.0
     */
    $setup_fee_value = 0;

    if ($this->plan->has_setup_fee() && ! $this->subscription->has_paid_setup_fee()) {

      $setup_fee_value = $this->plan->get_setup_fee();

    } // end if;

    // Load the item itself
    $itemAmount  = new BasicAmountType(WU_Settings::get_setting('currency_symbol'), $args->price);

    $paymentDetails = new PaymentDetailsType();
    $paymentDetails->PaymentAction = 'Authorization';

    /**
     * @since  1.1.2 Notify URL for dynamic IPNs
     */
    $paymentDetails->NotifyURL = $this->notification_url;

    /**
     * @since  1.1.0 Add Coupon Code Stuff
     */
    
    if ($args->initial_profile) {

      $itemDetails = new PaymentDetailsItemType();

      $itemDetails->Name         = sprintf(__('Subscription for plan %s', 'wp-ultimo'), $args->plan_name);
      $itemDetails->Amount       = $itemAmount;
      $itemDetails->Quantity     = 1;
      //$itemDetails->ItemCategory = 'Digital';

      $paymentDetails->PaymentDetailsItem[] = $itemDetails;

      // Get the coupon code info
      $coupon_code = $this->subscription->get_coupon_code();

      /**
       * Discount Line
       */
      if ($coupon_code && $coupon_code['cycles'] !== false) {

        $price = round($args->price - $this->subscription->get_price_after_coupon_code(), 2);

        $itemAmount2  = new BasicAmountType(WU_Settings::get_setting('currency_symbol'), -$price);
        $itemDetails2 = new PaymentDetailsItemType();

        $itemDetails2->Name         = $this->subscription->get_coupon_code_string();
        $itemDetails2->Amount       = $itemAmount2;
        $itemDetails2->Quantity     = 1;
        //$itemDetails2->ItemCategory = 'Digital';

        $itemAmount = new BasicAmountType(WU_Settings::get_setting('currency_symbol'), $args->price - $price);

        $paymentDetails->PaymentDetailsItem[] = $itemDetails2;

      } // end if;

      if ($setup_fee_value) {

        $itemDetailsSetupFee = new PaymentDetailsItemType();

        $itemDetailsSetupFee->Name         = sprintf(__('Setup fee for %s: %s', 'wp-ultimo'), $this->plan->title, wu_format_currency($this->plan->get_setup_fee()));
        $itemDetailsSetupFee->Amount       = (float) $setup_fee_value;
        $itemDetailsSetupFee->Quantity     = 1;
        //$itemDetails->ItemCategory = 'Digital';

        $paymentDetails->PaymentDetailsItem[] = $itemDetailsSetupFee;

      } // end if;

      if ($this->subscription->get_price_after_coupon_code() == 0 && $coupon_code['cycles'] === 0) {
        $itemAmount = null;
        $paymentDetails->PaymentDetailsItem[0]->Amount = null;
      }

      $orderAmount = new BasicAmountType(WU_Settings::get_setting('currency_symbol'), $args->price + $setup_fee_value - $price);

      // Order totals
      $paymentDetails->ItemTotal  = $orderAmount;
      $paymentDetails->TaxTotal   = new BasicAmountType(WU_Settings::get_setting('currency_symbol'), 0);
      $paymentDetails->OrderTotal = $orderAmount;

      // Add the item
      $setECReqDetails->PaymentDetails[0] = $paymentDetails;

    } // end if;

    /**
     * Finally processes the call and get the result, wrapping in a try catch statement
     * @var PayPalAPIInterfaceServiceService
     */
    $paypalService = new PayPalAPIInterfaceServiceService($this->config);
    
    // Let's Try =D
    try {
      $setECResponse = $paypalService->SetExpressCheckout($setECReq);
    }
    catch (Exception $ex) {

      // Add the error
      WP_Ultimo()->add_message(sprintf(__('There was a problem setting up the paypal payment:<br />"<strong>%s</strong>"<br />Please try again.', 'wp-ultimo'), $ex->getMessage()), 'error');
      return;

    } // end try catch;

    // Redirect in case of success
    if (isset($setECResponse)) {

      if ($setECResponse->Ack =='Success') {
        $this->redirect_to_paypal($setECResponse->Token);
      } else {

        $message = $setECResponse->Errors[0]->LongMessage;

        WP_Ultimo()->add_message(sprintf(__('There was an error when trying to contact PayPal. Contact the Network admin. %s Code: 1', 'wp-ultimo'), $message), 'error');
        return;
      }

    } else {
      WP_Ultimo()->add_message(__('There was an error when trying to contact PayPal. Contact the Network admin. Code: 2', 'wp-ultimo'), 'error');
      return;
    }

  } // end create_express_checkout;

  /**
   * Create the recurring profile
   * @param  array $args Create the PayPals Recurring Profile
   */
  public function create_recurring_profile($args) {

    /**
     * Arguments Array
     * - token
     * - desc
     * - site
     * - plan
     * - price
     * - freq
     * - trial
     */
    $args = (object) $args;

    /**
     * Start of the recurring details
     * @var RecurringPaymentsProfileDetailsType
     */
    $RPProfileDetails                   = new RecurringPaymentsProfileDetailsType();
    $RPProfileDetails->SubscriberName   = $args->site->site_owner->display_name;
    $RPProfileDetails->ProfileReference = "user:".$args->site->site_owner->ID;

    $RPProfileDetails->BillingStartDate = $args->start_date;
    
    /**
     * Set the activation details
     * Leaving here for future "enrollment fee" implementation
     * @var ActivationDetailsType
     */
    $activationDetails = new ActivationDetailsType();

    /**
     * @since  1.1.0 Initial payment if the payment is delayed
     */
    if ($args->charge_initial_amount) {

      // If we have a coupon code, we need to apply that, and remove a cycle
      if (!empty($args->trial)) {

        $args->trial = (object) $args->trial;

        $activationDetails->InitialAmount = new BasicAmountType(WU_Settings::get_setting('currency_symbol'), round($args->trial->price, 2));
        $args->trial->cycles--;

      } else {

        $activationDetails->InitialAmount = new BasicAmountType(WU_Settings::get_setting('currency_symbol'), $args->charge_initial_amount);

      }

    } // end if;
    

    /**
     * Payment Period
     * @var BillingPeriodDetailsType
     */
    $paymentBillingPeriod = new BillingPeriodDetailsType();

    // Set the frequency
    $billing_period = 'Month';
    $frequency      = $args->freq;

    $paymentBillingPeriod->BillingFrequency = $frequency;
    $paymentBillingPeriod->BillingPeriod    = $billing_period;
    $paymentBillingPeriod->Amount           = new BasicAmountType(WU_Settings::get_setting('currency_symbol'), $args->price);

    /**
     * Set the description and other elements
     * @var ScheduleDetailsType
     */
    $scheduleDetails = new ScheduleDetailsType();
    $scheduleDetails->Description       = $args->desc;
    $scheduleDetails->PaymentPeriod     = $paymentBillingPeriod;
    $scheduleDetails->MaxFailedPayments = apply_filters('wu_gateway_paypal_max_failed_payments', 3);

    $scheduleDetails->ActivationDetails = $activationDetails;

    /**
     * Set trial settings
     * TODO: Set trial settings using the new method from the subscription class
     * TODO: Use this with our cupons
     * @var BillingPeriodDetailsType
     */
    if (!empty($args->trial)) {

      $args->trial        = (object) $args->trial;
      $trialBillingPeriod =  new BillingPeriodDetailsType();

      $trialBillingPeriod->BillingPeriod      = 'Month';
      $trialBillingPeriod->BillingFrequency   = $args->trial->freq;
      $trialBillingPeriod->Amount             = new BasicAmountType(WU_Settings::get_setting('currency_symbol'), round($args->trial->price, 2));
      $scheduleDetails->TrialPeriod           = $trialBillingPeriod;

      if ($args->trial->cycles > 0) {
        $trialBillingPeriod->TotalBillingCycles = $args->trial->cycles;
      } else {
        $trialBillingPeriod->TotalBillingCycles = 10000;
      }

    }
    
    /**
     * Loads the token we returned from our previous call
     * @var CreateRecurringPaymentsProfileRequestDetailsType
     */
    $createRPProfileRequestDetail = new CreateRecurringPaymentsProfileRequestDetailsType();

    if (trim($args->token) != "") {
      $createRPProfileRequestDetail->Token = $args->token;
    } else {
      // Throws errors
      WP_Ultimo()->add_message(__('There is no valid Token present in the transaction.', 'wp-ultimo'), 'error');
      return;
    }

    $createRPProfileRequestDetail->ScheduleDetails = $scheduleDetails;
    $createRPProfileRequestDetail->RecurringPaymentsProfileDetails = $RPProfileDetails;

    $createRPProfileRequest = new CreateRecurringPaymentsProfileRequestType();
    $createRPProfileRequest->CreateRecurringPaymentsProfileRequestDetails = $createRPProfileRequestDetail;

    $createRPProfileReq =  new CreateRecurringPaymentsProfileReq();
    $createRPProfileReq->CreateRecurringPaymentsProfileRequest = $createRPProfileRequest;

    /**
     * Send the request and get the results
     * @var PayPalAPIInterfaceServiceService
     */
    $paypalService = new PayPalAPIInterfaceServiceService($this->config);

    // Let's Try =D
    try {

      /* wrap API method calls on the service object with a try catch */
      $createRPProfileResponse = $paypalService->CreateRecurringPaymentsProfile($createRPProfileReq);

    } catch (PPConnectionException $ex) {

      // Add the error
      WP_Ultimo()->add_message(sprintf(__('There was a problem setting up the paypal payment:<br />"<strong>%s</strong>"<br />Please try again.', 'wp-ultimo'), $ex->getMessage()), 'error');
      return;

    }

    /**
     * Create the subscription itself
     */
    if (isset($createRPProfileResponse) && $createRPProfileResponse->Ack == 'Success') {

      /**
       *
       * Save the Integration
       * integration_key: PROFILEID
       * 
       */
      $this->create_integration($args->site->subscription, $args->plan, $args->freq, $createRPProfileResponse->CreateRecurringPaymentsProfileResponseDetails->ProfileID, array());

      // Redirect and mark as success
      wp_redirect(WU_Gateway::get_url('success'));
      exit;
      
    } // end if;

    // Else, display error message
    else {

      $message = $createRPProfileResponse->Errors[0]->LongMessage;

      WP_Ultimo()->add_message(sprintf(__('There was a problem setting up the paypal payment: %s. Please try again.', 'wp-ultimo'), $message), 'error');
      return;
    }

  } // end create_recurring_profile;

  /**
   * Cancel one of the recurring profiles in on of the subscriptions
   * @param  [type] $subscription [description]
   * @return [type]               [description]
   */
  public function cancel_recurring_profile($subscription, $redirect = true) {

    /*
     * The ManageRecurringPaymentsProfileStatus API operation cancels, suspends, or reactivates a recurring payments profile. 
     */
    $manageRPPStatusReqestDetails            = new ManageRecurringPaymentsProfileStatusRequestDetailsType();
    $manageRPPStatusReqestDetails->Action    = 'Cancel';
    $manageRPPStatusReqestDetails->ProfileID = $subscription->integration_key;

    $manageRPPStatusReqest = new ManageRecurringPaymentsProfileStatusRequestType();
    $manageRPPStatusReqest->ManageRecurringPaymentsProfileStatusRequestDetails = $manageRPPStatusReqestDetails;

    $manageRPPStatusReq = new ManageRecurringPaymentsProfileStatusReq();
    $manageRPPStatusReq->ManageRecurringPaymentsProfileStatusRequest = $manageRPPStatusReqest;

    $paypalService = new PayPalAPIInterfaceServiceService($this->config);

    // Let's Try =D
    try {

      /* wrap API method calls on the service object with a try catch */
      $manageRPPStatusResponse = $paypalService->ManageRecurringPaymentsProfileStatus($manageRPPStatusReq);

    } catch (PPConnectionException $ex) {

      // Add the error
      WP_Ultimo()->add_message(sprintf(__('There was a problem setting up the paypal payment:<br />"<strong>%s</strong>"<br />Please try again.', 'wp-ultimo'), $ex->getMessage()), 'error');
      return;

    }

    if ($manageRPPStatusResponse->Ack =='Success') {

      // We do nothing, we should just wait for the responde from the server

      // // Save subscription status
      $subscription->integration_status = false;
      $subscription->gateway = '';
      $subscription->save();

      $log = sprintf(__('The payment subscription using %s, was canceled BY user %s.', 'wp-ultimo'), $this->title, $subscription->user_id);
      WU_Logger::add('gateway-'.$this->id, $log);

      // Redirect and mark as success
      if ($redirect){
          wp_redirect(WU_Gateway::get_url('integration-removed'));
          exit;
      }
      else return;

    }

    else {

      $message = $manageRPPStatusResponse->Errors[0]->LongMessage;

      // Add the error
      WP_Ultimo()->add_message(sprintf(__('There was a problem setting up the paypal payment. %s. Please try again.', 'wp-ultimo'), $message), 'error');
      return;
    }

  } // end cancel_recurring_profile;
  
  /**
   * Handles the giving of a refund on a specific transaction
   * @param integer $transaction_id Transaction id in our database
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
      // Add the error
      WP_Ultimo()->add_message(__('You need to pass a valid transaction id.', 'wp-ultimo'), 'error');
      return;
    }

    $transaction_id = $_GET['transaction_id'];
    $transaction = WU_Transactions::get_transaction($transaction_id);

    if (!$transaction) {
      // Add the error
      WP_Ultimo()->add_message(__('There was a problem processing this refund. Please try again.', 'wp-ultimo'), 'error');
      return;
    }

    $response = $this->paypal_refund_transaction($transaction, $_GET['value']);

    echo json_encode($response);

    exit;

  } // end proccess_refund;

  /**
   * Handles the notifications sent by PayPal's API
   */
  public function handle_notifications() {

    global $wpdb;

    // first param takes ipn data to be validated. if null, raw POST data is read from input stream
    $ipn_object = new PPIPNMessage(null, $this->config);

    // get variables
    $ipn = (object) $ipn_object->getRawData();

    // For DEBUGING PURPUSES - Log entire request
    if (WP_DEBUG) {

      WU_Logger::add("gateway-$this->id-ipn", "------");
      foreach ($ipn_object->getRawData() as $key => $value) { 
        WU_Logger::add("gateway-$this->id-ipn", "IPN: $key => $value"); 
      }
      WU_Logger::add("gateway-$this->id-ipn", "------");

    } // end if;

    /**
     * @since 1.5.0 Add a hook to allow devs to leverage our IPN
     */
    do_action('wu_paypal_handle_notifications', $ipn_object);

    /**
     * Get important information sent by API
     */

    $profile_string = isset($ipn->recurring_payment_id) ? ' - ' . $ipn->recurring_payment_id : '';

    $payment_status = isset($ipn->initial_payment_status) 
      ? $ipn->initial_payment_status 
      : $ipn->payment_status;

    /**
     * Get the user based on the custom information
     */
    $user_id = isset($ipn->invoice) ? explode(':', $ipn->invoice)[1] : 0;
    $user_id = isset($ipn->rp_invoice_id) ? explode(':', $ipn->rp_invoice_id)[1] : $user_id;

    /**
     * Check for the second case, Pyapal Buttons
     */
    if ($user_id == false && isset($ipn->option_selection1) && $ipn->option_selection1) {
      $user_id = $ipn->option_selection1;
    }

    /**
     * Allow third-parties to change that target user
     */
    $user_id = apply_filters('wu_paypal_get_target_subscription', $user_id, $ipn, $this);

    $subscription = WU_Subscription::get_instance($user_id);

    // Only go on if subscription exists
    if (!$subscription || !isset($subscription->plan_id)) {
      // If nothing is found, we log the incident and try to move on.
      WU_Logger::add('gateway-'.$this->id, sprintf(__('User ID: %s - PayPal IPN "%s" received: We received a request but we were unable to find a user to applied the changes to in our database. This user id was not found, however.', 'wp-ultimo'), $user_id, $payment_status) . $profile_string);
      exit;
    }

    // Also, we need to get the plan for loggin purposes
    $plan = new WU_Plan($subscription->plan_id);

    $setup_fee_value = 0;

    if ($plan->has_setup_fee() && ! $subscription->has_paid_setup_fee()) {

      $setup_fee_value = $plan->get_setup_fee();

    } // end if;

    /**
     * Switch all the different types of notifications
     */
    switch ($payment_status) {

      /**
       * Canceled and reversal
       */
      case 'Canceled_Reversal':
        WU_Logger::add('gateway-'.$this->id, sprintf(__('User ID: %s - PayPal IPN "%s" received: A reversal has been canceled; for example, when you win a dispute and the funds for the reversal have been returned to you.', 'wp-ultimo'), $user_id, $payment_status) . $profile_string);
      break;

      /**
       * Payment authorization expired
       */
      case 'Expired':
        WU_Logger::add('gateway-'.$this->id, sprintf(__('User ID: %s - PayPal IPN "%s" received: The authorization period for this payment has been reached.', 'wp-ultimo'), $user_id, $payment_status) . $profile_string);
        break;

      /**
       * Authorization has been voided
       */
      case 'Voided':
        WU_Logger::add('gateway-'.$this->id, sprintf(__('User ID: %s - PayPal IPN "%s" received: An authorization for this transaction has been voided.', 'wp-ultimo'), $user_id, $payment_status) . $profile_string);
        break;

      /**
       * Transaction failed
       * Action: send email - failed
       */
      case 'Failed':

        // Log this
        WU_Logger::add('gateway-'.$this->id, sprintf(__( 'User ID: %s - PayPal IPN "%s" received: The payment has failed. This happens only if the payment was made from your customer\'s bank account.', 'wp-ultimo'), $user_id, $payment_status) . $profile_string);

        // Add transaction in our transactions database
        $message = sprintf(__('Payment for the plan %s failed', 'wp-ultimo'), $plan->title);

        // Log Transaction and the results
        WU_Transactions::add_transaction($user_id, $ipn->txn_id, 'failed', $ipn->mc_gross, $this->id, $message);
          
        // Send fail email
        WU_Mail()->send_template('payment_failed', $subscription->get_user_data('user_email'), array(
          'amount'           => wu_format_currency($ipn->mc_gross),
          'date'             => date(get_option('date_format')),
          'gateway'          => $this->title,
          'user_name'        => $subscription->get_user_data('display_name'),
          'account_link'     => wp_login_url(),
        ));

        WU_Mail()->send_template('payment_failed_admin', get_network_option(null, 'admin_email'), array(
          'amount'                       => wu_format_currency($ipn->mc_gross),
          'date'                         => date(get_option('date_format')),
          'gateway'                      => $this->title,
          'user_name'                    => $subscription->get_user_data('display_name'),
          'subscription_management_link' => $subscription->get_manage_url(),
        ));

        /**
         * @since  1.1.2 Hooks for payments and integrations
         */
        do_action('wp_ultimo_payment_failed', $user_id, $this->id, $ipn->mc_gross);

        break;

      /**
       * Transaction partially refunded
       * Action: record partially refunded transaction
       */
      case 'Refunded':

        WU_Logger::add('gateway-'.$this->id, sprintf(__('User ID: %s - PayPal IPN "%s" received: You refunded the payment with %s.', 'wp-ultimo'), $user_id, $payment_status, wu_format_currency($ipn->mc_gross)) . $profile_string);

        $message = sprintf(__('A refund was issued to your account. Payment reference %s.', 'wp-ultimo'), $ipn->parent_txn_id);

        // Log Transaction and the results
        // WU_Transactions::add_transaction($user_id, $ipn->txn_id, 'refund', $ipn->mc_gross, $this->id, $message);
        WU_Transactions::add_transaction($user_id, $ipn->txn_id, 'refund', abs($ipn->mc_gross), $this->id, $message);
        
        // Remove one period from blog
        // $subscription->withdraw();

        // Send refund Mail
        WU_Mail()->send_template('refund_issued', $subscription->get_user_data('user_email'), array(
          'amount'           => wu_format_currency(abs($ipn->mc_gross)),
          'date'             => date(get_option('date_format')),
          'gateway'          => $this->title,
          'new_active_until' => $subscription->get_date('active_until'),
          'user_name'        => $subscription->get_user_data('display_name')
        ));

        /**
         * @since  1.1.2 Hooks for payments and integrations
         */
        do_action('wp_ultimo_payment_refunded', $user_id, $this->id, $ipn->mc_gross);

        break;

      /**
       * Transaction in progress
       */
      case 'In-Progress':
        WU_Logger::add('gateway-'.$this->id, sprintf(__('User ID: %s - PayPal IPN "%s" received: The transaction has not terminated, e.g. an authorization may be awaiting completion.', 'wp-ultimo'), $user_id, $payment_status) . $profile_string);
        break;

      /**
       * Payment reversed
       * Action: Deactivate (?) the subscription and register refunded amount
       */
      case 'Reversed':
        
        $status = __('A payment was reversed due to a chargeback or other type of reversal. The funds have been removed from your account balance: ', 'wp-ultimo');
        
        $reverse_reasons = array(
          'none'                     => '',
          'chargeback'               => __('A reversal has occurred on this transaction due to a chargeback by your customer.', 'wp-ultimo'),
          'chargeback_reimbursement' => __('A reversal has occurred on this transaction due to a reimbursement of a chargeback.', 'wp-ultimo'),
          'chargeback_settlement'    => __('A reversal has occurred on this transaction due to settlement of a chargeback.', 'wp-ultimo'),
          'guarantee'                => __('A reversal has occurred on this transaction due to your customer triggering a money-back guarantee.', 'wp-ultimo'),
          'buyer_complaint'          => __('A reversal has occurred on this transaction due to a complaint about the transaction from your customer.', 'wp-ultimo'),
          'unauthorized_claim'       => __('A reversal has occurred on this transaction due to the customer claiming it as an unauthorized payment.', 'wp-ultimo'),
          'refund'                   => __('A reversal has occurred on this transaction because you have given the customer a refund.', 'wp-ultimo'),
          'other'                    => __('A reversal has occurred on this transaction due to an unknown reason.', 'wp-ultimo')
        );

        // Add the correct status
        $status .= $reverse_reasons[$ipn->reason_code];

        // Log message and reasons
        WU_Logger::add('gateway-'.$this->id, sprintf(__('User ID: %s - PayPal IPN "%s" received: %s', 'wp-ultimo'), $user_id, $payment_status, $status) . $profile_string);
          
        // Add transaction in our transactions database
        $message = sprintf(__('A refund was issued to your account.', 'wp-ultimo'), wu_format_currency($ipn->mc_gross));

        // Log Transaction and the results
        WU_Transactions::add_transaction($user_id, $ipn->txn_id, 'refund', $ipn->mc_gross, $this->id, $message);

        // Remove one period from blog
        $subscription->withdraw();

        // Send refund Mail
        WU_Mail()->send_template('refund_issued', $subscription->get_user_data('user_email'), array(
          'amount'           => wu_format_currency($ipn->mc_gross),
          'date'             => date(get_option('date_format')),
          'gateway'          => $this->title,
          'new_active_until' => $subscription->get_date('active_until'),
          'user_name'        => $subscription->get_user_data('display_name')
        ));

        /**
         * @since  1.1.2 Hooks for payments and integrations
         */
        do_action('wp_ultimo_payment_refunded', $user_id, $this->id, $ipn->mc_gross);

        break;

      /**
       * Transaction Denied
       * Action: Cancel transaction after the payed period and log the cacelation
       */
      case 'Denied':
        WU_Logger::add('gateway-'.$this->id, sprintf(__('User ID: %s - PayPal IPN "%s" received: You denied the payment when it was marked as pending.', 'wp-ultimo'), $user_id, $payment_status) . $profile_string);
        
        // TODO: Remove blog from the database and log the refund
        break;

      /**
       * Transaction Completed and Processed
       * Action: Extend period of subscription
       */
      case 'Completed':
      case 'Processed':

        // receipts and record new transaction
        if ( $ipn->txn_type == 'recurring_payment' 
          || $ipn->txn_type == 'express_checkout' 
          || $ipn->txn_type == 'subscr_payment' 
          || $ipn->txn_type == 'web_accept') {

          // Extend the subscription for the duration of the frequency
          $subscription->paid_setup_fee = true;
          $subscription->extend_future();

          // Add transaction in our transactions database
          $message = sprintf(__('Payment for the plan %s - The account will now be active until %s', 'wp-ultimo'), $plan->title, $subscription->get_date('active_until'));

          // Log Transaction and the results
          WU_Transactions::add_transaction($user_id, $ipn->txn_id, 'payment', $ipn->mc_gross, $this->id, $message);

          /**
           * @since  1.2.0 Send the invoice as an attachment
           */
          $invoice     = $this->generate_invoice($this->id, $subscription, $message, $ipn->mc_gross);
          $attachments = $invoice ? array($invoice) : array();

          // Send receipt Mail
          WU_Mail()->send_template('payment_receipt', $subscription->get_user_data('user_email'), array(
            'amount'           => wu_format_currency($ipn->mc_gross),
            'date'             => date(get_option('date_format')),
            'gateway'          => $this->title,
            'new_active_until' => $subscription->get_date('active_until'),
            'user_name'        => $subscription->get_user_data('display_name')
          ), $attachments);

          WU_Logger::add('gateway-'.$this->id, sprintf(__('User ID: %s - PayPal IPN "%s" received: %s %s payment received, transaction ID %s', 'wp-ultimo'), $user_id, $payment_status, wu_format_currency($ipn->mc_gross), $ipn->txn_type, $ipn->txn_id) . $profile_string);

          /**
           * Remove one cycle from the cupom code
           */
          $subscription->remove_cycles_from_coupon_code(1);

          /**
           * @since  1.1.2 Hooks for payments and integrations
           */
          do_action('wp_ultimo_payment_completed', $user_id, $this->id, $ipn->mc_gross, $setup_fee_value);

        } // end if;

        break;

      /**
       * Payment is Pending
       */
      case 'Pending':

        $pending_str = array(
          'address'        => __('The payment is pending because your customer did not include a confirmed shipping address and your Payment Receiving Preferences is set such that you want to manually accept or deny each of these payments. To change your preference, go to the Preferences  section of your Profile.', 'wp-ultimo'),
          'authorization'  => __('The payment is pending because it has been authorized but not settled. You must capture the funds first.', 'wp-ultimo'),
          'echeck'         => __('The payment is pending because it was made by an eCheck that has not yet cleared.', 'wp-ultimo'),
          'intl'           => __('The payment is pending because you hold a non-U.S. account and do not have a withdrawal mechanism. You must manually accept or deny this payment from your Account Overview.', 'wp-ultimo'),
          'multi-currency' => __('You do not have a balance in the currency sent, and you do not have your Payment Receiving Preferences set to automatically convert and accept this payment. You must manually accept or deny this payment.', 'wp-ultimo'),
          'order'          => __('The payment is pending because it is part of an order that has been authorized but not settled.', 'wp-ultimo'),
          'paymentreview'  => __('The payment is pending while it is being reviewed by PayPal for risk.', 'wp-ultimo'),
          'unilateral'     => __('The payment is pending because it was made to an email address that is not yet registered or confirmed.', 'wp-ultimo'),
          'upgrade'        => __('The payment is pending because it was made via credit card and you must upgrade your account to Business or Premier status in order to receive the funds. It can also mean that you have reached the monthly limit for transactions on your account.', 'wp-ultimo'),
          'verify'         => __('The payment is pending because you are not yet verified. You must verify your account before you can accept this payment.', 'wp-ultimo'),
          'other'          => __('The payment is pending for an unknown reason. For more information, contact PayPal customer service.', 'wp-ultimo'),
          '*'              => ''
        );

        // Get the reason
        $reason = @$_POST['pending_reason'];
        $reason = $pending_str[$reason];

        WU_Logger::add('gateway-'.$this->id, sprintf(__('User ID: %s - PayPal IPN "%s" received: Last payment is pending (%s). Reason: %s', 'wp-ultimo'), $user_id, $payment_status, $ipn->txn_id, $reason) . $profile_string);
        break;

        default:
          // case: various error cases

      } // end switch;

      // handle exceptions from the subscription specific fields
      if (in_array($ipn->txn_type, array('subscr_failed', 'subscr_eot'))) {
        WU_Logger::add('gateway-'.$this->id, sprintf(__('User ID: %s - PayPal subscription IPN "%s" received.', 'wp-ultimo'), $user_id, $ipn->txn_type) . $profile_string);
      }

      // New subscriptions - (Really these are subscription added after a previous cancelation)
      if ($ipn->txn_type == 'recurring_payment_profile_created' || $ipn->txn_type == 'subscr_signup') {

        // Make sure to add the integration key
        // @since 1.1.2   added for PayPal Button
        if ($ipn->subscr_id) {

          // $subscription->integration_key = $ipn->subscr_id;
          $this->create_integration($subscription, $subscription->get_plan(), $subscription->freq, $ipn->subscr_id, array());

        } // end paypal button;

        WU_Logger::add('gateway-'.$this->id, sprintf(__('User ID: %s - PayPal subscription IPN "%s" received.', 'wp-ultimo'), $user_id, $ipn->txn_type) . $profile_string);

        // Send setup Mail
        WU_Mail()->send_template('subscription_created', $subscription->get_user_data('user_email'), array(
          'date'               => date(get_option('date_format')),
          'gateway'            => $this->title,
          'billing_start_date' => $subscription->get_billing_start(get_option('date_format'), false),
          'user_name'          => $subscription->get_user_data('display_name')
        ));

        // failed initial payment
        if ($ipn->initial_payment_status == 'Completed') {

          // Extend the subscription for the duration of the frequency
          $subscription->paid_setup_fee = true;
          $subscription->extend_future();

          // Add transaction in our transactions database
          $message = sprintf(__('Payment for the plan %s - The account will now be active until %s', 'wp-ultimo'), $plan->title, $subscription->get_date('active_until'));

          // Log Transaction and the results
          WU_Transactions::add_transaction($user_id, $ipn->initial_payment_txn_id, 'payment', $ipn->initial_payment_amount, $this->id, $message);

          // Send receipt Mail
          WU_Mail()->send_template('payment_receipt', $subscription->get_user_data('user_email'), array(
            'amount'           => wu_format_currency($ipn->initial_payment_amount),
            'date'             => date(get_option('date_format')),
            'gateway'          => $this->title,
            'new_active_until' => $subscription->get_date('active_until'),
            'user_name'        => $subscription->get_user_data('display_name')
          ));

          WU_Logger::add('gateway-'.$this->id, sprintf(__('User ID: %s - PayPal IPN "%s" received: %s %s payment received, transaction ID %s', 'wp-ultimo'), $user_id, $payment_status, wu_format_currency($ipn->initial_payment_amount), $ipn->txn_type, $ipn->initial_payment_txn_id) . $profile_string);

          /**
           * Remove one cycle from the cupom code
           */
          $subscription->remove_cycles_from_coupon_code(1);

          /**
           * @since  1.1.2 Hooks for payments and integrations
           */
          do_action('wp_ultimo_payment_completed', $user_id, $this->id, $ipn->initial_payment_amount, $setup_fee_value);

        } // end if;

      } // end if;

      // Canceled subsscription
      if ($ipn->txn_type == 'recurring_payment_profile_cancel' || $ipn->txn_type == 'subscr_cancel') {

        WU_Logger::add('gateway-'.$this->id, sprintf(__('User ID: %s - PayPal subscription IPN "%s" received. The subscription has been canceled.', 'wp-ultimo'), $user_id, $ipn->txn_type) . $profile_string);

        // only do stuff if this is stil the same integration key
        if ($ipn->recurring_payment_id !== $subscription->integration_key &&
            $ipn->subscr_id !== $subscription->integration_key) return;

        // Save subscription status
        $subscription->integration_status = false;
        $subscription->save();

        // Log Transaction
        WU_Transactions::add_transaction($user_id, $ipn->ipn_track_id, 'cancel', '--', $this->id, sprintf(__('The subscription for %s was canceled.', 'wp-ultimo'), $plan->title));

        // Log Event
        $log = sprintf(__('The payment subscription using %s, was canceled for user %s.', 'wp-ultimo'), $this->title, $user_id);
        WU_Logger::add('gateway-'.$this->id, $log);

        // Send cancel Mail
        WU_Mail()->send_template('subscription_canceled', $subscription->get_user_data('user_email'), array(
          'date'             => date(get_option('date_format')),
          'gateway'          => $this->title,
          'new_active_until' => $subscription->get_date('active_until'),
          'user_name'        => $subscription->get_user_data('display_name')
        ));
        
      }

    // Validate data
    if ($ipn_object->validate()) 
      WU_Logger::add('gateway-'.$this->id, sprintf(__('User ID: %s - Validated the PayPal request.', 'wp-ultimo'), $user_id));
    else 
      WU_Logger::add('gateway-'.$this->id, sprintf(__('User ID: %s - Error validating the PayPal request.', 'wp-ultimo'), $user_id));

    // Kill the execution
    exit;

  } // end handle_notifications;

  /**
   * Creates the custom fields need to our Gateway
   * @since  1.1.0 requires now works with multiple gateways
   * @since  1.1.2 option to use PayPal standard (subscription button)
   * @return array Setting fields
   */
  public function settings() {

    // Define the webhook link
    $note = sprintf(__('You should also add the url <code>%s</code> to your webhook list on Paypal Account Settings as the IPN listener. If you don\'t do that, WP Ultimo won\'t receive any notifications from PayPal and this can lead to out-of-date subscriptions and fail to log payments. <a href="https://www.%spaypal.com/cgi-bin/customerprofileweb?cmd=_profile-ipn-notify" target="_blank">Go to your PayPal settings &rarr;</a>', 'wp-ultimo'), $this->notification_url, WU_Settings::get_setting('paypal_sandbox') ? 'sandbox.' : '');
    
    // Defines this gateway settigs field
    return array(

      'paypal_username'  => array(
        'title'          => __('PayPal API Username', 'wp-ultimo'),
        'desc'           => __('User of the paypal account.', 'wp-ultimo').'<br><br>'.$note,
        'tooltip'        => '',
        'type'           => 'text',
        'placeholder'    => '',
        'default'        => '',
        // @since 1.0.5
        'require'        => array('active_gateway[paypal]' => true),
      ),
      
      'paypal_pass'      => array(
        'title'          => __('PayPal API Password', 'wp-ultimo'),
        'desc'           => __('The password of the paypal account', 'wp-ultimo'),
        'tooltip'        => '',
        'type'           => 'text',
        'placeholder'    => '',
        'default'        => '',
        // @since 1.0.5
        'require'        => array('active_gateway[paypal]' => true),
      ),
      
      'paypal_signature' => array(
        'title'          => __('PayPal API Signature', 'wp-ultimo'),
        'desc'           => __('The signature of the paypal account', 'wp-ultimo'),
        'tooltip'        => '',
        'type'           => 'text',
        'placeholder'    => '',
        'default'        => '',
        // @since 1.0.5
        'require'        => array('active_gateway[paypal]' => true),
      ),
      
      'paypal_sandbox'   => array(
        'title'          => __('PayPal Sandbox', 'wp-ultimo'),
        'desc'           => __('Unchecking this box will set Paypal to "live" environment.', 'wp-ultimo'),
        'tooltip'        => __('This is highly recommended in development and test stages of your project.', 'wp-ultimo'), 
        'type'           => 'checkbox',
        'placeholder'    => '',
        'default'        => 1,
        // @since 1.0.5
        'require'        => array('active_gateway[paypal]' => true),
      ),

      'paypal_standard'  => array(
        'title'          => __('PayPal - Subscription Button', 'wp-ultimo'),
        'desc'           => __('PayPal API (the fields above) requires that you set a single IPN listener on your PayPal account settings. That may not be posible for users that have multiple networks linked to the same PayPal account. This option will replace the PayPal Subscription API with the PayPal subscription button, setting up a dynamic IPN. IF YOU DON\'T NEED MULTIPLE IPNS, USE THE REGULAR PAYPAL INTEGRATION AND LEAVE THIS OPTION UNMARKED. COUPON CODES ARE NOT SUPPORTED IF YOU USE THIS OPTION.', 'wp-ultimo'),
        'tooltip'        => __('Even thougth the notification URL will be define dynamicaly, you still need to make sure that IPN is enabled in your PayPal account.', 'wp-ultimo'),
        'type'           => 'checkbox',
        'placeholder'    => '',
        'default'        => 0,
        'require'        => array('active_gateway[paypal]' => true),
      ),

      'paypal_standard_email'  => array(
        'title'          => __('PayPal Email', 'wp-ultimo'),
        'desc'           => __('Enter the email associated to your PayPal account.', 'wp-ultimo'),
        'tooltip'        => '',
        'type'           => 'text',
        'placeholder'    => '',
        'default'        => '',
        'require'        => array('active_gateway[paypal]' => true, 'paypal_standard' => true),
      ),

    );
    
  } // end settings;
  
  /**
   * Get the URL to redirect to paypal
   * @param  string $token The Token necessary
   * @return string The URL to redirect to
   */
  function get_redirect_url($token) {
    
    $redirect_endpoint = WU_Settings::get_setting('paypal_sandbox')
      ? 'https://www.sandbox.paypal.com/webscr?cmd=_express-checkout&token='
      : 'https://www.paypal.com/cgi-bin/webscr?cmd=_express-checkout&token=';
    
    // Redirect to paypal.com here
    return $redirect_endpoint.$token;
    
  } // end get_redirect_url;
  
  /**
   * Redirect to the PayPal page
   * @param string $token Redirect to the paypal page
   */
  function redirect_to_paypal($token) {

    wp_redirect($this->get_redirect_url($token));

    exit;

  } // end redirect_to_paypal;
  
} // end class WU_Gateway_Paypal

/**
 * Register the gateway =D
 */
wu_register_gateway('paypal', __('PayPal', 'wp-ultimo'), __('PayPal is the leading provider in checkout solutions and it is the easier way to get your network subscriptions going.', 'wp-ultimo'), 'WU_Gateway_Paypal');

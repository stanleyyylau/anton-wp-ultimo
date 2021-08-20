<?php
/**
 * Stripe Gateway
 *
 * Handles Stripe subscriptions
 *
 * @author      Arindo Duque
 * @category    Admin
 * @package     WP_Ultimo/Gateways/Stripe
 * @version     0.0.1
*/

if (!defined('ABSPATH')) {
  exit;
}

/**
 * WU_Gateway_Stripe
 */
class WU_Gateway_Stripe extends WU_Gateway {
  
  /**
   * Initialize the Gateway key elements
   */
  public function init() {

    // Require Stripe API
    if (!class_exists('WU_Stripe\Stripe')) {
      require_once 'stripe-php/init.php';
    }

    // Set your secret key: remember to change this to your live secret key in production
    // See your keys here: https://dashboard.stripe.com/account/apikeys
    WU_Stripe\Stripe::setApiKey(WU_Settings::get_setting('stripe_api_sk'));
    WU_Stripe\Stripe::setApiVersion('2016-07-06');

    // Change the Stripe button
    add_filter('wu_gateway_integration_button_stripe', array($this, 'change_stripe_button'));

    // Add the Stripe button to that particular subscription
    add_action('wu_button_subscription_on_site', array($this, 'stripe_links'));

    /**
     * @since  1.1.0 add that to our edit subscription screen as well
     */
    add_action('wu_edit_subscription_integration_meta_box', array($this, 'stripe_links'));

    add_action('wu_subscription_charge_stripe', array($this, 'create_charge'), 10, 4);
    
    /**
     * Stripe supports single payments
     * @since 1.7.0
     */
    add_filter('wu_gateway_supports_single_payments_stripe', '__return_true');

    /**
     * Adding option to update card info on the Account page for Stripe
     * @since 1.7.0
     */
    add_filter('wu_account_integrated_method_title', array($this, 'get_stripe_payment_method'), 10, 3);

    // add_action('wu_account_integrated_method_actions_before', array($this, 'display_update_method_link'), 10, 3);

    add_action('wu_edit_subscription_integration_meta_box', array($this, 'display_card_information_on_management_screen'), 10, 1);

    add_action('wu_integration_status_widget_actions', array($this, 'add_view_on_stripe_link_on_management_screen'), 10, 1);

    $this->register_gateway_page('update-payment-method', array($this, 'update_payment_method'));

  } // end init;

  public function get_credit_card_icon($brand) {

    $brand = str_replace(' ',  '-', strtolower($brand));

    return sprintf('<span class="wu-credit-card wu-icons-cc-%s"></span>', $brand);

  } // end get_credit_card_icon;

  /**
   * Update the payment method on Stripe
   * 
   * @since 1.7.0
   * @return void
   */
  public function update_payment_method() {

    if (!isset($_POST['stripeToken'])) return;

    // Get the tokens
    $token       = $_POST['stripeToken'];
    $token_email = $_POST['stripeEmail'];

    // Get the customer
    $customer = $this->get_stripe_customer($this->subscription->integration_key);

    // If costumer exists
    if ($customer) {

      try {
        
        $customer->source = $token;

        $customer->save();

        $title = apply_filters('wu_update_payment_method_success_title', __('Success!', 'wp-ultimo'));

        $message = apply_filters('wu_update_payment_method_success_message', __('Payment method successfully updated!', 'wp-ultimo'));

        return WU_Util::display_alert($title, $message);

      } catch (Exception $e) {

        return WP_Ultimo()->add_message(sprintf(__('An error occured processing your request: %s', 'wp-ultimo'), $e->getMessage()), 'error');

      } // end catch;

    } // end if;

    return WP_Ultimo()->add_message(__('An error occured processing your request', 'wp-ultimo'), 'error');

  } // end update_payment_method;

  /**
   * Display the Card information on the management screen as well so the 
   * admin can provide support right from that screen
   *
   * @since 1.7.0
   * @param WU_Subscription $subscription
   * @return void
   */
  public function display_card_information_on_management_screen($subscription) {

    if ($this->id !== $subscription->gateway || ! $subscription->integration_status) return;

    echo "<li>
      <label>". __('Card Information', 'wp-ultimo') ."</label>
      <span>". $this->get_current_payment_method($subscription->integration_key) ."</span>
    </li>";

  } // end display_card_information_on_management_screen;

  /**
   * Add a link to redirect to Stripe Dashboard
   *
   * @since 1.7.0
   * @param WU_Subscription $subscription
   * @return void
   */
  public function add_view_on_stripe_link_on_management_screen($subscription) {

    if ($this->id !== $subscription->gateway || ! $subscription->integration_status) return;

    $url = $this->get_subscription_url_on_stripe($subscription->meta->subscription_id);

    printf(' <small>- <a href="%s" target="_blank">%s</a></small>', $url, __('View on Stripe Dashboard', 'wp-ultimo'));

  } // end add_view_on_stripe_link_on_management_screen;

  /**
   * Get the current payment source over at Stripe, only supports credidt cards for now
   *
   * @since 1.7.0
   * @param string $customer_id Stripe custumer_id
   * @return string
   */
  public function get_current_payment_method($customer_id) {

    wp_enqueue_style('wu-payment-font');

    try {

      // Retrieve the customer and expand their default source
      $payment_method = WU_Stripe\Customer::Retrieve(
        array(
          "id"     => $customer_id,
          "expand" => array("default_source"),
        )
      );

      if (is_object($payment_method->default_source) && $payment_method->default_source->object == 'card') {

        wp_enqueue_style('wu-payment-font');

        $icon = $this->get_credit_card_icon($payment_method->default_source->brand);

        // Return pretty string if has card
        return sprintf(__('%s ending in %s', 'wp-ultimo'), $payment_method->default_source->brand, $payment_method->default_source->last4) . $icon;

      } // end if;

      return __('Stripe', 'wp-ultimo');

    } catch (Exception $e) { 
    
      // Log errors
      WU_Logger::add("gateway-$this->id", $e->getMessage());

    } // end catch;

    return __('No active method', 'wp-ultimo');

  } // end get_current_payment_method;

  /**
   * Display the update method link
   *
   * @since 1.7.0
   * @param WU_Gateway $gateway
   * @param WU_Subscription $subscription
   * @return void
   */
  public function display_update_method_link($gateway, $subscription) {

    if (!$gateway || $gateway->id != $this->id) return;

    ?>
    - 
    <span class="plugins">

      <script type="text/javascript" src="https://checkout.stripe.com/checkout.js"></script>
      
        <a href="<?php echo $this->get_url('update-payment-method'); ?>" 
          id = "stripe-button" 
          type="submit"
          src="https://checkout.stripe.com/checkout.js" class="stripe-button" 
          data-key="<?php echo WU_Settings::get_setting('stripe_api_pk'); ?>"  
          data-image="<?php echo WU_Settings::get_logo('full', WU_Settings::get_setting('logo-square')); ?>" 
          data-panel-label="<?php _e('Update Card Details', 'wp-ultimo'); ?>" 

          data-name="<?php echo get_site_option('site_name'); ?>" 
          data-allow-remember-me="false" 
          data-email="<?php echo $subscription->get_user_data('user_email'); ?>"  
          data-locale="<?php echo strtok(get_user_locale($this->subscription->user_id), '_'); ?>" 
          data-zip-code="<?php echo WU_Settings::get_setting('stripe_should_collect_billing_address', false) ? 'true' : 'false' ?>" 
          data-billing-address="<?php echo WU_Settings::get_setting('stripe_should_collect_billing_address', false) ? 'true' : 'false' ?>" 
        >
          <?php _e('Update Payment Method', 'wp-ultimo'); ?>
        </a>

        <script>
        (function($) {
          $(document).ready(function() {

            $('#stripe-button').on('click', function(event) {

              event.preventDefault();

              var $button = $(this);

              var opts = $.extend({}, $button.data(), {
                token: function(result) {

                  // console.log(result); return;

                  $.redirectPost($button.attr('href'), {
                    stripeEmail: result.email,
                    stripeToken: result.id,
                  });
                  
                }
              });

              StripeCheckout.open(opts);

            });
          });
        })(jQuery);
        </script>

    </span>

    <?php

  } // end display_update_method_link;

  /**
   * Get the current stripe active payment method
   *
   * @since 1.7.0
   * @param string $title
   * @param WU_Gateway $gateway
   * @param WU_Subscription $subscription
   * @return string
   */
  public function get_stripe_payment_method($title, $gateway, $subscription) {

    if (!$gateway || $gateway->id != $this->id) return $title;

    return $this->get_current_payment_method($subscription->integration_key);

  } // end get_stripe_payment_method;

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
  public function create_charge($amount, $description, $subscription, $type = "single_charge") {

    $amount = (float) $this->format_for_stripe( $amount );

    $status = WU_Stripe\Charge::create(array(
      "amount"      => $amount,
      "currency"    => WU_Settings::get_setting('currency_symbol'),
      "customer"    => $subscription->integration_key,
      "description" => $description,
      "metadata"    => array(
        "type"        => $type,
        "user_id"     => $subscription->user_id,
        "email"       => $subscription->get_user_data('user_email'),
      ),
    ));

    return $status;

  } // end create_charge;

  /**
   * Format Stripe values to make it work
   * @since  1.2.0 Support for zero decimal currencies
   * @param  interger $value Value to be formated to amount
   */
  public function format_for_stripe($value) {

    $value = WU_Util::to_float($value);

    // No Cents currencies
    $no_cents = array("BIF", "CLP", "DJF", "GNF", "JPY", "KMF", "KRW", "MGA", "PYG", "RWF", "VND", "VUV", "XAF", "XOF", "XPF");

    return in_array( strtoupper(WU_Settings::get_setting('currency_symbol')), $no_cents) ? $value : $value * 100;

  } // end format_for_stripe;

  /**
   * Format Stripe values received from stripe
   * @since  1.2.0 Support for zero decimal currencies
   * @param  interger $value Value to be formated to amount
   */
  public function format_from_stripe($value) {

    $value = WU_Util::to_float($value);

    // No Cents currencies
    $no_cents = array("BIF", "CLP", "DJF", "GNF", "JPY", "KMF", "KRW", "MGA", "PYG", "RWF", "VND", "VUV", "XAF", "XOF", "XPF");

    return in_array( strtoupper(WU_Settings::get_setting('currency_symbol')), $no_cents) ? $value : $value / 100;

  } // end format_from_stripe;

  public function get_local_subscription_by_stripe_customer_id($customer_id) {

    global $wpdb;

    $table_name = WU_Subscription::get_table_name();

    $prefix = apply_filters('wu_core_get_table_prefix', $wpdb->base_prefix);

    $query = $wpdb->prepare("SELECT sub.user_id FROM $table_name sub WHERE integration_key = %s", $customer_id);

    $user_id = $wpdb->get_var($query);

    return wu_get_subscription($user_id);

  }

  public function create_stripe_session() {

    // $sub = $this->get_local_subscription_by_stripe_customer_id("cus_FngWm6aW7ikbKs");

    // Trial Day
    $trial_end  = $this->subscription->get_billing_start('U');

    // We need to create our plan now
    $plan_id     = $this->create_stripe_plan_id($this->plan->title, $this->freq, $this->price);
    $plan_exists = $this->check_stripe_plan_id($plan_id);

    // If theres no stripe plan that macthes that, we create a new one
    if (!$plan_exists) {

      try {

        $create_plan = WU_Stripe\Plan::create(array(
          "amount"         => (float) $this->format_for_stripe( $this->price ),
          "interval"       => 'month',
          "interval_count" => $this->freq,
          "name"           => $this->plan->title,
          "currency"       => strtolower(WU_Settings::get_setting('currency_symbol')),
          "id"             => $plan_id,
          "metadata"       => array(
            "plan_id"      => $this->plan->id
          )
        ));

      }

      // Catch if plan already exists
      catch (Exception $e) { 

        WP_Ultimo()->add_message(sprintf(__('An error occured processing your request: %s', 'wp-ultimo'), $e->getMessage()), 'error');

        return;

      }

    } // end if;

    $customer = false;

		$coupon_code = $this->subscription->get_coupon_code();

    $stripe_coupon_code = null;
    
    if ($coupon_code && $coupon_code['value'] != 0) {

      $stripe_coupon_code = $this->get_stripe_coupon($coupon_code);

    } // end if;

		// Check if client exists
		if ($this->subscription->integration_key) {

			// Get the customer
			$customer = $this->get_stripe_customer($this->subscription->integration_key);

    } // end if;

		// If costumer exists
		// if ($coupon_code) {

    if (!$customer) {

      $customer_array = array(
        'coupon'   => $stripe_coupon_code,
        'email'    => $this->subscription->get_user_data('user_email'),
        'metadata' => $this->get_user_meta_to_send(),
        'name'     => get_user_meta($this->user_id, 'first_name', true) . ' ' . get_user_meta($this->user_id, 'last_name', true),
        'address'  => array(
          'line1'       => get_user_meta($this->user_id, 'line1', true) ?: '',
          'line2'       => get_user_meta($this->user_id, 'line2', true) ?: '',
          'city'        => get_user_meta($this->user_id, 'city', true) ?: '',
          'state'       => get_user_meta($this->user_id, 'state', true) ?: '',
          'postal_code' => get_user_meta($this->user_id, 'zip_code', true) ?: '',
          'country'     => get_user_meta($this->user_id, 'country', true) ?: '',
        ),
      );

      // We can now create our costumer and add him to our plan
      try {

        $customer = WU_Stripe\Customer::create($customer_array);

      } catch (Exception $e) {

        $this->errors->add('customer', $e->getMessage());

        return;

      } // end try;

    } else {

      try {

        $customer->coupon = $stripe_coupon_code;
        
        $customer->save();

      } catch (Exception $e) {

        $this->errors->add('customer', $e->getMessage());

        return;

      } // end try;

    } // end if;
    
    $this->subscription->integration_key = $customer->id;
    
    $this->subscription->save();

    $subscription_data = array(
        'payment_method_types' => ['card'],
        'subscription_data' => [
          'items' => [[
            'plan' => $plan_id,
          ]],
        ],
        'billing_address_collection' => WU_Settings::get_setting('stripe_should_collect_billing_address', false) ? 'required' : 'auto',
        'success_url' => $this->get_url('success'),
        'cancel_url' => admin_url(),
        'client_reference_id' => $this->subscription->user_id,
    );

    /**
     * Handles setup fee
     * @since 1.7.0
     */
    if ($this->plan->has_setup_fee() && ! $this->subscription->has_paid_setup_fee()) {

      $description = apply_filters('wu_setup_fee_message', sprintf(__('Setup fee for Plan %s', 'wp-ultimo'), $this->plan->title), $this->plan);

      $subscription_data['line_items'][] = array(
        "name"        => __('Setup Fee', 'wp-ultimo'),
        "amount"      => $this->format_for_stripe($this->plan->get_setup_fee()),
        "quantity"    => 1,
        "currency"    => strtolower(WU_Settings::get_setting('currency_symbol')),
        "description" => $description,
      );

    } // end if;

    if ($trial_end && $trial_end > WU_Transactions::get_current_time('timestamp')) {

      // Greater than two days?
      if ($trial_end - WU_Transactions::get_current_time('timestamp') <= 2 * DAY_IN_SECONDS) {

        $trial_end = WU_Transactions::get_current_time('timestamp') + (2 * DAY_IN_SECONDS) + 60;

        $trial_end = apply_filters('wu_stripe_should_set_48h_trial', true, $trial_end) ? $trial_end : false;

      } // end if;

      if ($trial_end) {

        $subscription_data['subscription_data']['trial_end'] = $trial_end;

      } // end if;

    } // end if;

    if ($customer) {

      $subscription_data['customer'] = $customer->id;

    } else {

      $subscription_data['customer_email'] = $this->subscription->get_user_data('user_email');
      
    } // end if;

    try {

      $session = WU_Stripe\Checkout\Session::create(
        $subscription_data
      );

      return $session;

    } catch (Exception $e) {

      WU_Logger::add("gateway-$this->id", $e->getMessage());

      echo $e->getMessage();

      return false;

    }

  } // end create_stripe_session;

  /**
   * We need to change the integration button on the stripe button
   * @param  string $html HTML button
   * @return string       Return the new button
   */
  public function change_stripe_button($html) {

    // if (!$this->is_desired_gateway()) return $html;

    // Check for settings
    if (!WU_Settings::get_setting('stripe_api_pk')) {

      $html = str_replace('<a', '<button disabled="disabled"', $html);
      $html = str_replace('</a', '</button', $html);
      $html = str_replace('button-primary', 'button-disabled', $html);

      return $html;

    } // end if;
    
    /**
     * We need to build the description for the PayPal request
     */
    $desc = wu_get_interval_string($this->subscription->get_price_after_coupon_code(), $this->freq, true);

    $value = $this->subscription->get_price_after_coupon_code();

    if ($this->plan->has_setup_fee() && ! $this->subscription->has_paid_setup_fee()) {

      $desc = wu_get_interval_string($this->subscription->get_price_after_coupon_code(), $this->freq, false);
      $desc  .= ' '. sprintf(__('+ Setup Fee: %s', 'wp-ultimo'), wu_format_currency($this->plan->get_setup_fee()));
      $value += (float) $this->plan->get_setup_fee();

    } // end if;

    /**
     * @since  1.1.0 Add the coupon code string
     */
    if ($cc = $this->subscription->get_coupon_code()) {
      $desc .= " ". $cc['coupon_code'];
    }

    $session = $this->create_stripe_session();

    if (!$session) {
      
      return;

    }
    
    ob_start();
    
    ?>

    <!-- Load Stripe.js on your website. -->
    <script src="https://js.stripe.com/v3"></script>

    <!-- Create a button that your customers click to complete their purchase. Customize the styling to suit your branding. -->
    <a id="stripe-checkout-button" style="width: 100%; text-align: center; margin-top: 10px;" class="button button-primary button-streched" href="#">
      <?php echo $this->get_button_label(); ?>
    </a>

    <div id="error-message"></div>

    <script>
      var stripe = Stripe('<?php echo esc_js(WU_Settings::get_setting('stripe_api_pk')); ?>');

      var checkoutButton = document.getElementById('stripe-checkout-button');

      checkoutButton.addEventListener('click', function () {
        // When the customer clicks on the button, redirect
        // them to Checkout.
        stripe.redirectToCheckout({
          // Do not rely on the redirect to the successUrl for fulfilling
          // purchases, customers may not always reach the success_url after
          // a successful payment.
          // Instead use one of the strategies described in
          // https://stripe.com/docs/payments/checkout/fulfillment
          sessionId: '<?php echo esc_js($session->id); ?>',
        })
        .then(function (result) {
          if (result.error) {
            // If `redirectToCheckout` fails due to a browser or network
            // error, display the localized error message to your customer.
            var displayError = document.getElementById('error-message');
            displayError.textContent = result.error.message;
          }
        });
      });
    </script>

    <?php 

    $button = ob_get_clean(); 

    return $button; 

  } // end change_stripe_button;

  /**
   * Returns the Remote URL of the subscription on the Stripe Dashboard
   *
   * @since 1.7.0
   * @param string $stripe_subscription_id
   * @return string
   */
  public function get_subscription_url_on_stripe($stripe_subscription_id) {

    // Check for test environment
    $test = strpos(WU_Settings::get_setting('stripe_api_sk'), 'test') !== false;

    return sprintf("https://dashboard.stripe.com%s/subscriptions/%s", $test ? '/test' : '', $stripe_subscription_id);

  } // end get_subscription_url_on_stripe;

  /**
   * Add a direct link to check a subscription in paypal's website
   */
  public function stripe_links() {

    // If there's no integration key, there's nothing to do
    if (wu_get_active_gateway()->id != $this->id) return;

    if ($this->subscription->integration_key == '' || !$this->subscription->integration_status) return;

    $url = $this->get_subscription_url_on_stripe($this->subscription->meta->subscription_id);

    if (current_user_can('manage_network')) {

      printf('<li><a href="%s" target="_blank">%s</a></li>', $url, __('Check this subscription at Stripe', 'wp-ultimo'));

    } // end if;

  } // end paypal_links;

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

    if (!isset($_GET['value'])) {

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

    /**
     * Get the invoice
     */
    try {
      $invoice = WU_Stripe\Invoice::retrieve($transaction->reference_id);
    }

    // Catch if plan already exists
    catch (Exception $e) { 
      
       die(json_encode(array(
         'status'  => false,
         'message' => __('Invoice not found on Stripe.', 'wp-ultimo'),
       )));

    }

    // Desired Charge
    $charge = $invoice->charge;

    try {

      $re = WU_Stripe\Refund::create(array(
        "charge" => $charge,
        "amount" => $this->format_for_stripe( (float) $_GET['value'] ),
      ));

      die(json_encode(array(
         'status'  => true,
         'message' => __('Refund issued successfully. It should appear on this panel shortly.', 'wp-ultimo'),
      )));

    }

    // Catch 
    catch (Exception $e) { 
      
       die(json_encode(array(
         'status'  => false,
         'message' => $e->getMessage(),
       )));

    }

  } // end process_refund;
  
  /**
   * First step of the payment flow: proccess_form
   */
  public function process_integration() {

    // Trial Day
    $trial_end  = $this->subscription->get_billing_start('U');

    // If not token, we return
    if (!isset($_POST['stripeToken'])) {
      WP_Ultimo()->add_message(__('No valid token found. Please contact the administrator.', 'wp-ultimo'), 'error');
      return;
    }

    // We need to create our plan now
    $plan_id     = $this->create_stripe_plan_id($this->plan->title, $this->freq, $this->price);
    $plan_exists = $this->check_stripe_plan_id($plan_id);

    // If theres no stripe plan that macthes that, we create a new one
    if (!$plan_exists) {

      try {
        $create_plan = WU_Stripe\Plan::create(array(
          "amount"         => (float) $this->format_for_stripe( $this->price ),
          "interval"       => 'month',
          "interval_count" => $this->freq,
          "name"           => $this->plan->title,
          "currency"       => strtolower(WU_Settings::get_setting('currency_symbol')),
          "id"             => $plan_id,
          "metadata"       => array(
            "plan_id"      => $this->plan->id
          )
        ));
      }

      // Catch if plan already exists
      catch (Exception $e) { 
        WP_Ultimo()->add_message(sprintf(__('An error occured processing your request: %s', 'wp-ultimo'), $e->getMessage()), 'error');
        return;
      }

    } // end if;
    
    /**
     * Now we move on to the Costumer signup part
     */

    // Get the tokens
    $token       = $_POST['stripeToken'];
    $token_email = $_POST['stripeEmail'];

    /** 
     * Let's get the coupon code
     * @since  1.1.0
     */

    $customer = false;
    
    $coupon_code        = $this->subscription->get_coupon_code();
    $stripe_coupon_code = $coupon_code ? $this->get_stripe_coupon($coupon_code) : null;
    
    // Check if client exists
    if ($this->subscription->integration_key) {

      // Get the customer
      $customer = $this->get_stripe_customer($this->subscription->integration_key);

    } // end if;

    // If costumer exists
    if (!$customer) {

      $customer_array = array(

        "coupon"    => $stripe_coupon_code,

        //"source"    => $token,
        "email"     => $token_email,
        "metadata"  => array_merge(array(
          "user_id" => $this->site->site_owner->ID,
        ), (array) apply_filters('wu_stripe_user_metadata', array(), $this->subscription->user_id, $this->plan)),
      );

      // We can now create our costumer and add him to our plan
      try {
        $customer = WU_Stripe\Customer::create($customer_array);
      } 

      // Catch if plan already exists
      catch (Exception $e) {
        WP_Ultimo()->add_message(sprintf(__('An error occured processing your request: %s', 'wp-ultimo'), $e->getMessage()), 'error');
        return;
      }

    } // end if;

    /**
     * Handles setup fee
     * @since 1.7.0
     */
    if ($this->plan->has_setup_fee() && ! $this->subscription->has_paid_setup_fee()) {

      $description = apply_filters('wu_setup_fee_message', sprintf(__('Setup fee for Plan %s', 'wp-ultimo'), $this->plan->title), $this->plan);

      $create_invoice = WU_Stripe\InvoiceItem::create(array(
          "customer"    => $customer->id,
          "amount"      => $this->format_for_stripe($this->plan->get_setup_fee()),
          "currency"    => strtolower(WU_Settings::get_setting('currency_symbol')),
          "description" => $description,
          "metadata"    => array(
            "setupfee"  => 1,
          )
      ));

    } // end if;

    try {

      $customer->coupon = $stripe_coupon_code;

      // Add the new source and email
      $customer->source    = $token;
      $customer->email     = $token_email;
      $customer->plan      = $plan_id;
      $customer->metadata  = array_merge(array(
        "user_id" => $this->site->site_owner->ID,
      ), (array) apply_filters('wu_stripe_user_metadata', array(), $this->subscription->user_id, $this->plan));

      /**
       * @since  1.1.0 Add the trial_end, but only if we need to
       */
      if ($trial_end) {
        $customer->trial_end = $trial_end;
      }

      $customer->save();

    }

    catch (Exception $e) {

      WP_Ultimo()->add_message(sprintf(__('An error occured processing your request: %s', 'wp-ultimo'), $e->getMessage()), 'error');
      return;

    }

    /**
     * Now we get the costumer ID to be saved in the integration
     */
    
    $this->create_integration($this->subscription, $this->plan, $this->freq, $customer->id, array(
      'subscription_id' => $customer->subscriptions->data[0]->id,
    ));

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

    if (!$new_plan->id) {
      WP_Ultimo()->add_message(__('You need to select a valid plan to change to.', 'wp-ultimo'), 'error');
      return;
    }

    /**
     * Now we have the new plan and the new frequency
     */
    // Case: new plan if free
    if ($new_plan->free) {

      // Just remove the integration
      $this->remove_integration(false);

      // Send email confirming things
      // TODO: send email
      
      // Set new plan
      $this->subscription->plan_id = $new_plan->id;
      $this->subscription->freq    = 1;
      $this->subscription->price   = 0; // $new_plan->{"price_".$_POST['plan_freq']};
      $this->subscription->integration_status = false;

      $this->subscription->gateway = '';

      $this->subscription->save();

      // Hooks, passing new plan
      do_action('wu_subscription_change_plan', $this->subscription, $new_plan, $current_plan);
      
      // Redirect to success page
      wp_redirect(WU_Gateway::get_url('plan-changed'));

      exit;

    } // end if free;

    $new_price = $new_plan->{"price_".$_POST['plan_freq']};
    $new_freq  = (int) $_POST['plan_freq'];

    // Get the new stripe plan, or create a new one
    $plan_id     = $this->create_stripe_plan_id($new_plan->title, $new_freq, $new_price);
    $plan_exists = $this->check_stripe_plan_id($plan_id);

    // If theres no stripe plan that macthes that, we create a new one
    if (!$plan_exists) {

      try {
        $create_plan = WU_Stripe\Plan::create(array(
          "amount"         => (float) $this->format_for_stripe( $new_price ),
          "interval"       => 'month',
          "interval_count" => $new_freq,
          "name"           => $new_plan->title,
          "currency"       => strtolower(WU_Settings::get_setting('currency_symbol')),
          "id"             => $plan_id,
          "metadata"       => array(
            "plan_id"      => $new_plan->id
          )
        ));
      }

      // Catch if plan already exists
      catch (Exception $e) { 
        WP_Ultimo()->add_message(sprintf(__('An error occured processing your request: %s', 'wp-ultimo'), $e->getMessage()), 'error');
        return;
      }

    } // end if;

    // get Subscription
    $subscription_id = ($this->plan->free || !$this->subscription->integration_status) ? false : $this->subscription->meta->subscription_id;
    $stripe_subscription = $this->get_stripe_subscription($subscription_id);

    if ($stripe_subscription != false) {

      // Update the stripe subscription
      try {
        $stripe_subscription->plan = $plan_id;
        $stripe_subscription->save();
      }

      catch (Exception $e) { 
        WP_Ultimo()->add_message(sprintf(__('An error occured processing your request: %s', 'wp-ultimo'), $e->getMessage()), 'error');
        return;
      }

    } // end subscription exists;

    // Update our subscription object now
    $this->subscription->plan_id            = $new_plan->id;
    $this->subscription->freq               = $new_freq;
    $this->subscription->price              = $new_price;
    $this->subscription->integration_status = true;

    $this->subscription->save();

    // Hooks, passing new plan
    do_action('wu_subscription_change_plan', $this->subscription, $new_plan, $current_plan);

    // Redirect to success page
    wp_redirect(WU_Gateway::get_url('plan-changed'));

    exit;

  } // end change_plan;

  /**
   * Creates a unique plan id based on the title, freqeuncy and price
   * @param  string $plan_title Plan title
   * @param  string $plan_freq  Plan frenquency
   * @param  string $plan_price Plan price
   * @return string             New uniqui plan identifier
   */
  public function create_stripe_plan_id($plan_title, $plan_freq, $plan_price) {
    return sanitize_title( $plan_title.$plan_freq.$plan_price);
  }

  /**
   * Remove the Stripe integration
   */
  public function remove_integration($redirect = true, $subscription = false) {

    // Get the subscription
    if (!$subscription) {

      $subscription = $this->subscription;

    } // end if;

    // We can now create our costumer and add him to our plan
    try {
    
      // Retrieve the Stripe API
      $stripe_subscription = WU_Stripe\Subscription::retrieve($subscription->meta->subscription_id);

      if ($stripe_subscription && $stripe_subscription->status !== 'canceled') {

        // Cancel
        $stripe_subscription->cancel();

      } // end if;

      // Finally we remove the integration from our database
      $subscription->meta->subscription_id = '';
      $subscription->integration_status = false;
      $subscription->save();

    } // end try;

    // Catch if plan already exists
    catch (Exception $e) {
      WP_Ultimo()->add_message(sprintf(__('An error occured processing your request: %s', 'wp-ultimo'), $e->getMessage()), 'error');
      return;
    }

    // if we need to redirect
    if ($redirect) {

      // Redirect and mark as success
      wp_redirect(WU_Gateway::get_url('integration-removed'));

      exit;

    } // end if;

  } // end remove_integration;

  /**
   * Return a stripe costumer, or false. Usefull to have access to metadata
   * @param  string $customer_id Stripe Costumer ID
   * @return object              Stripe costumer or false, if not found
   */
  public function get_stripe_customer($customer_id) {

    try { $customer = WU_Stripe\Customer::retrieve($customer_id);

    } catch (Exception $e) { 
      WP_Ultimo()->add_message(sprintf(__('An error occured processing your request: %s', 'wp-ultimo'), $e->getMessage()), 'error');
      $customer = false; 
    }

    // return the customer or false
    return $customer;

  } // create_stripe_customer;

  /**
   * Returns Stripe Subscription
   * @param  string $subscription_id Subscription id
   * @return object                  Subscription object
   */
  public function get_stripe_subscription($subscription_id) {
     try { $subscription = WU_Stripe\Subscription::retrieve($subscription_id); }
     catch (Exception $e) { $subscription = false; }
     return $subscription;
  }

  /**
   * Check if a given plan with the same price and all, alreayd exists on Stripe
   * @param  string   $plan_id Stripe plan id to check
   * @param  string   $period  Month or Year, for stripe
   * @param  interger $price   Price of the plan
   * @return string/boolean    Stripe Plan id or false
   */
  public function check_stripe_plan_id($plan_id) {
    try { $plan = WU_Stripe\Plan::retrieve($plan_id);
    } catch (Exception $e) { return false; }
    return $plan->id;
  } // end check_stripe_plan

  public function check_stripe_coupon_id($coupon_id) {
    try { $coupon = WU_Stripe\Coupon::retrieve($coupon_id);
    } catch (Exception $e) { return false; }
    return $coupon->id;
  } // end check_stripe_plan

  /**
   * Create the Coupon code or retrieve it
   * @param  array $coupon_code 
   * @return string
   */
  public function get_stripe_coupon($coupon_code) {

    // Get the new stripe plan, or create a new one
    $coupon_id     = $this->create_stripe_plan_id($coupon_code['coupon_code'], $coupon_code['type'].$coupon_code['cycles'], $coupon_code['value']);
    $coupon_exists = $this->check_stripe_coupon_id($coupon_id);

    if (!$coupon_exists) {

      $creation_array = array(
        "id" => $coupon_id,
      );

      /**
       * Switch based on type
       */
      if ($coupon_code['type'] == 'percent') {
        $creation_array['percent_off'] = $coupon_code['value'];
      } else {
        $creation_array['amount_off']  = $this->format_for_stripe($coupon_code['value']);
      }

      /**
       * Get the cycles
       */
      if ($coupon_code['cycles'] > 0) {
        $creation_array['duration']           = 'repeating';
        $creation_array['duration_in_months'] = $coupon_code['cycles'];
      } else {
        $creation_array['duration']           = 'forever';
      }

      // Add the currency symbol
      $creation_array['currency'] = strtolower(WU_Settings::get_setting('currency_symbol'));

      try {
        $coupon_id = WU_Stripe\Coupon::create($creation_array);
        return $coupon_id;
      }

      // Catch if plan already exists
      catch (Exception $e) { 
        return $coupon_id;
      }

    } // end if;

    else {return $coupon_id;}

  } // end create_coupon;

  /**
   * Handles the notifications sent by Stripe's API
   */
  public function handle_notifications() {

    global $wpdb;

    // Retrieve the request's body and parse it as JSON
    $input = @file_get_contents("php://input");
    $event = json_decode($input);

    // Get user sent from metadata
    
    // For DEBUGING PURPUSES - Log entire request
    if (defined('WP_DEBUG') && WP_DEBUG) {

      WU_Logger::add("gateway-$this->id-ipn", "------");
      WU_Logger::add("gateway-$this->id-ipn", json_encode($event)); 
      WU_Logger::add("gateway-$this->id-ipn", "------");

    } // end if;

    if (!isset($event->data->object->customer)) {
      
      return;

    } // end if;

    /**
     * Get the user based on the custom information
     */
    $customer_id     = $event->data->object->customer;
    $stripe_customer = $this->get_stripe_customer($customer_id);
    $user_id         = $stripe_customer ? $stripe_customer->metadata->user_id : 0;

    /** New Checkout Code */
    $user_id         = $user_id ?: $event->data->object->client_reference_id;

    $subscription = WU_Subscription::get_instance($user_id);

    if (!$subscription) {

      $subscription = $this->get_local_subscription_by_stripe_customer_id($customer_id);
      $user_id     = $subscription ? $subscription->user_id : 0;

    } // end if;

    // Only go on if subscription exists
    if (!$subscription) {
      // If nothing is found, we log the incident and try to move on.
      WU_Logger::add('gateway-'.$this->id, sprintf(__('User ID: %s - Stripe Webhook "%s" received: We received a request but we were unable to find a user to applied the changes to in our database. This user id was not found, however.', 'wp-ultimo'), $user_id, $event->type) . $event->id);
      exit;
    }

    // Also, we need to get the plan for loggin purposes
    $plan = new WU_Plan($subscription->plan_id);

    /**
     * Now we switch each important message to take appropriate measures
     */
    switch ($event->type) {

      case 'checkout.session.completed':

        $this->create_integration($subscription, $plan, $subscription->freq, $customer_id, array(
          'subscription_id' => $event->data->object->subscription,
        ));

        break;

      /**
       * Case Cancel
       * Now we handle the cancelation of a subscription
       */
      case 'customer.subscription.deleted':
        // case 'customer.deleted':

        WU_Logger::add('gateway-'.$this->id, sprintf(__('User ID: %s - Stripe Webhook "%s" received. The subscription has been canceled.', 'wp-ultimo'), $user_id, $event->type) . $event->id);

        $new_active_until = new DateTime();
        $new_active_until->setTimestamp($event->data->object->current_period_end + wu_get_offset_timestamp());

        // Save subscription status
        $subscription->active_until = $new_active_until->format('Y-m-d H:i:s');
        
        $subscription->integration_status = false;
        $subscription->save();

        // Log Transaction
        WU_Transactions::add_transaction($user_id, $event->id, 'cancel', '--', $this->id, sprintf(__('The subscription for %s was canceled.', 'wp-ultimo'), $plan->title));

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

        // End case
        break;

      /**
       * Case Subscriptions created and updated
       * Handles the payment received
       */
      case 'customer.subscription.created':
      // case 'customer.subscription.updated':

        WU_Logger::add('gateway-'.$this->id, sprintf(__('User ID: %s - Stripe Webhook "%s" received.', 'wp-ultimo'), $user_id, $event->type) . $event->id);

        WU_Mail()->send_template('subscription_created', $subscription->get_user_data('user_email'), array(
          'date'               => date(get_option('date_format')),
          'gateway'            => $this->title,
          'billing_start_date' => $subscription->get_billing_start(get_option('date_format'), false),
          'user_name'          => $subscription->get_user_data('display_name')
        ));

        break;

      case 'charge.succeeded':

        if ($event->data->object->metadata->type == 'setup_fee' && $event->data->object->paid) {

          // Add transaction in our transactions database
          $message = $event->data->object->description;

          // Log Transaction and the results
          WU_Transactions::add_transaction($user_id, $event->data->object->id, 'payment', $this->format_from_stripe( $event->data->object->amount ), $this->id, $message);

          $subscription->paid_setup_fee = true;
          
          $subscription->save();

        } // end if;

        if ($event->data->object->metadata->type == 'single_charge' && $event->data->object->paid) {

          // Add transaction in our transactions database
          $message = $event->data->object->description;

          // Log Transaction and the results
          WU_Transactions::add_transaction($user_id, $event->data->object->id, 'payment', $this->format_from_stripe( $event->data->object->amount ), $this->id, $message);

        } // end if;

        break;

      /**
       * Case Payment Received - Successfully
       * Handles the payment received
       */
      case 'invoice.payment_succeeded':

        $_transaction = WU_Transactions::get_transaction_by_reference_id($event->data->object->id);

        /**
         * Transaction already exists, skip re-adding it.
         */
        if ($_transaction) {

          WU_Logger::add('gateway-'.$this->id, sprintf(__('User ID: %s - Stripe Webhook received: %s %s payment received, transaction ID %s. This is a repeated webhook call, though, so no transactions were added.', 'wp-ultimo'), $user_id, $event->type, wu_format_currency( $this->format_from_stripe( $event->data->object->amount_due ) ), $event->id));

          break;

        } // end if;

        // Extend the subscription for the duration of the frequency
        // $subscription->extend();
        
        /**
         * Resets the active until
         * @var DateTime
         */
        $stripe_subscription = $this->get_stripe_subscription($subscription->meta->subscription_id);

        $new_active_until = $stripe_subscription ? new DateTime() : false;

        if ($new_active_until) {

          $new_active_until->setTimestamp($stripe_subscription->current_period_end + wu_get_offset_timestamp());

          $subscription->active_until = $new_active_until->format('Y-m-d H:i:s');

          $subscription->paid_setup_fee = true;

          $subscription->save();

          /**
           * Remove one cycle from the cupom code
           */
          $subscription->remove_cycles_from_coupon_code(1);

        }

        $setup_fee_value = 0;

        /**
         * Checks if payment is setup fee as well
         * @since 1.7.0
         */
        if (isset($event->data->object->lines->data) && is_array($event->data->object->lines->data)) {

          foreach($event->data->object->lines->data as $line) {

            if (isset($line->metadata->setupfee) && $line->metadata->setupfee) {

              $setup_fee_value = $plan->get_setup_fee();
              
              $subscription->paid_setup_fee = true;
              $subscription->save();
              break;

            } // end if;

          } // end foreach;

        } // end if;

        // Add transaction in our transactions database
        $message = sprintf(__('Payment for the plan %s - The account will now be active until %s', 'wp-ultimo'), $plan->title, $subscription->get_date('active_until'));

        // Log Transaction and the results
        WU_Transactions::add_transaction($user_id, $event->data->object->id, 'payment', $this->format_from_stripe( $event->data->object->amount_due ), $this->id, $message);

        /**
         * @since  1.2.0 Send the invoice as an attachment
         */
        $invoice     = $this->generate_invoice($this->id, $subscription, $message, $this->format_from_stripe( $event->data->object->amount_due ));
        $attachments = $invoice ? array($invoice) : array();

        // Send receipt Mail
        WU_Mail()->send_template('payment_receipt', $subscription->get_user_data('user_email'), array(
          'amount'           => wu_format_currency( $this->format_from_stripe( $event->data->object->amount_due ) ),
          'date'             => date(get_option('date_format')),
          'gateway'          => $this->title,
          'new_active_until' => $subscription->get_date('active_until'),
          'user_name'        => $subscription->get_user_data('display_name')
        ), $attachments);

        WU_Logger::add('gateway-'.$this->id, sprintf(__('User ID: %s - Stripe Webhook received: %s %s payment received, transaction ID %s', 'wp-ultimo'), $user_id, $event->type, wu_format_currency( $this->format_from_stripe( $event->data->object->amount_due ) ), $event->id));

        /**
         * @since  1.1.2 Hooks for payments and integrations
         */
        do_action('wp_ultimo_payment_completed', $user_id, $this->id, $this->format_from_stripe( $event->data->object->amount_due ), $setup_fee_value);

        // End case
        break;

      /**
       * Case Payment Received - Failed
       * Handles the payment received in case of failure
       */
      case 'invoice.payment_failed':

        // Log this
        WU_Logger::add('gateway-'.$this->id, sprintf(__( 'User ID: %s - Stripe Webhook received: The payment has failed.', 'wp-ultimo'), $user_id, $event->id) . $event_id);

        // Add transaction in our transactions database
        $message = sprintf(__('Payment for the plan %s failed', 'wp-ultimo'), $plan->title);

        // Log Transaction and the results
        WU_Transactions::add_transaction($user_id, $ipn->txn_id, 'failed', $this->format_from_stripe( $event->data->object->amount_due ), $this->id, $message);
          
        // Send fail email
        WU_Mail()->send_template('payment_failed', $subscription->get_user_data('user_email'), array(
          'amount'           => wu_format_currency( $this->format_from_stripe( $event->data->object->amount_due ) ),
          'date'             => date(get_option('date_format')),
          'gateway'          => $this->title,
          'user_name'        => $subscription->get_user_data('display_name'),
          'account_link'     => wp_login_url(),
        ));

        // Send fail email
        WU_Mail()->send_template('payment_failed_admin', get_network_option(null, 'admin_email'), array(
          'amount'                       => wu_format_currency( $this->format_from_stripe( $event->data->object->amount_due ) ),
          'date'                         => date(get_option('date_format')),
          'gateway'                      => $this->title,
          'user_name'                    => $subscription->get_user_data('display_name'),
          'subscription_management_link' => $subscription->get_manage_url(),
        ));

        /**
         * @since  1.1.2 Hooks for payments and integrations
         */
        do_action('wp_ultimo_payment_failed', $user_id, $this->id, $this->format_from_stripe( $event->data->object->amount_due ));

        break;

      /**
       * Refund Given
       */
      case 'charge.refunded':

        $value = $this->format_from_stripe((float) $event->data->object->refunds->data[0]->amount);

        WU_Logger::add('gateway-'.$this->id, sprintf(__('User ID: %s - Stripe WebHook "%s" received: You refunded the payment with %s.', 'wp-ultimo'), $user_id, $event->id, wu_format_currency($value)) . $event->id);

        $message = sprintf(__('A refund was issued to your account. Payment reference %s.', 'wp-ultimo'), $event->data->object->id);

        // Log Transaction and the results
        WU_Transactions::add_transaction($user_id, $event->data->object->balance_transaction, 'refund', $value, $this->id, $message);

        // Remove one period from blog
        // $subscription->withdraw();

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
        do_action('wp_ultimo_payment_refunded', $user_id, $this->id, $value);

        break;

    } // end switch;

    // Let Stripe know that everything went fine
    http_response_code(200); // PHP 5.4 or greater

    // Return and Kill
    echo json_encode(array('message' => 'Thanks, Stripe'));
    die;

  } // end handle_notifications;

  /**
   * Creates the custom fields need to our Gateway
   * @since  1.1.0 requires now works with multiple gateways
   * @return array Setting fields
   */
  public function settings() {

    // Define the webhook link
    $note = sprintf(__('You should also add the url <code>%s</code> to your webhook list on Stripe Account Settings. If you don\'t do that, WP Ultimo won\'t receive any notifications from Stripe and this can lead to out-of-date subscriptions and fail to log payments. <a href="%s" target="_blank">Go to your Stripe Account settings &rarr;</a>', 'wp-ultimo'), $this->notification_url, 'https://dashboard.stripe.com/account/webhooks');
    
    // Defines this gateway settigs field
    return array(

      'stripe_api_pk'   => array(
        'title'          => __('Stripe Publishable Key', 'wp-ultimo'),
        'desc'           => __('Enter your Stripe API Publishable key.', 'wp-ultimo').'<br><br>'.$note,
        'tooltip'        => __('Keep an eye if you are placing your test of live API key.', 'wp-ultimo'), 
        'type'           => 'text',
        'placeholder'    => '',
        'default'        => '',
        // @since 1.0.5
        'require'        => array('active_gateway[stripe]' => true),
      ),
      
      'stripe_api_sk'   => array(
        'title'          => __('Stripe Secret Key', 'wp-ultimo'),
        'desc'           => __('Enter your Stripe API key.', 'wp-ultimo'),
        'tooltip'        => __('Keep an eye if you are placing your test of live API key.', 'wp-ultimo'), 
        'type'           => 'text',
        'placeholder'    => '',
        'default'        => '',
        'require'        => array('active_gateway[stripe]' => true),
      ),

      'stripe_should_collect_billing_address'   => array(
        'title'          => __('Collect Billing Address', 'wp-ultimo'),
        'desc'           => __('Enabling this option will add the Billing Address step to the Stripe Checkout form. This information will also be saved by WP Ultimo.', 'wp-ultimo'),
        'tooltip'        => '',
        'type'           => 'checkbox',
        'placeholder'    => '',
        'default'        => 0,
        'require'        => array('active_gateway[stripe]' => true),
      ),

    );
    
  } // end settings;

  /**
	 * Get the user meta we want to send to to Stripe
	 *
	 * @since 2.0.0
	 * @return array
	 */
	public function get_user_meta_to_send() {

		$meta_data = array('user_id' => $this->user_id);

		$additional_meta_data = apply_filters('wu_stripe_user_metadata', array(), $this->user_id, $this->plan_id);

		return array_merge($meta_data, $additional_meta_data);

	} // end get_user_meta_to_send;
  
} // end class WU_Gateway_Stripe

/**
 * Register the gateway =D
 */
wu_register_gateway('stripe', __('Stripe', 'wp-ultimo'), __('Stripe is the best software platform for running an internet business.', 'wp-ultimo'), 'WU_Gateway_Stripe');

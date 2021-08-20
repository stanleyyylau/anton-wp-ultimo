<?php

function wu_get_offset_timestamp() {

  $offset_in_seconds = 0;

  get_current_site() && switch_to_blog(get_current_site()->blog_id);
    
    $offset_in_seconds = get_option( 'gmt_offset' ) * HOUR_IN_SECONDS;
    
  restore_current_blog();

  return $offset_in_seconds;

} // end wu_get_offset_timestamp;

/**
 * Retursn the billing description string
 *
 * @since 1.7.0
 * @param float $price
 * @param int $interval
 * @param boolean $extended
 * @return string
 */
function wu_get_interval_string($price, $interval, $extended = false) {

  $default_string = sprintf(__('%s, billed every %s month(s)', 'wp-ultimo'), wu_format_currency($price), $interval);

  if ($extended) return $default_string;

  $strings = array(
    1  => __('monthly', 'wp-ultimo'),
    3  => __('quarterly', 'wp-ultimo'),
    12 => __('yearly', 'wp-ultimo'),
  );

  if (isset($strings[ $interval ])) return sprintf('%s, %s', wu_format_currency($price), $strings[ $interval ]);

  return $default_string;

} // end wu_get_interval_string;

/**
 * Returns the site plan of a given site
 * @param  interger $site_id Site id to retrieve plan
 * @return string/boolean    Plan name on success, false if account has no plan
 */
function wu_get_account_plan($site_id = false) {
  
  $site_id = $site_id ? $site_id : get_current_blog_id();
  $site = wu_get_site($site_id);

  if ($plan = $site->get_plan()) return $plan->title;
  else return false;

} // end wu_get_site_plan;

/**
 * Returns if an account is active or not
 * @param  interger $site_id Site id to check
 * @return boolean           Wether or not the subscription is active
 */
function wu_is_account_active($site_id = false) {
  
  $site_id = $site_id ? $site_id : get_current_blog_id();
  $site = wu_get_site($site_id);

  return $site->subscription->is_active();

} // end wu_is_account_active;

function wu_register_gateway($id, $title, $desc, $class_name) {
  
  // Get global
  global $wu_payment_gateways;
  
  // Checks if gateway was already added
  if (is_array($wu_payment_gateways) && isset($wu_payment_gateways[$id])) return;
  
  // Init gateway
  $gateway = new $class_name($id, $title, $desc, $class_name);
  
  // Adds to the global
  $wu_payment_gateways[$id] = array(
    'title'      => $title, 
    'desc'       => $desc, 
    'gateway'    => $gateway,
  );
  
  // Return the value
  return $gateway;

} // end wu_register_gateway;

function wu_get_gateways() {
  global $wu_payment_gateways;
  return $wu_payment_gateways ? $wu_payment_gateways : array();
}

function wu_get_gateway($gateway) {
  global $wu_payment_gateways;
  return isset($wu_payment_gateways[$gateway]['gateway']) ? $wu_payment_gateways[$gateway]['gateway'] : false;
}

/**
 * Returns the gateway being used by the current user at the moment
 * @since  1.1.0
 * @return WU_Gateway
 */
function wu_get_active_gateway() {

  $gateways = wu_get_gateways();

  $subscription = wu_get_current_site()->get_subscription();

  if ($subscription && $subscription->gateway) {

    $active_gateway_id = $subscription->gateway;

    return isset($gateways[$active_gateway_id])
      ? $gateways[$active_gateway_id]['gateway']
      : new WU_Gateway(null, null, null, null);

  } else {

    return new WU_Gateway(null, null, null, null);

  }

} // end wu_get_active_gateway;

/**
 * Get all the currencies we use in Ultimo
 * @return array Return the currencies array
 */
function get_wu_currencies() {
  return array_unique(
    apply_filters('wu_currencies',
      array(
        'AED' => __( 'United Arab Emirates Dirham', 'wp-ultimo'),
        'ARS' => __( 'Argentine Peso', 'wp-ultimo'),
        'AUD' => __( 'Australian Dollars', 'wp-ultimo'),
        'BDT' => __( 'Bangladeshi Taka', 'wp-ultimo'),
        'BRL' => __( 'Brazilian Real', 'wp-ultimo'),
        'BGN' => __( 'Bulgarian Lev', 'wp-ultimo'),
        'CAD' => __( 'Canadian Dollars', 'wp-ultimo'),
        'CLP' => __( 'Chilean Peso', 'wp-ultimo'),
        'CNY' => __( 'Chinese Yuan', 'wp-ultimo'),
        'COP' => __( 'Colombian Peso', 'wp-ultimo'),
        'CZK' => __( 'Czech Koruna', 'wp-ultimo'),
        'DKK' => __( 'Danish Krone', 'wp-ultimo'),
        'DOP' => __( 'Dominican Peso', 'wp-ultimo'),
        'EUR' => __( 'Euros', 'wp-ultimo'),
        'HKD' => __( 'Hong Kong Dollar', 'wp-ultimo'),
        'HRK' => __( 'Croatia kuna', 'wp-ultimo'),
        'HUF' => __( 'Hungarian Forint', 'wp-ultimo'),
        'ISK' => __( 'Icelandic krona', 'wp-ultimo'),
        'IDR' => __( 'Indonesia Rupiah', 'wp-ultimo'),
        'INR' => __( 'Indian Rupee', 'wp-ultimo'),
        'NPR' => __( 'Nepali Rupee', 'wp-ultimo'),
        'ILS' => __( 'Israeli Shekel', 'wp-ultimo'),
        'JPY' => __( 'Japanese Yen', 'wp-ultimo'),
        'KIP' => __( 'Lao Kip', 'wp-ultimo'),
        'KRW' => __( 'South Korean Won', 'wp-ultimo'),
        'MYR' => __( 'Malaysian Ringgits', 'wp-ultimo'),
        'MXN' => __( 'Mexican Peso', 'wp-ultimo'),
        'NGN' => __( 'Nigerian Naira', 'wp-ultimo'),
        'NOK' => __( 'Norwegian Krone', 'wp-ultimo'),
        'NZD' => __( 'New Zealand Dollar', 'wp-ultimo'),
        'PYG' => __( 'Paraguayan Guaraní', 'wp-ultimo'),
        'PHP' => __( 'Philippine Pesos', 'wp-ultimo'),
        'PLN' => __( 'Polish Zloty', 'wp-ultimo'),
        'GBP' => __( 'Pounds Sterling', 'wp-ultimo'),
        'RON' => __( 'Romanian Leu', 'wp-ultimo'),
        'RUB' => __( 'Russian Ruble', 'wp-ultimo'),
        'SGD' => __( 'Singapore Dollar', 'wp-ultimo'),
        'ZAR' => __( 'South African rand', 'wp-ultimo'),
        'SEK' => __( 'Swedish Krona', 'wp-ultimo'),
        'CHF' => __( 'Swiss Franc', 'wp-ultimo'),
        'TWD' => __( 'Taiwan New Dollars', 'wp-ultimo'),
        'THB' => __( 'Thai Baht', 'wp-ultimo'),
        'TRY' => __( 'Turkish Lira', 'wp-ultimo'),
        'UAH' => __( 'Ukrainian Hryvnia', 'wp-ultimo'),
        'USD' => __( 'US Dollars', 'wp-ultimo'),
        'VND' => __( 'Vietnamese Dong', 'wp-ultimo'),
        'EGP' => __( 'Egyptian Pound', 'wp-ultimo')
      )
    )
  );
}

function get_wu_currency_symbol($currency = '') {
  if (!$currency) {
    $currency = WU_Settings::get_setting('currency_symbol');
  }

  switch ( $currency ) {
    case 'AED' :
      $currency_symbol = 'د.إ';
      break;
    case 'AUD' :
    case 'ARS' :
    case 'CAD' :
    case 'CLP' :
    case 'COP' :
    case 'HKD' :
    case 'MXN' :
    case 'NZD' :
    case 'SGD' :
    case 'USD' :
      $currency_symbol = '$';
      break;
    case 'BDT':
      $currency_symbol = '৳&nbsp;';
      break;
    case 'BGN' :
      $currency_symbol = 'лв.';
      break;
    case 'BRL' :
      $currency_symbol = 'R$';
      break;
    case 'CHF' :
      $currency_symbol = 'CHF';
      break;
    case 'CNY' :
    case 'JPY' :
    case 'RMB' :
      $currency_symbol = '&yen;';
      break;
    case 'CZK' :
      $currency_symbol = 'Kč';
      break;
    case 'DKK' :
      $currency_symbol = 'DKK';
      break;
    case 'DOP' :
      $currency_symbol = 'RD$';
      break;
    case 'EGP' :
      $currency_symbol = 'EGP';
      break;
    case 'EUR' :
      $currency_symbol = '&euro;';
      break;
    case 'GBP' :
      $currency_symbol = '&pound;';
      break;
    case 'HRK' :
      $currency_symbol = 'Kn';
      break;
    case 'HUF' :
      $currency_symbol = 'Ft';
      break;
    case 'IDR' :
      $currency_symbol = 'Rp';
      break;
    case 'ILS' :
      $currency_symbol = '₪';
      break;
    case 'INR' :
      $currency_symbol = 'Rs.';
      break;
    case 'ISK' :
      $currency_symbol = 'Kr.';
      break;
    case 'KIP' :
      $currency_symbol = '₭';
      break;
    case 'KRW' :
      $currency_symbol = '₩';
      break;
    case 'MYR' :
      $currency_symbol = 'RM';
      break;
    case 'NGN' :
      $currency_symbol = '₦';
      break;
    case 'NOK' :
      $currency_symbol = 'kr';
      break;
    case 'NPR' :
      $currency_symbol = 'Rs.';
      break;
    case 'PHP' :
      $currency_symbol = '₱';
      break;
    case 'PLN' :
      $currency_symbol = 'zł';
      break;
    case 'PYG' :
      $currency_symbol = '₲';
            break;
    case 'RON' :
      $currency_symbol = 'lei';
      break;
    case 'RUB' :
      $currency_symbol = 'руб.';
      break;
    case 'SEK' :
      $currency_symbol = 'kr';
      break;
    case 'THB' :
      $currency_symbol = '฿';
      break;
    case 'TRY' :
      $currency_symbol = '₺';
      break;
    case 'TWD' :
      $currency_symbol = 'NT$';
      break;
    case 'UAH' :
      $currency_symbol = '₴';
      break;
    case 'VND' :
      $currency_symbol = '₫';
      break;
    case 'ZAR' :
      $currency_symbol = 'R';
      break;
    default :
      $currency_symbol = $currency;
      break;
  }

  return apply_filters('wu_currency_symbol', $currency_symbol, $currency);
}
 
/**
 * Formats a value into our defined format
 * @param  string $value Value to be processed
 * @return string Formated Value
 */
function wu_format_currency($value) {
  
  $value = floatval(str_replace(',', '.', $value));
  
  $currency        = WU_Settings::get_setting('currency_symbol');
  $currency_symbol = get_wu_currency_symbol($currency);
  $thousands_sep   = WU_Settings::get_setting('thousand_separator');
  $decimal_sep     = WU_Settings::get_setting('decimal_separator');
  $num_decimals    = (int) WU_Settings::get_setting('precision', 0);
  $format          = WU_Settings::get_setting('currency_position');

  $value = number_format($value, $num_decimals, $decimal_sep, $thousands_sep);
  
  $format = str_replace('%v', $value, $format);
  $format = str_replace('%s', $currency_symbol, $format);
  
  return apply_filters('wu_format_currency', $format, $currency_symbol, $value);
  
}

/**
 * Cleans the float values before we save them to the database
 *
 * @since 1.7.0
 * @param string $value
 * @return string
 */
function wu_sanitize_currency_for_saving($value) {
  
  $thousands_sep   = WU_Settings::get_setting('thousand_separator');
  $decimal_sep     = WU_Settings::get_setting('decimal_separator');
  $num_decimals    = WU_Settings::get_setting('precision');
  
  $value           = (float) str_replace($thousands_sep, '', sanitize_text_field($value));

  return $value;

} // end wu_sanitize_currency_for_saving;

/**
 * Returns how many days ago the first date was in relation to the second date
 * If second date is empty, now is used
 * 
 * @since 1.7.0
 * @param string   $date_1
 * @param mixed    $date_2
 * @return integer Positive if days ago, positive if days in the future
 */
function wu_get_days_ago($date_1, $date_2 = false) {

  $date_2 = $date_2 ?: WU_Transactions::get_current_time('mysql');

  $datetime_1 = new DateTime($date_1);
  $datetime_2 = new DateTime($date_2);

  return - ((int) $datetime_2->diff($datetime_1)->format("%r%a"));

} // end wu_compare_dates;
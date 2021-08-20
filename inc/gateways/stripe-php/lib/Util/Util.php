<?php

namespace WU_Stripe\Util;

use WU_Stripe\StripeObject;

abstract class Util
{
    private static $isMbstringAvailable = null;
    private static $isHashEqualsAvailable = null;

    /**
     * Whether the provided array (or other) is a list rather than a dictionary.
     * A list is defined as an array for which all the keys are consecutive
     * integers starting at 0. Empty arrays are considered to be lists.
     *
     * @param array|mixed $array
     * @return boolean true if the given object is a list.
     */
    public static function isList($array)
    {
        if (!is_array($array)) {
            return false;
        }
        if ($array === []) {
            return true;
        }
        if (array_keys($array) !== range(0, count($array) - 1)) {
            return false;
        }
        return true;
    }

    /**
     * Converts a response from the Stripe API to the corresponding PHP object.
     *
     * @param array $resp The response from the Stripe API.
     * @param array $opts
     * @return StripeObject|array
     */
    public static function convertToStripeObject($resp, $opts)
    {
        $types = [
            // data structures
            \WU_Stripe\Collection::OBJECT_NAME => \WU_Stripe\Collection::class,

            // business objects
            \WU_Stripe\Account::OBJECT_NAME => \WU_Stripe\Account::class,
            \WU_Stripe\AccountLink::OBJECT_NAME => \WU_Stripe\AccountLink::class,
            \WU_Stripe\AlipayAccount::OBJECT_NAME => \WU_Stripe\AlipayAccount::class,
            \WU_Stripe\ApplePayDomain::OBJECT_NAME => \WU_Stripe\ApplePayDomain::class,
            \WU_Stripe\ApplicationFee::OBJECT_NAME => \WU_Stripe\ApplicationFee::class,
            \WU_Stripe\ApplicationFeeRefund::OBJECT_NAME => \WU_Stripe\ApplicationFeeRefund::class,
            \WU_Stripe\Balance::OBJECT_NAME => \WU_Stripe\Balance::class,
            \WU_Stripe\BalanceTransaction::OBJECT_NAME => \WU_Stripe\BalanceTransaction::class,
            \WU_Stripe\BankAccount::OBJECT_NAME => \WU_Stripe\BankAccount::class,
            \WU_Stripe\BitcoinReceiver::OBJECT_NAME => \WU_Stripe\BitcoinReceiver::class,
            \WU_Stripe\BitcoinTransaction::OBJECT_NAME => \WU_Stripe\BitcoinTransaction::class,
            \WU_Stripe\Capability::OBJECT_NAME => \WU_Stripe\Capability::class,
            \WU_Stripe\Card::OBJECT_NAME => \WU_Stripe\Card::class,
            \WU_Stripe\Charge::OBJECT_NAME => \WU_Stripe\Charge::class,
            \WU_Stripe\Checkout\Session::OBJECT_NAME => \WU_Stripe\Checkout\Session::class,
            \WU_Stripe\CountrySpec::OBJECT_NAME => \WU_Stripe\CountrySpec::class,
            \WU_Stripe\Coupon::OBJECT_NAME => \WU_Stripe\Coupon::class,
            \WU_Stripe\CreditNote::OBJECT_NAME => \WU_Stripe\CreditNote::class,
            \WU_Stripe\Customer::OBJECT_NAME => \WU_Stripe\Customer::class,
            \WU_Stripe\CustomerBalanceTransaction::OBJECT_NAME => \WU_Stripe\CustomerBalanceTransaction::class,
            \WU_Stripe\Discount::OBJECT_NAME => \WU_Stripe\Discount::class,
            \WU_Stripe\Dispute::OBJECT_NAME => \WU_Stripe\Dispute::class,
            \WU_Stripe\EphemeralKey::OBJECT_NAME => \WU_Stripe\EphemeralKey::class,
            \WU_Stripe\Event::OBJECT_NAME => \WU_Stripe\Event::class,
            \WU_Stripe\ExchangeRate::OBJECT_NAME => \WU_Stripe\ExchangeRate::class,
            \WU_Stripe\File::OBJECT_NAME => \WU_Stripe\File::class,
            \WU_Stripe\File::OBJECT_NAME_ALT => \WU_Stripe\File::class,
            \WU_Stripe\FileLink::OBJECT_NAME => \WU_Stripe\FileLink::class,
            \WU_Stripe\Invoice::OBJECT_NAME => \WU_Stripe\Invoice::class,
            \WU_Stripe\InvoiceItem::OBJECT_NAME => \WU_Stripe\InvoiceItem::class,
            \WU_Stripe\InvoiceLineItem::OBJECT_NAME => \WU_Stripe\InvoiceLineItem::class,
            \WU_Stripe\Issuing\Authorization::OBJECT_NAME => \WU_Stripe\Issuing\Authorization::class,
            \WU_Stripe\Issuing\Card::OBJECT_NAME => \WU_Stripe\Issuing\Card::class,
            \WU_Stripe\Issuing\CardDetails::OBJECT_NAME => \WU_Stripe\Issuing\CardDetails::class,
            \WU_Stripe\Issuing\Cardholder::OBJECT_NAME => \WU_Stripe\Issuing\Cardholder::class,
            \WU_Stripe\Issuing\Dispute::OBJECT_NAME => \WU_Stripe\Issuing\Dispute::class,
            \WU_Stripe\Issuing\Transaction::OBJECT_NAME => \WU_Stripe\Issuing\Transaction::class,
            \WU_Stripe\LoginLink::OBJECT_NAME => \WU_Stripe\LoginLink::class,
            \WU_Stripe\Order::OBJECT_NAME => \WU_Stripe\Order::class,
            \WU_Stripe\OrderItem::OBJECT_NAME => \WU_Stripe\OrderItem::class,
            \WU_Stripe\OrderReturn::OBJECT_NAME => \WU_Stripe\OrderReturn::class,
            \WU_Stripe\PaymentIntent::OBJECT_NAME => \WU_Stripe\PaymentIntent::class,
            \WU_Stripe\PaymentMethod::OBJECT_NAME => \WU_Stripe\PaymentMethod::class,
            \WU_Stripe\Payout::OBJECT_NAME => \WU_Stripe\Payout::class,
            \WU_Stripe\Person::OBJECT_NAME => \WU_Stripe\Person::class,
            \WU_Stripe\Plan::OBJECT_NAME => \WU_Stripe\Plan::class,
            \WU_Stripe\Product::OBJECT_NAME => \WU_Stripe\Product::class,
            \WU_Stripe\Radar\EarlyFraudWarning::OBJECT_NAME => \WU_Stripe\Radar\EarlyFraudWarning::class,
            \WU_Stripe\Radar\ValueList::OBJECT_NAME => \WU_Stripe\Radar\ValueList::class,
            \WU_Stripe\Radar\ValueListItem::OBJECT_NAME => \WU_Stripe\Radar\ValueListItem::class,
            \WU_Stripe\Recipient::OBJECT_NAME => \WU_Stripe\Recipient::class,
            \WU_Stripe\RecipientTransfer::OBJECT_NAME => \WU_Stripe\RecipientTransfer::class,
            \WU_Stripe\Refund::OBJECT_NAME => \WU_Stripe\Refund::class,
            \WU_Stripe\Reporting\ReportRun::OBJECT_NAME => \WU_Stripe\Reporting\ReportRun::class,
            \WU_Stripe\Reporting\ReportType::OBJECT_NAME => \WU_Stripe\Reporting\ReportType::class,
            \WU_Stripe\Review::OBJECT_NAME => \WU_Stripe\Review::class,
            \WU_Stripe\SetupIntent::OBJECT_NAME => \WU_Stripe\SetupIntent::class,
            \WU_Stripe\Sigma\ScheduledQueryRun::OBJECT_NAME => \WU_Stripe\Sigma\ScheduledQueryRun::class,
            \WU_Stripe\SKU::OBJECT_NAME => \WU_Stripe\SKU::class,
            \WU_Stripe\Source::OBJECT_NAME => \WU_Stripe\Source::class,
            \WU_Stripe\SourceTransaction::OBJECT_NAME => \WU_Stripe\SourceTransaction::class,
            \WU_Stripe\Subscription::OBJECT_NAME => \WU_Stripe\Subscription::class,
            \WU_Stripe\SubscriptionItem::OBJECT_NAME => \WU_Stripe\SubscriptionItem::class,
            \WU_Stripe\SubscriptionSchedule::OBJECT_NAME => \WU_Stripe\SubscriptionSchedule::class,
            \WU_Stripe\TaxId::OBJECT_NAME => \WU_Stripe\TaxId::class,
            \WU_Stripe\TaxRate::OBJECT_NAME => \WU_Stripe\TaxRate::class,
            \WU_Stripe\ThreeDSecure::OBJECT_NAME => \WU_Stripe\ThreeDSecure::class,
            \WU_Stripe\Terminal\ConnectionToken::OBJECT_NAME => \WU_Stripe\Terminal\ConnectionToken::class,
            \WU_Stripe\Terminal\Location::OBJECT_NAME => \WU_Stripe\Terminal\Location::class,
            \WU_Stripe\Terminal\Reader::OBJECT_NAME => \WU_Stripe\Terminal\Reader::class,
            \WU_Stripe\Token::OBJECT_NAME => \WU_Stripe\Token::class,
            \WU_Stripe\Topup::OBJECT_NAME => \WU_Stripe\Topup::class,
            \WU_Stripe\Transfer::OBJECT_NAME => \WU_Stripe\Transfer::class,
            \WU_Stripe\TransferReversal::OBJECT_NAME => \WU_Stripe\TransferReversal::class,
            \WU_Stripe\UsageRecord::OBJECT_NAME => \WU_Stripe\UsageRecord::class,
            \WU_Stripe\UsageRecordSummary::OBJECT_NAME => \WU_Stripe\UsageRecordSummary::class,
            \WU_Stripe\WebhookEndpoint::OBJECT_NAME => \WU_Stripe\WebhookEndpoint::class,
        ];
        if (self::isList($resp)) {
            $mapped = [];
            foreach ($resp as $i) {
                array_push($mapped, self::convertToStripeObject($i, $opts));
            }
            return $mapped;
        } elseif (is_array($resp)) {
            if (isset($resp['object']) && is_string($resp['object']) && isset($types[$resp['object']])) {
                $class = $types[$resp['object']];
            } else {
                $class = \WU_Stripe\StripeObject::class;
            }
            return $class::constructFrom($resp, $opts);
        } else {
            return $resp;
        }
    }

    /**
     * @param string|mixed $value A string to UTF8-encode.
     *
     * @return string|mixed The UTF8-encoded string, or the object passed in if
     *    it wasn't a string.
     */
    public static function utf8($value)
    {
        if (self::$isMbstringAvailable === null) {
            self::$isMbstringAvailable = function_exists('mb_detect_encoding');

            if (!self::$isMbstringAvailable) {
                trigger_error("It looks like the mbstring extension is not enabled. " .
                    "UTF-8 strings will not properly be encoded. Ask your system " .
                    "administrator to enable the mbstring extension, or write to " .
                    "support@stripe.com if you have any questions.", E_USER_WARNING);
            }
        }

        if (is_string($value) && self::$isMbstringAvailable && mb_detect_encoding($value, "UTF-8", true) != "UTF-8") {
            return utf8_encode($value);
        } else {
            return $value;
        }
    }

    /**
     * Compares two strings for equality. The time taken is independent of the
     * number of characters that match.
     *
     * @param string $a one of the strings to compare.
     * @param string $b the other string to compare.
     * @return bool true if the strings are equal, false otherwise.
     */
    public static function secureCompare($a, $b)
    {
        if (self::$isHashEqualsAvailable === null) {
            self::$isHashEqualsAvailable = function_exists('hash_equals');
        }

        if (self::$isHashEqualsAvailable) {
            return hash_equals($a, $b);
        } else {
            if (strlen($a) != strlen($b)) {
                return false;
            }

            $result = 0;
            for ($i = 0; $i < strlen($a); $i++) {
                $result |= ord($a[$i]) ^ ord($b[$i]);
            }
            return ($result == 0);
        }
    }

    /**
     * Recursively goes through an array of parameters. If a parameter is an instance of
     * ApiResource, then it is replaced by the resource's ID.
     * Also clears out null values.
     *
     * @param mixed $h
     * @return mixed
     */
    public static function objectsToIds($h)
    {
        if ($h instanceof \WU_Stripe\ApiResource) {
            return $h->id;
        } elseif (static::isList($h)) {
            $results = [];
            foreach ($h as $v) {
                array_push($results, static::objectsToIds($v));
            }
            return $results;
        } elseif (is_array($h)) {
            $results = [];
            foreach ($h as $k => $v) {
                if (is_null($v)) {
                    continue;
                }
                $results[$k] = static::objectsToIds($v);
            }
            return $results;
        } else {
            return $h;
        }
    }

    /**
     * @param array $params
     *
     * @return string
     */
    public static function encodeParameters($params)
    {
        $flattenedParams = self::flattenParams($params);
        $pieces = [];
        foreach ($flattenedParams as $param) {
            list($k, $v) = $param;
            array_push($pieces, self::urlEncode($k) . '=' . self::urlEncode($v));
        }
        return implode('&', $pieces);
    }

    /**
     * @param array $params
     * @param string|null $parentKey
     *
     * @return array
     */
    public static function flattenParams($params, $parentKey = null)
    {
        $result = [];

        foreach ($params as $key => $value) {
            $calculatedKey = $parentKey ? "{$parentKey}[{$key}]" : $key;

            if (self::isList($value)) {
                $result = array_merge($result, self::flattenParamsList($value, $calculatedKey));
            } elseif (is_array($value)) {
                $result = array_merge($result, self::flattenParams($value, $calculatedKey));
            } else {
                array_push($result, [$calculatedKey, $value]);
            }
        }

        return $result;
    }

    /**
     * @param array $value
     * @param string $calculatedKey
     *
     * @return array
     */
    public static function flattenParamsList($value, $calculatedKey)
    {
        $result = [];

        foreach ($value as $i => $elem) {
            if (self::isList($elem)) {
                $result = array_merge($result, self::flattenParamsList($elem, $calculatedKey));
            } elseif (is_array($elem)) {
                $result = array_merge($result, self::flattenParams($elem, "{$calculatedKey}[{$i}]"));
            } else {
                array_push($result, ["{$calculatedKey}[{$i}]", $elem]);
            }
        }

        return $result;
    }

    /**
     * @param string $key A string to URL-encode.
     *
     * @return string The URL-encoded string.
     */
    public static function urlEncode($key)
    {
        $s = urlencode($key);

        // Don't use strict form encoding by changing the square bracket control
        // characters back to their literals. This is fine by the server, and
        // makes these parameter strings easier to read.
        $s = str_replace('%5B', '[', $s);
        $s = str_replace('%5D', ']', $s);

        return $s;
    }

    public static function normalizeId($id)
    {
        if (is_array($id)) {
            $params = $id;
            $id = $params['id'];
            unset($params['id']);
        } else {
            $params = [];
        }
        return [$id, $params];
    }

    /**
     * Returns UNIX timestamp in milliseconds
     *
     * @return integer current time in millis
     */
    public static function currentTimeMillis()
    {
        return (int) round(microtime(true) * 1000);
    }
}

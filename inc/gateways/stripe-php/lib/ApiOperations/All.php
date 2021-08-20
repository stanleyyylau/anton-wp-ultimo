<?php

namespace WU_Stripe\ApiOperations;

/**
 * Trait for listable resources. Adds a `all()` static method to the class.
 *
 * This trait should only be applied to classes that derive from StripeObject.
 */
trait All
{
    /**
     * @param array|null $params
     * @param array|string|null $opts
     *
     * @throws \WU_Stripe\Exception\ApiErrorException if the request fails
     *
     * @return \WU_Stripe\Collection of ApiResources
     */
    public static function all($params = null, $opts = null)
    {
        self::_validateParams($params);
        $url = static::classUrl();

        list($response, $opts) = static::_staticRequest('get', $url, $params, $opts);
        $obj = \WU_Stripe\Util\Util::convertToStripeObject($response->json, $opts);
        if (!($obj instanceof \WU_Stripe\Collection)) {
            throw new \WU_Stripe\Exception\UnexpectedValueException(
                'Expected type ' . \WU_Stripe\Collection::class . ', got "' . get_class($obj) . '" instead.'
            );
        }
        $obj->setLastResponse($response);
        $obj->setFilters($params);
        return $obj;
    }
}

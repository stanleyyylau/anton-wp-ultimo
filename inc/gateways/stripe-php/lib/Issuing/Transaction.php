<?php

namespace WU_Stripe\Issuing;

/**
 * Class Transaction
 *
 * @property string $id
 * @property string $object
 * @property int $amount
 * @property string $authorization
 * @property string $balance_transaction
 * @property string $card
 * @property string $cardholder
 * @property int $created
 * @property string $currency
 * @property string $dispute
 * @property bool $livemode
 * @property mixed $merchant_data
 * @property int $merchant_amount
 * @property string $merchant_currency
 * @property \WU_Stripe\StripeObject $metadata
 * @property string $type
 *
 * @package WU_Stripe\Issuing
 */
class Transaction extends \WU_Stripe\ApiResource
{
    const OBJECT_NAME = "issuing.transaction";

    use \WU_Stripe\ApiOperations\All;
    use \WU_Stripe\ApiOperations\Create;
    use \WU_Stripe\ApiOperations\Retrieve;
    use \WU_Stripe\ApiOperations\Update;
}

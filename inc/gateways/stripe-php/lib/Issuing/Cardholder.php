<?php

namespace WU_Stripe\Issuing;

/**
 * Class Cardholder
 *
 * @property string $id
 * @property string $object
 * @property mixed $billing
 * @property int $created
 * @property string $email
 * @property bool $livemode
 * @property \WU_Stripe\StripeObject $metadata
 * @property string $name
 * @property string $phone_number
 * @property string $status
 * @property string $type
 *
 * @package WU_Stripe\Issuing
 */
class Cardholder extends \WU_Stripe\ApiResource
{
    const OBJECT_NAME = "issuing.cardholder";

    use \WU_Stripe\ApiOperations\All;
    use \WU_Stripe\ApiOperations\Create;
    use \WU_Stripe\ApiOperations\Retrieve;
    use \WU_Stripe\ApiOperations\Update;
}

<?php

namespace WU_Stripe\Issuing;

/**
 * Class CardDetails
 *
 * @property string $id
 * @property string $object
 * @property Card $card
 * @property string $cvc
 * @property int $exp_month
 * @property int $exp_year
 * @property string $number
 *
 * @package WU_Stripe\Issuing
 */
class CardDetails extends \WU_Stripe\ApiResource
{
    const OBJECT_NAME = "issuing.card_details";
}

<?php

namespace WU_Stripe\Radar;

/**
 * Class ValueListItem
 *
 * @property string $id
 * @property string $object
 * @property int $created
 * @property string $created_by
 * @property string $list
 * @property bool $livemode
 * @property string $value
 *
 * @package WU_Stripe\Radar
 */
class ValueListItem extends \WU_Stripe\ApiResource
{
    const OBJECT_NAME = "radar.value_list_item";

    use \WU_Stripe\ApiOperations\All;
    use \WU_Stripe\ApiOperations\Create;
    use \WU_Stripe\ApiOperations\Delete;
    use \WU_Stripe\ApiOperations\Retrieve;
}

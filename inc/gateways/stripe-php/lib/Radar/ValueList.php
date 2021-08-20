<?php

namespace WU_Stripe\Radar;

/**
 * Class ValueList
 *
 * @property string $id
 * @property string $object
 * @property string $alias
 * @property int $created
 * @property string $created_by
 * @property string $item_type
 * @property Collection $list_items
 * @property bool $livemode
 * @property StripeObject $metadata
 * @property mixed $name
 * @property int $updated
 * @property string $updated_by
 *
 * @package WU_Stripe\Radar
 */
class ValueList extends \WU_Stripe\ApiResource
{
    const OBJECT_NAME = "radar.value_list";

    use \WU_Stripe\ApiOperations\All;
    use \WU_Stripe\ApiOperations\Create;
    use \WU_Stripe\ApiOperations\Delete;
    use \WU_Stripe\ApiOperations\Retrieve;
    use \WU_Stripe\ApiOperations\Update;
}

<?php

namespace WU_Stripe\Terminal;

/**
 * Class Location
 *
 * @property string $id
 * @property string $object
 * @property mixed $address
 * @property bool $deleted
 * @property string $display_name
 *
 * @package WU_Stripe\Terminal
 */
class Location extends \WU_Stripe\ApiResource
{
    const OBJECT_NAME = "terminal.location";

    use \WU_Stripe\ApiOperations\All;
    use \WU_Stripe\ApiOperations\Create;
    use \WU_Stripe\ApiOperations\Delete;
    use \WU_Stripe\ApiOperations\Retrieve;
    use \WU_Stripe\ApiOperations\Update;
}

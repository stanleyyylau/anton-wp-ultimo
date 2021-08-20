<?php

namespace WU_Stripe\Terminal;

/**
 * Class Reader
 *
 * @property string $id
 * @property string $object
 * @property bool $deleted
 * @property string $device_sw_version
 * @property string $device_type
 * @property string $ip_address
 * @property string $label
 * @property string $location
 * @property string $serial_number
 * @property string $status
 *
 * @package WU_Stripe\Terminal
 */
class Reader extends \WU_Stripe\ApiResource
{
    const OBJECT_NAME = "terminal.reader";

    use \WU_Stripe\ApiOperations\All;
    use \WU_Stripe\ApiOperations\Create;
    use \WU_Stripe\ApiOperations\Delete;
    use \WU_Stripe\ApiOperations\Retrieve;
    use \WU_Stripe\ApiOperations\Update;
}

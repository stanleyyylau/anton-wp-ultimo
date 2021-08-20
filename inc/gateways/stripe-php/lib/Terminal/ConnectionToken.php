<?php

namespace WU_Stripe\Terminal;

/**
 * Class ConnectionToken
 *
 * @property string $secret
 *
 * @package WU_Stripe\Terminal
 */
class ConnectionToken extends \WU_Stripe\ApiResource
{
    const OBJECT_NAME = "terminal.connection_token";

    use \WU_Stripe\ApiOperations\Create;
}

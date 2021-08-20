<?php

namespace WU_Stripe\Exception\OAuth;

/**
 * InvalidRequestException is thrown when a code, refresh token, or grant
 * type parameter is not provided, but was required.
 *
 * @package WU_Stripe\Exception\OAuth
 */
class InvalidRequestException extends OAuthErrorException
{
}

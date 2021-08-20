<?php

namespace WU_Stripe\Exception;

/**
 * AuthenticationException is thrown when invalid credentials are used to
 * connect to Stripe's servers.
 *
 * @package WU_Stripe\Exception
 */
class AuthenticationException extends ApiErrorException
{
}

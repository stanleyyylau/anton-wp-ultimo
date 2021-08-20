<?php

namespace WU_Stripe\Exception;

/**
 * IdempotencyException is thrown in cases where an idempotency key was used
 * improperly.
 *
 * @package WU_Stripe\Exception
 */
class IdempotencyException extends ApiErrorException
{
}

<?php

namespace WU_Stripe\Exception;

/**
 * UnknownApiErrorException is thrown when the client library receives an
 * error from the API it doesn't know about. Receiving this error usually
 * means that your client library is outdated and should be upgraded.
 *
 * @package WU_Stripe\Exception
 */
class UnknownApiErrorException extends ApiErrorException
{
}

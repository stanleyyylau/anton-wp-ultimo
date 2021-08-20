<?php

namespace WU_Stripe\Exception;

// TODO: remove this check once we drop support for PHP 5
if (interface_exists(\Throwable::class)) {
    /**
     * The base interface for all Stripe exceptions.
     *
     * @package WU_Stripe\Exception
     */
    interface ExceptionInterface extends \Throwable
    {
    }
} else {
    /**
     * The base interface for all Stripe exceptions.
     *
     * @package WU_Stripe\Exception
     */
    // phpcs:disable PSR1.Classes.ClassDeclaration.MultipleClasses
    interface ExceptionInterface
    {
    }
    // phpcs:enable
}

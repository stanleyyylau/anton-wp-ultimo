<?php

/**
 * Define our SUNRISE version
 */
define('WPULTIMO_SUNRISE_VERSION', '1.0.0');

/**
 * Include mercator
 */

$mercator = defined('WP_PLUGIN_DIR')
? WP_PLUGIN_DIR . '/wp-ultimo/inc/mercator/mercator.php'
: WP_CONTENT_DIR . '/plugins/wp-ultimo/inc/mercator/mercator.php';

if (file_exists($mercator)) {

	require $mercator;

} // end if;

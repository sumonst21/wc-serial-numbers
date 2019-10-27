<?php
// don't call the file directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @since 1.0.0
 * @param array $args
 * @param bool $count
 *
 * @return array|object|null
 */
function wcsn_get_serial_numbers($args = array(), $count = false){
	_deprecated_function( __FUNCTION__, '1.1.0', 'SerialNumbers::query()' );

	return Serial_Number::query($args = array(), $count = false);
}

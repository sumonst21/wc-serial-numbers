<?php
// don't call the file directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * add serial number checkbox
 * since 1.0.0
 *
 * @param $options
 *
 * @return mixed
 */
function wc_sn_product_type_options( $options ) {
	$options['is_serial_number'] = array(
		'id'            => '_is_serial_number',
		'wrapper_class' => 'show_if_simple',
		'label'         => __( 'Serial Number', 'wc-serial-numbers' ),
		'description'   => __( 'Enable this option if you want to enable license numbers', 'wc-serial-numbers' )
	);

	return $options;
}

add_filter( 'product_type_options', 'wc_sn_product_type_options' );

/**
 * Save product meta
 * @return void
 * @since 1.0.0
 */
function wc_sn_product_save_data() {
	global $post;

	if ( ! empty( $_POST['_is_serial_number'] ) ) {
		update_post_meta( $post->ID, '_is_serial_number', 'yes' );
	} else {
		update_post_meta( $post->ID, '_is_serial_number', 'no' );
	}

	$source           = ! empty( $_POST['_serial_key_source'] ) ? sanitize_key( $_POST['_serial_key_source'] ) : 'custom_source';
	$prefix           = ! empty( $_POST['_serial_number_key_prefix'] ) ? sanitize_key( $_POST['_serial_number_key_prefix'] ) : '';
	$activation_limit = ! empty( $_POST['_activation_limit'] ) ? intval( $_POST['_activation_limit'] ) : '0';
	$validity         = ! empty( $_POST['_validity'] ) ? sanitize_key( $_POST['_validity'] ) : '0';
	$software_version = ! empty( $_POST['_software_version'] ) ? sanitize_key( $_POST['_software_version'] ) : '0';


	update_post_meta( $post->ID, '_serial_key_source', $source );
	update_post_meta( $post->ID, '_serial_number_key_prefix', $prefix );
	update_post_meta( $post->ID, '_activation_limit', $activation_limit );
	update_post_meta( $post->ID, '_validity', $validity );
	update_post_meta( $post->ID, '_software_version', $software_version );
	//todo change hook name
	do_action( 'wcsn_save_simple_product_meta', $post );
}

add_filter( 'woocommerce_process_product_meta', 'wc_sn_product_save_data' );

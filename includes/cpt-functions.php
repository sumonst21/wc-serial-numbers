<?php
// don't call the file directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
function serial_number_register_cpt() {
	$args = array(
		'labels'             => __( 'Serial Numbers', 'wc-serial-numbers' ),
		'public'             => false,
		'publicly_queryable' => false,
		'show_ui'            => false,
		'show_in_menu'       => false,
		'query_var'          => false,
		'capability_type'    => 'post',
		'has_archive'        => false,
		'hierarchical'       => false,
		'menu_position'      => null,
		'supports'           => array(),
	);

	register_post_type( 'sn_serial', $args );

	$args = array(
		'labels'             => __( 'Serial Number Activations', 'wc-serial-numbers' ),
		'public'             => false,
		'publicly_queryable' => false,
		'show_ui'            => false,
		'show_in_menu'       => false,
		'query_var'          => false,
		'capability_type'    => 'post',
		'has_archive'        => false,
		'hierarchical'       => false,
		'menu_position'      => null,
		'supports'           => array(),
	);

	register_post_type( 'sn_activation', $args );


	register_post_status( 'new', array(
		'label'                     => _x( 'New', 'post' ),
		'public'                    => false,
		'exclude_from_search'       => false,
		'show_in_admin_all_list'    => true,
		'show_in_admin_status_list' => false,
		'post_type'                 => array( 'sn_serial' )
	) );

	register_post_status( 'pending', array(
		'label'                     => _x( 'Pending', 'post' ),
		'public'                    => false,
		'exclude_from_search'       => false,
		'show_in_admin_all_list'    => true,
		'show_in_admin_status_list' => false,
		'post_type'                 => array( 'sn_serial' )
	) );

	register_post_status( 'expired', array(
		'label'                     => _x( 'Pending', 'post' ),
		'public'                    => false,
		'exclude_from_search'       => false,
		'show_in_admin_all_list'    => true,
		'show_in_admin_status_list' => false,
		'post_type'                 => array( 'sn_serial' )
	) );

	register_post_status( 'new', array(
		'label'                     => _x( 'New', 'post' ),
		'public'                    => false,
		'exclude_from_search'       => false,
		'show_in_admin_all_list'    => true,
		'show_in_admin_status_list' => false,
		'post_type'                 => array( 'sn_activation' )
	) );

	register_post_status( 'activated', array(
		'label'                     => _x( 'Activated', 'post' ),
		'public'                    => false,
		'exclude_from_search'       => false,
		'show_in_admin_all_list'    => true,
		'show_in_admin_status_list' => false,
		'post_type'                 => array( 'sn_activation' )
	) );

	register_post_status( 'expired', array(
		'label'                     => _x( 'Expired', 'post' ),
		'public'                    => false,
		'exclude_from_search'       => false,
		'show_in_admin_all_list'    => true,
		'show_in_admin_status_list' => false,
		'post_type'                 => array( 'sn_activation' )
	) );

}

add_action( 'init', 'serial_number_register_cpt' );

/**
 * Get serial numbers
 *
 * @param $id
 *
 * @return array|null
 * @since 1.1.1
 */
function sn_get_serial_number( $id ) {
	global $wpdb;
	$serial = $wpdb->get_row( $wpdb->prepare( "select id, post_password serial, post_status status, post_date created,  m.* from wp_posts p
			left outer join(
			  select post_id ,
			     max(case when meta_key = '_product_id' then meta_value else null end) as product_id,
			     max(case when meta_key = '_variation_id' then meta_value else null end) as variation_id,
			     max(case when meta_key = '_activation_limit' then meta_value else null end) as activation_limit,
			     max(case when meta_key = '_activation_email' then meta_value else null end) as activation_email,
			     max(case when meta_key = '_validity' then meta_value else null end) as validity, 
			     max(case when meta_key = '_expire_date' then meta_value else null end) as expire_date,
			     max(case when meta_key = '_order_date' then meta_value else null end) as order_date 
			     from wp_postmeta pm group by 1
			) m on m.post_id = p.id where post_type='sn_serial' AND p.id=%d", $id ) );

	if ( isset( $serial->post_id ) ) {
		unset( $serial->post_id );
	}

	return apply_filters( 'serial_numbers_get_serial_number', $serial );
}

function sn_create_serial_number( $args ) {
	$update = false;
	$id     = null;
	$data   = (array) apply_filters( 'serial_numbers_insert_serial', $args );

	if ( isset( $args['id'] ) && ! empty( trim( $args['id'] ) ) ) {
		$id             = (int) $args['id'];
		$update         = true;
		$contact_before = (array) sn_get_serial_number( $id );
		if ( is_null( $contact_before ) ) {
			return false;
		}

		$data = array_merge( $contact_before, $data );
	}

	$serial_number    = isset( $data['serial'] ) ? sanitize_text_field( $data['serial'] ) : null;
	$status           = ! empty( $data['status'] ) ? sanitize_key( $data['status'] ) : 'new';
	$order_id         = isset( $data['order_id'] ) ? absint( $data['order_id'] ) : null;
	$product_id       = isset( $data['product_id'] ) ? absint( $data['product_id'] ) : null;
	$variation_id     = isset( $data['variation_id'] ) ? absint( $data['variation_id'] ) : null;
	$activation_limit = ! empty( $data['activation_limit'] ) ? absint( $data['activation_limit'] ) : 1;
	$activation_email = isset( $data['activation_email'] ) ? sanitize_email( $data['activation_email'] ) : null;
	$validity         = ! empty( $data['validity'] ) ? absint( $data['validity'] ) : '365';
	$expire_date      = isset( $data['expire_date'] ) ? sanitize_text_field( $data['expire_date'] ) : '0000-00-00 00:00:00';
	$order_date       = isset( $data['order_date'] ) ? sanitize_text_field( $data['order_date'] ) : '0000-00-00 00:00:00';
	$date_created     = ! empty( $data['date_created'] ) ? sanitize_text_field( $data['date_created'] ) : date( 'Y-m-d H:i:s' );

	if ( $order_date !== '0000-00-00 00:00:00' && $expire_date === '0000-00-00 00:00:00' && ! empty( $validity ) ) {
		$expire_date = date( 'Y-m-d H:i:s', strtotime( sprintf( "+%d days ", $validity ) . $order_date ) );
	}


	$post_id = wp_insert_post( array(
		'ID'            => $id,
		'post_type'     => 'sn_serial',
		'post_password' => $serial_number,
		'post_status'   => $status,
		'post_parent'   => $order_id,
		'post_date'     => $date_created,
		'meta_input'    => array(
			'_product_id'       => $product_id,
			'_variation_id'     => $variation_id,
			'_activation_limit' => $activation_limit,
			'_activation_email' => $activation_email,
			'_validity'         => $validity,
			'_expire_date'      => $expire_date,
			'_order_date'       => $order_date,
		)
	) );

	if ( is_wp_error( $post_id ) ) {
		return false;
	}

	return sn_get_serial_number( $post_id );
}

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

	register_post_status( 'active', array(
		'label'                     => _x( 'Active', 'post' ),
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

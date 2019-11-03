<?php
// don't call the file directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Serial_Number {
	/**
	 * @var array
	 */
	private $props = array();

	/**
	 * Serial_Number constructor.
	 *
	 * @param $serial_number int|object|array
	 */
	public function __construct( $serial_number ) {
		$default = array(
			'id'               => null,
			'serial_key'       => '',
			'status'           => 'new',
			'image_url'        => '',
			'product_id'       => '',
			'variation_id'     => '',
			'order_id'         => '',
			'customer_id'      => '',
			'activation_limit' => '',
			'activation_email' => '',
			'validity'         => '',
			'expire_date'      => '',
			'order_date'       => '',
			'created'          => date( 'Y-m-d H:i:s' ),
		);

		if ( is_object( $serial_number ) ) {
			$this->props = array_merge( $default, get_object_vars( $serial_number ) );
		} elseif ( is_array( $serial_number ) ) {
			$this->props = array_merge( $default, $serial_number );
		} elseif ( is_numeric( $serial_number ) ) {
			$serial      = $this->get( $serial_number );
			$this->props = is_object( $serial ) ? array_merge( $default, (array) $serial ) : $default;
		} else {
			$this->props = $default;
		}

	}

	/**
	 * Set prop
	 *
	 * @param $key
	 * @param $value
	 *
	 * @since 1.1.0
	 */
	public function set_prop( $key, $value ) {
		if ( array_key_exists( $key, $this->props ) ) {
			$this->props[ $key ] = $value;
		}
	}


	/**
	 * @param $key
	 * @param $value
	 *
	 * @return null|string|int
	 * @since 1.1.0
	 */
	public function get_prop( $key, $value ) {
		if ( array_key_exists( $key, $this->props ) ) {
			$this->props[ $key ] = $value;
		}

		return null;
	}

	public function get_product() {

	}


	public function get_order() {

	}


	public function get_activation_limit() {

	}

	public function get_status() {

	}

	public function get_validity() {

	}

	public function is_active() {

	}


	/**
	 * Update change
	 *
	 * @return WP_Error|boolean
	 * @since 1.1.0
	 */
	public function save() {
		return self::create( $this->props );
	}

	/**
	 * @param $order_id
	 *
	 * @return WP_Error|boolean
	 * @since 1.1.0
	 */
	public function assign_order( $order_id ) {
		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			return new WP_Error( 'invalid-order-id', __( 'Invalid order id', 'wc-serial-numbers' ) );
		}

		$this->set_prop( 'order_id', $order->get_id() );
		$this->set_prop( 'activation_email', $order->get_billing_email( 'edit' ) );
		$this->set_prop( 'status', $order->get_status( 'edit' ) == 'completed' ? 'active' : 'pending' );
		$this->set_prop( 'order_date', current_time( 'mysql' ) );

		return $this->save();
	}

	/**
	 * @param bool $reuse
	 *
	 * @return bool|WP_Error
	 * @since 1.1.0
	 */
	public function revoke( $reuse = true ) {
		if ( $reuse ) {
			$this->set_prop( 'status', 'rejected' );
		} else {
			$this->set_prop( 'order_id', '' );
			$this->set_prop( 'activation_email', '' );
			$this->set_prop( 'status', 'new' );
			$this->set_prop( 'order_date', '' );
		}

		return $this->save();
	}

	/**
	 * Create serial number
	 *
	 * @param $args
	 *
	 * @return object|WP_Error
	 * @since 1.1.0
	 */
	public static function create( $args ) {
		global $wpdb;
		$table  = $wpdb->prefix . 'wcsn_serial_numbers';
		$update = false;
		$id     = null;
		$data   = (array) apply_filters( 'serial_numbers_insert_serial', $args );
		error_log( print_r( $data, true ) );
		if ( isset( $args['id'] ) && ! empty( trim( $args['id'] ) ) ) {
			$id             = (int) $args['id'];
			$update         = true;
			$contact_before = (array) self::get( $id );
			if ( is_null( $contact_before ) ) {
				return new WP_Error( 'invalid_action', __( 'Could not find the serial number to update', 'wc-serial-numbers' ) );
			}

			$data = array_merge( $contact_before, $data );
		}

		$serial_key       = isset( $data['serial_key'] ) ? sanitize_text_field( $data['serial_key'] ) : null;
		$status           = ! empty( $data['status'] ) ? sanitize_key( $data['status'] ) : 'new';
		$order_id         = isset( $data['order_id'] ) ? absint( $data['order_id'] ) : null;
		$product_id       = isset( $data['product_id'] ) ? absint( $data['product_id'] ) : null;
		$variation_id     = isset( $data['variation_id'] ) ? absint( $data['variation_id'] ) : null;
		$activation_limit = ! empty( $data['activation_limit'] ) ? absint( $data['activation_limit'] ) : null;
		$activation_email = isset( $data['activation_email'] ) ? sanitize_email( $data['activation_email'] ) : null;
		$validity         = ! empty( $data['validity'] ) ? absint( $data['validity'] ) : null;
		$expire_date      = isset( $data['expire_date'] ) ? sanitize_text_field( $data['expire_date'] ) : null;
		$order_date       = isset( $data['order_date'] ) ? sanitize_text_field( $data['order_date'] ) : null;
		$date_created     = ! empty( $data['date_created'] ) ? sanitize_text_field( $data['date_created'] ) : date( 'Y-m-d H:i:s' );

		if ( ! $order_date && ! $expire_date && ! empty( $validity ) ) {
			$expire_date = date( 'Y-m-d H:i:s', strtotime( sprintf( "+%d days ", $validity ) . $order_date ) );
		}

		if ( empty( $serial_key ) ) {
			return new WP_Error( 'missing_serial_number', __( 'Serial number is not defined', 'wc-serial-numbers' ) );
		}

		if ( empty( $product_id ) ) {
			return new WP_Error( 'missing_product_id', __( 'Product id is missing', 'wc-serial-numbers' ) );
		}

		$fields     = array(
			'serial_key',
			'serial_image',
			'product_id',
			'activation_limit',
			'order_id',
			'activation_email',
			'status',
			'validity',
			'expire_date',
			'order_date',
			'created'
		);
		$serial_arr = compact( $fields );
		$serial_arr = wp_unslash( $serial_arr );
		$where      = array( 'id' => $id );
		error_log( print_r( $serial_arr, true ) );
		if ( $update ) {
			if ( false === $wpdb->update( $table, $serial_arr, $where ) ) {
				return new WP_Error( 'db_update_error', __( 'Could not update serial number in the database', 'wc-serial-numbers' ), $wpdb->last_error );
			}
		} else {
			if ( false === $wpdb->insert( $table, $serial_arr ) ) {
				return new WP_Error( 'db_insert_error', __( 'Could not insert serial number the database', 'wc-serial-numbers' ), $wpdb->last_error );
			}
			$id = (int) $wpdb->insert_id;
		}

		return (object) self::get( $id );
	}

	/**
	 * Get serial numbers
	 *
	 * @param $id
	 *
	 * @return array|null
	 * @since 1.1.1
	 */
	public static function get( $id ) {
		global $wpdb;
		$serial = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}wcsn_serial_numbers WHERE id=%d", $id ) );

		return apply_filters( 'serial_numbers_get_serial_number', $serial );
	}


	/**
	 * @param $args
	 * @param bool $count
	 *
	 * @return array|object|null
	 * @since 1.1.0
	 */
	public static function query( $args, $count = false ) {
		global $wpdb;
		$table         = $wpdb->prefix . 'wcsn_serial_numbers';
		$query_where   = '';
		$query_orderby = '';
		$query_limit   = '';


		$default = array(
			'include'          => array(),
			'exclude'          => array(),
			'status'           => 'all',
			'order_id'         => array(),
			'product_id'       => array(),
			'activation_limit' => '',
			'orderby'          => 'id',
			'order'            => 'DESC',
			'per_page'         => 20,
			'page'             => 1,
			'offset'           => 0,
		);
		//sn_serial
		$args = wp_parse_args( $args, $default );

		$query_where = " WHERE 1=1 ";

		//include
		if ( ! empty( $args['include'] ) ) {
			$include     = implode( ',', wp_parse_id_list( $args['include'] ) );
			$query_where .= " AND id IN ( $include ) ";
		}

		//exclude
		if ( ! empty( $args['exclude'] ) ) {
			$exclude     = implode( ',', wp_parse_id_list( $args['exclude'] ) );
			$query_where .= " AND id NOT IN ( $exclude ) ";
		}

		//product_id
		if ( ! empty( $args['product_id'] ) ) {
			$product_id  = implode( ',', wp_parse_id_list( $args['product_id'] ) );
			$query_where .= " AND product_id IN ( $product_id ) ";
		}

		//order_id
		if ( ! empty( $args['order_id'] ) ) {
			$order_id    = implode( ',', wp_parse_id_list( $args['order_id'] ) );
			$query_where .= " AND order_id IN ( $order_id ) ";
		}

		//activation_limit
		if ( ! empty( $args['activation_limit'] ) ) {
			$activation_limit = implode( ',', wp_parse_id_list( $args['activation_limit'] ) );
			$query_where      .= " AND activation_limit IN ( $activation_limit ) ";
		}

		//status
		if ( ! empty( $args['status'] ) && 'all' !== $args['status'] ) {
			$query_where .= $wpdb->prepare( " AND status=%s", sanitize_key( $args['status'] ) );
		}

		//ordering
		$order         = isset( $args['order'] ) ? esc_sql( strtoupper( $args['order'] ) ) : 'ASC';
		$order_by      = esc_sql( $args['orderby'] );
		$query_orderby = sprintf( " ORDER BY %s %s ", $order_by, $order );

		// limit
		if ( isset( $args['per_page'] ) && $args['per_page'] > 0 ) {
			if ( $args['offset'] ) {
				$query_limit = $wpdb->prepare( 'LIMIT %d, %d', $args['offset'], $args['per_page'] );
			} else {
				$query_limit = $wpdb->prepare( 'LIMIT %d, %d', $args['per_page'] * ( $args['page'] - 1 ), $args['per_page'] );
			}
		}


		if ( $count ) {
			return $wpdb->get_var( "SELECT count(id) from {$table} $query_where $query_orderby $query_limit" );
		}
		error_log("SELECT * from {$table} $query_where $query_orderby $query_limit");
		$items  = $wpdb->get_results( "SELECT * from {$table} $query_where $query_orderby $query_limit" );
		$result = [];
		foreach ( $items as $item ) {
			$result[] = new Serial_Number( $item );
		}

		return $result;
	}


}

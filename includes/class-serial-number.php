<?php
// don't call the file directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Serial_Number{
	/**
	 * @var array
	 */
	protected $props = array();

	/**
	 * Serial_Number constructor.
	 *
	 * @param $serial_number int|object|array
	 */
	public function __construct( $serial_number ) {
		$default = array(
			'id'               => null,
			'serial'           => '',
			'status'           => 'new',
			'image_url'        => '',
			'product_id'       => '',
			'variation_id'     => '',
			'order_id'         => '',
			'customer_id'      => '',
			'activation_limit' => '1',
			'activation_email' => '',
			'validity'         => '0',
			'expire_date'      => '0000-00-00 00:00:00',
			'order_date'       => '0000-00-00 00:00:00',
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


	/**
	 * Checks whether a key is encrypted or not
	 *
	 * @param $key
	 *
	 * @return bool
	 * @since 1.1.0
	 */
	protected function is_encrypted( $key ) {
		if ( preg_match( '/^(?:[A-Za-z0-9+\/]{4})*(?:[A-Za-z0-9+\/]{2}==|[A-Za-z0-9+\/]{3}=|[A-Za-z0-9+\/]{4})$/', $key ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Encrypt key
	 *
	 * @param $key
	 *
	 * @return string
	 * @since 1.1.0
	 */
	public function encrypt( $key ) {
		$p_key = wcsn_get_encrypt_key();
		$hash  = wc_serial_numbers()->encryption->encrypt( $key, $p_key, 'kcv4tu0FSCB9oJyH' );

		return $hash;
	}

	/**
	 * Decrypt key
	 *
	 * @param $key
	 *
	 * @return string
	 * @since 1.1.0
	 */
	public function decrypt( $key ) {
		$p_key  = wcsn_get_encrypt_key();
		$string = wc_serial_numbers()->encryption->decrypt( $key, $p_key, 'kcv4tu0FSCB9oJyH' );

		return $string;
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
		$update = false;
		$id     = null;
		$data   = (array) apply_filters( 'serial_numbers_insert_serial', $args );

		if ( isset( $args['id'] ) && ! empty( trim( $args['id'] ) ) ) {
			$id             = (int) $args['id'];
			$update         = true;
			$contact_before = (array) self::get($id);
			if ( is_null( $contact_before ) ) {
				return new WP_Error( 'invalid_action', __( 'Could not find the serial number to update', 'wc-serial-numbers' ) );
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

		if ( empty( $props['serial'] ) ) {
			return new WP_Error( 'missing_serial_number', __( 'Serial number is not set', 'wc-serial-numbers' ) );
		}

		if ( empty( $props['product_id'] ) ) {
			return new WP_Error( 'missing_product_id', __( 'Product id is missing', 'wc-serial-numbers' ) );
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
			return $post_id;
		}



		return (object) self::get( $post_id );
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


	/**
	 * @param $args
	 * @param bool $count
	 *
	 * @return array|object|null
	 * @since 1.1.0
	 */
	public static function query( $args, $count = false ) {
		global $wpdb;
		$query_where   = '';
		$query_orderby = '';
		$query_limit   = '';


		$default = array(
			'include'      => array(),
			'exclude'      => array(),
			'status'       => 'all',
			'order_id'     => array(),
			'product_id'   => array(),
			'variation_id' => array(),
			'orderby'      => 'id',
			'order'        => 'DESC',
			'per_page'     => 20,
			'page'         => 1,
			'offset'       => 0,
		);
		//sn_serial
		$args = wp_parse_args( $args, $default );

		$query_where = " WHERE 1=1  AND post_type='sn_serial' ";

		//include
		if ( ! empty( $args['include'] ) ) {
			$include     = implode( ',', wp_parse_id_list( $args['include'] ) );
			$query_where .= " AND p.id IN ( $include ) ";
		}

		//exclude
		if ( ! empty( $args['exclude'] ) ) {
			$exclude     = implode( ',', wp_parse_id_list( $args['exclude'] ) );
			$query_where .= " AND p.id NOT IN ( $exclude ) ";
		}

		//product_id
		if ( ! empty( $args['product_id'] ) ) {
			$product_id  = implode( ',', wp_parse_id_list( $args['product_id'] ) );
			$query_where .= " AND m.product_id NOT IN ( $product_id ) ";
		}

		//variation_id
		if ( ! empty( $args['variation_id'] ) ) {
			$variation_id = implode( ',', wp_parse_id_list( $args['variation_id'] ) );
			$query_where  .= " AND m.variation_id NOT IN ( $variation_id ) ";
		}

		//order_id
		if ( ! empty( $args['order_id'] ) ) {
			$order_id    = implode( ',', wp_parse_id_list( $args['order_id'] ) );
			$query_where .= " AND m.order_id NOT IN ( $order_id ) ";
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

		$cache_key     = md5( serialize( $args ) );
		$query_results = wp_cache_get( $cache_key );
		$results       = array();
		if ( false === $query_results ) {
			$request = "select id, post_password serial, post_status status, post_date created,  m.* from wp_posts p
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
			) m on m.post_id = p.id $query_where $query_orderby $query_limit";

			$results = $wpdb->get_results( $request );
		}

		return $results;

	}


}

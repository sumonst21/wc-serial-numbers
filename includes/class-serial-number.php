<?php
// don't call the file directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Serial_Number {
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
			'serial_key'       => '',
			'serial_image'     => '',
			'product_id'       => '',
			'activation_limit' => '1',
			'order_id'         => '',
			'activation_email' => '',
			'status'           => 'new',
			'validity'         => '365',
			'expire_date'      => '0000-00-00 00:00:00',
			'order_date'       => '0000-00-00 00:00:00',
			'created'          => date( 'Y-m-d H:i:s' ),
		);

		if ( is_object( $serial_number ) ) {
			$this->props = array_merge( $default, get_object_vars( $serial_number ) );
		} elseif ( is_array( $serial_number ) ) {
			$this->props = array_merge( $default, $serial_number );
		} elseif ( is_numeric( $serial_number ) ) {
			$serial      = $this->pull( $serial_number );
			$this->props = $serial ? array_merge( $default, $serial ) : $default;
		} else {
			$this->props = $default;
		}

	}

	/**
	 *
	 * @param $id
	 *
	 * @return array
	 * @since 1.0.0
	 */
	protected function pull( $id ) {
		global $wpdb;
		$serial = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}wcsn_serial_numbers WHERE id=%d", absint( $id ) ), ARRAY_A );
		if ( $this->is_encrypted( $serial['serial_key'] ) ) {
			$serial['serial_key'] = $this->decrypt( $serial['serial_key'] );
		}

		return $serial;
	}

	/**
	 * @return string
	 * @since 1.0.0
	 */
	public function get_key() {
		return $this->props['serial_key'];
	}

	/**
	 * @return int
	 * @since 1.0.0
	 */
	public function get_product_id() {
		return $this->props['product_id'];
	}

	/**
	 * @return string
	 * @since 1.0.0
	 */
	public function get_activation_limit() {
		return $this->props['activation_limit'];
	}

	/**
	 * @return string
	 * @since 1.0.0
	 */
	public function get_order_id() {
		return $this->props['order_id'];
	}

	/**
	 * @return string
	 * @since 1.0.0
	 */
	public function get_activation_email() {
		return $this->props['activation_email'];
	}

	/**
	 * @return string
	 * @since 1.0.0
	 */
	public function get_status() {
		return $this->props['status'];
	}

	/**
	 * @return string
	 * @since 1.0.0
	 */
	public function get_validity() {
		return $this->props['validity'];
	}

	/**
	 * @return mixed
	 * @since 1.0.0
	 */
	public function get_expire_date() {
		return $this->props['expire_date'];
	}

	/**
	 * Checks whether a key is encrypted or not
	 *
	 * @param $key
	 *
	 * @return bool
	 * @since 1.0.0
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
	 * @since 1.0.0
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
	 * @since 1.0.0
	 */
	public function decrypt( $key ) {
		$p_key  = wcsn_get_encrypt_key();
		$string = wc_serial_numbers()->encryption->decrypt( $key, $p_key, 'kcv4tu0FSCB9oJyH' );

		return $string;
	}

	/**
	 * Set prop
	 *
	 * @param $key
	 * @param $value
	 *
	 * @since 1.0.0
	 */
	public function set_prop( $key, $value ) {
		if ( array_key_exists( $key, $this->props ) ) {
			if ( 'serial_key' == $key ) {
				$this->set_key( $value );
			}
			$this->props[ $key ] = $value;
		}
	}

	/**
	 * Set key
	 *
	 * @param $key
	 *
	 * @since 1.0.0
	 */
	public function set_key( $key ) {
		$this->props['serial_key'] = $this->encrypt( $key );
	}

	/**
	 * Update change
	 *
	 * @return WP_Error|boolean
	 * @since 1.0.0
	 */
	public function save() {
		global $wpdb;
		$table = "{$wpdb->prefix}wcsn_serial_numbers";
		$props = apply_filters( 'wc_serial_number_insert_key', $this->props );
		$where = array( 'id' => $props['id'] );
		if ( empty( $props['serial_key'] ) ) {
			return new WP_Error( 'missing_serial_number', __( 'Serial number is not set', 'wc-serial-numbers' ) );
		}
		$props['serial_key'] = $this->encrypt( $props['serial_key'] );

		if ( ! empty( $this->props['id'] ) ) {
			$wpdb->update( $table, $props, $where );
			do_action( 'wc_serial_number_update_key', $props );

			return true;
		}

		$wpdb->insert( $table, $props );
		do_action( 'wc_serial_number_insert_key', $props );

		return true;
	}

	/**
	 * @param $order_id
	 *
	 * @return WP_Error|boolean
	 * @since 1.0.0
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
	 * @since 1.0.0
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


	public static function query( $args, $count = false ) {
		global $wpdb;
		$query_where    = '';
		$query_orderby  = '';
		$query_limit    = '';


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
			'page'     => 1,
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
			$product_id     = implode( ',', wp_parse_id_list( $args['product_id'] ) );
			$query_where .= " AND m.product_id NOT IN ( $product_id ) ";
		}

		//variation_id
		if ( ! empty( $args['variation_id'] ) ) {
			$variation_id     = implode( ',', wp_parse_id_list( $args['variation_id'] ) );
			$query_where .= " AND m.variation_id NOT IN ( $variation_id ) ";
		}

		//order_id
		if ( ! empty( $args['order_id'] ) ) {
			$order_id     = implode( ',', wp_parse_id_list( $args['order_id'] ) );
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
		$results = array();
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

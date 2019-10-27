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
			'serial'           => '',
			'status'           => 'new',
			'image_url'        => '',
			'product_id'       => '',
			'variation_id'     => '',
			'order_id'         => '',
			'activation_limit' => '1',
			'activation_email' => '',
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
	 * @since 1.0.0
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
	 * @since 1.0.0
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

	/**
	 * Create serial number
	 * @since 1.0.0
	 * @param $args
	 *
	 * @return array|bool|null
	 */
	public static function create( $args ) {
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

		return self::get( $post_id );
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


	public static function query( $args ) {
		$default = array(
			'include'      => array(),
			'exclude'      => array(),
			'status'       => 'all',
			'order_id'     => '',
			'product_id'   => '',
			'variation_id' => '',
			'orderby'      => 'id',
			'order'        => 'DESC',
			'per_page'     => 20,
			'page'         => 1,
			'offset'       => 0,
		);


	}


}

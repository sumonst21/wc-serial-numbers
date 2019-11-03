<?php
if ( class_exists( 'WP_CLI' ) ) {
	class WC_Serial_Numbers_Commands extends \WP_CLI_Command{

		public function generate_serials($args) {
			list( $amount ) = $args;
			$progress = \WP_CLI\Utils\make_progress_bar( 'Generating Serials', $amount );
			$generated = 0;
			global $wpdb;
			for ( $i = 1; $i <= $amount; $i ++ ) {
				$product_id = $wpdb->get_var("SELECT ID FROM $wpdb->posts where post_type IN ('product', 'product_variation') AND post_status='publish' order by rand()");
				error_log($product_id);
				$created = Serial_Number::create(array(
					'serial_key'       => wp_generate_password('12', false, false),
					'status'           => 'new',
					'image_url'        => '',
					'product_id'       => $product_id,
					'order_id'         => '',
					'customer_id'      => '',
					'activation_limit' => '',
					'activation_email' => '',
					'validity'         => '',
					'expire_date'      => '',
					'order_date'       => '',
				));
				if(is_wp_error($created)){
					\WP_CLI::error($created->get_error_message());
				}
				$progress->tick();
			}
			$progress->finish();
			WP_CLI::success( sprintf("%d serials generated", $generated) );
		}


		public function clear_db(){

		}

	}

	WP_CLI::add_command( 'sn generate serials', array( 'WC_Serial_Numbers_Commands', 'generate_serials') );
	WP_CLI::add_command( 'sn clear db', array( 'WC_Serial_Numbers_Commands', 'clear_db') );
}

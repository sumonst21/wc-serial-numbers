<?php
if ( defined( 'WP_CLI' ) && WP_CLI ) {
	class WC_Serial_Numbers_Commands{

		public function create_serials() {
			WP_CLI::success( 'hello from exposed_function() !' );
		}

	}

	WP_CLI::add_command( 'sn', 'create_serials' );
}

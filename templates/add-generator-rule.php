<?php

if ( ! empty($_REQUEST['generator_rule']) ) {

	$generator_rule_id    = intval( $_REQUEST['generator_rule'] );
	$product              = get_post_meta( $generator_rule_id, 'product', true );
	$variation            = get_post_meta( $generator_rule_id, 'variation', true );
	$prefix               = get_post_meta( $generator_rule_id, 'prefix', true );
	$chunks_number        = get_post_meta( $generator_rule_id, 'chunks_number', true );
	$chunk_length         = get_post_meta( $generator_rule_id, 'chunk_length', true );
	$suffix               = get_post_meta( $generator_rule_id, 'suffix', true );
	$deliver_times        = get_post_meta( $generator_rule_id, 'deliver_times', true );
	$instance             = get_post_meta( $generator_rule_id, 'max_instance', true );
	$validity             = get_post_meta( $generator_rule_id, 'validity', true );
	$validity_type        = get_post_meta( $generator_rule_id, 'validity_type', true );
	$title                = __( 'Edit Generator Rule', 'wc-serial-number-pro' );
	$submit               = __( 'Save changes', 'wc-serial-number-pro' );
	$action_type          = 'edit';
	$generator_rule_input = sprintf( '<input type="hidden" name="generator_rule_id" value="%d">', $generator_rule_id );

} else {

	$generator_rule_id    = '';
	$product              = '';
	$variation            = '';
	$prefix               = wsn_get_settings( 'wsn_generator_prefix', '', 'wsn_serial_generator_settings' );
	$chunks_number        = wsn_get_settings( 'wsn_generator_chunks_number', '', 'wsn_serial_generator_settings' );
	$chunk_length         = wsn_get_settings( 'wsn_generator_chunks_length', '', 'wsn_serial_generator_settings' );
	$suffix               = wsn_get_settings( 'wsn_generator_suffix', '', 'wsn_serial_generator_settings' );
	$deliver_times        = wsn_get_settings( 'wsn_generator_deliver_times', '1', 'wsn_serial_generator_settings' );
	$instance             = wsn_get_settings( 'wsn_generator_instance', '1', 'wsn_serial_generator_settings' );
	$validity             = wsn_get_settings( 'wsn_generator_validity', '', 'wsn_serial_generator_settings' );
	$validity_type        = 'days';
	$title                = __( 'Add New Generator Rule', 'wc-serial-number-pro' );
	$submit               = __( 'Add Generator Rule', 'wc-serial-number-pro' );
	$action_type          = 'add';
	$generator_rule_input = '';

}


?>


<div class="wrap wsn-container">

	<div class="ever-form-group">

		<h1 class="wp-heading-inline"><?php echo $title ?></h1>

		<a href="<?php echo add_query_arg( 'type', 'manual', WPWSN_ADD_SERIAL_PAGE ); ?>" class="wsn-button add-serial-title page-title-action"><?php _e( 'Add serial key manually', 'wc-serial-number-pro' ) ?></a>

		<a href="<?php echo add_query_arg( 'type', 'automate', WPWSN_ADD_SERIAL_PAGE ); ?>" class="wsn-button page-title-action <?php echo wsn_class_disabled() ?>" <?php echo wsn_disabled() ?>><?php _e( 'Generate serial key Automatically', 'wc-serial-number-pro' ) ?></a>

	</div>

	<div class="ever-panel">

		<form action="<?php echo admin_url( 'admin-post.php' ) ?>" method="post">

			<?php wp_nonce_field( 'wsn_add_edit_generator_rule', '_nonce' ) ?>

			<input type="hidden" name="action" value="wsn_add_edit_generator_rule">
			<input type="hidden" name="action_type" value="<?php echo $action_type ?>">

			<?php echo $generator_rule_input ?>

			<table class="form-table">
				<tbody>

				<?php if ( ! isset( $is_serial_number_enabled ) ) { ?>

					<tr>
						<th scope="row">
							<label for="product"><?php _e( 'Choose Product', 'wc-serial-number-pro' ) ?></label>
						</th>

						<td>
							<select name="product" id="product" class="ever-select  ever-field-inline" required>

								<option value=""><?php _e( 'Choose a product', 'wc-serial-number-pro' ) ?></option>

								<?php
								$posts = wsn_get_products();

								foreach ( $posts as $post ) {

									setup_postdata( $post );
									$selected = $post->get_id() == $product ? 'selected' : '';
									echo sprintf( '<option value="%d" %s >%1$d - %s </option>', $post->get_id(), $selected, get_the_title( $post->get_id() ) );
								}

								?>
							</select>
							<div class="ever-spinner-product hidden"></div>
						</td>
					</tr>

				<?php } ?>

				<tr>
					<th scope="row">
						<label for="variation"><?php _e( 'Product Variation', 'wc-serial-number-pro' ) ?></label>
					</th>
					<td>

						<select name="variation" id="variation" class="ever-field-inline">
							<option value=""><?php _e( 'Main Product', 'wc-serial-number-pro' ) ?></option>

							<?php

							if ( ! empty( $variation ) ) {
								$product_obj = wc_get_product( $product );

								$variations = $product_obj->get_children();

								if ( ! empty( $variations ) ) {

									foreach ( $variations as $all_variation ) {

										$variation_selected = ( $all_variation == $variation ) ? 'selected' : 'selected';

										echo '<option value="' . $all_variation . '" ' . $variation_selected . '>' . get_the_title( $variation ) . '</option>';
									}
								}
							}

							?>

						</select>

					</td>
				</tr>

				<tr>
					<th scope="row">
						<label for="prefix"><?php _e( 'Prefix', 'wc-serial-number-pro' ) ?></label></th>
					<td class="ever-form-group">
						<input type="text" class="ever-field-inline" name="prefix" id="prefix" value="<?php echo $prefix ?>">
						<div class="ever-helper"> ? <span class="text">
								<?php _e( 'Prefix to added before the serial number', 'wc-serial-number-pro' ) ?>
								<br><strong>ex: <em>sl-xxxx-xxxx-xxxx-xxxx</em></strong>
							</span>
						</div>
					</td>
				</tr>

				<tr>
					<th scope="row">
						<label for="chunks_number"><?php _e( 'Chunks Number', 'wc-serial-number-pro' ) ?></label></th>
					<td class="ever-form-group">
						<input type="number" class="ever-field-inline" name="chunks_number" id="chunks_number" value="<?php echo $chunks_number ?>">
						<div class="ever-helper"> ? <span class="text">
								<?php _e( 'The number of chunks for the serial number.', 'wc-serial-number-pro' ) ?>
								<br><strong>ex: <em>xxxx-xxxx-xxxx-xxxx</em></strong>;
							</span>
						</div>
					</td>
				</tr>

				<tr>
					<th scope="row">
						<label for="chunk_length"><?php _e( 'Chunk Length', 'wc-serial-number-pro' ) ?></label></th>
					<td class="ever-form-group">
						<input type="number" class="ever-field-inline" name="chunk_length" id="chunk_length" value="<?php echo $chunk_length ?>">
						<div class="ever-helper"> ? <span class="text">
								<?php _e( 'The number of chunks length for the serial number.', 'wc-serial-number-pro' ); ?>
								<br><strong>ex: <em>xxxx-xxxx-xxxx-xxxx</em></strong>
							</span>
						</div>
					</td>
				</tr>

				<tr>
					<th scope="row">
						<label for="suffix"><?php _e( 'Suffix', 'wc-serial-number-pro' ) ?></label></th>
					<td class="ever-form-group">
						<input type="text" class="ever-field-inline" name="suffix" id="suffix" value="<?php echo $suffix ?>">
						<div class="ever-helper"> ? <span class="text">
								<?php _e( 'Suffix to added after the serial number.', 'wc-serial-number-pro' ); ?>
								<br><strong>ex: <em>xxxx-xxxx-xxxx-xxxx-suffix</em></strong>
							</span>
						</div>
					</td>
				</tr>

				<tr>
					<th scope="row">
						<label for="deliver_times"><?php _e( 'Max. Deliver Times', 'wc-serial-number-pro' ) ?></label>
					</th>
					<td>
						<input type="number" min="1" value="<?php echo $deliver_times ?>" name="deliver_times" id="deliver_times" class=" ever-field-inline">
						<div class="ever-helper"> ? <span class="text">
								<?php _e( 'The maximum number, the serial number can be delivered.', 'wc-serial-number-pro' ) ?>
							</span>
						</div>
					</td>
				</tr>

				<tr>
					<th scope="row">
						<label for="max_instance"><?php _e( 'Maximum Instance', 'wc-serial-number-pro' ) ?></label>
					</th>
					<td class="ever-form-group">
						<input type="number" min="0" value="<?php echo $instance ?>" name="max_instance" id="max_instance" class="ever-field-inline">
						<div class="ever-helper"> ? <span class="text">
							<?php _e( 'Maximum instance for the serial number.', 'wc-serial-number-pro' ) ?>
							</span>
						</div>
					</td>
				</tr>

				<tr>
					<th scope="row">
						<label for="validity"><?php _e( 'Validity', 'wc-serial-number-pro' ) ?></label>
					</th>
					<td>

						<input type="radio" class="validity_type" name="validity_type" value="days" <?php echo $validity_type == 'days' ? 'checked' : '' ?>> <?php _e( 'Days', 'wc-serial-number-pro' ) ?>
						&ensp;
						<input type="radio" class="validity_type" name="validity_type" value="date" <?php echo $validity_type == 'date' ? 'checked' : '' ?>> <?php _e( 'Date', 'wc-serial-number-pro' ) ?>

						<br> <br>

						<input type="<?php echo $validity_type == 'days' ? 'number' : 'text' ?>" min="0" name="validity" id="validity" class="regular-text  ever-field-inline" value="<?php echo $validity ?>">
						<div class="ever-helper"> ? <span class="text">
								<?php _e( 'Check Days for validity type of Days numbers', 'wc-serial-number-pro' ); ?>
								<hr>
								<?php _e( 'Check Date for validity type of Date', 'wc-serial-number-pro' ); ?>
							</span>
						</div>
					</td>
				</tr>

				</tbody>

			</table>

			<p class="submit">
				<input type="submit" name="submit" id="submit" class="button button-primary add-serial-number-manually" value="<?php echo $submit ?>">
			</p>

		</form>

	</div>
</div>

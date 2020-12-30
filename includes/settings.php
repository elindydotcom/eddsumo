<?php
/**
 * Add setting fields in edd download post edit page.
 *
 * @package   Appsumo
 * @author    Kashif
 */

/**
 * Add appsumo enable field
 *
 * @param int      $download_id edd download id.
 * @param int|null $price_id edd download variable price id.
 */
function appsumo_edd_product_settings_field_active( $download_id, $price_id = null ) {

	$name = 'appsumo_active';

	if ( null !== $price_id ) {
		$name = "edd_variable_prices[{$price_id}][appsumo_active]";
	}

	$active = appsumo_is_active( $download_id, $price_id );

	?>
	<span class="appsumo-edd-enabled">
		<label class="edd-legacy-setting-label"><?php esc_html_e( 'Enable', 'appsumo' ); ?></label>

		<select name="<?php echo esc_attr( $name ); ?>" id="<?php echo esc_attr( $name ); ?>">
			<option value="no" <?php selected( $active, false ); ?>><?php echo esc_attr_e( 'No', 'appsumo' ); ?></option>
			<option value="yes" <?php selected( $active, true ); ?>><?php echo esc_attr_e( 'Yes', 'appsumo' ); ?></option>
		</select>
	</span>

	<?php
}



/**
 * Add appsumo codes field
 *
 * @param int      $download_id edd download id.
 * @param int|null $price_id edd download variable price id.
 */
function appsumo_edd_product_settings_field_codes_num( $download_id, $price_id = null ) {

	if ( null !== $price_id ) {
		$name  = "edd_variable_prices[{$price_id}][appsumo_codes_num]";
		$codes = appsumo_get_variable_price_option( $download_id, $price_id, 'appsumo_codes_num' );
	} else {
		$name  = 'appsumo_codes_num';
		$codes = appsumo_get_regular_price_option( $download_id, 'codes_num' );
	}

	$active = appsumo_is_active( $download_id, $price_id );

	?>
	<span class="appsumo-edd-codes">
		<label class="edd-legacy-setting-label"><?php esc_html_e( 'Codes', 'appsumo' ); ?></label>
		<input type="number" value="<?php echo esc_attr( $codes ); ?>" name="<?php echo esc_attr( $name ); ?>" id="<?php echo esc_attr( $name ); ?>" <?php disabled( $active, false ); ?> />
	</span>
	<?php
}


/**
 * Add appsumo redeemed code field
 *
 * @param int      $download_id edd download id.
 * @param int|null $price_id edd download variable price id.
 */
function appsumo_edd_product_settings_field_redeemed( $download_id, $price_id = null ) {

	$redeemed = appsumo_redeemed_codes_count( $download_id, $price_id );

	?>

	<span class="appsumo-edd-codes-redeemed">
		<label class="edd-legacy-setting-label"><?php esc_html_e( 'Redeemed', 'appsumo' ); ?></label>
		<input type="text" value="<?php echo esc_attr( $redeemed ); ?>" disabled="disabled" />

		<?php if ( $redeemed > 0 ) { ?>

		<a href="<?php echo esc_attr( appsumo_get_csv_download_link( $download_id, $price_id, 'redeemed' ) ); ?>" target="_blank" class="appsumo_download_csv_btn" title="Download csv"><span class="dashicons dashicons-media-spreadsheet"></span></a>

		<?php } ?>
	</span>

	<?php

}


/**
 * Add appsumo remaining codes field
 *
 * @param int      $download_id edd download id.
 * @param int|null $price_id edd download variable price id.
 */
function appsumo_edd_product_settings_field_remaining( $download_id, $price_id = null ) {

	$remaining = appsumo_remaining_codes_count( $download_id, $price_id );

	?>

	<span class="appsumo-edd-codes-remaining">
		<label class="edd-legacy-setting-label"><?php esc_html_e( 'Remaining', 'appsumo' ); ?></label>
		<input type="text" value="<?php echo esc_attr( $remaining ); ?>" disabled="disabled" />

		<?php if ( $remaining > 0 ) { ?>
		<a href="<?php echo esc_attr( appsumo_get_csv_download_link( $download_id, $price_id ) ); ?>" target="_blank" class="appsumo_download_csv_btn" title="Download csv"><span class="dashicons dashicons-media-spreadsheet"></span></a>
		<?php } ?>
	</span>

	<?php

}


/**
 * Add appsumo variable price settings
 *
 * @global array $post
 *
 * @param int      $download_id edd download id.
 * @param int|null $price_id edd download variable price id.
 * @param array    $args price args.
 */
function appsumo_edd_variable_price_settings( $download_id, $price_id, $args ) {
	global $post;

	?>
	<div class="edd-custom-price-option-section">
		<span class="edd-custom-price-option-section-title"><?php esc_html_e( 'Appsumo Settings', 'edd-all-access' ); ?></span>

		<?php

		appsumo_edd_product_settings_field_active( $download_id, $price_id );
		appsumo_edd_product_settings_field_codes_num( $download_id, $price_id );

		appsumo_edd_product_settings_field_redeemed( $download_id, $price_id );
		appsumo_edd_product_settings_field_remaining( $download_id, $price_id );

		if ( $post->post_name ) {

			$link = home_url( "apps_landing/{$post->post_name}-pid-{$price_id}" );
			?>

		<div class="appsumo_vp_landing_page_link">
			<span class="edd-custom-price-option-section-title">Appsumo Landing page</span>
			<a target="_blank" href="<?php echo esc_attr( $link ); ?>"><?php echo esc_html( $link ); ?></a>
		</div>

			<?php
		}

		?>

	</div>
	<?php
}

add_action( 'edd_download_price_table_row', 'appsumo_edd_variable_price_settings', 10, 3 );


/**
 * Regular price settings
 *
 * @global array $post
 *
 * @param int $download_id edd download id.
 */
function appsumo_edd_settings( $download_id ) {

	global $post;

	?>
	<div id="appsumo_regular_price_settings" style="<?php echo ( edd_has_variable_prices( $download_id ) ? 'display:none' : '' ); ?>">
		<span class="edd-custom-price-option-section-title"><?php esc_html_e( 'Appsumo Settings', 'edd-all-access' ); ?></span>

		<?php

		appsumo_edd_product_settings_field_active( $download_id );
		appsumo_edd_product_settings_field_codes_num( $download_id );

		appsumo_edd_product_settings_field_redeemed( $download_id );
		appsumo_edd_product_settings_field_remaining( $download_id );

		if ( $post->post_name ) {

			$link = home_url( "apps_landing/{$post->post_name}" );
			?>
			<div>
				<span class="edd-custom-price-option-section-title">Appsumo Landing page</span>
				<a target="_blank" href="<?php echo esc_attr( $link ); ?>"><?php echo esc_html( $link ); ?></a>
			</div>
			<?php
		}
		?>

	</div>

	<?php

}

add_action( 'edd_after_price_field', 'appsumo_edd_settings', 10, 1 );


/**
 * Save appsumo settings
 *
 * @param int   $post_id Edd download id.
 * @param array $post Post object.
 *
 * @return void
 */
function appsumo_edd_download_settings_save( $post_id, $post ) {

	/* Does the current user has permission? */
	if ( ! current_user_can( 'edit_product', $post_id ) ) {
		return;
	}

	if ( edd_has_variable_prices( $post_id ) ) {

		$prices = edd_get_variable_prices( $post_id );

		foreach ( $prices as $price ) {

			if ( 'yes' === $price['appsumo_active'] ) {
				appsumo_save_download_settings( $post_id, $price['index'] );
			}
		}

		delete_post_meta( $post_id, 'appsumo_active' );
		delete_post_meta( $post_id, 'appsumo_codes_num' );

		appsumo_delete_codes(
			array(
				'download_id' => $post_id,
				'price_id'    => '0',
			)
		);
	} else {

		$active            = filter_input( INPUT_POST, 'appsumo_active' );
		$appsumo_codes_num = filter_input( INPUT_POST, 'appsumo_codes_num' );
		$appsumo_codes_num = is_numeric( $appsumo_codes_num ) && $appsumo_codes_num > 0 ? $appsumo_codes_num : 0;

		update_post_meta( $post_id, 'appsumo_active', $active );
		update_post_meta( $post_id, 'appsumo_codes_num', $appsumo_codes_num );

		if ( 'yes' === $active ) {

			appsumo_save_download_settings( $post_id );
		}
		appsumo_delete_codes(
			array(
				'download_id' => $post_id,
				'price_id'    => '0',
			),
			null,
			array( 'price_id' => '>' )
		);
	}

}

add_action( 'edd_save_download', 'appsumo_edd_download_settings_save', 20, 2 );


/**
 * Save settings and generate codes
 *
 * @param int      $download_id edd download id.
 * @param int|null $price_id edd download variable price id.
 */
function appsumo_save_download_settings( $download_id, $price_id = null ) {

	$previous_codes = appsumo_generated_codes_count( $download_id, $price_id );

	if ( $price_id ) {
		$appsumo_code_gen_num = (int) appsumo_get_variable_price_option( $download_id, $price_id, 'appsumo_codes_num', 0 );
	} else {
		$appsumo_code_gen_num = (int) appsumo_get_regular_price_option( $download_id, 'codes_num' );
		$appsumo_code_gen_num = $appsumo_code_gen_num > 0 ? $appsumo_code_gen_num : 0;
	}

	if ( $appsumo_code_gen_num !== $previous_codes ) {

		if ( $appsumo_code_gen_num > $previous_codes ) {

			appsumo_generate_codes( $download_id, $price_id, ( $appsumo_code_gen_num - $previous_codes ) );
		} elseif ( $appsumo_code_gen_num < $previous_codes ) {

			$redeemed_codes = appsumo_redeemed_codes_count( $download_id, $price_id );

			if ( $appsumo_code_gen_num < $redeemed_codes ) {
				$appsumo_code_gen_num = $redeemed_codes;
			}

			$limit    = $previous_codes - $appsumo_code_gen_num;
			$redeemed = 0;

			appsumo_delete_codes( compact( 'download_id', 'price_id', 'redeemed' ), $limit );
		}
	}

}

<?php
/**
 * Common functions
 *
 * @package   Appsumo
 * @author    Kashif
 */

/**
 * Return landing page id.
 *
 * @return int
 */
function appsumo_get_landing_page_id() {

	$page_id = get_option( 'appsumo_landing_page' );

	$page_id = $page_id ? $page_id : 0;

	return $page_id;
}


/**
 * Generate codes for appsumo and store in database.
 *
 * @param int      $download_id edd download id.
 * @param int|null $price_id edd download variable price id.
 * @param int      $gen_code_num how many codes to generate.
 */
function appsumo_generate_codes( $download_id, $price_id = null, $gen_code_num ) {

	$codes = array();

	for ( $i = 0; $i < $gen_code_num; $i++ ) {

		$exists = true;
		$code   = '';

		do {
			$code = wp_generate_password( 12, false, false );

			if ( ! appsumo_code_exists( $code ) && ! in_array( $code, $codes ) ) {
				$exists = false;
			}
		} while ( $exists );

		array_push( $codes, $code );
	}

	appsumo_store_codes_to_db( $download_id, $price_id, $codes );
}


/**
 * Store appsumo codes in database
 *
 * @global Object  $wpdb
 *
 * @param int      $download_id edd download id.
 * @param int|null $price_id edd download variable price id.
 * @param array    $all_codes appsumo codes array.
 */
function appsumo_store_codes_to_db( $download_id, $price_id = null, $all_codes ) {

	global $wpdb;

	$codes_chucks = array_chunk( $all_codes, 1000 );

	$placeholders     = '(%d,%s)';
	$placeholder_args = array( $download_id );
	$fields           = array( 'download_id' );

	if ( $price_id ) {
		$placeholders       = '(%d,%d,%s)';
		$placeholder_args[] = $price_id;
		$fields[]           = 'price_id';
	}

	$fields[] = 'code';

	foreach ( $codes_chucks as $codes ) {

		$values = array();
		foreach ( $codes as $code ) {

			$params   = array_merge( array( $placeholders ), array_merge( $placeholder_args, array( $code ) ) );
			$values[] = call_user_func_array( array( $wpdb, 'prepare' ), $params );
		}

		if ( ! empty( $values ) ) {

			$fields_list = '(`' . implode( '`,`', $fields ) . '`)';
			$query       = "INSERT INTO {$wpdb->prefix}appsumo_codes {$fields_list} VALUES " . implode( ',', $values );

			// phpcs:disable
			$wpdb->query( $query );
			// phpcs:enable
		}
	}

}


/**
 * Delete Appsumo codes
 *
 * @global wpdb    $wpdb WordPress database class.
 * @param array    $args where clouse fields.
 * @param int|null $limit Max number to codes to delete.
 * @param array    $comparisons query where comparisons.
 */
function appsumo_delete_codes( $args, $limit = null, $comparisons = array() ) {
	global $wpdb;

	$where = array();

	foreach ( $args as $field => $value ) {

		$comparison = isset( $comparisons[ $field ] ) ? $comparisons[ $field ] : '=';

		$where[] = "`{$field}` {$comparison} " . ( is_numeric( $value ) ? '%d' : '%s' );
	}

	$query = "DELETE FROM {$wpdb->prefix}appsumo_codes WHERE " . implode( ' AND ', $where );

	if ( $limit ) {
		$query .= " LIMIT {$limit}";
	}

	$prepare_params = array_merge( array( $query ), array_values( $args ) );

	// phpcs:disable
	$wpdb->query( call_user_func_array( array( $wpdb, 'prepare' ), $prepare_params ) );
	// phpcs:enable
}

/**
 * Check if appsumo code exists.
 *
 * @param string $code appsumo code.
 *
 * @return boolean
 */
function appsumo_code_exists( $code ) {

	if ( appsumo_get_code( $code ) ) {
		return true;
	}

	return ( $count && $count > 0 );
}

/**
 * Get appsumo code record from database.
 *
 * @global wpdb $wpdb WordPress database class.
 *
 * @param string $code appsumo code.
 *
 * @return object
 */
function appsumo_get_code( $code ) {
	global $wpdb;

	return $wpdb->get_row(
		$wpdb->prepare( "SELECT * FROM {$wpdb->prefix}appsumo_codes WHERE `code`=%s", $code )
	);

}

/**
 * Return redeemed codes count for a edd download.
 *
 * @global wpdb $wpdb WordPress database class
 *
 * @param int      $download_id edd download id.
 * @param int|null $price_id edd download variable price id.
 *
 * @return int
 */
function appsumo_redeemed_codes_count( $download_id, $price_id = null ) {

	global $wpdb;

	$sql = "SELECT COUNT(*) FROM {$wpdb->prefix}appsumo_codes WHERE download_id = %d";

	$params = array( $download_id );
	if ( null !== $price_id ) {
		$sql     .= ' AND price_id = %d';
		$params[] = $price_id;
	}

	$sql .= ' AND redeemed = 1';

	$params = array_merge( array( $sql ), $params );

	// phpcs:disable
	$count = $wpdb->get_var(
		call_user_func_array( array( $wpdb, 'prepare' ), $params )
	);
	// phpcs:enable

	return $count;
}

/**
 * Count total generated codes for a download.
 *
 * @global wpdb $wpdb WordPress database class
 *
 * @param int      $download_id edd download id.
 * @param int|null $price_id edd download variable price id.
 *
 * @return int
 */
function appsumo_generated_codes_count( $download_id, $price_id = null ) {

	global $wpdb;

	$sql = "SELECT COUNT(*) FROM {$wpdb->prefix}appsumo_codes WHERE download_id = %d";

	$params = array( $download_id );
	if ( null !== $price_id ) {
		$sql     .= ' AND price_id = %d';
		$params[] = $price_id;
	}

	$params = array_merge( array( $sql ), $params );

	//phpcs:disable
	$count = $wpdb->get_var(
		call_user_func_array( array( $wpdb, 'prepare' ), $params )
	);
	//phpcs:enable

	return (int) $count;

}

/**
 * Count unredeemed codes of a download.
 *
 * @param int      $download_id edd download id.
 * @param int|null $price_id edd download variable price id.
 *
 * @return int
 */
function appsumo_remaining_codes_count( $download_id, $price_id = null ) {

	$total     = appsumo_generated_codes_count( $download_id, $price_id );
	$redeemed  = appsumo_redeemed_codes_count( $download_id, $price_id );
	$remaining = ( $total - $redeemed );

	return $remaining;
}

/**
 * Return csv download link for backend.
 *
 * @param int      $download_id edd download id.
 * @param int|null $price_id edd download variable price id.
 * @param string   $type Remaining or Redeemed codes.
 *
 * @return string
 */
function appsumo_get_csv_download_link( $download_id, $price_id = null, $type = 'remaining' ) {

	$params = array(
		'action'      => 'appsumo_download_csv',
		'type'        => $type,
		'nonce'       => wp_create_nonce( "appsumo_download_csv_{$type}" ),
		'download_id' => $download_id,
	);

	if ( $price_id ) {
		$params['price_id'] = $price_id;
	}

	return admin_url( 'admin-ajax.php?' . build_query( $params ) );
}

add_action( 'wp_ajax_appsumo_download_csv', 'appsumo_ajax_download_codes_csv' );


/**
 * Download codes cvs file
 *
 * @global wpdb $wpdb WordPress database class
 */
function appsumo_ajax_download_codes_csv() {
	global $wpdb;

	$type        = filter_input( INPUT_GET, 'type' );
	$nonce       = filter_input( INPUT_GET, 'nonce' );
	$download_id = filter_input( INPUT_GET, 'download_id' );
	$price_id    = filter_input( INPUT_GET, 'price_id' );

	$types = array( 'remaining', 'redeemed' );

	if ( ! in_array( $type, $types, true ) || ! $download_id || ! wp_verify_nonce( $nonce, "appsumo_download_csv_{$type}" ) ) {
		exit( esc_html__( 'Invlid request, try again later', 'appsumo' ) );
	}

	$sql = "SELECT * FROM {$wpdb->prefix}appsumo_codes WHERE download_id = %d";

	$params = array( $download_id );
	if ( $price_id ) {
		$sql     .= ' AND price_id = %d';
		$params[] = $price_id;
	}

	$sql .= ' AND redeemed = ' . ( 'remaining' === $type ? '0' : '1' );

	$params = array_merge( array( $sql ), $params );

	// phpcs:disable
	$results = $wpdb->get_results(
		call_user_func_array( array( $wpdb, 'prepare' ), $params )
	);
	// phpcs:enable

	$filename = "edd_appsumo_codes_{$download_id}" . ( $price_id ? "_{$price_id}" : '' ) . '_' . time() . '.csv';

	$fp = fopen( 'php://output', 'w' );
	if ( $fp && $results ) {
		header( 'Content-Type: text/csv' );
		header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
		header( 'Pragma: no-cache' );
		header( 'Expires: 0' );

		foreach ( $results as $result ) {
			fputcsv( $fp, array( $result->code ) );
		}
	}

	fclose( $fp );
	die();

}

/**
 * Return variable priced download option.
 *
 * @param int      $download_id edd download id.
 * @param int|null $price_id edd download variable price id.
 * @param string   $option option name.
 * @param string   $default default option value.
 *
 * @return string
 */
function appsumo_get_variable_price_option( $download_id, $price_id, $option, $default = '' ) {

	$prices = edd_get_variable_prices( $download_id );

	$price_options = array();

	if ( isset( $prices[ $price_id ] ) ) {
		$price_options = $prices[ $price_id ];
	}

	if ( empty( $price_options ) ) {

		return $default;
	}

	if ( isset( $price_options[ $option ] ) ) {
		return $price_options[ $option ];
	} else {
		return $default;
	}

}

/**
 * Return regular price edd download option
 *
 * @param int    $download_id edd download id.
 * @param string $option option name.
 *
 * @return string
 */
function appsumo_get_regular_price_option( $download_id, $option ) {

	$value = get_post_meta( $download_id, "appsumo_{$option}", true );

	return $value;
}

/**
 * Check if appsumo is active for a download
 *
 * @param int      $download_id edd download id.
 * @param int|null $price_id edd download variable price id.
 *
 * @return boolean
 */
function appsumo_is_active( $download_id, $price_id = null ) {

	if ( null !== $price_id ) {
		$active = appsumo_get_variable_price_option( $download_id, $price_id, 'appsumo_active' );
	} else {
		$active = appsumo_get_regular_price_option( $download_id, 'active' );
	}

	return ( 'yes' === $active ? true : false );
}

/**
 * Mark appsumo code as redeemed.
 *
 * @global wpdb $wpdb
 *
 * @param int $id database row id of code.
 */
function appsumo_do_redeem_code( $id ) {
	global $wpdb;

	$table_name = "{$wpdb->prefix}appsumo_codes";

	$wpdb->update( $table_name, array( 'redeemed' => '1' ), array( 'id' => $id ) );
}


/**
 * Start redeeming appsumo code.
 *
 * @global WP_Error $appsumo_errors
 * @param int      $download_id edd download id.
 * @param int|null $price_id edd download variable price id.
 * @param string   $code Appsumo code.
 */
function appsumo_redeem_code( $download_id, $price_id = null, $code ) {
	global $appsumo_errors;

	$row = appsumo_get_code( $code );

	$error = '';

	if ( ! $row ) {
		$error = 'invalid';
	} elseif ( (int) $row->download_id !== (int) $download_id ) {
		$error = 'invalid_product';
	} elseif ( ( $price_id && (int) $price_id !== (int) $row->price_id ) || ( ! $price_id && (int) $row->price_id > 0 ) ) {
		$error = 'invalid_price_id';
	} elseif ( 1 === (int) $row->redeemed ) {
		$error = 'redeemed';
	} else {
		Appsumo_Purchase::process( $download_id, $price_id, $row );
	}

	if ( $error ) {
		edd_set_error( $error, __( 'Appsumo code is invalid.', 'appsumo' ) );
	}

	foreach ( edd_get_errors() as $err_id => $err ) {
		$appsumo_errors->add( $err_id, $err );
	}

}


add_action( 'edd_before_purchase_history', 'appsumo_redeem_code_success_message' );

/**
 * Print successful purchase message.
 *
 * @param WP_Post[]|false $payments List of all user purchases.
 */
function appsumo_redeem_code_success_message( $payments ) {

	if ( isset( $_SESSION['appsumo_success_message'] ) ) {
		echo "<p class=\"appsumo_success_message\">{$_SESSION['appsumo_success_message']}</p>";
		unset( $_SESSION['appsumo_success_message'] );
	}
}



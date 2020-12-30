<?php
/**
 * Front end appsumo landing page.
 *
 * @package   Appsumo
 * @author    Kashif
 */

/**
 * Handle login, registration or code redeem request.
 *
 * @global WP_Error $appsumo_errors
 * @global array $appsumo_data
 *
 * @return void
 */
function appsumo_landing_init() {
	global $appsumo_errors, $appsumo_data;

	$appsumo_errors = new WP_Error();

	if ( ! appsumo_is_landing_page() ) {

		return;

	}

	$appsumo_data = array();

	$product_slug = get_query_var( 'appsumo_product_slug' );

	if ( ! $product_slug ) {
		appsumo_404();
	}

	$slug_parts = explode( '-pid-', $product_slug );

	$product_slug = $slug_parts[0];

	$price_id = isset( $slug_parts[1] ) && $slug_parts[1] ? $slug_parts[1] : null;

	$download_id = appsumo_get_product_id_by_slug( $product_slug );

	$appsumo_data['download_slug'] = $product_slug;
	$appsumo_data['price_id']      = $price_id;
	$appsumo_data['download_id']   = $download_id;
	$appsumo_data['code']          = get_query_var( 'appsumo_code' );

	if ( ! appsumo_is_active( $download_id, $price_id ) ) {

		appsumo_404();
	}

	$appsumo_data['view'] = 'registration';

	if ( is_user_logged_in() ) {

		$appsumo_data['view'] = 'redeem';

		if ( empty( $_POST ) && $appsumo_data['code'] ) {
			appsumo_redeem_code( $download_id, $price_id, $appsumo_data['code'] );
		}
	}

	if ( empty( $_POST ) ) {
		return;
	}

	$user_data = prepare_user_data();

	if ( empty( $user_data ) ) {
		return;
	}

	$appsumo_data['view'] = $user_data['action'];
	switch ( $user_data['action'] ) {
		case 'registration':
			if ( appsumo_validate_registration_form( $user_data ) ) {
				$user_id = appsumo_insert_user( $user_data );

				if ( $user_id ) {

					appsumo_do_login_user( $user_id );
					$link = appsumo_landing_page_url( $product_slug, $price_id, $user_data['code'] );
					wp_safe_redirect( $link );
					die();

				} else {
					$appsumo_errors->add( 'failed', __( 'Failed to register, try again later', 'appsumo' ) );
				}
			}

			break;

		case 'login':
			if ( ! wp_verify_nonce( $user_data['nonce'], 'appsumo-login-nonce' ) ) {
				$appsumo_errors->add( 'request_invalid', __( 'There is an error while processing your request, try again later', 'appsumo' ) );
			} elseif ( empty( $user_data['email'] ) || empty( $user_data['pass'] ) ) {
				$appsumo_errors->add( 'inlalid_login', __( 'Invalid email adress or password.', 'appsumo' ) );
			} else {

				$user = edd_log_user_in( 0, $user_data['email'], $user_data['pass'], false );

				if ( ! $user instanceof WP_User ) {
					$appsumo_errors->add( 'inlalid_login', __( 'Invalid email adress or password.', 'appsumo' ) );
				} else {

					$link = appsumo_landing_page_url( $product_slug, $price_id, $user_data['code'] );

					wp_safe_redirect( $link );
					die();
				}
			}

			break;

		case 'redeem':
			if ( ! wp_verify_nonce( $user_data['nonce'], 'appsumo-redeem-nonce' ) ) {
				$appsumo_errors->add( 'request_invalid', __( 'There is an error while processing your request, try again later.', 'appsumo' ) );
			} elseif ( empty( $user_data['code'] ) ) {
				$appsumo_errors->add( 'code_required', __( 'Provide appsumo code.', 'appsumo' ) );
			} else {
				appsumo_redeem_code( $download_id, $price_id, $user_data['code'] );
			}
			break;

	}

	$appsumo_data['post'] = $user_data;

}

add_action( 'wp', 'appsumo_landing_init', 99 );



/**
 * Display appsumo landing page.
 *
 * @global array $appsumo_data
 *
 * @param array $args shortcode arguments.
 */
function appsumo_langing_page( $args = array() ) {

	global $appsumo_data;

	ob_start();

	appsumo_show_error_messages();

	if ( 'redeem' === $appsumo_data['view'] ) {
		appsumo_redeem_form();
	} else {
		appsumo_registration_form();
		appsumo_login_form();
	}

	return ob_get_clean();

}

add_shortcode( 'appsumo-landingpage', 'appsumo_langing_page' );


/**
 * Check if user is on appsumo landing page.
 *
 * @return boolean
 */
function appsumo_is_landing_page() {

	if ( ! is_admin() && get_query_var( 'appsumo_product_slug' ) ) {
		return true;
	}

	return false;

}

/**
 * Enqueue style and script on appsumo landing page.
 *
 * @global WP_Post $post
 * @global array $appsumo_data
 */
function appsumo_enqueue_scripts() {
	global $post, $appsumo_data;

	$page_id = (int) appsumo_get_landing_page_id();

	if ( (int) edd_get_option( 'purchase_history_page' ) === (int) $post->ID || (int) $post->ID === $page_id ) {

		wp_enqueue_style( 'appsumo-style', APPSUMO_URL . 'assets/public/css/style.css', array(), APPSUMO_VERSION );
	}

	if ( (int) $post->ID === (int) $page_id ) {

		wp_enqueue_script( 'appsumo-script', APPSUMO_URL . 'assets/public/js/script.js', array( 'jquery' ), APPSUMO_VERSION, true );

		wp_localize_script(
			'appsumo-script',
			'appsumo',
			apply_filters( 'appsumo_localize_data', array( 'active_form' => $appsumo_data['view'] ) )
		);
	}
}
add_action( 'wp_enqueue_scripts', 'appsumo_enqueue_scripts' );


/**
 * Render Redeem form.
 */
function appsumo_redeem_form() {

	$code = get_query_var( 'appsumo_code' );

	?>

	<div id="appsumo_redeem_form_div">
		<form action="<?php echo esc_attr( appsumo_current_url() ); ?>" method="post">
			<p>
				<label for="reg_appsumo_code">Appsumo Code: </label>
				<input type="text" name="appsumo_code" id="reg_appsumo_code" value="<?php echo esc_attr( $code ); ?>" />
			</p>
			<p>
				<input type="hidden" name="appsumo_redeem_nonce" value="<?php echo esc_attr( wp_create_nonce( 'appsumo-redeem-nonce' ) ); ?>" />
				<input type="hidden" name="appsumo_action" value="redeem" />
				<input name="submit" type="submit" value="Redeem" />
			</p>
		</form>

	</div>
	<?php

}

/**
 * Render appsumo login form.
 */
function appsumo_login_form() {

	$product_slug = get_query_var( 'appsumo_product_slug' );

	$code = get_query_var( 'appsumo_code' );

	?>

	<div id="appsumo_login_form_div" style="display: none">
		<form action="<?php echo esc_attr( appsumo_current_url() ); ?>" method="post">

			<p>
				<label for="login_email">Email: </label>
				<input type="text" name="email" id="login_email" />
			</p>
			<p>
				<label for="login_pass">Password: </label>
				<input type="password" name="password" id="login_pass" />
			</p>
			<p>
				<label for="reg_appsumo_code">Appsumo Code: </label>
				<input type="text" name="appsumo_code" id="reg_appsumo_code" value="<?php echo esc_attr( $code ); ?>" />
			</p>

			<input type="hidden" name="appsumo_login_nonce" value="<?php echo esc_attr( wp_create_nonce( 'appsumo-login-nonce' ) ); ?>" />
			<input type="hidden" name="appsumo_action" value="login" />

			<p>
				<input name="submit" type="submit" value="Sign In" />
			</p>

		</form>

		<a href="#" class="appsumo_btn_switch_to_registration"> New to <?php echo get_bloginfo( 'name' ); ?>? Register for new account.</a>
	</div>
	<?php

}


/**
 * Render appsumo registration form.
 */
function appsumo_registration_form() {

	$fname = filter_input( INPUT_POST, 'fname' );
	$lname = filter_input( INPUT_POST, 'lname' );
	$email = filter_input( INPUT_POST, 'email' );

	$appsumo_code = get_query_var( 'appsumo_code' );

	?>
	<div id="appsumo_registration_form_div" style="display: none">
		<form action="<?php echo esc_attr( appsumo_current_url() ); ?>" method="post">
			<p>
				<label for="reg_fname">First name: </label>
				<input type="text" name="fname" id="reg_fname" value="<?php echo esc_attr( $fname ); ?>" />
			</p>
			<p>
				<label for="reg_lname">Last name: </label>
				<input type="text" name="lname" id="reg_lname" value="<?php echo esc_attr( $lname ); ?>" />
			</p>
			<p>
				<label for="reg_email">Email: </label>
				<input type="text" name="email" id="reg_email" value="<?php echo esc_attr( $email ); ?>" />
			</p>
			<p>
				<label for="reg_pass">Password: </label>
				<input type="password" name="pass" id="reg_pass" />
			</p>
			<p>
				<label for="reg_appsumo_code">Appsumo Code: </label>
				<input type="text" name="appsumo_code" id="reg_appsumo_code" value="<?php echo esc_attr( $appsumo_code ); ?>" />
			</p>

			<input type="hidden" name="appsumo_registration_nonce" value="<?php echo esc_attr( wp_create_nonce( 'appsumo-register-nonce' ) ); ?>" />
			<input type="hidden" name="appsumo_action" value="registration" />
			<p>
				<input name="submit" type="submit" value="Register" />
			</p>

		</form>

			<a href="#" class="appsumo_btn_switch_to_login"> Have an account? Signin to <?php echo get_bloginfo( 'name' ); ?></a>
	</div>
	<?php

}

/**
 * Validate appsumo registration form.
 *
 * @global WP_Error $appsumo_errors
 *
 * @param array $user_data new user data.
 *
 * @return type
 */
function appsumo_validate_registration_form( $user_data ) {
	global $appsumo_errors;

	if ( ! wp_verify_nonce( $user_data['nonce'], 'appsumo-register-nonce' ) ) {

		$appsumo_errors->add( 'request_invalid', __( 'There is an error while processing your request, try again later', 'appsumo' ) );

	} else {

		$fname = $user_data['fname'];
		$lname = $user_data['lname'];
		$email = $user_data['email'];
		$pass  = $user_data['pass'];

		// This is required for username checks.
		require_once ABSPATH . WPINC . '/registration.php';

		if ( '' === $fname || ! $fname ) {
			$appsumo_errors->add( 'fname', __( 'First name is required', 'appsumo' ) );
		}
		if ( '' === $lname || ! $lname ) {
			$appsumo_errors->add( 'lname', __( 'Last name is required', 'appsumo' ) );
		}

		if ( '' === $email || ! $email ) {
			$appsumo_errors->add( 'email_required', __( 'Email address is required', 'appsumo' ) );
		} elseif ( ! is_email( $email ) ) {
			$appsumo_errors->add( 'email_invalid', __( 'Invalid email', 'appsumo' ) );
		} elseif ( email_exists( $email ) || username_exists( $email ) ) {
			$appsumo_errors->add( 'email_used', __( 'Email already registered', 'appsumo' ) );
		}

		if ( '' === $pass || ! $pass ) {
			$appsumo_errors->add( 'password_empty', __( 'Please enter a password', 'appsumo' ) );
		} elseif ( 5 > strlen( $pass ) ) {
			$appsumo_errors->add( 'password', __( 'Password length must be greater than 5', 'appsumo' ) );
		}
	}

	$errors = $appsumo_errors->get_error_messages();

	return ( 0 === count( $errors ) ? true : false );
}

/**
 * Insert new user after successful registration.
 *
 * @param array $user_data new user data.
 *
 * @return int|boolean
 */
function appsumo_insert_user( $user_data ) {
	$user_id = wp_insert_user(
		array(
			'user_login'      => $user_data['email'],
			'user_pass'       => $user_data['pass'],
			'user_email'      => $user_data['email'],
			'first_name'      => $user_data['fname'],
			'last_name'       => $user_data['lname'],
			'user_registered' => gmdate( 'Y-m-d H:i:s' ),
			'role'            => get_option( 'default_role' ),
		)
	);

	if ( $user_id ) {
		wp_new_user_notification( $user_id );
		return $user_id;
	}

	return false;

}


/**
 * Login user by user id after successful registration.
 *
 * @param int $user_id user id.
 */
function appsumo_do_login_user( $user_id ) {

	$user = get_user_by( 'id', $user_id );

	clean_user_cache( $user->ID );
	wp_clear_auth_cookie();
	wp_set_current_user( $user->ID );
	wp_set_auth_cookie( $user->ID, true, false );
	update_user_caches( $user );
}


/**
 * Print validation error messages.
 *
 * @global WP_Error $appsumo_errors
 */
function appsumo_show_error_messages() {

	global $appsumo_errors;

	if ( is_wp_error( $appsumo_errors ) ) {
		foreach ( $appsumo_errors->get_error_messages() as $error ) {
			echo "<div><strong>ERROR</strong>:{$error}</div>";
		}
	}

}

/**
 * Return post id of edd download from post name.
 *
 * @param string $slug edd download post slug.
 *
 * @return int
 */
function appsumo_get_product_id_by_slug( $slug ) {

	$products = get_posts(
		array(
			'post_type' => 'download',
			'name'      => $slug,
		)
	);

	$product_id = null;
	if ( $products && ! empty( $products ) && $products[0] ) {
		$product_id = $products[0]->ID;
	}

	return $product_id;

}

/**
 * Send to 404 page.
 *
 * @global object $wp_query
 */
function appsumo_404() {
	global $wp_query;

	$wp_query->set_404();
	status_header( 404 );

	require get_404_template();
	exit;
}


/**
 * Prepare and sanitize user registration data.
 */
function appsumo_prepare_registration_form_data() {

	$fname = filter_input( INPUT_POST, 'fname' );
	$lname = filter_input( INPUT_POST, 'lname' );
	$email = filter_input( INPUT_POST, 'email' );
	$pass  = filter_input( INPUT_POST, 'pass' );

	return array(
		'fname' => sanitize_text_field( $fname ),
		'lname' => sanitize_text_field( $lname ),
		'email' => sanitize_text_field( $email ),
		'pass'  => sanitize_text_field( $pass ),
	);
}

/**
 * Prepare and sanitize user login data.
 */
function appsumo_prepare_login_form_data() {
	$email = filter_input( INPUT_POST, 'email' );
	$pass  = filter_input( INPUT_POST, 'password' );

	return array(
		'email' => sanitize_text_field( $email ),
		'pass'  => sanitize_text_field( $pass ),
	);
}

/**
 * Prepare user input data.
 */
function prepare_user_data() {

	$action = filter_input( INPUT_POST, 'appsumo_action' );

	$user_data = array();
	if ( 'registration' === $action ) {
		$user_data = appsumo_prepare_registration_form_data();
	} elseif ( 'login' === $action ) {
		$user_data = appsumo_prepare_login_form_data();
	} elseif ( 'redeem' !== $action ) {
		return $user_data;
	}

	$code  = filter_input( INPUT_POST, 'appsumo_code' );
	$nonce = filter_input( INPUT_POST, "appsumo_{$action}_nonce" );

	$common_data = array(
		'code'   => sanitize_text_field( $code ),
		'nonce'  => sanitize_text_field( $nonce ),
		'action' => $action,
	);

	return array_merge( $user_data, $common_data );
}

/**
 * Return current appsumo landing page url.
 */
function appsumo_current_url() {
	global $wp;
	return home_url( $wp->request );
}

/**
 * Return landing page url based on params.
 *
 * @param string $product_slug edd download post slug.
 * @param string $price_id edd download price id.
 * @param string $code appsumo code.
 */
function appsumo_landing_page_url( $product_slug, $price_id = '', $code = '' ) {
	$link = home_url( "apps_landing/{$product_slug}" );

	if ( $price_id ) {
		$link .= "-pid-{$price_id}";
	}

	if ( $code ) {
		$link .= "/{$code}";
	}

	return $link;
}

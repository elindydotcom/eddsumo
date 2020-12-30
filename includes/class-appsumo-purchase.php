<?php
/**
 * Trigger a sale in edd.
 *
 * @package   Appsumo
 * @author    Kashif
 */

/**
 * Add edd download payment.
 */
class Appsumo_Purchase {

	/**
	 * Edd download id.
	 *
	 * @var int
	 */
	private $download_id;

	/**
	 * Variable price id of a download.
	 *
	 * @var null|int
	 */
	private $price_id;


	/**
	 * Appsumo code.
	 *
	 * @var string
	 */
	private $code;


	/**
	 * Class constructor.
	 *
	 * @param int      $download_id edd download id.
	 * @param int|null $price_id edd download variable price id.
	 * @param string   $code Appsumo code.
	 */
	public function __construct( $download_id, $price_id = null, $code = null ) {
		$this->download_id = absint( $download_id );

		$this->price_id = $price_id;
		$this->code     = $code;
	}



	/**
	 * Initialize this class and process edd download purchase.
	 *
	 * @param int      $download_id edd download id.
	 * @param int|null $price_id edd download variable price id.
	 * @param string   $code Appsumo code.
	 */
	public static function process( $download_id, $price_id, $code ) {
		$purchase = new self( $download_id, $price_id, $code );
		$purchase->add_to_cart();

		add_action( 'edd_insert_payment', array( $purchase, 'redeem_code' ), 10, 2 );
		add_filter( 'edd_success_page_redirect', array( $purchase, 'success_page_redirect' ), 10, 3 );

		add_filter( 'edd_get_cart_content_details_item_discount_amount', array( $purchase, 'item_discount' ), 11, 2 );

		$purchase->edd_process_purchase_form();
	}

	/**
	 * Total discount product.
	 *
	 * @param int|float $discount amount.
	 * @param string    $item cart item.
	 *
	 * @return string
	 */
	public function item_discount( $discount, $item ) {

		$options = array();
		if ( ! ( (int) $this->download_id === (int) $this->price_id ) && $this->price_id ) {
			$options = array(
				'price_id' => $this->price_id,
				'quantity' => 1,
			);
		}

		return EDD()->cart->get_item_price( $item['id'], $options );
	}

	/**
	 * Redirect user to purchase history page after successful code redeem.
	 *
	 * @param string $redirect Redirect url.
	 * @param string $gateway Payment gateway name.
	 * @param string $query_string extra query strings to add with url.
	 *
	 * @return string
	 */
	public function success_page_redirect( $redirect, $gateway, $query_string ) {

		$redirect = get_permalink( edd_get_option( 'purchase_history_page' ) );

		if ( $query_string ) {
			$redirect .= $query_string;
		}

		return $redirect;
	}

	/**
	 * Mark appsumo code as redeemed after payment has been inserted.
	 *
	 * @param int   $payment_id edd payment id.
	 * @param array $payment_data payment data.
	 */
	public function redeem_code( $payment_id, $payment_data ) {

		$_SESSION['appsumo_success_message'] = __( 'Success! Now you have license to the product you just redeemed.', 'appsumo' );

		if ( $this->code ) {
			appsumo_do_redeem_code( $this->code->id );
		}
	}


	/**
	 * Add edd product to cart.
	 */
	public function add_to_cart() {

		edd_empty_cart();

		$options = array();
		if ( (int) $this->download_id === (int) $this->price_id ) {
			$options = array();
		} elseif ( $this->price_id ) {
			$options = array(
				'price_id' => $this->price_id,
				'quantity' => 1,
			);
		}

		edd_add_to_cart( $this->download_id, $options );
	}



	/**
	 * Process purchase of redeeming product.
	 */
	public function edd_process_purchase_form() {

		$current_user = wp_get_current_user();

		$post_data = array(
			'edd_action'  => 'purchase',
			'edd-gateway' => 'manual',
			'action'      => 'edd_process_checkout',
			'edd_ajax'    => false,
			'edd_email'   => $current_user->user_email,
			'edd_first'   => $current_user->user_firstname,
		);

		foreach ( $post_data as $pd_name => $pd ) {
			$_POST[ $pd_name ] = $pd;
		}

		$user = array(
			'user_id'    => $current_user->ID,
			'user_email' => $current_user->user_email,
			'user_first' => $current_user->first_name,
			'user_last'  => $current_user->last_name,
		);

		// Setup user information.
		$user_info = array(
			'id'         => $user['user_id'],
			'email'      => $user['user_email'],
			'first_name' => $user['user_first'],
			'last_name'  => $user['user_last'],
			'discount'   => $this->code->code,
			'address'    => ! empty( $user['address'] ) ? $user['address'] : array(),
		);

		$auth_key = defined( 'AUTH_KEY' ) ? AUTH_KEY : '';

		// Set up the unique purchase key.
		$purchase_key = strtolower( md5( $user['user_email'] . gmdate( 'Y-m-d H:i:s' ) . $auth_key . uniqid( 'edd', true ) ) );

		// Setup purchase information.
		$purchase_data = array(
			'downloads'    => edd_get_cart_contents(),
			'fees'         => edd_get_cart_fees(),        // Any arbitrary fees that have been added to the cart.
			'subtotal'     => edd_get_cart_subtotal(),    // Amount before taxes and discounts.
			'discount'     => edd_get_cart_discounted_amount(), // Discounted amount.
			'tax'          => edd_get_cart_tax(),               // Taxed amount.
			'tax_rate'     => edd_use_taxes() ? edd_get_cart_tax_rate( false, false, false ) : 0, // Tax rate.
			'price'        => edd_get_cart_total(),    // Amount after taxes.
			'purchase_key' => $purchase_key,
			'user_email'   => $user['user_email'],
			'date'         => gmdate( 'Y-m-d H:i:s' ),
			'user_info'    => stripslashes_deep( $user_info ),
			'post_data'    => $post_data,
			'cart_details' => edd_get_cart_content_details(),
			'gateway'      => $post_data['edd-gateway'],
			'card_info'    => edd_get_purchase_cc_info(),
		);

		// Setup the data we're storing in the purchase session.
		$session_data = $purchase_data;

		// Used for showing download links to non logged-in users after purchase, and for other plugins needing purchase data.
		edd_set_purchase_session( $session_data );

		// Send info to the gateway for payment processing.
		edd_send_to_gateway( $purchase_data['gateway'], $purchase_data );
		remove_filter( 'edd_get_cart_content_details_item_discount_amount', array( $this, 'item_discount' ), 11 );

		edd_die();
	}

}

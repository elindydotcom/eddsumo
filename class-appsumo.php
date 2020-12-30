<?php
/**
 * Main plugin class
 *
 * @package   Appsumo
 * @author    Kashif
 *
 * Plugin Name: Appsumo Code Redemption
 * Plugin URI: https://wpclouddeploy.com
 * Description: This plugin helps redeem appsumo codes with easy digital downloads.
 * Author: WPCloudDeploy
 * Version: 1.1.0
 */

register_activation_hook( __FILE__, array( 'Appsumo', 'activate' ) );

register_deactivation_hook( __FILE__, array( 'Appsumo', 'deactivate' ) );

add_action( 'plugins_loaded', array( 'Appsumo', 'get_instance' ), 10, 0 );

/**
 * Main Appsumo plugin class
 */
class Appsumo {

	/**
	 * Hold instance of this class.
	 *
	 * @var null|Appsumo
	 */
	protected static $instance = null;

	/**
	 * Class constructor
	 */
	public function __construct() {

		$this->includes();
		$this->init();
	}

	/**
	 * Register hooks and plugin constants
	 */
	public function init() {

		define( 'APPSUMO_VERSION', '1.1.0' );
		define( 'APPSUMO_URL', trailingslashit( plugin_dir_url( __FILE__ ) ) );
		define( 'APPSUMO_PATH', trailingslashit( plugin_dir_path( __FILE__ ) ) );

		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );
		add_action( 'init', array( $this, 'rewrites_init' ) );
		add_filter( 'query_vars', array( $this, 'query_vars' ) );
	}

	/**
	 * Register new query vars for appsumo landing page
	 *
	 * @param array $query_vars Existing query vars.
	 *
	 * @return array
	 */
	public function query_vars( $query_vars ) {

		$query_vars[] = 'appsumo_product_slug';
		$query_vars[] = 'appsumo_code';
		return $query_vars;
	}


	/**
	 * Add new rewrite rule and appsumo landing page
	 */
	public function rewrites_init() {

		$page_id = appsumo_get_landing_page_id();

		add_rewrite_rule(
			'^apps_landing/([^/]+)(?:/([A-Za-z0-9]+))?/?$',
			'index.php?page_id=' . $page_id . '&appsumo_product_slug=$matches[1]&appsumo_code=$matches[2]',
			'top'
		);

		if ( get_option( 'appsumo_flush_rewrite_rules' ) ) {
			flush_rewrite_rules();
			delete_option( 'appsumo_flush_rewrite_rules' );
		}

	}



	/**
	 * Enqueue admin style and script file
	 */
	public function admin_enqueue_scripts() {
		wp_enqueue_script( 'appsumo-admin-script', APPSUMO_URL . 'assets/js/script.js', array( 'jquery' ), APPSUMO_VERSION, true );
		wp_enqueue_style( 'appsumo-admin-style', APPSUMO_URL . 'assets/css/style.css', array(), APPSUMO_VERSION );

	}


	/**
	 * Create new database table to store appsumo codes
	 *
	 * @global object $wpdb
	 */
	public static function activate() {
		global $wpdb;

		$table_name = $wpdb->prefix . 'appsumo_codes';
		$sql        = "CREATE TABLE IF NOT EXISTS {$table_name} (
            `id` int(20) NOT NULL AUTO_INCREMENT PRIMARY KEY,
            `download_id` int(11) NOT NULL,
			`price_id` int(11) NULL DEFAULT '0',
            `code` varchar(20) NOT NULL,
            `redeemed` int(1) NOT NULL DEFAULT '0'
             ) ENGINE=InnoDB;";

			require_once ABSPATH . 'wp-admin/includes/upgrade.php';
			dbDelta( $sql );

			$landing_page = array(
				'post_title'   => __( 'Appsumo', 'appsumo' ),
				'post_content' => '[appsumo-landingpage]',
				'post_status'  => 'publish',
				'post_author'  => 1,
				'post_type'    => 'page',
			);

			$page_id = wp_insert_post( $landing_page );

			update_option( 'appsumo_landing_page', $page_id );
			update_option( 'appsumo_flush_rewrite_rules', true );

	}


	/**
	 * Delete landing page after plugin deactivated
	 */
	public static function deactivate() {
	}

	/**
	 * Return single instance of this class.
	 */
	public static function get_instance() {

		// If the single instance hasn't been set, set it now.
		if ( null === self::$instance ) {
				self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Include plugin files
	 */
	public function includes() {
		require_once 'includes/functions.php';
		require_once 'includes/settings.php';

		require_once 'includes/class-appsumo-purchase.php';
		require_once 'includes/landing-page.php';
	}


}


<?php
/**
 * Plugin Name:       Rocktile Calculator
 * Plugin URI:        https://rocktile.eu
 * Description:       Rocktile tetőkalkulátor Vue alapú frontenddel.
 * Version:           1.1.0
 * Author:            Rocktile
 * Text Domain:       rocktile-calculator
 * Domain Path:       /languages
 * Requires at least: 6.5
 * Requires PHP:      7.4
 *
 * @package Rocktile_Calculator
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'ROCKTILE_CALCULATOR_VERSION', '1.1.0' );
define( 'ROCKTILE_CALCULATOR_FILE', __FILE__ );
define( 'ROCKTILE_CALCULATOR_PATH', plugin_dir_path( __FILE__ ) );
define( 'ROCKTILE_CALCULATOR_URL', plugin_dir_url( __FILE__ ) );

// Load includes
require_once ROCKTILE_CALCULATOR_PATH . 'includes/class-rocktile-products.php';
require_once ROCKTILE_CALCULATOR_PATH . 'includes/class-rocktile-roof-config.php';
require_once ROCKTILE_CALCULATOR_PATH . 'includes/class-rocktile-calculator.php';
require_once ROCKTILE_CALCULATOR_PATH . 'includes/class-rocktile-cart.php';
require_once ROCKTILE_CALCULATOR_PATH . 'includes/class-rocktile-order-admin.php';
require_once ROCKTILE_CALCULATOR_PATH . 'includes/class-rocktile-quote-request.php';
require_once ROCKTILE_CALCULATOR_PATH . 'includes/class-rocktile-api.php';
require_once ROCKTILE_CALCULATOR_PATH . 'includes/class-rocktile-assets.php';

/**
 * Main plugin bootstrap class.
 */
class Rocktile_Calculator {

	/**
	 * Single instance of the class.
	 *
	 * @var Rocktile_Calculator|null
	 */
	private static $instance = null;

	/**
	 * REST API instance.
	 *
	 * @var Rocktile_Calculator_API
	 */
	public $api;

	/**
	 * Cart handler instance.
	 *
	 * @var Rocktile_Calculator_Cart
	 */
	public $cart;

	/**
	 * Order Admin panel instance.
	 *
	 * @var Rocktile_Calculator_Order_Admin
	 */
	public $order_admin;

	/**
	 * Assets handler instance.
	 *
	 * @var Rocktile_Calculator_Assets
	 */
	public $assets;

	/**
	 * Main Rocktile_Calculator Instance.
	 *
	 * @return Rocktile_Calculator
	 */
	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->cart        = new Rocktile_Calculator_Cart();
		$this->order_admin = new Rocktile_Calculator_Order_Admin();
		$this->api         = new Rocktile_Calculator_API();
		$this->assets      = new Rocktile_Calculator_Assets();

		add_action( 'init', array( $this, 'register_shortcodes' ) );
		add_filter( 'upload_mimes', array( $this, 'allow_svg_uploads' ) );
		add_filter( 'wp_check_filetype_and_ext', array( $this, 'fix_svg_filetype_check' ), 10, 4 );
	}

	/**
	 * Allow SVG uploads in WordPress.
	 */
	public function allow_svg_uploads( $mimes ) {
		$mimes['svg']  = 'image/svg+xml';
		$mimes['svgz'] = 'image/svg+xml';
		return $mimes;
	}

	/**
	 * Fix WordPress 5.0+ strict filetype check for SVGs.
	 */
	public function fix_svg_filetype_check( $data, $file, $filename, $mimes ) {
		$filetype = wp_check_filetype( $filename, $mimes );
		if ( 'svg' === $filetype['ext'] ) {
			$data['ext']  = 'svg';
			$data['type'] = 'image/svg+xml';
		}
		return $data;
	}

	/**
	 * Register plugin shortcodes.
	 */
	public function register_shortcodes() {
		add_shortcode( 'rocktile_calculator', array( $this, 'render_shortcode' ) );
	}

	/**
	 * Render calculator shortcode mount container and enqueue frontend assets.
	 *
	 * @param array $atts
	 * @return string
	 */
	public function render_shortcode( $atts = array() ) {
		$this->assets->request_assets();

		if ( did_action( 'wp_enqueue_scripts' ) ) {
			$this->assets->enqueue_assets();
		}

		return '<div id="rocktile-calculator"></div>';
	}
}

/**
 * Initialize the plugin.
 */
function rocktile_calculator() {
	return Rocktile_Calculator::instance();
}

rocktile_calculator();

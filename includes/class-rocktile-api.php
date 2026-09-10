<?php
/**
 * Rocktile Calculator REST API handler.
 *
 * @package Rocktile_Calculator
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Rocktile_Calculator_API {

	/**
	 * Initialize REST API routes.
	 */
	public function __construct() {
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	/**
	 * Register REST API routes.
	 */
	public function register_routes() {
		// Ping teszt végpont
		register_rest_route(
			'rocktile/v1',
			'/ping',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'ping' ),
				'permission_callback' => '__return_true',
			)
		);

		// WooCommerce képek a termék- és szellőzésválasztóhoz.
		register_rest_route(
			'rocktile/v1',
			'/catalog',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'catalog' ),
				'permission_callback' => '__return_true',
			)
		);

		// Kalkulációs végpont
		register_rest_route(
			'rocktile/v1',
			'/calculate',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'calculate' ),
				'permission_callback' => '__return_true',
			)
		);

		// Kosárba helyezés végpont
		register_rest_route(
			'rocktile/v1',
			'/add-to-cart',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'add_to_cart' ),
				'permission_callback' => '__return_true',
			)
		);

		// Szakértői ajánlatkérés / felülvizsgálat végpont
		register_rest_route(
			'rocktile/v1',
			'/request-review',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'request_review' ),
				'permission_callback' => '__return_true',
			)
		);
	}

	/**
	 * Request review endpoint callback.
	 *
	 * @param WP_REST_Request $request
	 * @return WP_REST_Response|WP_Error
	 */
	public function request_review( $request ) {
		return Rocktile_Calculator_Quote_Request::handle_request( $request );
	}

	/**
	 * Ping endpoint callback.
	 *
	 * @param WP_REST_Request $request
	 * @return WP_REST_Response
	 */
	public function ping( $request ) {
		return new WP_REST_Response(
			array(
				'success' => true,
				'message' => 'Rocktile Calculator API működik',
			),
			200
		);
	}

	/**
	 * Product images used by the calculator selection steps.
	 *
	 * @return WP_REST_Response
	 */
	public function catalog() {
		return new WP_REST_Response( Rocktile_Calculator_Products::get_frontend_catalog(), 200 );
	}

	/**
	 * Calculate endpoint callback.
	 *
	 * @param WP_REST_Request $request
	 * @return WP_REST_Response|WP_Error
	 */
	public function calculate( $request ) {
		$params = $request->get_json_params();

		// Ha üres vagy nem JSON a body, fallback a sima request paraméterekre
		if ( empty( $params ) ) {
			$params = $request->get_params();
		}

		// 1. Szerveroldali validáció
		$validated = Rocktile_Calculator_Engine::validate_input( $params );
		if ( is_wp_error( $validated ) ) {
			return $validated;
		}

		// 2. Számítás végrehajtása
		$result = Rocktile_Calculator_Engine::calculate( $validated );

		return new WP_REST_Response( $result, 200 );
	}

	/**
	 * Add-to-cart endpoint callback.
	 *
	 * @param WP_REST_Request $request
	 * @return WP_REST_Response|WP_Error
	 */
	public function add_to_cart( $request ) {
		return Rocktile_Calculator_Cart::handle_add_to_cart( $request );
	}
}

<?php
/**
 * Rocktile Calculator Cart Handler.
 *
 * Manages transactional add-to-cart operations, WooCommerce session initialization,
 * unique calculation IDs, and order item metadata preservation.
 *
 * @package Rocktile_Calculator
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Rocktile_Calculator_Cart {

	/**
	 * Initialize hooks for WooCommerce cart & order metadata.
	 */
	public function __construct() {
		// Hook: Save calculation ID to order line item during checkout
		add_action( 'woocommerce_checkout_create_order_line_item', array( $this, 'add_order_item_meta' ), 10, 4 );

		// Hook: Save full calculation metadata to order
		add_action( 'woocommerce_checkout_order_created', array( $this, 'save_order_calculations' ), 10, 1 );

		// Hook: Display calculation ID badge in cart item review
		add_filter( 'woocommerce_get_item_data', array( $this, 'display_cart_item_data' ), 10, 2 );
	}

	/**
	 * Ensure WooCommerce Cart and Session are properly loaded in REST context.
	 */
	public static function ensure_cart_session() {
		if ( ! function_exists( 'WC' ) ) {
			return;
		}

		if ( is_null( WC()->session ) ) {
			$session_class = apply_filters( 'woocommerce_session_handler', 'WC_Session_Handler' );
			WC()->session = new $session_class();
			WC()->session->init();
		}

		if ( ! WC()->session->has_session() ) {
			WC()->session->set_customer_session_cookie( true );
		}

		if ( is_null( WC()->customer ) ) {
			WC()->customer = new WC_Customer( get_current_user_id(), true );
		}

		if ( is_null( WC()->cart ) ) {
			if ( function_exists( 'wc_load_cart' ) ) {
				wc_load_cart();
			} else {
				WC()->cart = new WC_Cart();
			}
		}
	}

	/**
	 * Handle POST /wp-json/rocktile/v1/add-to-cart request.
	 *
	 * @param WP_REST_Request $request
	 * @return WP_REST_Response|WP_Error
	 */
	public static function handle_add_to_cart( $request ) {
		self::ensure_cart_session();

		if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
			return new WP_Error( 'wc_not_loaded', 'A WooCommerce kosár rendszer nem érhető el.', array( 'status' => 500 ) );
		}

		$params = $request->get_json_params();
		if ( empty( $params ) ) {
			$params = $request->get_params();
		}

		// 1. Idempotency / Double submit ellenőrzés
		$requestId = ! empty( $params['requestId'] ) ? sanitize_text_field( $params['requestId'] ) : '';
		if ( ! empty( $requestId ) && WC()->session ) {
			$processed = WC()->session->get( 'rocktile_processed_requests', array() );
			if ( isset( $processed[ $requestId ] ) ) {
				return new WP_REST_Response( $processed[ $requestId ], 200 );
			}
		}

		// 2. Szerveroldali nyers bemenet validáció
		$validated = Rocktile_Calculator_Engine::validate_input( $params );
		if ( is_wp_error( $validated ) ) {
			return $validated;
		}

		// 3. Egyedi tetőforma nem helyezhető automatikusan kosárba
		if ( ! empty( $validated['roofConfig']['requiresManualReview'] ) ) {
			return new WP_Error(
				'rocktile_manual_review_required',
				'Az egyedi tetőforma szakértői ellenőrzést igényel, ezért nem helyezhető automatikusan a kosárba.',
				array( 'status' => 400 )
			);
		}

		// 4. Szerveroldali újraszámítás (authoritative kalkuláció)
		$calcResult = Rocktile_Calculator_Engine::calculate( $validated );
		if ( empty( $calcResult['items'] ) ) {
			return new WP_Error( 'empty_calculation', 'A kalkuláció nem tartalmazott megvásárolható tételeket.', array( 'status' => 400 ) );
		}

		// Ha a frontendből szerkesztett tételek (customItems) érkeztek:
		$itemsToCart = array();
		if ( ! empty( $validated['customItems'] ) && is_array( $validated['customItems'] ) ) {
			$customMap = array();
			foreach ( $validated['customItems'] as $cItem ) {
				if ( isset( $cItem['key'] ) && isset( $cItem['quantity'] ) && (int) $cItem['quantity'] > 0 ) {
					$customMap[ $cItem['key'] ] = (int) $cItem['quantity'];
				}
			}

			foreach ( $calcResult['items'] as $origItem ) {
				$k = $origItem['key'];
				if ( isset( $customMap[ $k ] ) ) {
					$origItem['quantity'] = $customMap[ $k ];
					if ( 'baseTile' === $k ) {
						$origItem['pieces'] = $origItem['quantity'] * Rocktile_Calculator_Engine::BASE_TILE_PER_PACKAGE;
					}
					$itemsToCart[] = Rocktile_Calculator_Products::enrich_item( $origItem );
				}
			}
		} else {
			$itemsToCart = $calcResult['items'];
		}

		if ( empty( $itemsToCart ) ) {
			return new WP_Error( 'empty_cart_items', 'Nincs kosárba helyezhető tétel.', array( 'status' => 400 ) );
		}

		// 5. Termékek előzetes vizsgálata (minden termék elérhetőségének ellenőrzése)
		foreach ( $itemsToCart as $item ) {
			$productId = (int) $item['productId'];
			$product   = wc_get_product( $productId );

			if ( ! $product ) {
				return new WP_Error(
					'product_not_found',
					sprintf( 'A kalkuláció nem helyezhető kosárba, mert a(z) "%s" termék (ID: %d) nem található.', $item['name'], $productId ),
					array( 'status' => 400 )
				);
			}

			if ( 'publish' !== $product->get_status() ) {
				return new WP_Error(
					'product_not_published',
					sprintf( 'A kalkuláció nem helyezhető kosárba, mert a(z) "%s" termék jelenleg nem aktív a webáruházban.', $product->get_name() ),
					array( 'status' => 400 )
				);
			}

			if ( ! $product->is_purchasable() ) {
				return new WP_Error(
					'product_not_purchasable',
					sprintf( 'A kalkuláció nem helyezhető kosárba, mert a(z) "%s" termék jelenleg nem megvásárolható.', $product->get_name() ),
					array( 'status' => 400 )
				);
			}

			if ( ! $product->is_in_stock() ) {
				return new WP_Error(
					'product_out_of_stock',
					sprintf( 'A kalkuláció nem helyezhető kosárba, mert a(z) "%s" termék jelenleg nincs készleten.', $product->get_name() ),
					array( 'status' => 400 )
				);
			}
		}

		// 6. Egyedi kalkulációazonosító generálása
		$calcUuid      = function_exists( 'wp_generate_uuid4' ) ? wp_generate_uuid4() : uniqid( 'rt_', true );
		$calculationId = 'RT-' . gmdate( 'Ymd' ) . '-' . strtoupper( substr( md5( $calcUuid ), 0, 6 ) );

		// 7. Tranzakciós kosárba helyezés (all-or-nothing stratégia, meglévő kosár ürítése TILOS)
		$added_cart_item_keys = array();
		$has_error            = false;
		$error_message        = '';

		foreach ( $itemsToCart as $item ) {
			$productId = Rocktile_Calculator_Products::resolve_product_id( (int) $item['productId'] );
			// Alapcserépnél a WooCommerce-ben a termék darabáras, így a darabszám kerül a kosárba (pl. 34 csomag = 408 db)
			$quantity  = ( 'baseTile' === $item['key'] && ! empty( $item['pieces'] ) ) ? (int) $item['pieces'] : (int) $item['quantity'];

			$product        = wc_get_product( $productId );
			$variationId    = 0;
			$variationAttrs = array();

			if ( $product && $product->is_type( 'variation' ) ) {
				$variationId    = $productId;
				$productId      = $product->get_parent_id();
				$variationAttrs = $product->get_variation_attributes();
			}

			// Egyedi cart_item_data: megakadályozza az azonos termékek összeolvadását más kalkulációkkal
			$cart_item_data = array(
				'rocktile_calculation_id'   => $calculationId,
				'rocktile_item_key'         => $item['key'],
				'rocktile_product_name'     => $item['name'],
				'rocktile_unit'             => $item['unit'],
				'rocktile_display_qty'      => ( 'baseTile' === $item['key'] ) ? sprintf( '%d csomag (%d db)', $item['quantity'], $quantity ) : sprintf( '%d %s', $quantity, $item['unit'] ),
				'rocktile_unique_key'       => md5( $calculationId . '_' . $item['key'] . '_' . $item['productId'] ),
			);

			$cart_item_key = WC()->cart->add_to_cart( $productId, $quantity, $variationId, $variationAttrs, $cart_item_data );

			if ( false === $cart_item_key || is_wp_error( $cart_item_key ) ) {
				$has_error      = true;
				$error_message  = sprintf( 'Nem sikerült kosárba helyezni a(z) "%s" terméket.', $item['name'] );
				break;
			}

			$added_cart_item_keys[] = $cart_item_key;
		}

		// Rollback, ha bármelyik tétel sikertelen volt
		if ( $has_error ) {
			foreach ( $added_cart_item_keys as $key ) {
				WC()->cart->remove_cart_item( $key );
			}
			return new WP_Error( 'cart_add_failed', $error_message, array( 'status' => 400 ) );
		}

		// 8. Kalkuláció mentése sessionbe
		if ( WC()->session ) {
			WC()->session->set(
				'rocktile_calc_' . $calculationId,
				array(
					'calculationId' => $calculationId,
					'createdAt'     => current_time( 'mysql' ),
					'input'         => $validated,
					'result'        => $calcResult,
				)
			);

			// Idempotency bejegyzés mentése
			if ( ! empty( $requestId ) ) {
				$processed               = WC()->session->get( 'rocktile_processed_requests', array() );
				$processed[ $requestId ] = array(
					'success'       => true,
					'calculationId' => $calculationId,
					'itemsAdded'    => count( $calcResult['items'] ),
					'cartItemCount' => WC()->cart->get_cart_contents_count(),
					'cartUrl'       => wc_get_cart_url(),
				);
				WC()->session->set( 'rocktile_processed_requests', $processed );
			}
		}

		// 9. Kosár kalkulációk és session perzisztálása
		if ( WC()->session ) {
			if ( ! WC()->session->has_session() ) {
				WC()->session->set_customer_session_cookie( true );
			}
			if ( WC()->cart ) {
				WC()->cart->calculate_totals();
				WC()->cart->maybe_set_cart_cookies();
			}
			WC()->session->save_data();
		}

		return new WP_REST_Response(
			array(
				'success'       => true,
				'calculationId' => $calculationId,
				'itemsAdded'    => count( $calcResult['items'] ),
				'cartItemCount' => WC()->cart->get_cart_contents_count(),
				'cartUrl'       => wc_get_cart_url(),
			),
			200
		);
	}

	/**
	 * Attach calculation ID to order line item during checkout.
	 *
	 * @param WC_Order_Item_Product $item
	 * @param string                $cart_item_key
	 * @param array                 $values
	 * @param WC_Order              $order
	 */
	public function add_order_item_meta( $item, $cart_item_key, $values, $order ) {
		if ( ! empty( $values['rocktile_calculation_id'] ) ) {
			$item->add_meta_data( 'rocktile_calculation_id', $values['rocktile_calculation_id'], true );
		}
		if ( ! empty( $values['rocktile_item_key'] ) ) {
			$item->add_meta_data( '_rocktile_item_key', $values['rocktile_item_key'], true );
		}
		if ( ! empty( $values['rocktile_unit'] ) ) {
			$item->add_meta_data( '_rocktile_unit', $values['rocktile_unit'], true );
		}
	}

	/**
	 * Attach full calculations data to the created WooCommerce Order.
	 *
	 * @param WC_Order $order
	 */
	public function save_order_calculations( $order ) {
		if ( ! $order || ! WC()->session ) {
			return;
		}

		$calculations = array();
		foreach ( $order->get_items() as $item ) {
			$calcId = $item->get_meta( 'rocktile_calculation_id' );
			if ( $calcId ) {
				$sessionData = WC()->session->get( 'rocktile_calc_' . $calcId );
				if ( $sessionData && ! isset( $calculations[ $calcId ] ) ) {
					$calculations[ $calcId ] = $sessionData;
				}
			}
		}

		if ( ! empty( $calculations ) ) {
			$order->update_meta_data( '_rocktile_calculations', $calculations );
			$order->save();
		}
	}

	/**
	 * Display metadata on cart and checkout pages.
	 *
	 * @param array $item_data
	 * @param array $cart_item
	 * @return array
	 */
	public function display_cart_item_data( $item_data, $cart_item ) {
		if ( ! empty( $cart_item['rocktile_calculation_id'] ) ) {
			$item_data[] = array(
				'key'   => 'Kalkuláció',
				'value' => $cart_item['rocktile_calculation_id'],
			);
		}
		return $item_data;
	}
}

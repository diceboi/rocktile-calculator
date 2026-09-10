<?php
/**
 * Rocktile Product Catalog & WooCommerce Mapping.
 *
 * Authoritative mapping between logical calculator product elements,
 * colors, and WooCommerce products / variations / SKUs.
 *
 * @package Rocktile_Calculator
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Rocktile_Calculator_Products {

	/**
	 * Actively enabled colors in the calculator.
	 * Authoritative list: only these colors are permitted in active calculations & cart.
	 *
	 * @var array
	 */
	const ENABLED_COLORS = array(
		'shadow-rock',        // Antracit (Shadow Rock W8318)
		'crimson-ember',      // Sötétvörös (Crimson Ember W2218)
		'earthwood-chestnut', // Barna (Earthwood Chestnut W4618)
	);

	/**
	 * Map of logical product items and colors to WooCommerce product IDs and SKUs.
	 *
	 * @var array
	 */
	const PRODUCT_CATALOG = array(
		'baseTile' => array(
			'shadow-rock' => array(
				'productId' => 5091,
				'sku'       => 'ROCK97289',
				'name'      => 'ROCKTILE Classic Bond alapcserép (Shadow Rock W8318 antracit)',
				'unit'      => 'csomag',
			),
			'desert-sunset' => array(
				'productId' => 5090,
				'sku'       => 'ROCK97288',
				'name'      => 'ROCKTILE Classic Bond alapcserép (Desert Sunset W2188 vörös)',
				'unit'      => 'csomag',
			),
			'crimson-ember' => array(
				'productId' => 5089,
				'sku'       => 'RCB97311',
				'name'      => 'ROCKTILE Classic Bond alapcserép (Crimson Ember W2218 sötétvörös)',
				'unit'      => 'csomag',
			),
			'earthwood-chestnut' => array(
				'productId' => 5092,
				'sku'       => 'RCB97313',
				'name'      => 'ROCKTILE Classic Bond alapcserép (Earthwood Chestnut W4618 barna)',
				'unit'      => 'csomag',
			),
		),

		'ridgeTile' => array(
			'shadow-rock' => array(
				'productId' => 5096,
				'sku'       => 'ROCK97291',
				'name'      => 'ROCKTILE íves kúpcserép (Shadow Rock W8318 antracit)',
				'unit'      => 'db',
			),
			'desert-sunset' => array(
				'productId' => 5095,
				'sku'       => 'ROCK97290',
				'name'      => 'ROCKTILE íves kúpcserép (Desert Sunset W2188 vörös)',
				'unit'      => 'db',
			),
			'crimson-ember' => array(
				'productId' => 5094,
				'sku'       => 'RK97315',
				'name'      => 'ROCKTILE íves kúpcserép (Crimson Ember W2218 sötétvörös)',
				'unit'      => 'db',
			),
			'earthwood-chestnut' => array(
				'productId' => 5097,
				'sku'       => 'RK97316',
				'name'      => 'ROCKTILE íves kúpcserép (Earthwood Chestnut W4618 barna)',
				'unit'      => 'db',
			),
		),

		'starterRidge' => array(
			'shadow-rock' => array(
				'productId' => 5104,
				'sku'       => 'ROCK97293',
				'name'      => 'ROCKTILE kezdőkúp íves kúpcseréphez (Shadow Rock W8318 antracit)',
				'unit'      => 'db',
			),
			'desert-sunset' => array(
				'productId' => 5103,
				'sku'       => 'ROCK97292',
				'name'      => 'ROCKTILE kezdőkúp íves kúpcseréphez (Desert Sunset W2188 vörös)',
				'unit'      => 'db',
			),
			'crimson-ember' => array(
				'productId' => 5102,
				'sku'       => 'RK97317',
				'name'      => 'ROCKTILE kezdőkúp íves kúpcseréphez (Crimson Ember W2218 sötétvörös)',
				'unit'      => 'db',
			),
			'earthwood-chestnut' => array(
				'productId' => 5105,
				'sku'       => 'RK97318',
				'name'      => 'ROCKTILE kezdőkúp íves kúpcseréphez (Earthwood Chestnut W4618 barna)',
				'unit'      => 'db',
			),
		),

		'ridgeEndCap' => array(
			'shadow-rock' => array(
				'productId' => 5100,
				'sku'       => 'ROCK97295',
				'name'      => 'ROCKTILE véglap íves kúpcseréphez (Shadow Rock W8318 antracit)',
				'unit'      => 'db',
			),
			'desert-sunset' => array(
				'productId' => 5099,
				'sku'       => 'ROCK97294',
				'name'      => 'ROCKTILE véglap íves kúpcseréphez (Desert Sunset W2188 vörös)',
				'unit'      => 'db',
			),
			'crimson-ember' => array(
				'productId' => 5098,
				'sku'       => 'RK97319',
				'name'      => 'ROCKTILE véglap íves kúpcseréphez (Crimson Ember W2218 sötétvörös)',
				'unit'      => 'db',
			),
			'earthwood-chestnut' => array(
				'productId' => 5101,
				'sku'       => 'RK97320',
				'name'      => 'ROCKTILE véglap íves kúpcseréphez (Earthwood Chestnut W4618 barna)',
				'unit'      => 'db',
			),
		),

		'eaves' => array(
			'shadow-rock' => array(
				'productId' => 5133,
				'sku'       => 'RSZ97340',
				'name'      => 'ROCKTILE Ereszszegély 1270mm (Shadow Rock W8318 antracit)',
				'unit'      => 'db',
			),
			'desert-sunset' => array(
				'productId' => 5132,
				'sku'       => 'RSZ97339',
				'name'      => 'ROCKTILE Ereszszegély 1270mm (Desert Sunset W2188 vörös)',
				'unit'      => 'db',
			),
			'crimson-ember' => array(
				'productId' => 5131,
				'sku'       => 'RSZ97338',
				'name'      => 'ROCKTILE Ereszszegély 1270mm (Crimson Ember W2218 sötétvörös)',
				'unit'      => 'db',
			),
			'earthwood-chestnut' => array(
				'productId' => 5134,
				'sku'       => 'RSZ97341',
				'name'      => 'ROCKTILE Ereszszegély 1270mm (Earthwood Chestnut W4618 barna)',
				'unit'      => 'db',
			),
		),

		'valley' => array(
			'shadow-rock' => array(
				'productId' => 5152,
				'sku'       => 'RSZ97359',
				'name'      => 'ROCKTILE Süllyesztett vápaelem 1270mm (Shadow Rock W8318 antracit)',
				'unit'      => 'db',
			),
			'desert-sunset' => array(
				'productId' => 5151,
				'sku'       => 'RSZ97358',
				'name'      => 'ROCKTILE Süllyesztett vápaelem 1270mm (Desert Sunset W2188 vörös)',
				'unit'      => 'db',
			),
			'crimson-ember' => array(
				'productId' => 5641,
				'sku'       => 'RSZ97360',
				'name'      => 'ROCKTILE Süllyesztett vápaelem 1270mm (Crimson Ember W2218 sötétvörös)',
				'unit'      => 'db',
			),
			'earthwood-chestnut' => array(
				'productId' => 5646,
				'sku'       => 'RSZ97361',
				'name'      => 'ROCKTILE Süllyesztett vápaelem 1270mm (Earthwood Chestnut W4618 barna)',
				'unit'      => 'db',
			),
		),

		'vergeUnder' => array(
			'shadow-rock' => array(
				'productId' => 5129,
				'sku'       => 'RSZ97332',
				'name'      => 'ROCKTILE Oromszegély 1270mm (Shadow Rock W8318 antracit)',
				'unit'      => 'db',
			),
			'desert-sunset' => array(
				'productId' => 5128,
				'sku'       => 'ROCK97303',
				'name'      => 'ROCKTILE Oromszegély 1270mm (Desert Sunset W2188 vörös)',
				'unit'      => 'db',
			),
			'crimson-ember' => array(
				'productId' => 5127,
				'sku'       => 'RSZ97331',
				'name'      => 'ROCKTILE Oromszegély 1270mm (Crimson Ember W2218 sötétvörös)',
				'unit'      => 'db',
			),
			'earthwood-chestnut' => array(
				'productId' => 5130,
				'sku'       => 'RSZ97333',
				'name'      => 'ROCKTILE Oromszegély 1270mm (Earthwood Chestnut W4618 barna)',
				'unit'      => 'db',
			),
		),

		'vergeOver' => array(
			'shadow-rock' => array(
				'productId' => 5125,
				'sku'       => 'RSZ97336',
				'name'      => 'ROCKTILE Oromdeszka szegélylemez 1270mm (Shadow Rock W8318 antracit)',
				'unit'      => 'db',
			),
			'desert-sunset' => array(
				'productId' => 5124,
				'sku'       => 'RSZ97335',
				'name'      => 'ROCKTILE Oromdeszka szegélylemez 1270mm (Desert Sunset W2188 vörös)',
				'unit'      => 'db',
			),
			'crimson-ember' => array(
				'productId' => 5123,
				'sku'       => 'RSZ97334',
				'name'      => 'ROCKTILE Oromdeszka szegélylemez 1270mm (Crimson Ember W2218 sötétvörös)',
				'unit'      => 'db',
			),
			'earthwood-chestnut' => array(
				'productId' => 5126,
				'sku'       => 'RSZ97337',
				'name'      => 'ROCKTILE Oromdeszka szegélylemez 1270mm (Earthwood Chestnut W4618 barna)',
				'unit'      => 'db',
			),
		),

		'ridgeVent' => array(
			'shadow-rock' => array(
				'productId' => 5119,
				'sku'       => 'RSZ97349',
				'name'      => 'ROCKTILE Kúpszellőző elem (Shadow Rock W8318 antracit)',
				'unit'      => 'db',
			),
			'desert-sunset' => array(
				'productId' => 5118,
				'sku'       => 'RSZ97348',
				'name'      => 'ROCKTILE Kúpszellőző elem (Desert Sunset W2188 vörös)',
				'unit'      => 'db',
			),
			'crimson-ember' => array(
				'productId' => 5117,
				'sku'       => 'RSZ97347',
				'name'      => 'ROCKTILE Kúpszellőző elem (Crimson Ember W2218 sötétvörös)',
				'unit'      => 'db',
			),
			'earthwood-chestnut' => array(
				'productId' => 5120,
				'sku'       => 'RSZ97350',
				'name'      => 'ROCKTILE Kúpszellőző elem (Earthwood Chestnut W4618 barna)',
				'unit'      => 'db',
			),
		),

		'repairKit' => array(
			'shadow-rock' => array(
				'productId' => 5116,
				'sku'       => 'ROCK97301',
				'name'      => 'ROCKTILE javítókészlet (Shadow Rock W8318 antracit)',
				'unit'      => 'db',
			),
			'desert-sunset' => array(
				'productId' => 5115,
				'sku'       => 'ROCK97300',
				'name'      => 'ROCKTILE javítókészlet (Desert Sunset W2188 vörös)',
				'unit'      => 'db',
			),
			'crimson-ember' => array(
				'productId' => 5114,
				'sku'       => 'RK97325',
				'name'      => 'ROCKTILE javítókészlet (Crimson Ember W2218 sötétvörös)',
				'unit'      => 'db',
			),
			'earthwood-chestnut' => array(
				'productId' => 5117,
				'sku'       => 'RK97326',
				'name'      => 'ROCKTILE javítókészlet (Earthwood Chestnut W4618 barna)',
				'unit'      => 'db',
			),
		),

		'nail' => array(
			'shadow-rock' => array(
				'productId' => 6147,
				'sku'       => '',
				'name'      => 'Rögzítőszeg (Shadow Rock W8318 antracit)',
				'unit'      => 'kg',
			),
			'desert-sunset' => array(
				'productId' => 6144,
				'sku'       => '',
				'name'      => 'Rögzítőszeg (Desert Sunset W2188 vörös)',
				'unit'      => 'kg',
			),
			'crimson-ember' => array(
				'productId' => 6145,
				'sku'       => '',
				'name'      => 'Rögzítőszeg (Crimson Ember W2218 sötétvörös)',
				'unit'      => 'kg',
			),
			'earthwood-chestnut' => array(
				'productId' => 6146,
				'sku'       => '',
				'name'      => 'Rögzítőszeg (Earthwood Chestnut W4618 barna)',
				'unit'      => 'kg',
			),
		),

		'screw' => array(
			'shadow-rock' => array(
				'productId' => 5120,
				'sku'       => 'RK97329',
				'name'      => 'LW-R önfúró rögzítő csavar 4,8×35mm (Shadow Rock W8318 antracit)',
				'unit'      => 'doboz',
			),
			'desert-sunset' => array(
				'productId' => 5119,
				'sku'       => 'RK97328',
				'name'      => 'LW-R önfúró rögzítő csavar 4,8×35mm (Desert Sunset W2188 vörös)',
				'unit'      => 'doboz',
			),
			'crimson-ember' => array(
				'productId' => 5118,
				'sku'       => 'RK97327',
				'name'      => 'LW-R önfúró rögzítő csavar 4,8×35mm (Crimson Ember W2218 sötétvörös)',
				'unit'      => 'doboz',
			),
			'earthwood-chestnut' => array(
				'productId' => 5121,
				'sku'       => 'RK97330',
				'name'      => 'ROCKTILE rögzítő csavar 4,8×35mm (Earthwood Chestnut W4618 barna)',
				'unit'      => 'doboz',
			),
		),

		'ventilation' => array(
			'shadow-rock' => array(
				'productId' => 5108,
				'sku'       => 'RK97323',
				'name'      => 'ROCKTILE strangszellőző (Shadow Rock W8318 antracit)',
				'unit'      => 'db',
			),
			'desert-sunset' => array(
				'productId' => 5107,
				'sku'       => 'RK97322',
				'name'      => 'ROCKTILE strangszellőző (Desert Sunset W2188 vörös)',
				'unit'      => 'db',
			),
			'crimson-ember' => array(
				'productId' => 5106,
				'sku'       => 'RK97321',
				'name'      => 'ROCKTILE strangszellőző (Crimson Ember W2218 sötétvörös)',
				'unit'      => 'db',
			),
			'earthwood-chestnut' => array(
				'productId' => 5109,
				'sku'       => 'RK97324',
				'name'      => 'ROCKTILE strangszellőző (Earthwood Chestnut W4618 barna)',
				'unit'      => 'db',
			),
		),
		'flatSheet' => array(
			'shadow-rock' => array(
				'productId' => 5112,
				'sku'       => 'RSZ97345',
				'name'      => 'ROCKTILE Síklemez 450×1270mm (Shadow Rock W8318 antracit)',
				'unit'      => 'db',
			),
			'desert-sunset' => array(
				'productId' => 5111,
				'sku'       => 'RSZ97344',
				'name'      => 'ROCKTILE Síklemez 450×1270mm (Desert Sunset W2188 vörös)',
				'unit'      => 'db',
			),
			'crimson-ember' => array(
				'productId' => 5110,
				'sku'       => 'RSZ97343',
				'name'      => 'ROCKTILE Síklemez 450×1270mm (Crimson Ember W2218 sötétvörös)',
				'unit'      => 'db',
			),
			'earthwood-chestnut' => array(
				'productId' => 5113,
				'sku'       => 'RSZ97346',
				'name'      => 'ROCKTILE Síklemez 450×1270mm (Earthwood Chestnut W4618 barna)',
				'unit'      => 'db',
			),
		),
	);

	/**
	 * Local fallback mapping when local database product IDs differ from production.
	 *
	 * @var array<int, int>
	 */
	const LOCAL_PRODUCT_ID_FALLBACKS = array(
		6147 => 6125, // Shadow Rock W8318 (antracit)
		6146 => 6124, // Earthwood Chestnut W4618 (barna)
		6145 => 6121, // Crimson Ember W2218 (sötétvörös)
		6144 => 6122, // Desert Sunset W2188 (vörös)
	);

	/**
	 * Resolve a product ID, automatically falling back to local ID if the primary ID is not a valid WooCommerce product.
	 *
	 * @param int $product_id
	 * @return int
	 */
	public static function resolve_product_id( $product_id ) {
		$product_id = (int) $product_id;
		if ( $product_id <= 0 ) {
			return $product_id;
		}

		if ( function_exists( 'wc_get_product' ) ) {
			$product = wc_get_product( $product_id );
			if ( $product && is_a( $product, 'WC_Product' ) ) {
				return $product_id;
			}
		}

		if ( isset( self::LOCAL_PRODUCT_ID_FALLBACKS[ $product_id ] ) ) {
			return self::LOCAL_PRODUCT_ID_FALLBACKS[ $product_id ];
		}

		return $product_id;
	}

	/**
	 * Get product item mapping for a specific element key and color.
	 *
	 * @param string $itemKey  e.g. 'baseTile', 'ridgeTile', 'starterRidge', etc.
	 * @param string $colorKey e.g. 'shadow-rock', 'desert-sunset', etc.
	 * @return array|null
	 */
	public static function get_item( $itemKey, $colorKey ) {
		if ( isset( self::PRODUCT_CATALOG[ $itemKey ][ $colorKey ] ) ) {
			$item = self::PRODUCT_CATALOG[ $itemKey ][ $colorKey ];
			$item['productId'] = self::resolve_product_id( $item['productId'] );
			return $item;
		}
		return null;
	}

	/**
	 * Check if a color is actively enabled for calculations and ordering.
	 *
	 * @param string $colorKey
	 * @return bool
	 */
	public static function is_enabled_color( $colorKey ) {
		return in_array( $colorKey, self::ENABLED_COLORS, true );
	}

	/**
	 * Check if a color is a known catalog color key.
	 *
	 * @param string $colorKey
	 * @return bool
	 */
	public static function is_valid_color( $colorKey ) {
		$valid_colors = array( 'shadow-rock', 'desert-sunset', 'crimson-ember', 'earthwood-chestnut' );
		return in_array( $colorKey, $valid_colors, true );
	}

	/**
	 * Check if a product family ID is valid.
	 *
	 * @param string $productId
	 * @return bool
	 */
	public static function is_valid_product( $productId ) {
		return in_array( $productId, array( 'classic-bond', 'classic' ), true );
	}

	/**
	 * Get human readable product family name.
	 *
	 * @param string $key
	 * @return string
	 */
	public static function get_product_family_name( $key ) {
		$map = array(
			'classic-bond' => 'Classic Bond',
			'classic'      => 'Classic Bond',
		);
		return isset( $map[ $key ] ) ? $map[ $key ] : (string) $key;
	}

	/**
	 * Get human readable color name with code.
	 *
	 * @param string $key
	 * @return string
	 */
	public static function get_color_name( $key ) {
		$map = array(
			'shadow-rock'        => 'Antracit – Shadow Rock W8318',
			'desert-sunset'      => 'Vörös – Desert Sunset W2188',
			'crimson-ember'      => 'Sötétvörös – Crimson Ember W2218',
			'earthwood-chestnut' => 'Barna – Earthwood Chestnut W4618',
		);
		return isset( $map[ $key ] ) ? $map[ $key ] : (string) $key;
	}

	/**
	 * Add live WooCommerce presentation and pricing data to a calculator item.
	 *
	 * @param array $item Calculator catalog item.
	 * @return array
	 */
	public static function enrich_item( $item ) {
		$item['image']              = '';
		$item['permalink']          = '';
		$item['unitPrice']          = null;
		$item['unitPriceFormatted'] = '';
		$item['piecePrice']          = null;
		$item['piecePriceFormatted'] = '';
		$item['lineTotal']          = null;
		$item['lineTotalFormatted'] = '';

		if ( ! function_exists( 'wc_get_product' ) || empty( $item['productId'] ) ) {
			return $item;
		}

		$item['productId'] = self::resolve_product_id( $item['productId'] );
		$product = wc_get_product( (int) $item['productId'] );
		if ( ! $product ) {
			return $item;
		}

		$image_id = $product->get_image_id();
		if ( ! $image_id && $product->is_type( 'variation' ) ) {
			$parent = wc_get_product( $product->get_parent_id() );
			$image_id = $parent ? $parent->get_image_id() : 0;
		}

		if ( $image_id ) {
			$image_url = wp_get_attachment_image_url( $image_id, 'woocommerce_thumbnail' );
			$item['image'] = $image_url ? $image_url : '';
		}

		$item['permalink'] = get_permalink( $product->get_id() );

		if ( '' !== $product->get_price() ) {
			$quantity = isset( $item['quantity'] ) ? max( 1, (float) $item['quantity'] ) : 1;
			$raw_wc_price = (float) wc_get_price_to_display( $product );

			// Ha alapcserép és csomag az egység, a WooCommerce-ben a termék ára darabár!
			// 1 csomag = 12 db cserép -> Csomag egységár = 12 * darabár
			$multiplier = ( isset( $item['piecesPerPkg'] ) && $item['piecesPerPkg'] > 1 ) ? (int) $item['piecesPerPkg'] : 1;
			
			$unit_price = $raw_wc_price * $multiplier;
			$line_total = $unit_price * $quantity;

			$item['unitPrice']          = $unit_price;
			$item['unitPriceFormatted'] = self::format_price( $unit_price );
			if ( $multiplier > 1 ) {
				$item['piecePrice']          = $raw_wc_price;
				$item['piecePriceFormatted'] = self::format_price( $raw_wc_price );
			}
			$item['lineTotal']          = $line_total;
			$item['lineTotalFormatted'] = self::format_price( $line_total );
		}

		return $item;
	}

	/**
	 * Return the product images needed before the calculation step.
	 *
	 * @return array
	 */
	public static function get_frontend_catalog() {
		$catalog = array(
			'products'    => array(),
			'ventilation' => array( 'colors' => array() ),
			'vergeUnder'  => array( 'colors' => array() ),
			'vergeOver'   => array( 'colors' => array() ),
		);

		foreach ( self::ENABLED_COLORS as $color ) {
			$base_item        = self::get_item( 'baseTile', $color );
			$vent_item        = self::get_item( 'ventilation', $color );
			$verge_under_item = self::get_item( 'vergeUnder', $color );
			$verge_over_item  = self::get_item( 'vergeOver', $color );

			if ( $base_item ) {
				$base_item['quantity'] = 1;
				$base_item = self::enrich_item( $base_item );
				$catalog['products']['classic-bond']['colors'][ $color ] = array(
					'image'     => $base_item['image'],
					'permalink' => $base_item['permalink'],
				);

				if ( empty( $catalog['products']['classic-bond']['image'] ) ) {
					$catalog['products']['classic-bond']['image'] = $base_item['image'];
				}
			}

			if ( $vent_item ) {
				$vent_item['quantity'] = 1;
				$vent_item = self::enrich_item( $vent_item );
				$catalog['ventilation']['colors'][ $color ] = array(
					'image'     => $vent_item['image'],
					'permalink' => $vent_item['permalink'],
				);
			}

			if ( $verge_under_item ) {
				$verge_under_item['quantity'] = 1;
				$verge_under_item = self::enrich_item( $verge_under_item );
				$catalog['vergeUnder']['colors'][ $color ] = array(
					'image'     => $verge_under_item['image'],
					'permalink' => $verge_under_item['permalink'],
				);
			}

			if ( $verge_over_item ) {
				$verge_over_item['quantity'] = 1;
				$verge_over_item = self::enrich_item( $verge_over_item );
				$catalog['vergeOver']['colors'][ $color ] = array(
					'image'     => $verge_over_item['image'],
					'permalink' => $verge_over_item['permalink'],
				);
			}
		}

		return $catalog;
	}

	/**
	 * Format a numeric WooCommerce price as plain text for the REST API.
	 *
	 * @param float $price Price to format.
	 * @return string
	 */
	public static function format_price( $price ) {
		if ( ! function_exists( 'wc_price' ) ) {
			return (string) $price;
		}

		return html_entity_decode( wp_strip_all_tags( wc_price( $price ) ), ENT_QUOTES, 'UTF-8' );
	}
}

<?php
/**
 * Rocktile Calculator Engine.
 *
 * Handles server-side validation and business calculation rules.
 *
 * @package Rocktile_Calculator
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Rocktile_Calculator_Engine {

	/**
	 * Calculation constants.
	 */
	const BASE_TILE_PIECES_PER_M2         = 2.16;
	const BASE_TILE_PER_PACKAGE           = 12;
	const RIDGE_PIECES_PER_METER          = 2.63;
	const FLASHING_COVERAGE_M             = 1.17; // 1170 mm hasznos fedési hossz
	const NAIL_COVERAGE_M2_PER_KG         = 40;   // 1 kg / 40 m2 (szegelt rögzítéshez)
	const SCREW_COVERAGE_M2_PER_BOX       = 25;   // 25 m2 / doboz (komplett tető csavaros rögzítéséhez)
	const SCREW_COVERAGE_M2_PER_BOX_RIDGE = 150;  // 1 doboz / 150 m2 (szegelt rögzítés esetén a kúpozáshoz)
	const VENTILATION_PER_ROOF            = 1;   // Ideiglenes V1 üzleti szabály: 1 db / tetőkalkuláció

	/**
	 * Calculate required ventilation product quantity.
	 *
	 * Opcionális szellőzés: ha kérik, a megadott darabszám (alapértelmezett 1 db), egyébként 0.
	 *
	 * @param array       $roofConfig
	 * @param array       $dimensions
	 * @param string|null $ventilationMode
	 * @param int         $ventilationCount
	 * @return int
	 */
	public static function calculate_ventilation_quantity( $roofConfig, $dimensions, $ventilationMode = null, $ventilationCount = 0 ) {
		if ( empty( $ventilationMode ) || 'none' === $ventilationMode || 'no' === $ventilationMode || false === $ventilationMode ) {
			return 0;
		}

		$count = (int) $ventilationCount;
		return $count > 0 ? $count : 1;
	}

	/**
	 * Validate input payload from request.
	 *
	 * @param array $data
	 * @return array|WP_Error Returns sanitized data array or WP_Error on validation failure.
	 */
	public static function validate_input( $data ) {
		if ( ! is_array( $data ) ) {
			return new WP_Error( 'invalid_payload', 'Érvénytelen kérés formátum.', array( 'status' => 400 ) );
		}

		// 1. Termékcsalád validáció
		$product = isset( $data['product'] ) ? sanitize_text_field( $data['product'] ) : '';
		if ( ! Rocktile_Calculator_Products::is_valid_product( $product ) ) {
			return new WP_Error( 'invalid_product', 'Érvénytelen vagy hiányzó termékcsalád.', array( 'status' => 400 ) );
		}

		// 2. Szín validáció (csak az engedélyezett aktív színeket fogadjuk el)
		$color = isset( $data['color'] ) ? sanitize_text_field( $data['color'] ) : '';
		if ( ! Rocktile_Calculator_Products::is_enabled_color( $color ) ) {
			return new WP_Error( 'invalid_color', 'A választott szín jelenleg nem engedélyezett a kalkulátorban.', array( 'status' => 400 ) );
		}

		// 3. Tetőforma validáció
		$roofType    = isset( $data['roofType'] ) ? sanitize_text_field( $data['roofType'] ) : '';
		$roofSubtype = isset( $data['roofSubtype'] ) ? sanitize_text_field( $data['roofSubtype'] ) : '';

		// Ha altípus van megadva, az a mérvadó konfiguráció, egyébként a fő típus
		$effectiveRoofKey = ! empty( $roofSubtype ) ? $roofSubtype : $roofType;

		$roofConfig = Rocktile_Calculator_Roof_Config::get_config( $effectiveRoofKey );
		if ( ! $roofConfig ) {
			return new WP_Error( 'invalid_roof_type', 'Érvénytelen tetőforma típus.', array( 'status' => 400 ) );
		}

		// 4. Méretek validációja
		$rawDimensions = isset( $data['dimensions'] ) && is_array( $data['dimensions'] ) ? $data['dimensions'] : array();
		$dimensions    = array();

		foreach ( $roofConfig['fields'] as $fieldKey ) {
			if ( ! isset( $rawDimensions[ $fieldKey ] ) || '' === $rawDimensions[ $fieldKey ] ) {
				return new WP_Error(
					'missing_dimension',
					sprintf( 'A(z) "%s" mező kitöltése kötelező ehhez a tetőformához.', $fieldKey ),
					array( 'status' => 400 )
				);
			}

			$val = $rawDimensions[ $fieldKey ];
			if ( is_string( $val ) ) {
				$val = str_replace( ',', '.', trim( $val ) );
			}

			if ( ! is_numeric( $val ) ) {
				return new WP_Error(
					'invalid_dimension_numeric',
					sprintf( 'A(z) "%s" mezőnek érvényes számnak kell lennie.', $fieldKey ),
					array( 'status' => 400 )
				);
			}

			$floatVal = (float) $val;
			$isRoofArea = ( 'roofArea' === $fieldKey );

			if ( $isRoofArea && $floatVal <= 0 ) {
				return new WP_Error(
					'invalid_dimension_positive',
					sprintf( 'A(z) "%s" (tetőfelület) mező értéke csak pozitív szám lehet (> 0).', $fieldKey ),
					array( 'status' => 400 )
				);
			}

			if ( ! $isRoofArea && $floatVal < 0 ) {
				return new WP_Error(
					'invalid_dimension_negative',
					sprintf( 'A(z) "%s" mező értéke nem lehet negatív.', $fieldKey ),
					array( 'status' => 400 )
				);
			}

			$dimensions[ $fieldKey ] = $floatVal;
		}

		// 5. Oromszegély típus (alapértelmezetten 'over-cover')
		$vergeType = isset( $data['vergeType'] ) && ! empty( $data['vergeType'] ) ? sanitize_text_field( $data['vergeType'] ) : 'over-cover';

		// 6. Szellőzés (opcionális: 'none', 'yes', 'standard' + darabszám)
		$ventilation = isset( $data['ventilation'] ) ? sanitize_text_field( $data['ventilation'] ) : 'none';
		$hasVentilation = false;
		if ( isset( $data['hasVentilation'] ) ) {
			$hasVentilation = filter_var( $data['hasVentilation'], FILTER_VALIDATE_BOOLEAN );
		} elseif ( in_array( $ventilation, array( 'yes', 'standard', 'ventilation-standard' ), true ) ) {
			$hasVentilation = true;
		}

		$ventilationCount = isset( $data['ventilationCount'] ) ? max( 0, (int) $data['ventilationCount'] ) : ( $hasVentilation ? 1 : 0 );
		if ( ! $hasVentilation ) {
			$ventilationCount = 0;
		}

		// 7. Kémény (opcionális: ha van, +2 db síklemez / kémény)
		$hasChimney = false;
		if ( isset( $data['hasChimney'] ) ) {
			$hasChimney = filter_var( $data['hasChimney'], FILTER_VALIDATE_BOOLEAN );
		}
		$chimneyCount = isset( $data['chimneyCount'] ) ? max( 1, (int) $data['chimneyCount'] ) : ( $hasChimney ? 1 : 0 );
		if ( ! $hasChimney ) {
			$chimneyCount = 0;
		}

		// 8. Opcionális tartalékcsomag (+1 csomag alapcserép)
		$addSparePackage = false;
		if ( isset( $data['addSparePackage'] ) ) {
			$addSparePackage = filter_var( $data['addSparePackage'], FILTER_VALIDATE_BOOLEAN );
		}

		// 9. Rögzítés módja ('screw' | 'nail' - alapértelmezetten 'screw')
		$fastening = isset( $data['fastening'] ) ? sanitize_text_field( $data['fastening'] ) : 'screw';
		// Szegelt rögzítés kizárólag Antracit ('shadow-rock') színnél engedélyezett
		if ( 'nail' === $fastening && 'shadow-rock' !== $color ) {
			$fastening = 'screw';
		}
		if ( ! in_array( $fastening, array( 'screw', 'nail' ), true ) ) {
			$fastening = 'screw';
		}

		// 10. Megjegyzés
		$note = isset( $data['note'] ) ? sanitize_textarea_field( $data['note'] ) : '';

		// 11. Egyedi / szerkesztett tételek (ha a kosárba tételkor módosított tételeket kapunk)
		$customItems = isset( $data['customItems'] ) && is_array( $data['customItems'] ) ? $data['customItems'] : null;

		return array(
			'product'          => $product,
			'color'            => $color,
			'roofType'         => $roofType,
			'roofSubtype'      => $roofSubtype,
			'effectiveRoofKey' => $effectiveRoofKey,
			'roofConfig'       => $roofConfig,
			'dimensions'       => $dimensions,
			'vergeType'        => $vergeType,
			'ventilation'      => $ventilation,
			'hasVentilation'   => $hasVentilation,
			'ventilationCount' => $ventilationCount,
			'hasChimney'       => $hasChimney,
			'chimneyCount'     => $chimneyCount,
			'fastening'        => $fastening,
			'addSparePackage'  => $addSparePackage,
			'note'             => $note,
			'customItems'      => $customItems,
		);
	}

	/**
	 * Perform calculation and return structured calculation response.
	 *
	 * @param array $input Sanitized input data.
	 * @return array
	 */
	public static function calculate( $input ) {
		$roofConfig = $input['roofConfig'];
		$color      = $input['color'];
		$dimensions = $input['dimensions'];

		// Egyedi tetőforma kezelése (szakértői ellenőrzést igényel)
		if ( ! empty( $roofConfig['requiresManualReview'] ) ) {
			return array(
				'success'              => true,
				'requiresManualReview' => true,
				'input'                => $input,
				'items'                => array(),
				'calculations'         => array(),
				'warnings'             => array(
					'Az egyedi tetőforma geometria pontos anyagszükséglete szakértői felülvizsgálatot igényel.',
				),
			);
		}

		$items        = array();
		$calculations = array();

		// 1. Alapcserép számítás
		$roofArea              = $dimensions['roofArea'];
		$rawBaseTilePieces     = $roofArea * self::BASE_TILE_PIECES_PER_M2;
		$calculatedPackages    = (int) ceil( $rawBaseTilePieces / self::BASE_TILE_PER_PACKAGE );
		$sparePackages         = ! empty( $input['addSparePackage'] ) ? 1 : 0;
		$finalPackages         = $calculatedPackages + $sparePackages;

		$calculations['baseTile'] = array(
			'roofArea'           => $roofArea,
			'piecesPerM2'        => self::BASE_TILE_PIECES_PER_M2,
			'rawPieces'          => round( $rawBaseTilePieces, 2 ),
			'piecesPerPackage'   => self::BASE_TILE_PER_PACKAGE,
			'calculatedPackages' => $calculatedPackages,
			'calculatedPieces'   => $calculatedPackages * self::BASE_TILE_PER_PACKAGE,
			'sparePackages'      => $sparePackages,
			'sparePieces'        => $sparePackages * self::BASE_TILE_PER_PACKAGE,
			'finalPackages'      => $finalPackages,
			'finalPieces'        => $finalPackages * self::BASE_TILE_PER_PACKAGE,
		);

		$baseTileProd = Rocktile_Calculator_Products::get_item( 'baseTile', $color );
		if ( $baseTileProd ) {
			$items[] = array(
				'key'          => 'baseTile',
				'name'         => $baseTileProd['name'],
				'productId'    => $baseTileProd['productId'],
				'sku'          => $baseTileProd['sku'],
				'quantity'     => $finalPackages,
				'unit'         => $baseTileProd['unit'],
				'pieces'       => $finalPackages * self::BASE_TILE_PER_PACKAGE,
				'piecesPerPkg' => self::BASE_TILE_PER_PACKAGE,
			);
		}

		// 2. Íves kúpcserép számítás
		$ridgeLength = isset( $dimensions['ridge'] ) ? (float) $dimensions['ridge'] : 0.0;
		$hipLength   = isset( $dimensions['hip'] ) ? (float) $dimensions['hip'] : 0.0;

		$effectiveRidgeMeters = $ridgeLength + ( $roofConfig['ridgeUsesHip'] ? $hipLength : 0.0 );
		$rawRidgePieces       = ( $effectiveRidgeMeters * self::RIDGE_PIECES_PER_METER ) + $roofConfig['ridgeExtra'];
		$ridgeTileQuantity    = (int) ceil( $rawRidgePieces );

		$calculations['ridgeTile'] = array(
			'ridgeMeters'    => $ridgeLength,
			'hipMeters'      => $hipLength,
			'effectiveMeters' => $effectiveRidgeMeters,
			'piecesPerMeter' => self::RIDGE_PIECES_PER_METER,
			'fixedExtra'     => $roofConfig['ridgeExtra'],
			'rawPieces'      => round( $rawRidgePieces, 2 ),
			'quantity'       => $ridgeTileQuantity,
		);

		$ridgeTileProd = Rocktile_Calculator_Products::get_item( 'ridgeTile', $color );
		if ( $ridgeTileProd && $ridgeTileQuantity > 0 ) {
			$items[] = array(
				'key'       => 'ridgeTile',
				'name'      => $ridgeTileProd['name'],
				'productId' => $ridgeTileProd['productId'],
				'sku'       => $ridgeTileProd['sku'],
				'quantity'  => $ridgeTileQuantity,
				'unit'      => $ridgeTileProd['unit'],
			);
		}

		// 3. Ereszszegély számítás
		if ( isset( $dimensions['eaves'] ) && $dimensions['eaves'] > 0 ) {
			$eavesMeters    = $dimensions['eaves'];
			$eavesQuantity  = (int) ceil( $eavesMeters / self::FLASHING_COVERAGE_M );
			$calculations['eaves'] = array(
				'meters'          => $eavesMeters,
				'coveragePerItem' => self::FLASHING_COVERAGE_M,
				'quantity'        => $eavesQuantity,
			);

			$eavesProd = Rocktile_Calculator_Products::get_item( 'eaves', $color );
			if ( $eavesProd ) {
				$items[] = array(
					'key'       => 'eaves',
					'name'      => $eavesProd['name'],
					'productId' => $eavesProd['productId'],
					'sku'       => $eavesProd['sku'],
					'quantity'  => $eavesQuantity,
					'unit'      => $eavesProd['unit'],
				);
			}
		}

		// 4. Vápaelem számítás
		if ( isset( $dimensions['valley'] ) && $dimensions['valley'] > 0 ) {
			$valleyMeters   = $dimensions['valley'];
			$valleyQuantity = (int) ceil( $valleyMeters / self::FLASHING_COVERAGE_M );
			$calculations['valley'] = array(
				'meters'          => $valleyMeters,
				'coveragePerItem' => self::FLASHING_COVERAGE_M,
				'quantity'        => $valleyQuantity,
			);

			$valleyProd = Rocktile_Calculator_Products::get_item( 'valley', $color );
			if ( $valleyProd ) {
				$items[] = array(
					'key'       => 'valley',
					'name'      => $valleyProd['name'],
					'productId' => $valleyProd['productId'],
					'sku'       => $valleyProd['sku'],
					'quantity'  => $valleyQuantity,
					'unit'      => $valleyProd['unit'],
				);
			}
		}

		// 5. Oromszegély számítás (ROCKTILE Oromszegély 1270mm)
		if ( $roofConfig['hasVerge'] && isset( $dimensions['verge'] ) && $dimensions['verge'] > 0 ) {
			$vergeMeters    = $dimensions['verge'];
			$vergeQuantity  = (int) ceil( $vergeMeters / self::FLASHING_COVERAGE_M );
			$vergeItemKey   = 'vergeUnder';

			$calculations['verge'] = array(
				'meters'          => $vergeMeters,
				'type'            => 'oromszegély',
				'coveragePerItem' => self::FLASHING_COVERAGE_M,
				'quantity'        => $vergeQuantity,
			);

			$vergeProd = Rocktile_Calculator_Products::get_item( $vergeItemKey, $color );
			if ( $vergeProd ) {
				$items[] = array(
					'key'       => $vergeItemKey,
					'name'      => $vergeProd['name'],
					'productId' => $vergeProd['productId'],
					'sku'       => $vergeProd['sku'],
					'quantity'  => $vergeQuantity,
					'unit'      => $vergeProd['unit'],
				);
			}
		}

		// 6. Kezdőkúp (Tetőforma-függő fix darabszám)
		if ( ! empty( $roofConfig['starterRidge'] ) && $roofConfig['starterRidge'] > 0 ) {
			$starterProd = Rocktile_Calculator_Products::get_item( 'starterRidge', $color );
			if ( $starterProd ) {
				$items[] = array(
					'key'       => 'starterRidge',
					'name'      => $starterProd['name'],
					'productId' => $starterProd['productId'],
					'sku'       => $starterProd['sku'],
					'quantity'  => (int) $roofConfig['starterRidge'],
					'unit'      => $starterProd['unit'],
				);
			}
		}

		// 7. Véglap (Tetőforma-függő fix darabszám)
		if ( ! empty( $roofConfig['ridgeEndCap'] ) && $roofConfig['ridgeEndCap'] > 0 ) {
			$endCapProd = Rocktile_Calculator_Products::get_item( 'ridgeEndCap', $color );
			if ( $endCapProd ) {
				$items[] = array(
					'key'       => 'ridgeEndCap',
					'name'      => $endCapProd['name'],
					'productId' => $endCapProd['productId'],
					'sku'       => $endCapProd['sku'],
					'quantity'  => (int) $roofConfig['ridgeEndCap'],
					'unit'      => $endCapProd['unit'],
				);
			}
		}

		// 8. Javítókészlet (1 db fixen minden kalkulációba)
		if ( ! empty( $roofConfig['repairKit'] ) && $roofConfig['repairKit'] > 0 ) {
			$repairProd = Rocktile_Calculator_Products::get_item( 'repairKit', $color );
			if ( $repairProd ) {
				$items[] = array(
					'key'       => 'repairKit',
					'name'      => $repairProd['name'],
					'productId' => $repairProd['productId'],
					'sku'       => $repairProd['sku'],
					'quantity'  => (int) $roofConfig['repairKit'],
					'unit'      => $repairProd['unit'],
				);
			}
		}

		// 9. Rögzítés (Csavaros vs. Szegelt rögzítés)
		$fastening = isset( $input['fastening'] ) ? $input['fastening'] : 'screw';
		if ( 'nail' === $fastening && 'shadow-rock' !== $color ) {
			$fastening = 'screw';
		}

		if ( 'screw' === $fastening ) {
			// Csavaros rögzítés a komplett tetőhöz: 25 m2 / doboz - mindig felfelé egész dobozra kerekítve (szeg nem kerül a kalkulációba)
			$screwQuantity = (int) ceil( $roofArea / self::SCREW_COVERAGE_M2_PER_BOX );
			$calculations['screws'] = array(
				'mode'           => 'screw',
				'roofArea'       => $roofArea,
				'coveragePerBox' => self::SCREW_COVERAGE_M2_PER_BOX,
				'quantity'       => $screwQuantity,
			);

			$screwProd = Rocktile_Calculator_Products::get_item( 'screw', $color );
			if ( $screwProd && $screwQuantity > 0 ) {
				$items[] = array(
					'key'       => 'screw',
					'name'      => $screwProd['name'],
					'productId' => $screwProd['productId'],
					'sku'       => $screwProd['sku'],
					'quantity'  => $screwQuantity,
					'unit'      => $screwProd['unit'],
				);
			}
		} else {
			// Szegelt rögzítés (kizárólag Antracit színnél): tetőre szeg (40 m2/kg), kúpozáshoz csavar (150 m2/doboz)
			$nailQuantity = (int) ceil( $roofArea / self::NAIL_COVERAGE_M2_PER_KG );
			$calculations['nails'] = array(
				'mode'          => 'nail',
				'roofArea'      => $roofArea,
				'coveragePerKg' => self::NAIL_COVERAGE_M2_PER_KG,
				'quantity'      => $nailQuantity,
			);

			$nailProd = Rocktile_Calculator_Products::get_item( 'nail', $color );
			if ( $nailProd && $nailQuantity > 0 ) {
				$items[] = array(
					'key'       => 'nail',
					'name'      => $nailProd['name'],
					'productId' => $nailProd['productId'],
					'sku'       => $nailProd['sku'],
					'quantity'  => $nailQuantity,
					'unit'      => $nailProd['unit'],
				);
			}

			$screwQuantity = (int) ceil( $roofArea / self::SCREW_COVERAGE_M2_PER_BOX_RIDGE );
			$calculations['screws'] = array(
				'mode'           => 'nail_ridge',
				'roofArea'       => $roofArea,
				'coveragePerBox' => self::SCREW_COVERAGE_M2_PER_BOX_RIDGE,
				'quantity'       => $screwQuantity,
			);

			$screwProd = Rocktile_Calculator_Products::get_item( 'screw', $color );
			if ( $screwProd && $screwQuantity > 0 ) {
				$items[] = array(
					'key'       => 'screw',
					'name'      => $screwProd['name'],
					'productId' => $screwProd['productId'],
					'sku'       => $screwProd['sku'],
					'quantity'  => $screwQuantity,
					'unit'      => $screwProd['unit'],
				);
			}
		}

		// 11. Szellőzés (Opcionális: kiválasztás esetén a megadott darabszám, alapértelmezetten 1 db)
		$ventilationCount    = isset( $input['ventilationCount'] ) ? (int) $input['ventilationCount'] : 0;
		$ventilationMode     = ! empty( $input['hasVentilation'] ) ? 'standard' : $input['ventilation'];
		$ventilationQuantity = self::calculate_ventilation_quantity( $roofConfig, $dimensions, $ventilationMode, $ventilationCount );
		$calculations['ventilation'] = array(
			'hasVentilation'  => ! empty( $input['hasVentilation'] ),
			'ventilationMode' => $ventilationMode,
			'quantity'        => $ventilationQuantity,
		);

		$ventilationProd = Rocktile_Calculator_Products::get_item( 'ventilation', $color );
		if ( $ventilationProd && $ventilationQuantity > 0 ) {
			$items[] = array(
				'key'       => 'ventilation',
				'name'      => $ventilationProd['name'],
				'productId' => $ventilationProd['productId'],
				'sku'       => $ventilationProd['sku'],
				'quantity'  => $ventilationQuantity,
				'unit'      => $ventilationProd['unit'],
			);
		}

		// 12. Kémény (ha van, kéményenként 2 db síklemez kerül a listába a választott színben)
		if ( ! empty( $input['hasChimney'] ) ) {
			$chimneyCount      = isset( $input['chimneyCount'] ) && (int) $input['chimneyCount'] > 0 ? (int) $input['chimneyCount'] : 1;
			$flatSheetQuantity = $chimneyCount * 2;
			$flatSheetProd     = Rocktile_Calculator_Products::get_item( 'flatSheet', $color );
			if ( $flatSheetProd ) {
				$items[] = array(
					'key'       => 'flatSheet',
					'name'      => $flatSheetProd['name'],
					'productId' => $flatSheetProd['productId'],
					'sku'       => $flatSheetProd['sku'],
					'quantity'  => $flatSheetQuantity,
					'unit'      => $flatSheetProd['unit'],
				);
			}
			$calculations['chimney'] = array(
				'hasChimney'   => true,
				'chimneyCount' => $chimneyCount,
				'flatSheets'   => $flatSheetQuantity,
			);
		}

		$total          = 0.0;
		$has_all_prices = true;

		foreach ( $items as $index => $item ) {
			$items[ $index ] = Rocktile_Calculator_Products::enrich_item( $item );

			if ( null === $items[ $index ]['lineTotal'] ) {
				$has_all_prices = false;
			} else {
				$total += (float) $items[ $index ]['lineTotal'];
			}
		}

		return array(
			'success'              => true,
			'requiresManualReview' => false,
			'input'                => $input,
			'items'                => $items,
			'total'                => $total,
			'totalFormatted'       => Rocktile_Calculator_Products::format_price( $total ),
			'hasCompletePricing'   => $has_all_prices,
			'calculations'         => $calculations,
			'warnings'             => array(
				'A kalkuláció az alapcserepeknél nem tartalmaz vágási veszteséget.',
			),
		);
	}
}

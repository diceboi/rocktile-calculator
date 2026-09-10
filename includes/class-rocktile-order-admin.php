<?php
/**
 * Rocktile Calculator WooCommerce Order Admin Panel.
 *
 * Renders human-readable calculation summaries, parameters, customer notes,
 * and calculated vs. ordered quantity comparisons in WooCommerce order edit screen.
 *
 * @package Rocktile_Calculator
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Rocktile_Calculator_Order_Admin {

	/**
	 * Constructor.
	 */
	public function __construct() {
		// Register meta box on WooCommerce order screens (CPT & HPOS)
		add_action( 'add_meta_boxes', array( $this, 'register_meta_boxes' ), 10, 2 );

		// Hide internal technical item meta from standard order item list
		add_filter( 'woocommerce_hidden_order_itemmeta', array( $this, 'hide_internal_item_meta' ) );
	}

	/**
	 * Register meta box for WooCommerce orders.
	 *
	 * @param string  $post_type
	 * @param WP_Post|WC_Order $post_or_order
	 */
	public function register_meta_boxes( $post_type, $post_or_order = null ) {
		$screen_ids = array(
			'shop_order',
			'woocommerce_page_wc-orders',
		);

		if ( function_exists( 'wc_get_page_screen_id' ) ) {
			$screen_ids[] = wc_get_page_screen_id( 'shop_order' );
		}

		$screen_ids = array_unique( array_filter( $screen_ids ) );

		foreach ( $screen_ids as $screen_id ) {
			add_meta_box(
				'rocktile_order_calculations_meta_box',
				__( 'Rocktile Tetőkalkuláció', 'rocktile-calculator' ),
				array( $this, 'render_meta_box' ),
				$screen_id,
				'normal',
				'high'
			);
		}
	}

	/**
	 * Hide internal Rocktile meta keys from the generic WooCommerce item meta list.
	 *
	 * @param array $hidden_meta
	 * @return array
	 */
	public function hide_internal_item_meta( $hidden_meta ) {
		$rocktile_keys = array(
			'rocktile_calculation_id',
			'_rocktile_calculation_id',
			'rocktile_item_key',
			'_rocktile_item_key',
			'rocktile_unit',
			'_rocktile_unit',
			'rocktile_unique_key',
			'_rocktile_unique_key',
			'rocktile_product_name',
			'_rocktile_product_name',
		);

		return array_unique( array_merge( $hidden_meta, $rocktile_keys ) );
	}

	/**
	 * Render the calculation meta box content.
	 *
	 * @param WP_Post|WC_Order $post_or_order
	 */
	public function render_meta_box( $post_or_order ) {
		$order = ( $post_or_order instanceof WC_Order ) ? $post_or_order : wc_get_order( $post_or_order->ID );
		if ( ! $order ) {
			return;
		}

		$calculations = $order->get_meta( '_rocktile_calculations' );

		// Ha nincs mentett kalkuláció, ellenőrizzük a tételeket is háttérmentés céljából
		if ( empty( $calculations ) || ! is_array( $calculations ) ) {
			$calculations = array();
			foreach ( $order->get_items() as $item ) {
				$calcId = $item->get_meta( 'rocktile_calculation_id' );
				if ( ! $calcId ) {
					$calcId = $item->get_meta( '_rocktile_calculation_id' );
				}
				if ( $calcId && ! isset( $calculations[ $calcId ] ) ) {
					$calculations[ $calcId ] = array(
						'calculationId' => $calcId,
						'input'         => array(),
						'result'        => array(),
					);
				}
			}
		}

		// TESZT K: Nem kalkulátorból származó rendeléseknél ne jelenjen meg tartalom
		if ( empty( $calculations ) ) {
			echo '<p style="color: #64748b; font-style: italic; margin: 8px 0;">' . esc_html__( 'Ehhez a rendeléshez nem tartozik Rocktile tetőkalkuláció.', 'rocktile-calculator' ) . '</p>';
			return;
		}

		// Összegyűjtjük a tényleges rendelési tételeket kalkuláció-azonosítónként
		$orderedItemsByCalc = array();
		foreach ( $order->get_items() as $item ) {
			$calcId = $item->get_meta( 'rocktile_calculation_id' );
			if ( ! $calcId ) {
				$calcId = $item->get_meta( '_rocktile_calculation_id' );
			}
			if ( ! $calcId ) {
				continue;
			}

			$itemKey   = $item->get_meta( '_rocktile_item_key' );
			$productId = (int) $item->get_product_id();
			$varId     = (int) $item->get_variation_id();
			$effId     = $varId > 0 ? $varId : $productId;

			if ( ! isset( $orderedItemsByCalc[ $calcId ] ) ) {
				$orderedItemsByCalc[ $calcId ] = array();
			}

			$orderedItemsByCalc[ $calcId ][] = array(
				'name'      => $item->get_name(),
				'itemKey'   => $itemKey,
				'productId' => $effId,
				'quantity'  => (int) $item->get_quantity(),
				'total'     => $item->get_total(),
			);
		}

		$this->render_styles();

		echo '<div class="rocktile-admin-panel-wrapper">';

		foreach ( $calculations as $calcId => $calcData ) {
			$this->render_calculation_block( $calcId, $calcData, $orderedItemsByCalc[ $calcId ] ?? array() );
		}

		echo '</div>';
	}

	/**
	 * Render a single calculation block.
	 *
	 * @param string $calcId
	 * @param array  $calcData
	 * @param array  $orderedItems
	 */
	private function render_calculation_block( $calcId, $calcData, $orderedItems ) {
		$input  = $calcData['input'] ?? array();
		$result = $calcData['result'] ?? array();

		$productFamily = ! empty( $input['product'] ) ? Rocktile_Calculator_Products::get_product_family_name( $input['product'] ) : 'Classic Bond';
		$colorName     = ! empty( $input['color'] ) ? Rocktile_Calculator_Products::get_color_name( $input['color'] ) : '-';
		$roofKey       = ! empty( $input['effectiveRoofKey'] ) ? $input['effectiveRoofKey'] : ( $input['roofSubtype'] ?? ( $input['roofType'] ?? '' ) );
		$roofName      = ! empty( $roofKey ) ? Rocktile_Calculator_Roof_Config::get_name( $roofKey ) : '-';
		$roofConfig    = ! empty( $input['roofConfig'] ) ? $input['roofConfig'] : Rocktile_Calculator_Roof_Config::get_config( $roofKey );
		$dimensions    = $input['dimensions'] ?? array();
		$hasVerge      = ! empty( $roofConfig['hasVerge'] );
		$vergeName     = $hasVerge && ! empty( $input['vergeType'] ) ? Rocktile_Calculator_Roof_Config::get_verge_name( $input['vergeType'] ) : '-';
		$ventName      = ! empty( $input['ventilation'] ) ? Rocktile_Calculator_Roof_Config::get_ventilation_name( $input['ventilation'] ) : 'Standard pontszellőzés';
		$addSpare      = ! empty( $input['addSparePackage'] );
		$note          = $input['note'] ?? '';

		$calcBaseTile = $result['calculations']['baseTile'] ?? array();
		$sparePackages = $calcBaseTile['sparePackages'] ?? ( $addSpare ? 1 : 0 );
		$calculatedPackages = $calcBaseTile['calculatedPackages'] ?? ( $calcBaseTile['packages'] ?? 0 );
		$finalPackages = $calcBaseTile['finalPackages'] ?? ( $calculatedPackages + $sparePackages );
		?>
		<div class="rocktile-admin-card">
			<div class="rocktile-admin-card-header">
				<div class="rocktile-header-title-row">
					<span class="rocktile-logo-badge">ROCKTILE TETŐKALKULÁCIÓ</span>
					<span class="rocktile-calc-id-badge"><?php echo esc_html( $calcId ); ?></span>
				</div>
				<?php if ( ! empty( $calcData['createdAt'] ) ) : ?>
					<span class="rocktile-created-at">Kalkuláció ideje: <?php echo esc_html( $calcData['createdAt'] ); ?></span>
				<?php endif; ?>
			</div>

			<!-- 1. Alapadatok rács -->
			<div class="rocktile-admin-section">
				<h4 class="rocktile-section-title">Tető és Termék Alapadatok</h4>
				<div class="rocktile-admin-grid">
					<div class="rocktile-grid-item">
						<span class="grid-label">Termékcsalád:</span>
						<span class="grid-value font-bold"><?php echo esc_html( $productFamily ); ?></span>
					</div>
					<div class="rocktile-grid-item">
						<span class="grid-label">Választott szín:</span>
						<span class="grid-value font-bold"><?php echo esc_html( $colorName ); ?></span>
					</div>
					<div class="rocktile-grid-item">
						<span class="grid-label">Tetőforma:</span>
						<span class="grid-value font-bold"><?php echo esc_html( $roofName ); ?></span>
					</div>
					<div class="rocktile-grid-item">
						<span class="grid-label">Szellőzési mód:</span>
						<span class="grid-value"><?php echo esc_html( $ventName ); ?></span>
					</div>
					<?php if ( $hasVerge ) : ?>
						<div class="rocktile-grid-item">
							<span class="grid-label">Oromszegély:</span>
							<span class="grid-value"><?php echo esc_html( $vergeName ); ?></span>
						</div>
					<?php endif; ?>
					<div class="rocktile-grid-item">
						<span class="grid-label">Tartalék alapcserép:</span>
						<span class="grid-value">
							<?php if ( $addSpare ) : ?>
								<span class="rocktile-badge spare-yes">+1 csomag kérve</span>
							<?php else : ?>
								<span class="rocktile-badge spare-no">Nem kért tartalékot</span>
							<?php endif; ?>
						</span>
					</div>
				</div>
			</div>

			<!-- 2. Releváns méretek -->
			<?php
			$activeFields = $roofConfig['fields'] ?? array_keys( $dimensions );
			$hasValidDimensions = false;
			foreach ( $activeFields as $fKey ) {
				if ( isset( $dimensions[ $fKey ] ) && (float) $dimensions[ $fKey ] > 0 ) {
					$hasValidDimensions = true;
					break;
				}
			}
			?>
			<?php if ( $hasValidDimensions ) : ?>
				<div class="rocktile-admin-section">
					<h4 class="rocktile-section-title">Megadott Méretek</h4>
					<div class="rocktile-dimensions-pill-list">
						<?php foreach ( $activeFields as $fKey ) : ?>
							<?php
							if ( ! isset( $dimensions[ $fKey ] ) || (float) $dimensions[ $fKey ] <= 0 ) {
								continue; // Releváns méretek: 0 vagy nem létező mezők kihagyása
							}
							$dimInfo = Rocktile_Calculator_Roof_Config::get_dimension_label( $fKey );
							?>
							<div class="rocktile-dim-pill">
								<span class="dim-label"><?php echo esc_html( $dimInfo['label'] ); ?>:</span>
								<span class="dim-value"><?php echo esc_html( $dimensions[ $fKey ] . ' ' . $dimInfo['unit'] ); ?></span>
							</div>
						<?php endforeach; ?>
					</div>
				</div>
			<?php endif; ?>

			<!-- 3. Ügyfél Megjegyzése (ha megadta) -->
			<?php if ( ! empty( $note ) ) : ?>
				<div class="rocktile-admin-section">
					<h4 class="rocktile-section-title">Ügyfél Megjegyzése</h4>
					<div class="rocktile-admin-note-box">
						<span class="note-icon">💬</span>
						<div class="note-text"><?php echo nl2br( esc_html( $note ) ); ?></div>
					</div>
				</div>
			<?php endif; ?>

			<!-- 4. Anyagszükséglet Összehasonlítás (Kalkulátor javaslat vs. Megrendelt mennyiség) -->
			<div class="rocktile-admin-section">
				<h4 class="rocktile-section-title">Anyagszükséglet és Megrendelés Összehasonlítása</h4>
				<table class="rocktile-admin-table widefat">
					<thead>
						<tr>
							<th class="th-prod">Termék megnevezése</th>
							<th class="th-sku">Cikkszám</th>
							<th class="th-calc">Kalkulátor javaslata</th>
							<th class="th-ordered">Ténylegesen megrendelve</th>
							<th class="th-status">Állapot</th>
						</tr>
					</thead>
					<tbody>
						<?php
						$calcItems = $result['items'] ?? array();
						if ( empty( $calcItems ) ) {
							echo '<tr><td colspan="5" style="text-align:center; color:#64748b;">Nincs megjeleníthető kalkulált tétel.</td></tr>';
						} else {
							foreach ( $calcItems as $item ) {
								$itemKey   = $item['key'] ?? '';
								$prodId    = (int) ( $item['productId'] ?? 0 );
								$unit      = $item['unit'] ?? 'db';
								$targetQty = (int) ( $item['quantity'] ?? 0 );

								// Megkeressük a tényleges rendelési tételt
								$matchedOrderedQty = 0;
								$foundInOrder      = false;

								foreach ( $orderedItems as $ordItem ) {
									if ( ( ! empty( $ordItem['itemKey'] ) && $ordItem['itemKey'] === $itemKey ) || ( (int) $ordItem['productId'] === $prodId ) ) {
										$matchedOrderedQty += (int) $ordItem['quantity'];
										$foundInOrder       = true;
									}
								}

								// Kalkulátor leírás (alapcserépnél részletezve a tartalékot)
								$calcDesc = $targetQty . ' ' . $unit;
								if ( 'baseTile' === $itemKey && $sparePackages > 0 ) {
									$calcDesc = sprintf( '%d %s <span class="sub-breakdown">(%d számított + %d tartalék)</span>', $finalPackages, $unit, $calculatedPackages, $sparePackages );
								}

								// Eltérés vizsgálata
								$isDiff = $foundInOrder && ( $matchedOrderedQty !== $targetQty );
								$isRemoved = ! $foundInOrder || ( 0 === $matchedOrderedQty );
								?>
								<tr class="<?php echo $isDiff ? 'row-modified' : ( $isRemoved ? 'row-removed' : '' ); ?>">
									<td class="td-prod">
										<strong><?php echo esc_html( $item['name'] ); ?></strong>
									</td>
									<td class="td-sku">
										<code><?php echo esc_html( $item['sku'] ?? '-' ); ?></code>
									</td>
									<td class="td-calc">
										<?php echo wp_kses_post( $calcDesc ); ?>
									</td>
									<td class="td-ordered font-bold">
										<?php if ( $foundInOrder && $matchedOrderedQty > 0 ) : ?>
											<?php echo esc_html( $matchedOrderedQty . ' ' . $unit ); ?>
										<?php else : ?>
											<span style="color: #94a3b8;">0 <?php echo esc_html( $unit ); ?></span>
										<?php endif; ?>
									</td>
									<td class="td-status">
										<?php if ( ! $foundInOrder || 0 === $matchedOrderedQty ) : ?>
											<span class="rocktile-badge badge-removed">Törölve a kosárból</span>
										<?php elseif ( $isDiff ) : ?>
											<span class="rocktile-badge badge-modified">Módosítva a kosárban</span>
										<?php else : ?>
											<span class="rocktile-badge badge-match">Megegyezik</span>
										<?php endif; ?>
									</td>
								</tr>
								<?php
							}
						}
						?>
					</tbody>
				</table>
			</div>
		</div>
		<?php
	}

	/**
	 * Render embedded admin styles.
	 */
	private function render_styles() {
		?>
		<style>
			.rocktile-admin-panel-wrapper {
				display: flex;
				flex-direction: column;
				gap: 20px;
				margin: 10px 0;
			}
			.rocktile-admin-card {
				background: #ffffff;
				border: 1px solid #c3c4c7;
				border-radius: 8px;
				box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
				overflow: hidden;
			}
			.rocktile-admin-card-header {
				background: #f8fafc;
				border-bottom: 1px solid #e2e8f0;
				padding: 12px 16px;
				display: flex;
				justify-content: space-between;
				align-items: center;
				flex-wrap: wrap;
				gap: 8px;
			}
			.rocktile-header-title-row {
				display: flex;
				align-items: center;
				gap: 10px;
			}
			.rocktile-logo-badge {
				background: #0284c7;
				color: #ffffff;
				font-size: 11px;
				font-weight: 700;
				padding: 3px 8px;
				border-radius: 4px;
				letter-spacing: 0.5px;
			}
			.rocktile-calc-id-badge {
				font-size: 14px;
				font-weight: 700;
				color: #0f172a;
				font-family: monospace;
			}
			.rocktile-created-at {
				font-size: 12px;
				color: #64748b;
			}
			.rocktile-admin-section {
				padding: 14px 16px;
				border-bottom: 1px solid #f1f5f9;
			}
			.rocktile-admin-section:last-child {
				border-bottom: none;
			}
			.rocktile-section-title {
				margin: 0 0 10px 0;
				font-size: 13px;
				font-weight: 700;
				color: #334155;
				text-transform: uppercase;
				letter-spacing: 0.4px;
			}
			.rocktile-admin-grid {
				display: grid;
				grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
				gap: 12px;
			}
			.rocktile-grid-item {
				display: flex;
				flex-direction: column;
				gap: 2px;
				background: #f8fafc;
				padding: 8px 12px;
				border-radius: 6px;
				border: 1px solid #edf2f7;
			}
			.grid-label {
				font-size: 11px;
				color: #64748b;
				text-transform: uppercase;
				font-weight: 600;
			}
			.grid-value {
				font-size: 13px;
				color: #1e293b;
			}
			.font-bold {
				font-weight: 700;
			}
			.rocktile-dimensions-pill-list {
				display: flex;
				flex-wrap: wrap;
				gap: 8px;
			}
			.rocktile-dim-pill {
				background: #f1f5f9;
				border: 1px solid #e2e8f0;
				border-radius: 20px;
				padding: 4px 12px;
				font-size: 12px;
				display: inline-flex;
				gap: 6px;
			}
			.rocktile-dim-pill .dim-label {
				color: #64748b;
			}
			.rocktile-dim-pill .dim-value {
				font-weight: 700;
				color: #0f172a;
			}
			.rocktile-admin-note-box {
				background: #fffbeb;
				border: 1px solid #fde68a;
				border-radius: 6px;
				padding: 10px 14px;
				display: flex;
				align-items: flex-start;
				gap: 10px;
				color: #92400e;
				font-size: 13px;
			}
			.note-icon {
				font-size: 18px;
			}
			.note-text {
				line-height: 1.5;
			}
			.rocktile-admin-table {
				margin-top: 6px !important;
				border: 1px solid #e2e8f0 !important;
				border-radius: 6px;
				overflow: hidden;
			}
			.rocktile-admin-table th {
				background: #f8fafc !important;
				color: #475569 !important;
				font-weight: 700 !important;
				font-size: 12px !important;
				padding: 8px 10px !important;
			}
			.rocktile-admin-table td {
				padding: 10px !important;
				font-size: 13px !important;
				vertical-align: middle !important;
			}
			.rocktile-admin-table tr.row-modified {
				background: #fefce8;
			}
			.rocktile-admin-table tr.row-removed {
				background: #fef2f2;
			}
			.sub-breakdown {
				font-size: 11px;
				color: #64748b;
				font-weight: normal;
				display: block;
			}
			.rocktile-badge {
				display: inline-block;
				font-size: 11px;
				font-weight: 700;
				padding: 3px 8px;
				border-radius: 12px;
			}
			.spare-yes {
				background: #dcfce7;
				color: #15803d;
			}
			.spare-no {
				background: #f1f5f9;
				color: #64748b;
			}
			.badge-match {
				background: #dcfce7;
				color: #15803d;
			}
			.badge-modified {
				background: #fef3c7;
				color: #b45309;
				border: 1px solid #fde68a;
			}
			.badge-removed {
				background: #fee2e2;
				color: #b91c1c;
			}
		</style>
		<?php
	}
}

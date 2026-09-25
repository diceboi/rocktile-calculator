<?php
/**
 * Rocktile Calculator Expert Review / Quote Request Handler.
 *
 * Handles customer contact information collection, admin email notifications,
 * and saving custom roof quote requests.
 *
 * @package Rocktile_Calculator
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Rocktile_Calculator_Quote_Request {

	/**
	 * Process an incoming expert quote review request.
	 *
	 * @param WP_REST_Request $request
	 * @return WP_REST_Response|WP_Error
	 */
	public static function handle_request( $request ) {
		$params = $request->get_json_params();
		if ( empty( $params ) ) {
			$params = $request->get_params();
		}

		$name       = isset( $params['name'] ) ? sanitize_text_field( $params['name'] ) : '';
		$email      = isset( $params['email'] ) ? sanitize_email( $params['email'] ) : '';
		$phone      = isset( $params['phone'] ) ? sanitize_text_field( $params['phone'] ) : '';
		$note       = isset( $params['note'] ) ? sanitize_textarea_field( $params['note'] ) : '';
		$source     = isset( $params['source'] ) ? sanitize_text_field( $params['source'] ) : '';
		$landingUrl = isset( $params['landingUrl'] ) ? esc_url_raw( $params['landingUrl'] ) : '';
		$referrer   = isset( $params['referrer'] ) ? esc_url_raw( $params['referrer'] ) : '';
		$isStep3Help = ( 'step3_help_request' === $source );

		if ( empty( $name ) ) {
			return new WP_Error( 'missing_name', 'Kérjük, adja meg nevét.', array( 'status' => 400 ) );
		}

		if ( empty( $email ) || ! is_email( $email ) ) {
			return new WP_Error( 'invalid_email', 'Kérjük, adjon meg egy érvényes email címet.', array( 'status' => 400 ) );
		}

		// A 3. lépésbeli segítségkérésnél a telefonszám opcionális, teljes ajánlatkérésnél kötelező
		if ( ! $isStep3Help && empty( $phone ) ) {
			return new WP_Error( 'missing_phone', 'Kérjük, adja meg telefonszámát.', array( 'status' => 400 ) );
		}

		$productKey   = isset( $params['product'] ) ? sanitize_text_field( $params['product'] ) : 'classic-bond';
		$colorKey     = isset( $params['color'] ) ? sanitize_text_field( $params['color'] ) : '';
		$roofType     = isset( $params['roofType'] ) ? sanitize_text_field( $params['roofType'] ) : '';
		$roofSubtype  = isset( $params['roofSubtype'] ) ? sanitize_text_field( $params['roofSubtype'] ) : '';
		$dimensions   = isset( $params['dimensions'] ) && is_array( $params['dimensions'] ) ? $params['dimensions'] : array();
		$hasChimney   = ! empty( $params['hasChimney'] );
		$chimneyCount = isset( $params['chimneyCount'] ) && (int) $params['chimneyCount'] > 0 ? (int) $params['chimneyCount'] : ( $hasChimney ? 1 : 0 );
		$hasVent      = ! empty( $params['hasVentilation'] );
		$ventCount    = isset( $params['ventilationCount'] ) ? (int) $params['ventilationCount'] : 0;
		$vergeType    = isset( $params['vergeType'] ) ? sanitize_text_field( $params['vergeType'] ) : '';
		$fastening    = isset( $params['fastening'] ) ? sanitize_text_field( $params['fastening'] ) : 'screw';
		$fasteningText = ( 'nail' === $fastening ) ? 'Szegelt rögzítés (tetőre szeg, kúpozáshoz csavar)' : 'Csavaros rögzítés (EPDM alátétes színezett csavar, 25 m² / doboz)';

		$items            = isset( $params['items'] ) && is_array( $params['items'] ) ? $params['items'] : array();
		$paletteCount     = isset( $params['paletteCount'] ) ? (int) $params['paletteCount'] : 0;
		$paletteFeeTotal  = isset( $params['paletteFeeTotal'] ) ? (float) $params['paletteFeeTotal'] : 0.0;
		$shippingFeeTotal = isset( $params['shippingFeeTotal'] ) ? (float) $params['shippingFeeTotal'] : 0.0;
		$materialTotal    = isset( $params['totalAmount'] ) ? (float) $params['totalAmount'] : 0.0;
		$grandTotal       = isset( $params['grandTotal'] ) ? (float) $params['grandTotal'] : ( $materialTotal + $paletteFeeTotal + $shippingFeeTotal );

		$productName  = Rocktile_Calculator_Products::get_product_family_name( $productKey );
		$colorName    = Rocktile_Calculator_Products::get_color_name( $colorKey );

		$roofName     = 'Egyedi tetőforma';
		if ( class_exists( 'Rocktile_Calculator_Roof_Config' ) ) {
			$effectiveKey = ! empty( $roofSubtype ) ? $roofSubtype : $roofType;
			$config = Rocktile_Calculator_Roof_Config::get_config( $effectiveKey );
			if ( $config && ! empty( $config['name'] ) ) {
				$roofName = $config['name'];
			}
		}

		// Összeállítjuk a méretek szöveges listáját
		$dimLines = array();
		$labels = array(
			'roofArea' => 'Tetőfelület',
			'ridge'    => 'Tetőgerinc',
			'hip'      => 'Élgerinc',
			'valley'   => 'Vápa',
			'eaves'    => 'Eresz',
			'verge'    => 'Oromszegély',
		);
		$units = array(
			'roofArea' => 'm²',
			'ridge'    => 'fm',
			'hip'      => 'fm',
			'valley'   => 'fm',
			'eaves'    => 'fm',
			'verge'    => 'fm',
		);

		foreach ( $dimensions as $k => $v ) {
			$label = isset( $labels[ $k ] ) ? $labels[ $k ] : $k;
			$unit  = isset( $units[ $k ] ) ? $units[ $k ] : '';
			$dimLines[] = sprintf( '<li><strong>%s:</strong> %s %s</li>', esc_html( $label ), esc_html( (string) $v ), esc_html( $unit ) );
		}
		$dimHtml = ! empty( $dimLines ) ? '<ul>' . implode( '', $dimLines ) . '</ul>' : '<em>Nem lettek megadva külön méretek</em>';

		$vergeText = 'Nem releváns';
		if ( ! empty( $vergeType ) ) {
			$vergeText = 'Oromszegély 1270mm';
		}

		$hasItems = ! empty( $items );
		$quotePrefix = $isStep3Help ? 'RT-HELP-' : ( $hasItems ? 'RT-AJ-' : 'RT-REQ-' );
		$quoteId = $quotePrefix . strtoupper( substr( md5( uniqid( (string) time(), true ) ), 0, 6 ) );

		$chimneyText = $hasChimney ? sprintf( 'Igen (%d db kémény, %d db síklemez)', $chimneyCount, $chimneyCount * 2 ) : 'Nincs';
		$ventText    = $hasVent ? sprintf( 'Igen (%d db átvezető elem)', $ventCount ) : 'Nem kér';

		// Összeállítjuk a Pipedrive / CRM / Megjegyzés szöveges összefoglalót az összes kitöltött adattal
		$summaryLines = array();
		if ( $isStep3Help ) {
			$summaryLines[] = "=== [CÍMKE: rockile-segitseg] SEGÍTSÉGKÉRÉS A MÉRETEKHEZ (3. LÉPÉS) ===";
			$summaryLines[] = "Pipedrive Címke: rockile-segitseg";
		} else {
			$summaryLines[] = "=== ROCKTILE TETŐKALKULÁCIÓ ÉS ÁRAJÁNLAT ADATOK ===";
		}
		$summaryLines[] = "Azonosító: " . $quoteId;
		$summaryLines[] = "Dátum: " . current_time( 'Y.m.d. H:i' );

		if ( ! empty( $note ) ) {
			$summaryLines[] = "\n[ÜGYFÉL EGYEDI MEGJEGYZÉSE / KÉRDÉSE]:\n" . $note;
		}

		$summaryLines[] = "\n--- TETŐ ÉS TERMÉK ADATOK ---";
		$summaryLines[] = "• Tetőforma: " . $roofName;
		$summaryLines[] = "• Termékcsalád: " . $productName;
		$summaryLines[] = "• Választott szín: " . $colorName;
		$summaryLines[] = "• Rögzítés módja: " . $fasteningText;
		$summaryLines[] = "• Kémény: " . $chimneyText;
		$summaryLines[] = "• Szellőzés: " . $ventText;
		$summaryLines[] = "• Oromszegély: " . $vergeText;

		$summaryLines[] = "\n--- MEGADOTT MÉRETEK ---";
		if ( ! empty( $dimensions ) ) {
			foreach ( $dimensions as $k => $v ) {
				$label = isset( $labels[ $k ] ) ? $labels[ $k ] : $k;
				$unit  = isset( $units[ $k ] ) ? $units[ $k ] : '';
				$summaryLines[] = "• {$label}: {$v} {$unit}";
			}
		} else {
			$summaryLines[] = "Nem lettek megadva külön méretek.";
		}

		if ( $hasItems ) {
			$summaryLines[] = "\n--- KALKULÁLT ANYAGSZÜKSÉGLET ÉS ÁRAK ---";
			$summaryLines[] = "• Anyagok részösszege (bruttó, 27% áfával): " . number_format( $materialTotal, 0, ',', ' ' ) . " Ft";
			$summaryLines[] = "• Raklap díj ({$paletteCount} db × br. 3 810 Ft): " . number_format( $paletteFeeTotal, 0, ',', ' ' ) . " Ft";
			$summaryLines[] = "• Várható szállítási díj ({$paletteCount} raklap × br. 38 100 Ft): " . number_format( $shippingFeeTotal, 0, ',', ' ' ) . " Ft";
			$summaryLines[] = "• ÁRAJÁNLAT VÉGÖSSZEGE (bruttó): " . number_format( $grandTotal, 0, ',', ' ' ) . " Ft";

			$summaryLines[] = "\nTételes anyaglista:";
			foreach ( $items as $it ) {
				$iN = isset( $it['name'] ) ? $it['name'] : '-';
				$iQ = isset( $it['quantity'] ) ? (int) $it['quantity'] : 1;
				$iU = isset( $it['unit'] ) ? $it['unit'] : 'db';
				$iP = isset( $it['lineTotalFormatted'] ) ? $it['lineTotalFormatted'] : ( isset( $it['lineTotal'] ) ? number_format( (float) $it['lineTotal'], 0, ',', ' ' ) . ' Ft' : '' );
				$summaryLines[] = "  - {$iQ} {$iU} {$iN} (" . ( $iP ? $iP : '-' ) . ")";
			}
		}

		if ( ! empty( $landingUrl ) || ! empty( $referrer ) ) {
			$summaryLines[] = "\n--- ÉRKEZÉSI MARKETING ADATOK ---";
			if ( ! empty( $landingUrl ) ) {
				$summaryLines[] = "• Landing oldal: " . $landingUrl;
			}
			if ( ! empty( $referrer ) ) {
				$summaryLines[] = "• Referrer: " . $referrer;
			}
		}

		$fullNote = implode( "\n", $summaryLines );

		// Frissítjük a paramétereket a Pipedrive és külső integrációk számára
		$params['customer_note']  = $note;
		$params['note']           = $fullNote;
		$params['full_note']      = $fullNote;
		$params['pipedrive_note'] = $fullNote;

		if ( $isStep3Help ) {
			$params['label']           = 'rockile-segitseg';
			$params['tag']             = 'rockile-segitseg';
			$params['tags']            = array( 'rockile-segitseg' );
			$params['deal_label']      = 'rockile-segitseg';
			$params['pipedrive_label'] = 'rockile-segitseg';
		}

		// Tételes anyagszükséglet táblázat HTML összeállítása (ha van kalkulált lista)
		$itemsTableHtml = '';
		if ( $hasItems ) {
			$rows = array();
			foreach ( $items as $idx => $item ) {
				$iName      = isset( $item['name'] ) ? esc_html( $item['name'] ) : '-';
				$iSku       = isset( $item['sku'] ) ? esc_html( $item['sku'] ) : '-';
				$iQty       = isset( $item['quantity'] ) ? (int) $item['quantity'] : 1;
				$iUnit      = isset( $item['unit'] ) ? esc_html( $item['unit'] ) : 'db';
				$iUnitPrice = isset( $item['unitPriceFormatted'] ) ? esc_html( $item['unitPriceFormatted'] ) : ( isset( $item['unitPrice'] ) ? number_format( (float) $item['unitPrice'], 0, ',', ' ' ) . ' Ft' : '-' );
				$iLineTotal = isset( $item['lineTotalFormatted'] ) ? esc_html( $item['lineTotalFormatted'] ) : ( isset( $item['lineTotal'] ) ? number_format( (float) $item['lineTotal'], 0, ',', ' ' ) . ' Ft' : '-' );
				$bgColor    = ( $idx % 2 === 1 ) ? '#fcfaf8' : '#ffffff';

				$rows[] = sprintf(
					'<tr style="background:%s;border-bottom:1px solid #e2e8f0;">
						<td style="padding:8px 10px;font-size:12px;color:#022a50;font-weight:600;">%s</td>
						<td style="padding:8px 10px;font-size:11px;font-family:monospace;color:#64748b;">%s</td>
						<td style="padding:8px 10px;font-size:12px;color:#022a50;text-align:right;font-weight:700;">%d %s</td>
						<td style="padding:8px 10px;font-size:12px;color:#475569;text-align:right;">%s</td>
						<td style="padding:8px 10px;font-size:12px;color:#022a50;text-align:right;font-weight:700;">%s</td>
					</tr>',
					$bgColor,
					$iName,
					$iSku,
					$iQty,
					$iUnit,
					$iUnitPrice,
					$iLineTotal
				);
			}

			$itemsTableHtml = sprintf(
				'<div style="margin-top:20px;border:1.5px solid #022a50;border-radius:4px;overflow:hidden;">
					<div style="background:#022a50;color:#ffffff;padding:10px 15px;font-size:13px;font-weight:700;">Kalkulált anyagszükséglet és árajánlat:</div>
					<table style="width:100%%;border-collapse:collapse;text-align:left;">
						<thead>
							<tr style="background:#f1f5f9;color:#022a50;border-bottom:1px solid #cbd5e1;">
								<th style="padding:8px 10px;font-size:11px;text-transform:uppercase;">Termék</th>
								<th style="padding:8px 10px;font-size:11px;text-transform:uppercase;">Cikkszám</th>
								<th style="padding:8px 10px;font-size:11px;text-transform:uppercase;text-align:right;">Mennyiség</th>
								<th style="padding:8px 10px;font-size:11px;text-transform:uppercase;text-align:right;">Bruttó egységár</th>
								<th style="padding:8px 10px;font-size:11px;text-transform:uppercase;text-align:right;">Bruttó összesen</th>
							</tr>
						</thead>
						<tbody>
							%s
						</tbody>
					</table>
					<div style="background:#f8fafc;border-top:1.5px solid #cbd5e1;padding:12px 15px;">
						<div style="display:flex;justify-content:space-between;padding:3px 0;font-size:12px;color:#475569;">
							<span>Anyagok részösszege (bruttó, 27%% áfával):</span>
							<strong style="color:#022a50;">%s Ft</strong>
						</div>
						<div style="display:flex;justify-content:space-between;padding:3px 0;font-size:12px;color:#475569;">
							<span>Raklap díj (%d db raklap × br. 3 810 Ft):</span>
							<strong style="color:#022a50;">%s Ft</strong>
						</div>
						<div style="display:flex;justify-content:space-between;padding:3px 0;font-size:12px;color:#475569;">
							<span>Várható házhozszállítási díj (%d raklap × br. 38 100 Ft):</span>
							<strong style="color:#022a50;">%s Ft</strong>
						</div>
					</div>
					<div style="background:#022a50;color:#ffffff;padding:12px 15px;display:flex;justify-content:space-between;align-items:center;">
						<div>
							<div style="font-size:13px;font-weight:700;text-transform:uppercase;">Árajánlat végösszege (bruttó):</div>
							<div style="font-size:10px;color:#cbd5e1;">Anyagok + Raklap díj + Szállítás (27%% ÁFÁ-val)</div>
						</div>
						<strong style="font-size:18px;font-weight:800;color:#ffffff;">%s Ft</strong>
					</div>
				</div>',
				implode( '', $rows ),
				number_format( $materialTotal, 0, ',', ' ' ),
				$paletteCount,
				number_format( $paletteFeeTotal, 0, ',', ' ' ),
				$paletteCount,
				number_format( $shippingFeeTotal, 0, ',', ' ' ),
				number_format( $grandTotal, 0, ',', ' ' )
			);
		}

		// Email küldése az adminisztrátoroknak és a megadott címekre
		$adminRecipients = array_unique( array_filter( array(
			'info@profiteto.hu',
			'marketing@profiteto.hu',
			get_option( 'admin_email' ),
		) ) );
		$adminRecipients = apply_filters( 'rocktile_calculator_admin_recipients', $adminRecipients );

		if ( $isStep3Help ) {
			$mailTitle = 'Rocktile – Segítségkérés a méretekhez (3. lépés)';
			$subject   = sprintf( '[Rocktile Kalkulátor] [rockile-segitseg] Segítségkérés a méretekhez: %s (%s)', $name, $roofName );
		} else {
			$mailTitle = $hasItems ? 'Rocktile – Új kalkulált árajánlat megkeresés' : 'Rocktile – Új szakértői ajánlatkérés';
			$subject   = sprintf( '[Rocktile Kalkulátor] %s: %s (%s)', $hasItems ? 'Új árajánlat' : 'Szakértői ajánlatkérés', $name, $roofName );
		}

		// Formázzuk a marketing / érkezési adatokat
		$landingHtml = ! empty( $landingUrl )
			? sprintf( '<a href="%s" style="color: #0284c7; word-break: break-all;" target="_blank">%s</a>', esc_url( $landingUrl ), esc_html( $landingUrl ) )
			: '<span style="color: #94a3b8;"><em>Közvetlen látogatás vagy nem azonosítható forrás</em></span>';

		$referrerHtml = ! empty( $referrer )
			? sprintf( '<p style="margin: 6px 0;"><strong>Hivatkozó oldal (Referrer):</strong> <a href="%s" style="color: #64748b; word-break: break-all;" target="_blank">%s</a></p>', esc_url( $referrer ), esc_html( $referrer ) )
			: '';

		$phoneHtml = ! empty( $phone )
			? sprintf( '<a href="tel:%s" style="color: #022a50;">%s</a>', esc_attr( $phone ), esc_html( $phone ) )
			: '<span style="color: #94a3b8;"><em>Nem adott meg telefonszámot</em></span>';

		$helpBannerHtml = $isStep3Help
			? '<div style="background: #eff6ff; border-left: 4px solid #0284c7; padding: 12px 16px; margin: 20px 25px 0 25px; border-radius: 4px;">
				<strong style="color: #0369a1; font-size: 14px;">Segítségkérés a 3. lépésnél (Méretek felmérése)</strong>
				<p style="margin: 4px 0 0 0; font-size: 13px; color: #1e3a8a; line-height: 1.4;">A látogató a méretek megadásánál kér segítséget. Elérhetőségeit megadta, hogy munkatársunk segítse a felmérést és az árajánlat elkészítését.</p>
				<p style="margin: 8px 0 0 0; font-size: 12px; color: #0369a1;"><strong>Pipedrive Címke:</strong> <span style="background: #fef08a; color: #854d0e; font-weight: bold; font-family: monospace; padding: 2px 8px; border-radius: 3px;">rockile-segitseg</span></p>
			</div>'
			: '';

		$pipedriveLabelRow = $isStep3Help
			? '<p style="margin: 6px 0;"><strong>Pipedrive Címke:</strong> <span style="background: #fef08a; color: #854d0e; font-weight: bold; font-family: monospace; padding: 2px 8px; border-radius: 3px;">rockile-segitseg</span></p>'
			: '';

		$message = sprintf(
			'<!DOCTYPE html>
			<html>
			<head><meta charset="utf-8"></head>
			<body style="font-family: Arial, sans-serif; color: #1e293b; line-height: 1.5; padding: 20px; background-color: #f8fafc;">
				<div style="max-width: 680px; margin: 0 auto; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 6px; overflow: hidden;">
					<div style="background: #022a50; color: #ffffff; padding: 20px 25px;">
						<h2 style="margin: 0; font-size: 20px;">%s</h2>
						<p style="margin: 5px 0 0 0; font-size: 13px; color: #cbd5e1;">Azonosító: <strong>%s</strong> | Dátum: %s</p>
					</div>

					%s

					<div style="padding: 25px;">
						<h3 style="color: #022a50; border-bottom: 2px solid #e2e8f0; padding-bottom: 8px; margin-top: 0;">Ügyfél elérhetőségei:</h3>
						<p style="margin: 6px 0;"><strong>Név:</strong> %s</p>
						<p style="margin: 6px 0;"><strong>Email:</strong> <a href="mailto:%s" style="color: #cf3f29;">%s</a></p>
						<p style="margin: 6px 0;"><strong>Telefonszám:</strong> %s</p>
						%s

						<h3 style="color: #022a50; border-bottom: 2px solid #e2e8f0; padding-bottom: 8px; margin-top: 25px;">Érkezési adatok (Marketing forrás):</h3>
						<p style="margin: 6px 0;"><strong>Érkezési oldal (Landing URL):</strong> %s</p>
						%s

						<h3 style="color: #022a50; border-bottom: 2px solid #e2e8f0; padding-bottom: 8px; margin-top: 25px;">Tető adatok:</h3>
						<p style="margin: 6px 0;"><strong>Tetőforma:</strong> %s</p>
						<p style="margin: 6px 0;"><strong>Termékcsalád:</strong> %s</p>
						<p style="margin: 6px 0;"><strong>Választott szín:</strong> %s</p>
						<p style="margin: 6px 0;"><strong>Rögzítés módja:</strong> %s</p>
						<p style="margin: 6px 0;"><strong>Kémény:</strong> %s</p>
						<p style="margin: 6px 0;"><strong>Szellőzés:</strong> %s</p>
						<p style="margin: 6px 0;"><strong>Oromszegély:</strong> %s</p>

						<h4 style="color: #022a50; margin-bottom: 5px; margin-top: 15px;">Megadott méretek:</h4>
						%s

						%s

						<div style="background: #fffaf6; border-left: 4px solid #cf3f29; padding: 15px; margin-top: 25px; border-radius: 4px;">
							<strong style="color: #022a50; font-size: 14px; display: block; margin-bottom: 6px;">Pipedrive / CRM Részletes Megjegyzés és Tetőadatok:</strong>
							<pre style="white-space: pre-wrap; font-family: Arial, sans-serif; font-size: 12px; color: #334155; line-height: 1.5; margin: 0; background: #ffffff; padding: 12px; border: 1px solid #fed7aa; border-radius: 3px;">%s</pre>
						</div>
					</div>

					<div style="background: #f1f5f9; padding: 15px 25px; font-size: 12px; color: #64748b; text-align: center; border-top: 1px solid #e2e8f0;">
						Ez az üzenet automatikusan generálódott a rocktile.eu online kalkulátorából.
					</div>
				</div>
			</body>
			</html>',
			esc_html( $mailTitle ),
			esc_html( $quoteId ),
			esc_html( current_time( 'Y.m.d. H:i' ) ),
			$helpBannerHtml,
			esc_html( $name ),
			esc_attr( $email ),
			esc_html( $email ),
			$phoneHtml,
			$pipedriveLabelRow,
			$landingHtml,
			$referrerHtml,
			esc_html( $roofName ),
			esc_html( $productName ),
			esc_html( $colorName ),
			esc_html( $fasteningText ),
			$chimneyText,
			esc_html( $ventText ),
			esc_html( $vergeText ),
			$dimHtml,
			$itemsTableHtml,
			esc_html( $fullNote )
		);

		// Feladó adatok (kalkulator@rocktile.eu)
		$fromEmail = apply_filters( 'rocktile_calculator_from_email', 'kalkulator@rocktile.eu' );
		$fromName  = apply_filters( 'rocktile_calculator_from_name', 'Rocktile Tetőrendszerek' );
		$fromHeader = sprintf( 'From: %s <%s>', $fromName, $fromEmail );

		$headers = array(
			'Content-Type: text/html; charset=UTF-8',
			$fromHeader,
			sprintf( 'Reply-To: %s <%s>', $name, $email ),
		);

		wp_mail( $adminRecipients, $subject, $message, $headers );

		// Visszaigazoló email az ügyfélnek
		if ( $isStep3Help ) {
			$clientSubject = sprintf( 'Köszönjük megkeresését – Segítség a méretekhez – Rocktile (%s)', $quoteId );
			$clientIntroText = sprintf(
				'<p>Kedves <strong>%s</strong>!</p>
				<p>Köszönjük, hogy felkereste a Rocktile online tetőkalkulátorát. Értesültünk róla, hogy szakértői segítségre van szüksége a tető pontos méreteinek megadásához és a tetőanyagok felméréséhez (Azonosító: <strong>%s</strong>).</p>
				<p>Kollégánk hamarosan felveszi Önnel a kapcsolatot a megadott elérhetőségein (%s), hogy egyeztessen Önnel a részletekről, és segítsen a pontos árajánlat összeállításában.</p>',
				esc_html( $name ),
				esc_html( $quoteId ),
				esc_html( $email . ( ! empty( $phone ) ? ' / ' . $phone : '' ) )
			);
		} else {
			$clientSubject = sprintf( 'Köszönjük ajánlatkérését – Rocktile Tetőrendszerek (%s)', $quoteId );
			$clientIntroText = sprintf(
				'<p>Kedves <strong>%s</strong>!</p>
				<p>Köszönjük, hogy a Rocktile online tetőkalkulátorát használta. Árajánlatát és megkeresését sikeresen továbbítottuk munkatársainknak (Azonosító: <strong>%s</strong>).</p>
				<p>Kollégánk hamarosan felveszi Önnel a kapcsolatot a megadott elérhetőségein (%s) az árajánlat részleteivel és a szállítással kapcsolatban.</p>',
				esc_html( $name ),
				esc_html( $quoteId ),
				esc_html( $email . ( ! empty( $phone ) ? ' / ' . $phone : '' ) )
			);
		}

		$clientMessage = sprintf(
			'<!DOCTYPE html>
			<html>
			<head><meta charset="utf-8"></head>
			<body style="font-family: Arial, sans-serif; color: #1e293b; line-height: 1.5; padding: 20px; background-color: #f8fafc;">
				<div style="max-width: 650px; margin: 0 auto; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 6px; overflow: hidden;">
					<div style="background: #022a50; color: #ffffff; padding: 20px 25px;">
						<h2 style="margin: 0; font-size: 20px;">Rocktile Tetőrendszerek</h2>
						<p style="margin: 5px 0 0 0; font-size: 13px; color: #cbd5e1;">%s</p>
					</div>

					<div style="padding: 25px;">
						%s

						<div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 4px; padding: 15px; margin: 20px 0;">
							<h4 style="margin: 0 0 10px 0; color: #022a50;">Kiválasztott tető paraméterek:</h4>
							<p style="margin: 4px 0; font-size: 13px;"><strong>Tetőforma:</strong> %s</p>
							<p style="margin: 4px 0; font-size: 13px;"><strong>Termékcsalád:</strong> %s</p>
							<p style="margin: 4px 0; font-size: 13px;"><strong>Szín:</strong> %s</p>
							<p style="margin: 4px 0; font-size: 13px;"><strong>Kémény:</strong> %s</p>
						</div>

						%s

						<p style="font-size: 13px; color: #64748b; margin-top: 25px;">Kérdés esetén forduljon hozzánk bizalommal a <a href="https://rocktile.eu" style="color: #cf3f29;">rocktile.eu</a> weboldalon vagy az <a href="mailto:info@profiteto.hu" style="color: #022a50;">info@profiteto.hu</a> címen!</p>
					</div>
				</div>
			</body>
			</html>',
			esc_html( $isStep3Help ? 'Segítségkérés visszaigazolása' : 'Árajánlat visszaigazolása' ),
			$clientIntroText,
			esc_html( $roofName ),
			esc_html( $productName ),
			esc_html( $colorName ),
			$chimneyText,
			$itemsTableHtml
		);

		$clientHeaders = array(
			'Content-Type: text/html; charset=UTF-8',
			$fromHeader,
			sprintf( 'Reply-To: %s <%s>', $fromName, $fromEmail ),
		);

		wp_mail( $email, $clientSubject, $clientMessage, $clientHeaders );

		/**
		 * Fire action when a quote request is successfully submitted from calculator.
		 * Allows Pipedrive and other integrations to sync quote requests.
		 *
		 * @param array  $params   Raw and calculated request parameters.
		 * @param string $quoteId  Generated unique quote request ID.
		 */
		do_action( 'rocktile_quote_request_submitted', $params, $quoteId );

		$respMessage = $isStep3Help
			? 'Segítségkérését sikeresen beküldtük kollégáinknak! Munkatársunk hamarosan felveszi Önnel a kapcsolatot.'
			: 'Ajánlatkérését sikeresen beküldtük kollégáinknak! Munkatársunk hamarosan felveszi Önnel a kapcsolatot.';

		$responsePayload = array(
			'success' => true,
			'quoteId' => $quoteId,
			'message' => $respMessage,
			'note'    => $fullNote,
		);

		if ( $isStep3Help ) {
			$responsePayload['label']           = 'rockile-segitseg';
			$responsePayload['tag']             = 'rockile-segitseg';
			$responsePayload['deal_label']      = 'rockile-segitseg';
			$responsePayload['pipedrive_label'] = 'rockile-segitseg';
		}

		return new WP_REST_Response( $responsePayload, 200 );
	}
}

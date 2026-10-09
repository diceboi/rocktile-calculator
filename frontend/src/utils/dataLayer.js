/**
 * Rocktile Tetőkalkulátor - Google Tag Manager / GA4 DataLayer Utility
 *
 * Biztonságos kommunikáció a böngésző window.dataLayer tömbjével.
 * Nem dob hibát akkor sem, ha a Tag Manager vagy a Google Site Kit még nem töltődött be,
 * vagy ha adblocker blokkolja azt.
 */

/**
 * Alapvető dataLayer push művelet
 * @param {Object} eventData
 */
export function pushDataLayer(eventData) {
  try {
    if (typeof window === 'undefined') return;
    window.dataLayer = window.dataLayer || [];
    window.dataLayer.push(eventData);
  } catch (err) {
    console.warn('[Rocktile Analytics] Nem sikerült az esemény továbbítása:', err);
  }
}

/**
 * Lépésváltás követése (Funnel / Tölcsér elemzés)
 * @param {number} stepNumber Lépés száma (1-6)
 * @param {Object} context További hasznos adatok (termék, tetőtípus, stb.)
 */
export function trackStepView(stepNumber, context = {}) {
  const stepMap = {
    1: { name: 'Termék kiválasztása', key: 'product' },
    2: { name: 'Tetőforma kiválasztása', key: 'roof_type' },
    3: { name: 'Méretek megadása', key: 'dimensions' },
    4: { name: 'Szellőzés és kémény', key: 'ventilation' },
    5: { name: 'Rögzítés módja', key: 'fastening' },
    6: { name: 'Összesítés és árajánlat', key: 'summary' },
  };

  const stepInfo = stepMap[stepNumber] || { name: `Lépés ${stepNumber}`, key: `step_${stepNumber}` };

  pushDataLayer({
    event: 'calculator_step_view',
    step_number: stepNumber,
    step_name: stepInfo.name,
    step_key: stepInfo.key,
    product_id: context.productId || null,
    product_name: context.productName || null,
    color_id: context.colorId || null,
    roof_type: context.roofType || null,
    roof_subtype: context.roofSubtype || null,
  });
}

/**
 * Sikeres árajánlat elküldése a kollégáknak (Fő konverzió / Lead)
 * @param {Object} details
 */
export function trackQuoteSubmission(details = {}) {
  pushDataLayer({
    event: 'calculator_quote_submitted',
    quote_id: details.quoteId || null,
    value: typeof details.value === 'number' ? details.value : null,
    currency: 'HUF',
    product_name: details.productName || null,
    color_name: details.colorName || null,
    roof_type: details.roofType || null,
    palette_count: details.paletteCount || 0,
    shipping_fee: details.shippingFee || 0,
    material_fee: details.materialFee || 0,
  });
}

/**
 * Szakértői felülvizsgálat kérése (Egyedi tetőknél)
 * @param {Object} details
 */
export function trackReviewRequest(details = {}) {
  pushDataLayer({
    event: 'calculator_review_request',
    quote_id: details.quoteId || null,
    product_name: details.productName || null,
    color_name: details.colorName || null,
    roof_type: details.roofType || null,
  });
}

/**
 * Árajánlat PDF letöltése
 * @param {Object} details
 */
export function trackPdfDownload(details = {}) {
  pushDataLayer({
    event: 'calculator_pdf_download',
    value: typeof details.value === 'number' ? details.value : null,
    currency: 'HUF',
    product_name: details.productName || null,
    roof_type: details.roofType || null,
  });
}

/**
 * Sikeres kosárba helyezés
 * @param {Object} details
 */
export function trackAddToCart(details = {}) {
  pushDataLayer({
    event: 'calculator_add_to_cart',
    value: typeof details.value === 'number' ? details.value : null,
    currency: 'HUF',
    items_count: details.itemsCount || 0,
    product_name: details.productName || null,
  });
}

import jsPDF from 'jspdf';
import html2canvas from 'html2canvas';

/**
 * Szám formázása forint formátumba.
 */
function formatCurrency(amount) {
  if (amount === null || amount === undefined || isNaN(amount)) return '-';
  return new Intl.NumberFormat('hu-HU', {
    style: 'currency',
    currency: 'HUF',
    maximumFractionDigits: 0,
  }).format(amount);
}

/**
 * Generál és letölt egy prémium minőségű, ékezetbiztos Rocktile árajánlat PDF-et.
 */
export async function generatePdfQuote({
  calculationId,
  productName,
  colorName,
  roofTypeName,
  dimensions,
  dimensionLabels,
  hasChimney,
  chimneyCount,
  hasVentilation,
  ventilationCount,
  items,
  totalAmount,
  paletteCount,
  paletteFeeTotal,
  shippingFeeTotal,
  note,
}) {
  const quoteId   = calculationId || 'RT-' + Date.now().toString().slice(-6);
  const quoteDate = new Date().toLocaleDateString('hu-HU', {
    year: 'numeric',
    month: '2-digit',
    day: '2-digit',
  });

  const grandTotal = (totalAmount || 0) + (paletteFeeTotal || 0) + (shippingFeeTotal || 0);

  // Méret adatok kigyűjtése
  const dimEntries = Object.entries(dimensions || {})
    .filter(([_, val]) => val !== null && val !== undefined && val !== '')
    .map(([key, val]) => {
      const label = dimensionLabels?.[key]?.label || key;
      const unit  = dimensionLabels?.[key]?.unit || '';
      return `<div style="background:#f8fafc;border:1px solid #e2e8f0;padding:6px 10px;border-radius:3px;">
        <span style="color:#64748b;font-size:11px;display:block;">${label}:</span>
        <strong style="color:#022a50;font-size:13px;">${val} ${unit}</strong>
      </div>`;
    }).join('');

  // Tételek táblázat sorai
  const rowsHtml = (items || []).map((item, idx) => {
    let qtyText = `${item.quantity} ${item.unit}`;
    if (item.key === 'baseTile') {
      const pcs = item.pieces || (item.quantity * 12);
      qtyText = `${item.quantity} csomag <span style="color:#64748b;font-size:11px;font-weight:normal;">(${pcs} db)</span>`;
    }
    const unitPrice = item.unitPriceFormatted || formatCurrency(item.unitPrice);
    const lineTotal = item.lineTotalFormatted || formatCurrency(item.lineTotal || (item.unitPrice * item.quantity));
    const isEven = idx % 2 === 1;

    return `
      <tr style="background:${isEven ? '#fcfaf8' : '#ffffff'};border-bottom:1px solid #e2e8f0;">
        <td style="padding:8px 12px;font-size:12px;color:#022a50;font-weight:600;">
          ${item.name || '-'}
        </td>
        <td style="padding:8px 12px;font-size:11px;font-family:monospace;color:#64748b;">
          ${item.sku || '-'}
        </td>
        <td style="padding:8px 12px;font-size:12px;color:#022a50;text-align:right;font-weight:700;">
          ${qtyText}
        </td>
        <td style="padding:8px 12px;font-size:12px;color:#475569;text-align:right;white-space:nowrap;">
          ${unitPrice}
        </td>
        <td style="padding:8px 12px;font-size:13px;color:#022a50;text-align:right;font-weight:700;white-space:nowrap;">
          ${lineTotal}
        </td>
      </tr>
    `;
  }).join('');

  // HTML sablon konténer létrehozása
  const container = document.createElement('div');
  container.style.position = 'fixed';
  container.style.left     = '-99999px';
  container.style.top      = '0';
  container.style.width    = '800px';
  container.style.minHeight = '1120px';
  container.style.background = '#ffffff';
  container.style.color    = '#1e293b';
  container.style.fontFamily = '"Montserrat", "Inter", -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif';
  container.style.padding  = '0';
  container.style.margin   = '0';
  container.style.boxSizing = 'border-box';
  container.style.webkitFontSmoothing = 'antialiased';

  container.innerHTML = `
    <div style="padding:0 0 30px 0;width:100%;box-sizing:border-box;">
      
      <!-- 1. Fejléc sáv -->
      <div style="background:#022a50;color:#ffffff;padding:20px 30px;display:flex;justify-content:space-between;align-items:center;">
        <div>
          <h1 style="margin:0;font-size:24px;font-weight:800;letter-spacing:1px;color:#ffffff;">ROCKTILE</h1>
          <p style="margin:4px 0 0 0;font-size:11px;color:#cbd5e1;font-weight:500;">Prémium Kőzúzalékos Acél Tetőrendszerek</p>
        </div>
        <div style="text-align:right;">
          <h2 style="margin:0;font-size:14px;font-weight:700;color:#ffffff;letter-spacing:0.5px;">ÁRAJÁNLAT / ANYAGSZÜKSÉGLET</h2>
          <p style="margin:4px 0 0 0;font-size:11px;color:#cbd5e1;">Dátum: ${quoteDate}</p>
        </div>
      </div>

      <div style="padding:24px 30px 0 30px;">

        <!-- 2. Meta adatok doboz -->
        <div style="background:#fffaf6;border:1.5px solid #e2ddd9;border-radius:4px;padding:16px 20px;margin-bottom:20px;">
          <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
            <div>
              <div style="font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:0.5px;">Ajánlat azonosító:</div>
              <div style="font-size:14px;font-weight:800;color:#cf3f29;margin-top:2px;">${quoteId}</div>
            </div>
            <div>
              <div style="font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:0.5px;">Tetőforma:</div>
              <div style="font-size:14px;font-weight:700;color:#022a50;margin-top:2px;">${roofTypeName || '-'}</div>
            </div>
            <div>
              <div style="font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:0.5px;">Termékcsalád:</div>
              <div style="font-size:13px;font-weight:700;color:#022a50;margin-top:2px;">${productName || 'Rocktile Classic Bond'}</div>
            </div>
            <div>
              <div style="font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:0.5px;">Kémény:</div>
              <div style="font-size:13px;font-weight:700;color:#022a50;margin-top:2px;">${hasChimney ? `${chimneyCount || 1} db (${(chimneyCount || 1) * 2} db síklemez)` : 'Nincs'}</div>
            </div>
            <div>
              <div style="font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:0.5px;">Választott szín:</div>
              <div style="font-size:13px;font-weight:700;color:#022a50;margin-top:2px;">${colorName || '-'}</div>
            </div>
            <div>
              <div style="font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:0.5px;">Szellőző:</div>
              <div style="font-size:13px;font-weight:700;color:#022a50;margin-top:2px;">${hasVentilation ? `${ventilationCount || 1} db átvezető elem` : 'Nem'}</div>
            </div>
          </div>
        </div>

        <!-- 3. Megadott méretek -->
        <div style="margin-bottom:20px;">
          <div style="font-size:12px;font-weight:800;color:#022a50;text-transform:uppercase;letter-spacing:0.5px;margin-bottom:8px;">Megadott tetőméretek:</div>
          <div style="display:flex;flex-wrap:wrap;gap:8px;">
            ${dimEntries}
          </div>
        </div>

        <!-- 4. Anyagszükséglet táblázat vagy Egyedi tető tájékoztató -->
        ${items && items.length > 0 ? `
          <div style="border:1.5px solid #022a50;border-radius:4px;overflow:hidden;margin-bottom:18px;">
            <table style="width:100%;border-collapse:collapse;text-align:left;">
              <thead>
                <tr style="background:#022a50;color:#ffffff;">
                  <th style="padding:10px 12px;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.5px;">Termék megnevezése</th>
                  <th style="padding:10px 12px;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.5px;">Cikkszám</th>
                  <th style="padding:10px 12px;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.5px;text-align:right;">Mennyiség</th>
                  <th style="padding:10px 12px;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.5px;text-align:right;">Bruttó egységár</th>
                  <th style="padding:10px 12px;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.5px;text-align:right;">Bruttó összesen</th>
                </tr>
              </thead>
              <tbody>
                ${rowsHtml}
              </tbody>
            </table>

            <!-- Költségbontás sáv (Anyagok, Raklap, Szállítás a végösszeg elé) -->
            <div style="background:#f8fafc;border-top:1.5px solid #cbd5e1;padding:12px 20px;">
              <div style="display:flex;justify-content:space-between;align-items:center;padding:4px 0;font-size:12px;color:#475569;">
                <span>Anyagok részösszege (bruttó, 27% áfával):</span>
                <strong style="color:#022a50;font-size:13px;">${formatCurrency(totalAmount)}</strong>
              </div>
              <div style="display:flex;justify-content:space-between;align-items:center;padding:4px 0;font-size:12px;color:#475569;">
                <span>Raklap díj (${paletteCount} db raklap × br. 3 810 Ft):</span>
                <strong style="color:#022a50;font-size:13px;">${formatCurrency(paletteFeeTotal)}</strong>
              </div>
              <div style="display:flex;justify-content:space-between;align-items:center;padding:4px 0;font-size:12px;color:#475569;">
                <span>Várható házhozszállítási díj (${paletteCount} raklap × br. 38 100 Ft):</span>
                <strong style="color:#022a50;font-size:13px;">${formatCurrency(shippingFeeTotal)}</strong>
              </div>
            </div>

            <!-- Végösszeg sáv -->
            <div style="background:#022a50;color:#ffffff;padding:14px 20px;display:flex;justify-content:space-between;align-items:center;">
              <div>
                <div style="font-size:13px;font-weight:800;text-transform:uppercase;letter-spacing:0.5px;color:#ffffff;">
                  Árajánlat végösszege (bruttó):
                </div>
                <div style="font-size:10px;color:#cbd5e1;margin-top:2px;">
                  Anyagok + Raklap díj + Szállítás (27% ÁFÁ-val)
                </div>
              </div>
              <div style="font-size:22px;font-weight:800;color:#ffffff;">
                ${formatCurrency(grandTotal)}
              </div>
            </div>
          </div>
        ` : `
          <div style="background:#fffaf0;border:1.5px solid #fbd38d;border-radius:4px;padding:18px 20px;margin-bottom:18px;">
            <div style="font-size:14px;font-weight:800;color:#7b341e;margin-bottom:4px;">Egyedi tetőforma – szakértői felülvizsgálat szükséges</div>
            <div style="font-size:12px;color:#9c4221;line-height:1.5;">
              Az egyedi tetőforma összetett geometriája miatt az anyagszükségletet műszaki szakértőnk ellenőrzi, és hamarosan felveszi Önnel a kapcsolatot a megadott adatok alapján.
            </div>
          </div>
        `}

        <!-- 6. Megjegyzés (ha van) -->
        ${note && note.trim() ? `
          <div style="background:#fffaf6;border:1px dashed #cbd5e1;border-radius:4px;padding:10px 14px;margin-bottom:16px;">
            <div style="font-size:11px;font-weight:700;color:#022a50;margin-bottom:2px;">Ügyfél megjegyzés:</div>
            <div style="font-size:11px;color:#475569;font-style:italic;">${note.trim()}</div>
          </div>
        ` : ''}

        <!-- 7. Lábléc -->
        <div style="border-top:1px solid #e2e8f0;padding-top:12px;margin-top:20px;display:flex;justify-content:space-between;align-items:center;font-size:10px;color:#94a3b8;">
          <div>Rocktile Tetőrendszerek • online tetőkalkulátor</div>
          <div>Weboldal: rocktile.eu • Az árak a 27% ÁFÁ-t tartalmazzák.</div>
        </div>

      </div>
    </div>
  `;

  document.body.appendChild(container);

  try {
    const canvas = await html2canvas(container, {
      scale: 2, // 2x felbontás a tűéles nyomdai minőségért
      useCORS: true,
      logging: false,
      backgroundColor: '#ffffff',
    });

    const imgData = canvas.toDataURL('image/jpeg', 0.95);
    const pdf = new jsPDF({
      orientation: 'portrait',
      unit: 'mm',
      format: 'a4',
    });

    const pdfWidth = pdf.internal.pageSize.getWidth();
    const pdfHeight = (canvas.height * pdfWidth) / canvas.width;

    pdf.addImage(imgData, 'JPEG', 0, 0, pdfWidth, pdfHeight, undefined, 'FAST');
    pdf.save(`Rocktile-Arajanlat-${quoteId}.pdf`);
  } catch (error) {
    console.error('Hiba történt a PDF generálás során:', error);
    throw error;
  } finally {
    if (container.parentNode) {
      container.parentNode.removeChild(container);
    }
  }
}

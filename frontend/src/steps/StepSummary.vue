<script setup>
import { ref, computed, onMounted } from 'vue';
import { useCalculator } from '../composables/useCalculator.js';
import { dimensionFields } from '../config/roofTypes.js';
import { generatePdfQuote } from '../utils/pdfGenerator.js';
import {
  trackQuoteSubmission,
  trackReviewRequest,
  trackPdfDownload,
  trackAddToCart,
} from '../utils/dataLayer.js';

const {
  state,
  selectedProduct,
  selectedColor,
  selectedRoofGroup,
  selectedRoofConfig,
  activeFieldKeys,
  hasVerge,
  selectedVerge,
  goToStep,
} = useCalculator();

const loading           = ref(false);
const error             = ref(null);
const calculationResult = ref(null);
const editableItems     = ref([]);

const cartLoading       = ref(false);
const cartError         = ref(null);
const cartSuccess       = ref(false);
const pdfLoading        = ref(false);

/* ─── Formatter ─── */
function formatHuf(amount) {
  if (amount === null || amount === undefined || isNaN(amount)) return '-';
  return new Intl.NumberFormat('hu-HU', {
    style: 'currency',
    currency: 'HUF',
    maximumFractionDigits: 0,
  }).format(amount);
}

/* ─── Calculate ─── */
async function runCalculation() {
  loading.value = true;
  error.value   = null;

  try {
    const payload = {
      product:          state.product,
      color:            state.color,
      roofType:         state.roofType,
      roofSubtype:      state.roofSubtype,
      dimensions:       { ...state.dimensions },
      ventilation:      state.hasVentilation ? 'standard' : 'none',
      hasVentilation:   Boolean(state.hasVentilation),
      ventilationCount: state.hasVentilation ? (state.ventilationCount || 1) : 0,
      hasChimney:       Boolean(state.hasChimney),
      chimneyCount:     state.hasChimney ? (state.chimneyCount || 1) : 0,
      vergeType:        state.vergeType,
      addSparePackage:  Boolean(state.addSparePackage),
      note:             state.note,
    };

    const response = await fetch('/wp-json/rocktile/v1/calculate', {
      method:      'POST',
      headers:     { 'Content-Type': 'application/json' },
      credentials: 'same-origin',
      body:        JSON.stringify(payload),
    });

    const data = await response.json();

    if (!response.ok) {
      throw new Error(data.message || 'Hiba történt a kalkuláció során.');
    }

    calculationResult.value = data;
    editableItems.value     = JSON.parse(JSON.stringify(data.items || []));
  } catch (err) {
    error.value             = err.message || 'Nem sikerült kiszámítani az anyagszükségletet.';
    calculationResult.value = null;
    editableItems.value     = [];
  } finally {
    loading.value = false;
  }
}

function onToggleSparePackage() {
  runCalculation();
}

/* ─── Item Editing ─── */
function updateItemQuantity(idx, newQty) {
  const q = parseInt(newQty, 10);
  if (isNaN(q) || q < 1) return;
  const item = editableItems.value[idx];
  if (!item) return;

  item.quantity = q;
  if (item.key === 'baseTile') {
    item.pieces = q * 12;
  }
  if (item.unitPrice) {
    item.lineTotal = item.unitPrice * q;
    item.lineTotalFormatted = formatHuf(item.lineTotal);
  }
}

function removeItem(idx) {
  editableItems.value.splice(idx, 1);
}

function resetToCalculated() {
  if (calculationResult.value?.items) {
    editableItems.value = JSON.parse(JSON.stringify(calculationResult.value.items));
  }
}

const isItemsModified = computed(() => {
  if (!calculationResult.value?.items) return false;
  const orig = calculationResult.value.items;
  const curr = editableItems.value;
  if (orig.length !== curr.length) return true;

  return curr.some((item, i) => {
    return item.key !== orig[i]?.key || item.quantity !== orig[i]?.quantity;
  });
});

/* ─── Dynamic Total ─── */
const currentTotal = computed(() => {
  if (!editableItems.value.length) return 0;
  return editableItems.value.reduce((acc, item) => {
    const lt = item.lineTotal !== null && item.lineTotal !== undefined ? Number(item.lineTotal) : ((item.unitPrice || 0) * item.quantity);
    return acc + lt;
  }, 0);
});

const currentTotalFormatted = computed(() => formatHuf(currentTotal.value));

/* ─── Shipping & Pallet Calculation ─── */
const roofArea = computed(() => {
  const a = state.dimensions.roofArea;
  return typeof a === 'number' && !isNaN(a) ? a : 0;
});

const paletteCount = computed(() => {
  const area = roofArea.value;
  if (area <= 0) return 1;
  if (area <= 150) return 1;
  if (area <= 300) return 2;
  if (area <= 400) return 3;
  return Math.ceil(area / 150);
});

const PALETTE_FEE_BRUTTO = 3810;
const SHIPPING_FEE_BRUTTO = 38100; // 30.000 Ft + áfa = 38.100 Ft

const paletteFeeTotal = computed(() => paletteCount.value * PALETTE_FEE_BRUTTO);
const shippingFeeTotal = computed(() => paletteCount.value * SHIPPING_FEE_BRUTTO);

const grandTotal = computed(() => {
  return (currentTotal.value || 0) + (paletteFeeTotal.value || 0) + (shippingFeeTotal.value || 0);
});
const grandTotalFormatted = computed(() => formatHuf(grandTotal.value));

/* ─── Szakértői ajánlatkérés (Egyedi tető esetén) ─── */
const contactName = ref('');
const contactEmail = ref('');
const contactPhone = ref('');
const contactTouched = ref(false);
const reviewLoading = ref(false);
const reviewError = ref(null);
const reviewSuccess = ref(false);
const reviewQuoteId = ref(null);

async function sendExpertReviewRequest() {
  contactTouched.value = true;
  reviewError.value = null;

  if (!contactName.value.trim()) {
    reviewError.value = 'Kérjük, adja meg a nevét.';
    return;
  }
  if (!contactEmail.value.trim() || !contactEmail.value.includes('@')) {
    reviewError.value = 'Kérjük, adjon meg egy érvényes email címet.';
    return;
  }
  if (!contactPhone.value.trim()) {
    reviewError.value = 'Kérjük, adja meg a telefonszámát.';
    return;
  }

  reviewLoading.value = true;

  try {
    const payload = {
      name:             contactName.value.trim(),
      email:            contactEmail.value.trim(),
      phone:            contactPhone.value.trim(),
      product:          state.product,
      color:            state.color,
      roofType:         state.roofType,
      roofSubtype:      state.roofSubtype,
      dimensions:       { ...state.dimensions },
      ventilation:      state.hasVentilation ? 'standard' : 'none',
      hasVentilation:   Boolean(state.hasVentilation),
      ventilationCount: state.hasVentilation ? (state.ventilationCount || 1) : 0,
      hasChimney:       Boolean(state.hasChimney),
      chimneyCount:     state.hasChimney ? (state.chimneyCount || 1) : 0,
      vergeType:        state.vergeType,
      note:             state.note,
    };

    const res = await fetch('/wp-json/rocktile/v1/request-review', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      credentials: 'same-origin',
      body: JSON.stringify(payload),
    });

    const data = await res.json();
    if (!res.ok) {
      throw new Error(data.message || 'Nem sikerült az ajánlatkérés elküldése.');
    }

    reviewSuccess.value = true;
    reviewQuoteId.value = data.quoteId || 'RT-REQ';

    trackReviewRequest({
      quoteId: reviewQuoteId.value,
      productName: selectedProduct.value?.name,
      colorName: selectedColor.value?.fullName || selectedColor.value?.name,
      roofType: (selectedRoofGroup.value?.name || '') + (selectedRoofConfig.value?.name ? ' - ' + selectedRoofConfig.value.name : ''),
    });
  } catch (err) {
    reviewError.value = err.message || 'Hiba történt az elküldés során.';
  } finally {
    reviewLoading.value = false;
  }
}

/* ─── Ajánlat elküldése kollégáknak (Sztenderd kalkuláció esetén) ─── */
const isSendQuoteModalOpen = ref(false);
const sendQuoteLoading     = ref(false);
const sendQuoteError       = ref(null);
const sendQuoteSuccess     = ref(false);
const sentQuoteId          = ref(null);

const sendQuoteName        = ref('');
const sendQuoteEmail       = ref('');
const sendQuotePhone       = ref('');
const sendQuoteNote        = ref('');
const sendQuoteTouched     = ref(false);

function openSendQuoteModal() {
  sendQuoteNote.value = state.note || '';
  sendQuoteError.value = null;
  sendQuoteTouched.value = false;
  isSendQuoteModalOpen.value = true;
}

function closeSendQuoteModal() {
  isSendQuoteModalOpen.value = false;
}

async function submitSendQuoteToStaff() {
  sendQuoteTouched.value = true;
  sendQuoteError.value = null;

  if (!sendQuoteName.value.trim()) {
    sendQuoteError.value = 'Kérjük, adja meg a nevét.';
    return;
  }
  if (!sendQuoteEmail.value.trim() || !sendQuoteEmail.value.includes('@')) {
    sendQuoteError.value = 'Kérjük, adjon meg egy érvényes email címet.';
    return;
  }
  if (!sendQuotePhone.value.trim()) {
    sendQuoteError.value = 'Kérjük, adja meg a telefonszámát.';
    return;
  }

  sendQuoteLoading.value = true;

  try {
    const payload = {
      name:             sendQuoteName.value.trim(),
      email:            sendQuoteEmail.value.trim(),
      phone:            sendQuotePhone.value.trim(),
      note:             sendQuoteNote.value.trim(),
      product:          state.product,
      color:            state.color,
      roofType:         state.roofType,
      roofSubtype:      state.roofSubtype,
      dimensions:       { ...state.dimensions },
      ventilation:      state.hasVentilation ? 'standard' : 'none',
      hasVentilation:   Boolean(state.hasVentilation),
      ventilationCount: state.hasVentilation ? (state.ventilationCount || 1) : 0,
      hasChimney:       Boolean(state.hasChimney),
      chimneyCount:     state.hasChimney ? (state.chimneyCount || 1) : 0,
      vergeType:        state.vergeType,
      items:            editableItems.value || [],
      totalAmount:      currentTotal.value || 0,
      paletteCount:     paletteCount.value,
      paletteFeeTotal:  paletteFeeTotal.value,
      shippingFeeTotal: shippingFeeTotal.value,
      grandTotal:       grandTotal.value,
    };

    const res = await fetch('/wp-json/rocktile/v1/request-review', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      credentials: 'same-origin',
      body: JSON.stringify(payload),
    });

    const data = await res.json();
    if (!res.ok) {
      throw new Error(data.message || 'Nem sikerült az ajánlat elküldése.');
    }

    sendQuoteSuccess.value = true;
    sentQuoteId.value = data.quoteId || 'RT-AJ';
    isSendQuoteModalOpen.value = false;

    trackQuoteSubmission({
      quoteId: sentQuoteId.value,
      value: grandTotal.value,
      productName: selectedProduct.value?.name,
      colorName: selectedColor.value?.fullName || selectedColor.value?.name,
      roofType: (selectedRoofGroup.value?.name || '') + (selectedRoofConfig.value?.name ? ' - ' + selectedRoofConfig.value.name : ''),
      paletteCount: paletteCount.value,
      shippingFee: shippingFeeTotal.value,
      materialFee: currentTotal.value,
    });
  } catch (err) {
    sendQuoteError.value = err.message || 'Hiba történt az ajánlat elküldése során.';
  } finally {
    sendQuoteLoading.value = false;
  }
}

/* ─── PDF Generation ─── */
function downloadPdf() {
  pdfLoading.value = true;
  try {
    trackPdfDownload({
      value: grandTotal.value || currentTotal.value,
      productName: selectedProduct.value?.name,
      roofType: (selectedRoofGroup.value?.name || '') + (selectedRoofConfig.value?.name ? ' - ' + selectedRoofConfig.value.name : ''),
    });

    generatePdfQuote({
      calculationId:    'RT-' + Date.now().toString().slice(-6),
      productName:      selectedProduct.value?.name,
      colorName:        selectedColor.value?.fullName || selectedColor.value?.name,
      roofTypeName:     (selectedRoofGroup.value?.name || '') + (selectedRoofConfig.value?.name ? ' - ' + selectedRoofConfig.value.name : ''),
      dimensions:       { ...state.dimensions },
      dimensionLabels:  dimensionFields,
      hasChimney:       state.hasChimney,
      chimneyCount:     state.hasChimney ? (state.chimneyCount || 1) : 0,
      hasVentilation:   state.hasVentilation,
      ventilationCount: state.ventilationCount || 1,
      items:            editableItems.value || [],
      totalAmount:      currentTotal.value || 0,
      paletteCount:     paletteCount.value,
      paletteFeeTotal:  paletteFeeTotal.value,
      shippingFeeTotal: shippingFeeTotal.value,
      note:             state.note,
    });
  } catch (e) {
    console.error('PDF generálási hiba:', e);
  } finally {
    pdfLoading.value = false;
  }
}

/* ─── Cart Integration ─── */
function openFloatingCart() {
  const elementorToggle = document.querySelector('#elementor-menu-cart__toggle_button');
  if (elementorToggle && elementorToggle.getAttribute('aria-expanded') !== 'true') {
    elementorToggle.click();
    return;
  }

  const blocksToggle = document.querySelector('.wc-block-mini-cart__button');
  if (blocksToggle && blocksToggle.getAttribute('aria-expanded') !== 'true') {
    blocksToggle.click();
  }
}

function refreshAndOpenFloatingCart() {
  if (window.jQuery) {
    const $body = window.jQuery(document.body);
    $body.trigger('added_to_cart', [{}, '', null]);
    $body.trigger('wc_fragment_refresh');
  }

  document.body.dispatchEvent(new CustomEvent('wc-blocks_added_to_cart', {
    bubbles: true,
    detail: { preserveCartData: false },
  }));

  window.setTimeout(openFloatingCart, 150);
  window.setTimeout(openFloatingCart, 900);
}

/* ─── Add to cart ─── */
async function addToCart() {
  if (
    cartLoading.value ||
    loading.value ||
    calculationResult.value?.requiresManualReview ||
    !editableItems.value?.length
  ) return;

  cartLoading.value = true;
  cartError.value   = null;
  cartSuccess.value = false;

  try {
    const requestId =
      typeof crypto !== 'undefined' && crypto.randomUUID
        ? crypto.randomUUID()
        : 'req_' + Date.now() + '_' + Math.random().toString(36).substring(2, 9);

    const payload = {
      product:          state.product,
      color:            state.color,
      roofType:         state.roofType,
      roofSubtype:      state.roofSubtype,
      dimensions:       { ...state.dimensions },
      ventilation:      state.hasVentilation ? 'standard' : 'none',
      hasVentilation:   Boolean(state.hasVentilation),
      ventilationCount: state.hasVentilation ? (state.ventilationCount || 1) : 0,
      hasChimney:       Boolean(state.hasChimney),
      vergeType:        state.vergeType,
      addSparePackage:  Boolean(state.addSparePackage),
      note:             state.note,
      customItems:      editableItems.value.map(item => ({
        key: item.key,
        quantity: item.quantity,
        pieces: item.pieces || null,
      })),
      requestId,
    };

    const response = await fetch('/wp-json/rocktile/v1/add-to-cart', {
      method:      'POST',
      headers:     { 'Content-Type': 'application/json' },
      credentials: 'same-origin',
      body:        JSON.stringify(payload),
    });

    const data = await response.json();

    if (!response.ok) {
      throw new Error(data.message || 'Nem sikerült a termékek kosárba helyezése.');
    }

    cartSuccess.value = true;
    trackAddToCart({
      value: currentTotal.value,
      itemsCount: editableItems.value?.length || 0,
      productName: selectedProduct.value?.name,
    });
    refreshAndOpenFloatingCart();
  } catch (err) {
    cartError.value = err.message || 'Hiba történt a kosárba helyezés során.';
  } finally {
    cartLoading.value = false;
  }
}

onMounted(() => {
  runCalculation();
});
</script>

<template>
  <div class="flex flex-col gap-6">
    <!-- Step header -->
    <div class="rc-step-header">
      <span class="rc-step-badge">Összesítés</span>
      <h2 class="rc-step-heading">Kalkuláció eredménye</h2>
      <p class="rc-step-subheading">A megadott adatok alapján a szerver által kiszámított anyagszükséglet és árajánlat.</p>
    </div>

    <!-- ══════════════════════════════
         A) MEGADOTT TETŐADATOK
    ══════════════════════════════ -->
    <div class="overflow-hidden border-[1.5px] border-line bg-white">
      <div class="flex items-center justify-between border-b border-cream bg-warm-white px-5 py-3.5">
        <span class="font-heading text-[13px] font-bold tracking-[0.5px] text-navy uppercase">Megadott tetőadatok</span>
        <div class="flex gap-2">
          <button type="button" class="rc-edit-btn cursor-pointer transition-colors hover:bg-brick/8 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brick" @click="goToStep(1)" aria-label="Termék és szín módosítása">
            Módosítás
          </button>
        </div>
      </div>

      <dl class="m-0 grid grid-cols-[repeat(auto-fill,minmax(180px,1fr))] p-0 max-sm:grid-cols-2 max-[380px]:grid-cols-1">
        <!-- Termék -->
        <div class="flex flex-col gap-[3px] border-b border-cream px-5 py-3.5 last:border-b-0">
          <dt class="font-sans text-[11px] font-semibold tracking-[0.5px] text-slate-400 uppercase">Termékcsalád</dt>
          <dd class="m-0 flex flex-wrap items-center gap-1.5 font-heading text-sm font-bold text-navy">{{ selectedProduct?.name || '-' }}</dd>
        </div>
        <!-- Szín -->
        <div class="flex flex-col gap-[3px] border-b border-cream px-5 py-3.5 last:border-b-0">
          <dt class="font-sans text-[11px] font-semibold tracking-[0.5px] text-slate-400 uppercase">Szín</dt>
          <dd class="m-0 flex flex-wrap items-center gap-1.5 font-heading text-sm font-bold text-navy">
            <span
              v-if="selectedColor?.hex"
              class="size-3.5 shrink-0 rounded-full border-[1.5px] border-black/12"
              :style="{ backgroundColor: selectedColor.hex }"
              aria-hidden="true"
            ></span>
            {{ selectedColor?.name || '-' }}
            <span v-if="selectedColor?.code" class="bg-cream px-1.5 py-px font-sans text-[11px] font-semibold text-muted">{{ selectedColor.code }}</span>
          </dd>
        </div>
        <!-- Tetőforma -->
        <div class="flex flex-col gap-[3px] border-b border-cream px-5 py-3.5 last:border-b-0">
          <dt class="font-sans text-[11px] font-semibold tracking-[0.5px] text-slate-400 uppercase">Tetőforma</dt>
          <dd class="m-0 flex flex-wrap items-center gap-1.5 font-heading text-sm font-bold text-navy">{{ selectedRoofGroup?.name || '-' }}</dd>
        </div>
        <!-- Altípus -->
        <div v-if="selectedRoofGroup?.subtypes?.length" class="flex flex-col gap-[3px] border-b border-cream px-5 py-3.5 last:border-b-0">
          <dt class="font-sans text-[11px] font-semibold tracking-[0.5px] text-slate-400 uppercase">Altípus</dt>
          <dd class="m-0 flex flex-wrap items-center gap-1.5 font-heading text-sm font-bold text-navy">{{ selectedRoofConfig?.name || '-' }}</dd>
        </div>
        <!-- Méretek -->
        <div
          v-for="key in activeFieldKeys"
          :key="key"
          class="flex flex-col gap-[3px] border-b border-cream px-5 py-3.5 last:border-b-0"
        >
          <dt class="font-sans text-[11px] font-semibold tracking-[0.5px] text-slate-400 uppercase">{{ dimensionFields[key]?.label }}</dt>
          <dd class="m-0 flex flex-wrap items-center gap-1.5 font-heading text-sm font-bold text-navy">
            {{ state.dimensions[key] !== null ? state.dimensions[key] : '-' }}
            <span class="text-xs font-semibold text-muted">{{ dimensionFields[key]?.unit }}</span>
          </dd>
        </div>
        <!-- Kémény -->
        <div class="flex flex-col gap-[3px] border-b border-cream px-5 py-3.5 last:border-b-0">
          <dt class="font-sans text-[11px] font-semibold tracking-[0.5px] text-slate-400 uppercase">Kémény</dt>
          <dd class="m-0 flex flex-wrap items-center gap-1.5 font-heading text-sm font-bold text-navy">
            {{ state.hasChimney ? `${state.chimneyCount || 1} db (${(state.chimneyCount || 1) * 2} db síklemez)` : 'Nincs' }}
          </dd>
        </div>
        <!-- Szellőzés -->
        <div class="flex flex-col gap-[3px] border-b border-cream px-5 py-3.5 last:border-b-0">
          <dt class="font-sans text-[11px] font-semibold tracking-[0.5px] text-slate-400 uppercase">Szellőzés</dt>
          <dd class="m-0 flex flex-wrap items-center gap-1.5 font-heading text-sm font-bold text-navy">
            {{ state.hasVentilation ? `${state.ventilationCount || 1} db strangszellőző` : 'Nincs' }}
          </dd>
        </div>
        <!-- Orom -->
        <div v-if="hasVerge" class="flex flex-col gap-[3px] border-b border-cream px-5 py-3.5 last:border-b-0">
          <dt class="font-sans text-[11px] font-semibold tracking-[0.5px] text-slate-400 uppercase">Oromszegély</dt>
          <dd class="m-0 flex flex-wrap items-center gap-1.5 font-heading text-sm font-bold text-navy">{{ selectedVerge?.name || '-' }}</dd>
        </div>
      </dl>
    </div>

    <!-- ══════════════════════════════
         B) ANYAGSZÜKSÉGLET & SZERKESZTÉS
    ══════════════════════════════ -->
    <div class="overflow-hidden border-2 border-navy bg-white">
      <div class="flex flex-wrap items-center justify-between gap-2 border-b border-cream bg-warm-white px-5 py-3.5">
        <div class="flex items-center gap-3">
          <span class="font-heading text-[13px] font-bold tracking-[0.5px] text-navy uppercase">Kiszámított anyagszükséglet</span>
          <span v-if="isItemsModified" class="bg-brick/10 px-2 py-0.5 font-sans text-xs font-bold text-brick">Módosítva</span>
        </div>
        
        <div class="flex items-center gap-3">
          <button
            v-if="isItemsModified"
            type="button"
            class="rc-reset-btn"
            @click="resetToCalculated"
          >
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="size-3.5">
              <path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/>
              <path d="M3 3v5h5"/>
            </svg>
            Visszaállítás az eredeti kalkulált állapotba
          </button>

          <span v-if="loading" class="flex items-center gap-1.5 font-sans text-xs font-semibold text-muted">
            <span class="rc-spinner size-3.5 rounded-full border-2 border-line border-t-navy" aria-hidden="true"></span>
            Számítás...
          </span>
        </div>
      </div>

      <!-- Loading -->
      <div v-if="loading" class="flex flex-col items-center justify-center gap-3.5 px-5 py-10 font-sans text-sm text-muted" role="status" aria-label="Kalkuláció folyamatban">
        <div class="rc-spinner size-9 rounded-full border-[3px] border-cream border-t-navy" aria-hidden="true"></div>
        <p class="m-0">A szerver számítja az anyagszükségletet...</p>
      </div>

      <!-- Error -->
      <div v-else-if="error" class="flex items-start gap-3.5 bg-red-50 p-5" role="alert">
        <div class="flex size-9 shrink-0 items-center justify-center rounded-full bg-red-200 text-red-700" aria-hidden="true">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="size-[18px]">
            <circle cx="12" cy="12" r="10"></circle>
            <line x1="12" y1="8" x2="12" y2="12"></line>
            <line x1="12" y1="16" x2="12.01" y2="16"></line>
          </svg>
        </div>
        <div class="flex flex-col gap-1">
          <p class="m-0 font-heading text-sm font-bold text-red-700">A kalkuláció nem sikerült</p>
          <p class="m-0 font-sans text-[13px] leading-6 text-red-900">{{ error }}</p>
          <button type="button" class="rc-btn-danger mt-2 w-fit" @click="runCalculation">
            Újrapróbálom
          </button>
        </div>
      </div>

      <!-- Manual review -->
      <div v-else-if="calculationResult?.requiresManualReview" class="flex lg:flex-row flex-col items-start gap-3.5 bg-amber-50 p-5" role="note">
        <div class="flex size-9 shrink-0 items-center justify-center rounded-full bg-amber-100 text-amber-800" aria-hidden="true">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-[18px]">
            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
            <polyline points="14 2 14 8 20 8"></polyline>
            <line x1="16" y1="13" x2="8" y2="13"></line>
            <line x1="16" y1="17" x2="8" y2="17"></line>
            <polyline points="10 9 9 9 8 9"></polyline>
          </svg>
        </div>
        <div>
          <p class="mt-0 mb-1 font-heading text-sm font-bold text-amber-800">Egyedi tetőforma – szakértői felülvizsgálat szükséges</p>
          <p class="m-0 font-sans text-[13px] leading-6 text-amber-900">Az egyedi tetőforma összetett geometriája miatt az anyagszükségletet munkatársunk ellenőrzi, és hamarosan felveszi Önnel a kapcsolatot.</p>
        </div>
      </div>

      <!-- Results table -->
      <div v-else-if="editableItems?.length">

        <!-- Desktop táblázat (≥ 640px) -->
        <table class="w-full border-collapse font-sans max-sm:hidden" aria-label="Anyagszükséglet táblázat">
          <thead>
            <tr>
              <th class="border-b border-cream bg-warm-white px-5 py-3 text-left text-[11px] font-bold tracking-[0.5px] text-muted uppercase">Termék megnevezése</th>
              <th class="border-b border-cream bg-warm-white px-3 py-3 text-left text-[11px] font-bold tracking-[0.5px] text-muted uppercase">Cikkszám</th>
              <th class="border-b border-cream bg-warm-white px-3 py-3 text-right text-[11px] font-bold tracking-[0.5px] text-muted uppercase">Mennyiség</th>
              <th class="border-b border-cream bg-warm-white px-4 py-3 text-right text-[11px] font-bold tracking-[0.5px] text-muted uppercase">Egységár (bruttó)</th>
              <th class="border-b border-cream bg-warm-white px-4 py-3 text-right text-[11px] font-bold tracking-[0.5px] text-muted uppercase">Összesen (bruttó)</th>
              <th class="border-b border-cream bg-warm-white px-3 py-3 text-center text-[11px] font-bold tracking-[0.5px] text-muted uppercase">Művelet</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="(item, idx) in editableItems" :key="idx" class="hover:bg-warm-white [&:last-child>td]:border-b-0">
              <!-- Név & kép -->
              <td class="border-b border-cream px-5 py-3.5">
                <div class="flex items-center gap-3">
                  <a v-if="item.permalink" :href="item.permalink" target="_blank" rel="noopener noreferrer" class="block shrink-0">
                    <img v-if="item.image" :src="item.image" :alt="item.name" class="size-12 object-cover" loading="lazy" />
                    <span v-else class="block size-12 bg-surface-muted" aria-hidden="true"></span>
                  </a>
                  <img v-else-if="item.image" :src="item.image" :alt="item.name" class="size-12 shrink-0 object-cover" loading="lazy" />
                  <div class="flex flex-col">
                    <a
                      v-if="item.permalink"
                      :href="item.permalink"
                      target="_blank"
                      rel="noopener noreferrer"
                      class="font-heading text-sm font-semibold text-navy underline decoration-navy/30 underline-offset-2 hover:text-brick"
                    >{{ item.name }}</a>
                    <span v-else class="font-heading text-sm font-semibold text-navy">{{ item.name }}</span>
                    <span v-if="item.key === 'baseTile' && item.piecePriceFormatted" class="font-sans text-xs text-muted">
                      {{ item.piecePriceFormatted }} / db (12 db / csomag)
                    </span>
                  </div>
                </div>
              </td>

              <!-- SKU -->
              <td class="border-b border-cream px-3 py-3.5 font-mono text-xs text-muted">{{ item.sku || '-' }}</td>

              <!-- Mennyiség állítás -->
              <td class="border-b border-cream px-3 py-3.5 text-right">
                <div class="inline-flex items-center border border-slate-300 bg-warm-white">
                  <button
                    type="button"
                    class="rc-qty-btn"
                    @click="updateItemQuantity(idx, item.quantity - 1)"
                    aria-label="Csökkentés"
                  >-</button>
                  <input
                    type="number"
                    min="1"
                    :value="item.quantity"
                    @change="updateItemQuantity(idx, $event.target.value)"
                    class="rc-qty-input"
                  />
                  <button
                    type="button"
                    class="rc-qty-btn"
                    @click="updateItemQuantity(idx, item.quantity + 1)"
                    aria-label="Növelés"
                  >+</button>
                </div>
                <span class="ml-1.5 font-sans text-xs font-semibold text-muted">{{ item.unit }}</span>
                <span v-if="item.key === 'baseTile'" class="block font-sans text-[11px] text-slate-400">({{ (item.pieces || item.quantity * 12) }} db)</span>
              </td>

              <!-- Egységár -->
              <td class="border-b border-cream px-4 py-3.5 text-right font-sans text-[13px] whitespace-nowrap text-muted">
                {{ item.unitPriceFormatted || '-' }}
              </td>

              <!-- Sorösszeg -->
              <td class="border-b border-cream px-4 py-3.5 text-right font-heading text-sm font-bold whitespace-nowrap text-navy">
                {{ formatHuf(item.lineTotal || (item.unitPrice * item.quantity)) }}
              </td>

              <!-- Törlés -->
              <td class="border-b border-cream px-3 py-3.5 text-center">
                <button
                  type="button"
                  class="rc-remove-btn"
                  @click="removeItem(idx)"
                  title="Tétel eltávolítása"
                  aria-label="Tétel törlése"
                >
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="size-4">
                    <polyline points="3 6 5 6 21 6"></polyline>
                    <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                  </svg>
                </button>
              </td>
            </tr>
          </tbody>
        </table>

        <!-- Mobil kártyák (< 640px) -->
        <ul class="m-0 hidden list-none p-0 max-sm:block" aria-label="Anyagszükséglet lista">
          <li v-for="(item, idx) in editableItems" :key="idx" class="flex flex-col gap-2.5 border-b border-cream px-[18px] py-3.5 last:border-b-0">
            <div class="flex items-start justify-between gap-3">
              <div class="flex items-start gap-3">
                <img v-if="item.image" :src="item.image" :alt="item.name" class="size-12 shrink-0 object-cover" loading="lazy" />
                <div class="flex min-w-0 flex-1 flex-col gap-0.5">
                  <span class="font-heading text-sm font-bold text-navy">{{ item.name }}</span>
                  <span class="font-mono text-[11px] text-slate-400">{{ item.sku || '' }}</span>
                </div>
              </div>
              <button
                type="button"
                class="rc-remove-btn"
                @click="removeItem(idx)"
                aria-label="Tétel törlése"
              >
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="size-4">
                  <polyline points="3 6 5 6 21 6"></polyline>
                  <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                </svg>
              </button>
            </div>

            <div class="flex items-center justify-between border-t border-slate-100 pt-2 font-sans text-xs">
              <div class="inline-flex items-center border border-slate-300 bg-warm-white">
                <button type="button" class="rc-qty-btn" @click="updateItemQuantity(idx, item.quantity - 1)">-</button>
                <input type="number" min="1" :value="item.quantity" @change="updateItemQuantity(idx, $event.target.value)" class="rc-qty-input" />
                <button type="button" class="rc-qty-btn" @click="updateItemQuantity(idx, item.quantity + 1)">+</button>
              </div>
              <span class="text-muted">{{ item.unit }}</span>
              <strong class="font-heading text-sm text-navy">{{ formatHuf(item.lineTotal || (item.unitPrice * item.quantity)) }}</strong>
            </div>
          </li>
        </ul>

        <!-- Költségbontás sáv (Anyagok, Raklap díj, Szállítás a végösszeg elé) -->
        <div class="border-t border-cream bg-slate-50/90 px-5 py-4 flex flex-col gap-2.5">
          <div class="flex items-center justify-between font-sans text-xs text-muted">
            <span>Anyagok részösszege (bruttó, 27% áfával):</span>
            <strong class="font-heading text-sm text-navy">{{ currentTotalFormatted }}</strong>
          </div>
          <div class="flex items-center justify-between font-sans text-xs text-muted">
            <div class="flex flex-col">
              <span>Raklap díj ({{ paletteCount }} db raklap):</span>
              <span class="text-[10px] text-slate-400">br. 3 810 Ft / raklap (Tetőfelület: {{ roofArea }} m² alapján)</span>
            </div>
            <strong class="font-heading text-sm text-navy">{{ formatHuf(paletteFeeTotal) }}</strong>
          </div>
          <div class="flex items-center justify-between font-sans text-xs text-muted">
            <div class="flex flex-col">
              <span>Várható házhozszállítási díj ({{ paletteCount }} raklap):</span>
              <span class="text-[10px] text-slate-400">br. 38 100 Ft / raklap</span>
            </div>
            <strong class="font-heading text-sm text-navy">{{ formatHuf(shippingFeeTotal) }}</strong>
          </div>
        </div>

        <!-- Végösszeg sáv -->
        <div class="flex items-center justify-between border-t-2 border-navy bg-warm-white px-5 py-4.5">
          <div>
            <span class="block font-heading text-sm font-bold tracking-[0.5px] text-navy uppercase">Ajánlat végösszege (bruttó)</span>
            <span class="font-sans text-xs text-slate-500">Anyagok + Raklap díj + Házhozszállítás (27% ÁFÁ-val)</span>
          </div>
          <strong class="font-heading text-2xl sm:text-3xl font-extrabold text-navy">{{ grandTotalFormatted }}</strong>
        </div>
      </div>
    </div>

    <!-- ══════════════════════════════
         D) TARTALÉKCSOMAG
    ══════════════════════════════ -->
    <div
      v-if="editableItems?.length && !loading && !error"
      class="flex flex-col gap-3.5 border-2 px-5 py-[18px] transition-colors"
      :class="state.addSparePackage ? 'border-navy bg-white' : 'border-line bg-warm-white'"
    >
      <label class="group flex cursor-pointer items-start gap-3 select-none" for="rss-spare-checkbox">
        <input
          id="rss-spare-checkbox"
          type="checkbox"
          v-model="state.addSparePackage"
          class="rc-checkbox absolute size-0 opacity-0"
          @change="onToggleSparePackage"
        />
        <span class="rc-checkbox-mark relative mt-px size-[22px] shrink-0 border-2 border-slate-300 bg-white transition group-hover:border-navy" aria-hidden="true"></span>
        <div class="flex flex-col gap-0.5">
          <span class="font-heading text-sm font-bold text-navy">+1 csomag tartalék alapcserepet kérek <span class="font-sans text-xs font-normal text-muted">(+12 db)</span></span>
          <span class="font-sans text-xs leading-[1.4] text-muted">Biztonsági tartalék a vágási hulladék és esetleges javítások fedezésére (12 db / csomag).</span>
        </div>
      </label>
    </div>

    <!-- ══════════════════════════════
         E) MEGJEGYZÉS
    ══════════════════════════════ -->
    <div class="flex flex-col gap-1.5">
      <label for="rss-note" class="font-heading text-sm font-bold text-navy">
        Megjegyzés az ajánlathoz / ügyfélszolgálat számára
        <span class="ml-1 font-sans text-xs font-normal text-slate-400">(opcionális)</span>
      </label>
      <textarea
        id="rss-note"
        v-model="state.note"
        rows="3"
        placeholder="Pl.: speciális szállítási igény, eltérő tetőhajlásszög..."
        class="rc-field min-h-[80px] resize-y px-3.5 py-2.5 text-sm leading-6"
      ></textarea>
    </div>

    <!-- ══════════════════════════════
         E/2) KAPCSOLATTARTÁSI ADATOK (Szakértői felülvizsgálat esetén)
    ══════════════════════════════ -->
    <div v-if="calculationResult?.requiresManualReview" class="flex flex-col gap-4 border-2 border-amber-300 bg-amber-50/60 p-5 rounded-sm">
      <div class="flex lg:flex-row flex-col items-start gap-3">
        <div class="flex size-9 shrink-0 items-center justify-center rounded-full bg-amber-200 text-amber-900" aria-hidden="true">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="size-5">
            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
            <circle cx="12" cy="7" r="4"></circle>
          </svg>
        </div>
        <div class="flex flex-col gap-1">
          <h3 class="m-0 font-heading text-base font-bold text-navy">Kapcsolati adatok a szakértői ajánlatkéréshez</h3>
          <p class="m-0 font-sans text-xs text-slate-700 leading-relaxed">
            Kérjük, adja meg elérhetőségeit, hogy szakértő munkatársunk elküldhesse Önnek a személyre szabott, pontosított kalkulációt.
          </p>
        </div>
      </div>

      <div class="grid grid-cols-3 gap-3.5 max-sm:grid-cols-1">
        <div class="flex flex-col gap-1">
          <label for="contact-name" class="font-heading text-xs font-bold text-navy">
            Név <span class="text-brick">*</span>
          </label>
          <input
            id="contact-name"
            type="text"
            v-model="contactName"
            placeholder="Pl. Kovács János"
            class="rc-field py-2 px-3 text-sm"
            :class="contactTouched && !contactName.trim() ? 'border-red-500 bg-red-50' : ''"
          />
        </div>

        <div class="flex flex-col gap-1">
          <label for="contact-email" class="font-heading text-xs font-bold text-navy">
            Email cím <span class="text-brick">*</span>
          </label>
          <input
            id="contact-email"
            type="email"
            v-model="contactEmail"
            placeholder="Pl. kovacs@pelda.hu"
            class="rc-field py-2 px-3 text-sm"
            :class="contactTouched && (!contactEmail.trim() || !contactEmail.includes('@')) ? 'border-red-500 bg-red-50' : ''"
          />
        </div>

        <div class="flex flex-col gap-1">
          <label for="contact-phone" class="font-heading text-xs font-bold text-navy">
            Telefonszám <span class="text-brick">*</span>
          </label>
          <input
            id="contact-phone"
            type="tel"
            v-model="contactPhone"
            placeholder="Pl. +36 30 123 4567"
            class="rc-field py-2 px-3 text-sm"
            :class="contactTouched && !contactPhone.trim() ? 'border-red-500 bg-red-50' : ''"
          />
        </div>
      </div>
    </div>

    <!-- ══════════════════════════════
         F) CTA GOMBOK & VISSZAJELZÉSEK
    ══════════════════════════════ -->
    <div class="flex flex-col gap-3">
      <!-- Review error -->
      <div v-if="reviewError" class="flex items-start gap-3 border border-red-200 bg-red-50 px-4 py-3.5 font-sans text-[13px] text-red-900" role="alert">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" class="size-[18px] shrink-0 text-red-700">
          <circle cx="12" cy="12" r="10"></circle>
          <line x1="12" y1="8" x2="12" y2="12"></line>
          <line x1="12" y1="16" x2="12.01" y2="16"></line>
        </svg>
        <div>
          <p class="mt-0 mb-[3px] font-heading text-[13px] font-bold text-red-700">Ajánlatkérés sikertelen</p>
          <p class="m-0 leading-6">{{ reviewError }}</p>
        </div>
      </div>

      <!-- Review success -->
      <div v-if="reviewSuccess" class="flex items-start gap-3.5 border-2 border-green-500 bg-green-50 px-5 py-4 font-sans text-[13px] text-green-950 rounded-sm" role="status">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" class="size-6 shrink-0 text-green-700 mt-0.5">
          <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
          <polyline points="22 4 12 14.01 9 11.01"></polyline>
        </svg>
        <div class="flex flex-col gap-1">
          <strong class="font-heading text-base font-bold text-green-900">Szakértői ajánlatkérését sikeresen rögzítettük!</strong>
          <p class="m-0 text-green-800 leading-relaxed">
            Azonosító: <strong>{{ reviewQuoteId }}</strong>. Munkatársunk áttekinti a megadott tetőadatokat, és hamarosan felveszi Önnel a kapcsolatot a megadott elérhetőségeken.
          </p>
        </div>
      </div>

      <!-- Send quote to staff success -->
      <div v-if="sendQuoteSuccess" class="flex items-start gap-3.5 border-2 border-green-500 bg-green-50 px-5 py-4 font-sans text-[13px] text-green-950 rounded-sm" role="status">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" class="size-6 shrink-0 text-green-700 mt-0.5">
          <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
          <polyline points="22 4 12 14.01 9 11.01"></polyline>
        </svg>
        <div class="flex flex-col gap-1">
          <strong class="font-heading text-base font-bold text-green-900">Az ajánlatot sikeresen elküldtük kollégáinknak!</strong>
          <p class="m-0 text-green-800 leading-relaxed">
            Azonosító: <strong>{{ sentQuoteId }}</strong>. Munkatársaink hamarosan felveszik Önnel a kapcsolatot a megadott elérhetőségeken. A kalkuláció részleteit és a visszaigazolást az Ön e-mail címére is elküldtük.
          </p>
        </div>
      </div>

      <!-- Cart error -->
      <div v-if="cartError" class="flex items-start gap-3 border border-red-200 bg-red-50 px-4 py-3.5 font-sans text-[13px] text-red-900" role="alert">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" class="size-[18px] shrink-0">
          <circle cx="12" cy="12" r="10"></circle>
          <line x1="12" y1="8" x2="12" y2="12"></line>
          <line x1="12" y1="16" x2="12.01" y2="16"></line>
        </svg>
        <div>
          <p class="mt-0 mb-[3px] font-heading text-[13px] font-bold text-red-700">Kosárba helyezés sikertelen</p>
          <p class="m-0 leading-6">{{ cartError }}</p>
          <button type="button" class="rc-btn-danger mt-2 px-3.5 py-1.5 text-xs" @click="addToCart">Újrapróbálom</button>
        </div>
      </div>

      <!-- Cart success -->
      <div v-if="cartSuccess" class="flex items-center gap-3 border border-green-200 bg-green-50 px-4 py-3.5 font-sans text-[13px] font-semibold text-green-900" role="status">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" class="size-[18px] shrink-0">
          <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
          <polyline points="22 4 12 14.01 9 11.01"></polyline>
        </svg>
        Sikeresen kosárba helyezve.
      </div>

      <!-- Action buttons grid -->
      <div
        class="grid gap-3"
        :class="calculationResult?.requiresManualReview ? 'grid-cols-2 max-sm:grid-cols-1' : 'grid-cols-3 max-md:grid-cols-1'"
      >
        <!-- PDF Letöltés CTA -->
        <button
          type="button"
          class="rc-btn rc-pdf-btn"
          :disabled="pdfLoading || loading"
          @click="downloadPdf"
        >
          <span v-if="pdfLoading" class="rc-spinner size-[18px] shrink-0 rounded-full border-2 border-slate-300 border-t-navy"></span>
          <svg v-else viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="size-5 shrink-0">
            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
            <polyline points="14 2 14 8 20 8"></polyline>
            <line x1="12" y1="18" x2="12" y2="12"></line>
            <line x1="9" y1="15" x2="12" y2="18"></line>
            <line x1="15" y1="15" x2="12" y2="18"></line>
          </svg>
          <span>Árajánlat letöltése (PDF)</span>
        </button>

        <!-- HA EGYEDI TETŐFORMA: Szakértői ajánlatkérés gomb -->
        <button
          v-if="calculationResult?.requiresManualReview"
          type="button"
          class="rc-btn rc-btn-primary"
          :disabled="reviewLoading || reviewSuccess"
          @click="sendExpertReviewRequest"
          :aria-busy="reviewLoading"
        >
          <span v-if="reviewLoading" class="rc-spinner size-[18px] shrink-0 rounded-full border-[2.5px] border-white/35 border-t-white" aria-hidden="true"></span>
          <svg v-else viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-5 shrink-0" aria-hidden="true">
            <path d="M22 2L11 13"></path>
            <polygon points="22 2 15 22 11 13 2 9 22 2"></polygon>
          </svg>
          <span>{{ reviewLoading ? 'Küldés folyamatban...' : reviewSuccess ? 'Ajánlatkérés elküldve ✓' : 'Szakértői ajánlatkérés elküldése' }}</span>
        </button>

        <!-- SZTENDERD TETŐFORMA: Ajánlat elküldése kollégáknak gomb -->
        <button
          v-if="!calculationResult?.requiresManualReview"
          type="button"
          class="rc-btn rc-quote-btn"
          :disabled="loading || !editableItems?.length"
          @click="openSendQuoteModal"
        >
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="size-5 shrink-0">
            <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
            <polyline points="22,6 12,13 2,6"></polyline>
          </svg>
          <span>Ajánlat elküldése kollégáknak</span>
        </button>

        <!-- SZTENDERD TETŐFORMA: Kosárba rakom gomb -->
        <button
          v-if="!calculationResult?.requiresManualReview"
          type="button"
          class="rc-btn rc-btn-primary rc-cart-btn"
          :disabled="cartLoading || loading || !editableItems?.length"
          @click="addToCart"
          :aria-busy="cartLoading"
        >
          <span v-if="cartLoading" class="rc-spinner size-[18px] shrink-0 rounded-full border-[2.5px] border-white/35 border-t-white" aria-hidden="true"></span>
          <svg v-else viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="size-5 shrink-0" aria-hidden="true">
            <circle cx="9" cy="21" r="1"></circle>
            <circle cx="20" cy="21" r="1"></circle>
            <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
          </svg>
          <span>{{ cartLoading ? 'Kosárba helyezés...' : 'Kosárba rakom' }}</span>
        </button>
      </div>
    </div>

    <!-- ══════════════════════════════
         MODAL: AJÁNLAT ELKÜLDÉSE KOLLÉGÁKNAK
    ══════════════════════════════ -->
    <Teleport to="body">
      <div
        v-if="isSendQuoteModalOpen"
        class="rocktile-calculator-ui rocktile-modal-backdrop"
        role="dialog"
        aria-modal="true"
        aria-labelledby="send-quote-title"
      >
        <div class="rocktile-modal-box relative flex w-full max-w-lg flex-col gap-5 border-2 border-navy bg-white p-6 shadow-2xl rounded-none max-h-[90vh] overflow-y-auto">
          <div class="flex items-start justify-between gap-4 border-b border-cream pb-3">
            <div>
              <h3 id="send-quote-title" class="m-0 font-heading text-lg font-bold text-navy">
                Ajánlat elküldése kollégáinknak
              </h3>
              <p class="mt-1 mb-0 font-sans text-xs text-muted leading-relaxed">
                Nem szeretne most azonnal vásárolni? Küldje el a kalkulációt, és munkatársaink hamarosan felveszik Önnel a kapcsolatot!
              </p>
            </div>
            <button
              type="button"
              class="rc-modal-close"
              @click="closeSendQuoteModal"
              aria-label="Bezárás"
            >
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="size-5">
                <line x1="18" y1="6" x2="6" y2="18"></line>
                <line x1="6" y1="6" x2="18" y2="18"></line>
              </svg>
            </button>
          </div>

          <div v-if="sendQuoteError" class="flex items-center gap-2 border border-red-200 bg-red-50 p-3 font-sans text-xs text-red-800">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="size-4 shrink-0 text-red-600">
              <circle cx="12" cy="12" r="10"></circle>
              <line x1="12" y1="8" x2="12" y2="12"></line>
              <line x1="12" y1="16" x2="12.01" y2="16"></line>
            </svg>
            <span>{{ sendQuoteError }}</span>
          </div>

          <!-- Összegző mini sáv a modalban -->
          <div class="flex items-center justify-between bg-slate-50 border border-slate-200 p-3 rounded-xs font-sans text-xs">
            <div>
              <span class="text-slate-500 block">Kalkulált végösszeg:</span>
              <strong class="font-heading text-base font-bold text-navy">{{ grandTotalFormatted }}</strong>
            </div>
            <div class="text-right text-slate-500">
              <div>{{ selectedProduct?.name }}</div>
              <div class="font-semibold text-slate-700">{{ selectedColor?.name }}</div>
            </div>
          </div>

          <form @submit.prevent="submitSendQuoteToStaff" class="flex flex-col gap-3.5">
            <div class="flex flex-col gap-1">
              <label for="sq-name" class="font-heading text-xs font-bold text-navy">
                Név <span class="text-brick">*</span>
              </label>
              <input
                id="sq-name"
                type="text"
                v-model="sendQuoteName"
                placeholder="Pl. Kovács János"
                class="rc-field py-2 px-3 text-sm"
                :class="sendQuoteTouched && !sendQuoteName.trim() ? 'border-red-500 bg-red-50' : ''"
                required
              />
            </div>

            <div class="grid grid-cols-2 gap-3 max-sm:grid-cols-1">
              <div class="flex flex-col gap-1">
                <label for="sq-email" class="font-heading text-xs font-bold text-navy">
                  Email cím <span class="text-brick">*</span>
                </label>
                <input
                  id="sq-email"
                  type="email"
                  v-model="sendQuoteEmail"
                  placeholder="Pl. kovacs@pelda.hu"
                  class="rc-field py-2 px-3 text-sm"
                  :class="sendQuoteTouched && (!sendQuoteEmail.trim() || !sendQuoteEmail.includes('@')) ? 'border-red-500 bg-red-50' : ''"
                  required
                />
              </div>

              <div class="flex flex-col gap-1">
                <label for="sq-phone" class="font-heading text-xs font-bold text-navy">
                  Telefonszám <span class="text-brick">*</span>
                </label>
                <input
                  id="sq-phone"
                  type="tel"
                  v-model="sendQuotePhone"
                  placeholder="Pl. +36 30 123 4567"
                  class="rc-field py-2 px-3 text-sm"
                  :class="sendQuoteTouched && !sendQuotePhone.trim() ? 'border-red-500 bg-red-50' : ''"
                  required
                />
              </div>
            </div>

            <div class="flex flex-col gap-1">
              <label for="sq-note" class="font-heading text-xs font-bold text-navy">
                Megjegyzés a kollégák számára <span class="text-slate-400 font-normal">(opcionális)</span>
              </label>
              <textarea
                id="sq-note"
                v-model="sendQuoteNote"
                rows="2"
                placeholder="Pl. kivitelezés tervezett időpontja, speciális szállítási feltétel..."
                class="rc-field py-2 px-3 text-xs resize-none"
              ></textarea>
            </div>

            <div class="mt-2 flex items-center justify-end gap-3 border-t border-cream pt-4">
              <button
                type="button"
                class="rc-modal-btn rc-modal-btn-secondary"
                @click="closeSendQuoteModal"
                :disabled="sendQuoteLoading"
              >
                Mégse
              </button>
              <button
                type="submit"
                class="rc-modal-btn rc-modal-btn-primary"
                :disabled="sendQuoteLoading"
              >
                <span v-if="sendQuoteLoading" class="rc-spinner size-3.5 shrink-0 rounded-full border-2 border-white/35 border-t-white" aria-hidden="true"></span>
                <span>{{ sendQuoteLoading ? 'Küldés...' : 'Ajánlat elküldése' }}</span>
              </button>
            </div>
          </form>
        </div>
      </div>
    </Teleport>
  </div>
</template>

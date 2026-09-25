<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { useCalculator } from '../composables/useCalculator.js';
import { pushDataLayer } from '../utils/dataLayer.js';

const {
  state,
  selectedProduct,
  selectedColor,
  selectedRoofGroup,
  selectedRoofConfig,
  activeFields,
  setDimension,
  setHasChimney,
  setChimneyCount,
  openStep3Help,
  closeStep3Help,
} = useCalculator();

// Touched tracking – hiba csak blur után jelenik meg
const touched = ref({});

const isCustomRoof = computed(() => state.roofType === 'custom');

const handleInput = (fieldId, event) => {
  setDimension(fieldId, event.target.value);
};

const handleBlur = (fieldId) => {
  touched.value[fieldId] = true;
};

const getError = (field) => {
  if (!touched.value[field.id]) return null;
  const val = state.dimensions[field.id];
  
  // Egyedi tetőnél a felület kivételével az üres mező vagy a 0 teljesen érvényes
  if (isCustomRoof.value && field.id !== 'roofArea') {
    if (val !== null && val !== undefined && val !== '') {
      if (typeof val !== 'number' || isNaN(val) || val < 0) {
        return `A(z) ${field.label.toLowerCase()} értéke nem lehet negatív szám.`;
      }
    }
    return null;
  }

  if (val === null || val === undefined || val === '') {
    return `Add meg a(z) ${field.label.toLowerCase()} értékét.`;
  }
  if (typeof val !== 'number' || isNaN(val) || val < 0) {
    return `A(z) ${field.label.toLowerCase()} értéke nem lehet negatív szám.`;
  }
  if (field.id === 'roofArea' && val <= 0) {
    return `A tetőfelület értéke 0-nál nagyobb kell legyen.`;
  }
  return null;
};

const hasError = (field) => Boolean(getError(field));

// Rövid segítség szövegek
const fieldHelp = {
  roofArea: 'A teljes befedendő tetőfelület nagysága.',
  ridge:    'A vízszintes tetőgerincek teljes hossza.',
  hip:      'A ferde külső tetőélek (élgerincek) teljes hossza.',
  valley:   'A belső tetőhajlatok (vápák) teljes hossza.',
  eaves:    'Az ereszvonal teljes kerületi hossza.',
  verge:    'Az oromoldali tetőszegélyek teljes hossza.',
};

// ─── Részletes "5 éves nyelven" mérési magyarázatok ───
const fieldMeasurementTips = {
  eaves: {
    title: 'Ereszvonal hossza',
    concept: 'Az eresz a tető legalsó, vízszintes széle, ahol az esővíz belefolyik a csatornába.',
    howTo: 'Nem kell felmászni! Sétálj körbe a ház körül a talajon a falak mellett, és lépd le a csatornás oldalak hosszát. 1 felnőtt lépés kb. 1 méter (fm). Add össze a csatornás oldalak lépéseit!',
    example: 'Ha a ház mindkét hosszanti oldalán 12-12 lépést számoltál: 12 + 12 = 24 fm.',
  },
  ridge: {
    title: 'Tetőgerinc hossza',
    concept: 'A tetőgerinc a ház legfelső vízszintes "csúcsvonala", ahol a két tetőoldal a legmagasabb ponton találkozik.',
    howTo: 'Egyszerű nyeregtetőnél a csúcs hossza pontosan megegyezik a ház alatta lévő falának hosszával. Lépd le lent a kertben a ház hosszát a gerinc vonalában, vagy mérd meg a padláson!',
    example: 'Ha a ház hossza a kertben 14 lépés, akkor a gerinc is kb. 14 fm.',
  },
  roofArea: {
    title: 'Tetőfelület mérete',
    concept: 'A teljes ferde tetőfelület, amit be kell fedni a cserepekkel.',
    howTo: 'Ha tudod a házad alapterületét (pl. 100 m²), a tető dőlése és a túlnyúló ereszek miatt a tetőfelület kb. 25-35%-kal nagyobb ennél. Egyszerűen szorozd meg a ház alapterületét 1,3-mal!',
    example: 'Egy 100 m² alapterületű háznál a tetőfelület általában kb. 130 – 140 m².',
  },
  verge: {
    title: 'Oromszegély hossza',
    concept: 'A nyeregtető ferde szélei a ház háromszög alakú falainál (ahol nincs csatorna, hanem a tető ferdén fut a csúcstól a sarokig).',
    howTo: 'Nézd meg a ház háromszög alakú homlokzatát! Egy ferde tetőszár a csúcstól a sarokig átlagosan 5-7 méter. Sima nyeregtetőnél 4 ilyen szár van (2 elöl, 2 hátul). Szorozd meg a szár hosszát a darabszámmal!',
    example: 'Ha egy ferde szár kb. 6 méter és 4 db van belőle: 4 × 6 = 24 fm.',
  },
  hip: {
    title: 'Élgerinc hossza (kontytető)',
    concept: 'Kontytetőknél a tetőcsúcstól ferdén lefutó élek, amik a ház külső sarkaihoz vezetnek.',
    howTo: 'Egy normál kontytetőnek 4 ilyen ferde külső éle van a sarkoknál. Egy él hossza a csúcstól a sarokig átlagosan 6-8 méter. Számold össze a ferde sarokélek hosszát!',
    example: '4 sarokél esetén, ha egyenként kb. 7 méter hosszúak: 4 × 7 = 28 fm.',
  },
  valley: {
    title: 'Vápa (tetőhajlat) hossza',
    concept: 'Belső vályú, ahol két tetősík találkozik és összefolyik az esővíz (pl. L vagy T alakú tetőnél).',
    howTo: 'Nézd meg, hány belső sarokmélyedés fut le a tetőn. Egy vápa hossza a belső saroktól a gerincig átlagosan kb. 5-7 méter. Add össze ezeknek a hosszát!',
    example: 'Ha 2 db belső vápa van és egyenként kb. 6 méteresek: 2 × 6 = 12 fm.',
  },
};

const activeMeasurementField = ref(null);

const currentMeasurementTip = computed(() => {
  if (!activeMeasurementField.value) return null;
  return fieldMeasurementTips[activeMeasurementField.value] || {
    title: 'Tetőméret megadása',
    concept: 'Kérjük, adja meg a tetőszerkezet méretét méterben.',
    howTo: 'Sétálj körbe a ház körül a talajon és lépd le a távolságot (1 felnőtt lépés kb. 1 méter), vagy mérd le mérőszalaggal.',
    example: 'Pl. 10 méter = 10 fm.',
  };
});

const openMeasurementModal = (fieldId) => {
  activeMeasurementField.value = fieldId;
};

const closeMeasurementModal = () => {
  activeMeasurementField.value = null;
};

const handleKeydown = (e) => {
  if (e.key === 'Escape') {
    if (activeMeasurementField.value) {
      closeMeasurementModal();
    } else if (state.isStep3HelpModalOpen) {
      handleCloseHelpModal();
    }
  }
};

onMounted(() => {
  window.addEventListener('keydown', handleKeydown);
});

onUnmounted(() => {
  window.removeEventListener('keydown', handleKeydown);
});

const handleChimneyCountChange = (event) => {
  const val = parseInt(event.target.value, 10);
  if (!isNaN(val) && val >= 1) {
    setChimneyCount(val);
  }
};

const decrementChimneyCount = () => {
  if (state.chimneyCount > 1) {
    setChimneyCount(state.chimneyCount - 1);
  }
};

const incrementChimneyCount = () => {
  setChimneyCount((state.chimneyCount || 1) + 1);
};

// ─── Segítségkérő Modal Állapot ───
const helpName = ref('');
const helpEmail = ref('');
const helpPhone = ref('');
const helpNote = ref('');
const helpTouched = ref(false);
const helpLoading = ref(false);
const helpError = ref(null);
const helpSuccess = ref(false);

const isHelpNameValid = computed(() => helpName.value.trim().length > 0);
const isHelpEmailValid = computed(() => {
  const email = helpEmail.value.trim();
  return email.length > 0 && email.includes('@') && email.includes('.');
});

async function submitStep3Help() {
  helpTouched.value = true;
  helpError.value = null;

  if (!isHelpNameValid.value) {
    helpError.value = 'Kérjük, adja meg a nevét!';
    return;
  }
  if (!isHelpEmailValid.value) {
    helpError.value = 'Kérjük, adjon meg egy érvényes email címet!';
    return;
  }

  helpLoading.value = true;

  try {
    const payload = {
      name:        helpName.value.trim(),
      email:       helpEmail.value.trim(),
      phone:       helpPhone.value.trim(),
      note:        helpNote.value.trim(),
      source:      'step3_help_request',
      landingUrl:  state.landingUrl,
      referrer:    state.referrerUrl,
      product:     state.product,
      color:       state.color,
      roofType:    state.roofType,
      roofSubtype: state.roofSubtype,
      dimensions:  { ...state.dimensions },
    };

    const res = await fetch('/wp-json/rocktile/v1/request-review', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      credentials: 'same-origin',
      body: JSON.stringify(payload),
    });

    const data = await res.json();
    if (!res.ok) {
      throw new Error(data.message || 'Nem sikerült a segítségkérés elküldése.');
    }

    helpSuccess.value = true;

    pushDataLayer({
      event: 'calculator_step3_help_request',
      product_name: selectedProduct.value?.name,
      color_name: selectedColor.value?.fullName || selectedColor.value?.name,
      roof_type: (selectedRoofGroup.value?.name || '') + (selectedRoofConfig.value?.name ? ' - ' + selectedRoofConfig.value.name : ''),
      landing_url: state.landingUrl,
    });
  } catch (err) {
    helpError.value = err.message || 'Hiba történt az elküldés során.';
  } finally {
    helpLoading.value = false;
  }
}

function handleCloseHelpModal() {
  closeStep3Help();
  if (helpSuccess.value) {
    setTimeout(() => {
      helpSuccess.value = false;
      helpTouched.value = false;
      helpName.value = '';
      helpEmail.value = '';
      helpPhone.value = '';
      helpNote.value = '';
      helpError.value = null;
    }, 500);
  }
}
</script>

<template>
  <div class="flex flex-col gap-7">
    <!-- Step header -->
    <div class="rc-step-header">
      <span class="rc-step-badge">3. lépés</span>
      <h2 class="rc-step-heading">Adja meg a tető méreteit</h2>
      <p class="rc-step-subheading">
        A(z) <strong>{{ selectedRoofConfig?.name }}</strong> tetőformához szükséges adatok.
        Kérjük, adja meg a méreteket méterben (fm = folyóméter), a felületet pedig négyzetméterben (m²).
      </p>
    </div>

    <!-- Sárgás tájékoztató Egyedi tetőforma esetén -->
    <div v-if="isCustomRoof" class="flex items-start gap-3.5 border border-amber-300 bg-amber-50/90 p-4 rounded-sm">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="size-5 shrink-0 text-amber-800 mt-0.5" aria-hidden="true">
        <circle cx="12" cy="12" r="10"></circle>
        <line x1="12" y1="8" x2="12" y2="12"></line>
        <line x1="12" y1="16" x2="12.01" y2="16"></line>
      </svg>
      <div class="flex flex-col gap-1">
        <strong class="font-heading text-sm font-bold text-amber-950">Egyedi tetőforma méretmegadása</strong>
        <p class="m-0 font-sans text-xs leading-relaxed text-amber-900">
          Kérjük, adja meg a tetőfelületet és az ismert méreteket. Azoknál a mezőknél, amelyek az Ön tetőszerkezeténél nem fordulnak elő, <strong>nyugodtan hagyja az értéket 0-án vagy kitöltetlenül</strong>.
        </p>
      </div>
    </div>

    <!-- Dinamikus beviteli mezők kártyákkal és képi illusztrációkkal -->
    <div class="grid grid-cols-[repeat(auto-fill,minmax(280px,1fr))] gap-5 max-sm:grid-cols-1 max-sm:gap-4">
      <div
        v-for="field in activeFields"
        :key="field.id"
        class="flex flex-col overflow-hidden border-2 transition duration-200 focus-within:border-navy focus-within:bg-white"
        :class="hasError(field)
          ? 'border-red-600 bg-red-50'
          : state.dimensions[field.id] !== null && state.dimensions[field.id] > 0
            ? 'border-navy/50 bg-white'
            : isCustomRoof && field.id !== 'roofArea' && state.dimensions[field.id] === 0
              ? 'border-amber-300 bg-amber-50/40'
              : 'border-line bg-warm-white'"
      >
        <!-- Képi illusztráció / Jelmagyarázat -->
        <div v-if="field.image" class="relative flex h-32 w-full items-center justify-center border-b border-cream bg-white p-2">
          <img
            :src="field.image"
            :alt="field.label + ' illusztráció'"
            class="max-h-full max-w-full object-contain"
            loading="lazy"
          />
        </div>

        <div class="flex flex-1 flex-col justify-between gap-3 p-4">
          <div class="flex flex-col gap-1">
            <label :for="'dim-' + field.id" class="flex items-center justify-between font-heading text-sm font-bold text-navy">
              <span>{{ field.label }}</span>
              <span
                v-if="field.id === 'roofArea' || !isCustomRoof"
                class="text-xs font-semibold text-brick"
                aria-label="kötelező"
              >* kötelező</span>
              <span
                v-else
                class="text-xs font-semibold text-amber-800 bg-amber-100/70 px-1.5 py-0.5 rounded-xs"
              >opcionális (vagy 0)</span>
            </label>
            <span v-if="fieldHelp[field.id]" class="font-sans text-xs leading-[1.4] text-muted">{{ fieldHelp[field.id] }}</span>
          </div>

          <div class="relative flex items-center">
            <input
              :id="'dim-' + field.id"
              type="number"
              step="0.01"
              min="0.01"
              inputmode="decimal"
              :placeholder="field.placeholder"
              :value="state.dimensions[field.id] !== null ? state.dimensions[field.id] : ''"
              class="rc-field appearance-none py-2.5 pr-18 pl-3.5 text-base font-semibold [&::-webkit-inner-spin-button]:m-0 [&::-webkit-inner-spin-button]:appearance-none [&::-webkit-outer-spin-button]:m-0 [&::-webkit-outer-spin-button]:appearance-none"
              :class="hasError(field) ? 'border-red-600 bg-red-50' : ''"
              :aria-describedby="hasError(field) ? 'err-' + field.id : 'help-' + field.id"
              :aria-invalid="hasError(field)"
              @input="handleInput(field.id, $event)"
              @blur="handleBlur(field.id)"
            />
            <span class="pointer-events-none absolute right-11 select-none font-sans text-xs font-bold text-muted" aria-hidden="true">{{ field.unit }}</span>

            <!-- Kérdőjel gomb az input mező végén (felugró mérési segédletet nyit) -->
            <button
              type="button"
              class="rc-dim-tip-btn"
              :title="'Hogyan mérje le egyszerűen a(z) ' + field.label.toLowerCase() + ' értéket?'"
              :aria-label="'Hogyan mérje le egyszerűen a(z) ' + field.label.toLowerCase() + ' értéket?'"
              @click.stop="openMeasurementModal(field.id)"
            >
              ?
            </button>
          </div>

          <!-- Hibaüzenet -->
          <p
            v-if="hasError(field)"
            :id="'err-' + field.id"
            class="m-0 flex items-center gap-1 font-sans text-xs font-medium text-red-700"
            role="alert"
          >
            {{ getError(field) }}
          </p>
        </div>
      </div>
    </div>

    <!-- ══════════════════════════════
         KÉMÉNY KÉRDÉS
    ══════════════════════════════ -->
    <div class="flex flex-col gap-3 border-2 border-slate-300 bg-white p-5">
      <div class="flex items-center justify-between gap-3 max-sm:flex-col max-sm:items-start">
        <div class="flex flex-col gap-1">
          <div class="flex items-center gap-2">
            <span class="font-heading text-sm font-bold text-navy uppercase tracking-wider">Kémény</span>
            <span class="bg-navy/8 px-2 py-0.5 font-sans text-xs font-semibold text-navy">Opcionális</span>
          </div>
          <span class="font-heading text-base font-bold text-navy">Van kémény a tetőn?</span>
          <p class="m-0 font-sans text-xs text-muted">
            Kémény esetén a szakszerű szegélyezéshez a rendszer automatikusan hozzáad <strong>kéményenként 2 db síklemezt</strong> a választott színben.
          </p>
        </div>

        <div class="flex lg:flex-row flex-col items-center gap-2">
          <button
            type="button"
            class="rc-choice-btn"
            :class="{ 'rc-choice-active-navy': !state.hasChimney }"
            @click="setHasChimney(false)"
          >
            Nincs kémény
          </button>
          <button
            type="button"
            class="rc-choice-btn"
            :class="{ 'rc-choice-active-brick': state.hasChimney }"
            @click="setHasChimney(true)"
          >
            Igen, van kémény
          </button>
        </div>
      </div>

      <!-- Kémény darabszám beállítása ha van kémény -->
      <div v-if="state.hasChimney" class="flex flex-col gap-3 border-t border-slate-200 pt-3">
        <div class="flex items-center justify-between gap-4 max-sm:flex-col max-sm:items-start">
          <div class="flex flex-col gap-0.5">
            <span class="font-heading text-sm font-bold text-navy">Kémények száma:</span>
            <span class="font-sans text-xs text-muted">Adja meg a tetőn található kémények darabszámát</span>
          </div>

          <div class="flex items-center gap-2">
            <div class="inline-flex items-center border border-slate-300 bg-warm-white">
              <button
                type="button"
                class="rc-qty-btn"
                @click="decrementChimneyCount"
                :disabled="(state.chimneyCount || 1) <= 1"
                aria-label="Kémények számának csökkentése"
              >-</button>
              <input
                type="number"
                min="1"
                :value="state.chimneyCount || 1"
                @change="handleChimneyCountChange"
                class="rc-qty-input font-bold"
                aria-label="Kémények darabszáma"
              />
              <button
                type="button"
                class="rc-qty-btn"
                @click="incrementChimneyCount"
                aria-label="Kémények számának növelése"
              >+</button>
            </div>
            <span class="font-sans text-xs font-semibold text-muted">db</span>
          </div>
        </div>

        <div class="flex items-center gap-2 border border-green-200 bg-green-50 px-3.5 py-2 font-sans text-xs font-semibold text-green-900">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" class="size-4 shrink-0 text-green-700">
            <polyline points="20 6 9 17 4 12"></polyline>
          </svg>
          <span>{{ state.chimneyCount || 1 }} db kéményhez összesen <strong>{{ (state.chimneyCount || 1) * 2 }} db síklemez</strong> kerül az anyaglistához (2 db / kémény).</span>
        </div>
      </div>
    </div>

    <!-- ══════════════════════════════════════════════════════
         BIZONYTALAN VÁSÁRLÓ MENTŐÖV / KILÉPÉSI PONT KÁRTYA
    ══════════════════════════════════════════════════════ -->
    <div class="rc-help-card">
      <div class="flex items-start gap-3.5">
        <div class="flex size-10 shrink-0 items-center justify-center rounded-full bg-amber-200/90 text-amber-950 font-bold text-lg">
          🆘
        </div>
        <div class="flex flex-col gap-1">
          <strong class="font-heading text-sm font-bold text-amber-950">
            Nem tudja a pontos méreteket? Segítünk a felmérésben!
          </strong>
          <p class="m-0 font-sans text-xs leading-relaxed text-amber-900">
            Nem szükséges azonnal milliméterre pontos adatokat tudnia. Adja meg elérhetőségeit, és szakértő munkatársunk díjmentesen felveszi Önnel a kapcsolatot a méretek pontosításához és az árajánlat elkészítéséhez.
          </p>
        </div>
      </div>

      <button
        type="button"
        class="rc-help-card-btn max-md:w-full"
        @click="openStep3Help"
      >
        Segítséget kérek a méretekhez
      </button>
    </div>

    <!-- ══════════════════════════════════════════════════════
         SEGÍTSÉGKÉRŐ MODAL (FELUGRÓ ABLAK)
    ══════════════════════════════════════════════════════ -->
    <transition name="rc-modal">
      <div
        v-if="state.isStep3HelpModalOpen"
        class="rocktile-modal-backdrop"
        role="dialog"
        aria-modal="true"
        aria-labelledby="help-modal-title"
        @click.self="handleCloseHelpModal"
      >
        <div class="rocktile-modal-box relative w-full max-w-lg border-2 border-navy bg-white p-6 shadow-2xl sm:p-8">
          <!-- Bezárás ikon -->
          <button
            type="button"
            class="rc-modal-close absolute top-4 right-4"
            @click="handleCloseHelpModal"
            aria-label="Bezárás"
          >
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" class="size-5">
              <line x1="18" y1="6" x2="6" y2="18"></line>
              <line x1="6" y1="6" x2="18" y2="18"></line>
            </svg>
          </button>

          <!-- Sikeres beküldés képernyő -->
          <div v-if="helpSuccess" class="flex flex-col items-center gap-4 text-center py-4">
            <div class="flex size-14 items-center justify-center rounded-full bg-green-100 text-green-700">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" class="size-7">
                <polyline points="20 6 9 17 4 12"></polyline>
              </svg>
            </div>
            <h3 class="m-0 font-heading text-xl font-bold text-navy">Köszönjük a megkeresést!</h3>
            <p class="m-0 font-sans text-sm text-slate-600 leading-relaxed max-w-md">
              Munkatársunk rögzítette segítségkérését, és hamarosan felveszi Önnel a kapcsolatot a megadott elérhetőségeken a tetőméretek pontosításához!
            </p>
            <button
              type="button"
              class="rc-modal-btn rc-modal-btn-primary mt-3 cursor-pointer"
              @click="handleCloseHelpModal"
            >
              Rendben, bezárás
            </button>
          </div>

          <!-- Űrlap kitöltés -->
          <form v-else @submit.prevent="submitStep3Help" class="flex flex-col gap-4">
            <div class="flex items-center gap-3">
              <div class="flex size-10 shrink-0 items-center justify-center rounded-full bg-amber-100 text-amber-800 font-bold text-lg">
                📋
              </div>
              <div>
                <span class="font-sans text-xs font-bold text-brick uppercase tracking-wider">Díjmentes segítség</span>
                <h3 id="help-modal-title" class="m-0 font-heading text-lg font-bold text-navy">
                  Segítséget kérek a méretekhez
                </h3>
              </div>
            </div>

            <p class="m-0 font-sans text-xs text-muted leading-relaxed">
              Adja meg nevét és elérhetőségét, és tetőszakértő munkatársunk segít a pontos méretek és anyagszükséglet meghatározásában.
            </p>

            <!-- Hibaüzenet -->
            <div v-if="helpError" class="p-3 bg-red-50 border border-red-200 text-red-700 font-sans text-xs rounded-xs">
              {{ helpError }}
            </div>

            <div class="flex flex-col gap-3">
              <!-- Név -->
              <div class="flex flex-col gap-1">
                <label for="step3-help-name" class="font-heading text-xs font-bold text-navy flex items-center justify-between">
                  <span>Teljes név:</span>
                  <span class="text-brick font-semibold text-[11px]">* kötelező</span>
                </label>
                <input
                  id="step3-help-name"
                  type="text"
                  v-model="helpName"
                  placeholder="pl. Kovács János"
                  class="rc-field py-2 px-3 text-sm"
                  :class="helpTouched && !isHelpNameValid ? 'border-red-600 bg-red-50' : ''"
                  required
                />
              </div>

              <!-- Email -->
              <div class="flex flex-col gap-1">
                <label for="step3-help-email" class="font-heading text-xs font-bold text-navy flex items-center justify-between">
                  <span>Email cím:</span>
                  <span class="text-brick font-semibold text-[11px]">* kötelező</span>
                </label>
                <input
                  id="step3-help-email"
                  type="email"
                  v-model="helpEmail"
                  placeholder="pl. kovacs.janos@example.com"
                  class="rc-field py-2 px-3 text-sm"
                  :class="helpTouched && !isHelpEmailValid ? 'border-red-600 bg-red-50' : ''"
                  required
                />
              </div>

              <!-- Telefonszám -->
              <div class="flex flex-col gap-1">
                <label for="step3-help-phone" class="font-heading text-xs font-bold text-navy flex items-center justify-between">
                  <span>Telefonszám:</span>
                  <span class="text-muted font-normal text-[11px]">(ajánlott a gyors egyeztetéshez)</span>
                </label>
                <input
                  id="step3-help-phone"
                  type="tel"
                  v-model="helpPhone"
                  placeholder="pl. +36 30 123 4567"
                  class="rc-field py-2 px-3 text-sm"
                />
              </div>

              <!-- Megjegyzés -->
              <div class="flex flex-col gap-1">
                <label for="step3-help-note" class="font-heading text-xs font-bold text-navy flex items-center justify-between">
                  <span>Megjegyzés, kérdés:</span>
                  <span class="text-muted font-normal text-[11px]">(opcionális)</span>
                </label>
                <textarea
                  id="step3-help-note"
                  rows="2"
                  v-model="helpNote"
                  placeholder="pl. kb. mekkora a ház, van-e tervrajz, mikor kereshetjük..."
                  class="rc-field py-2 px-3 text-sm resize-none"
                ></textarea>
              </div>
            </div>

            <div class="mt-2 flex items-center justify-end gap-3 pt-3 border-t border-slate-100">
              <button
                type="button"
                class="rc-modal-btn rc-modal-btn-secondary cursor-pointer"
                @click="handleCloseHelpModal"
                :disabled="helpLoading"
              >
                Mégse
              </button>
              <button
                type="submit"
                class="rc-modal-btn rc-modal-btn-primary cursor-pointer font-bold"
                :disabled="helpLoading"
              >
                <span v-if="helpLoading">Küldés folyamatban...</span>
                <span v-else>Segítségkérés elküldése</span>
              </button>
            </div>
          </form>
        </div>
      </div>
    </transition>

    <!-- ══════════════════════════════════════════════════════
         MÉRÉSI SEGÉDLET MODAL / POPUP (? GOMBOKHOZ)
    ══════════════════════════════════════════════════════ -->
    <transition name="rc-modal">
      <div
        v-if="activeMeasurementField"
        class="rocktile-modal-backdrop"
        role="dialog"
        aria-modal="true"
        aria-labelledby="measurement-modal-title"
        @click.self="closeMeasurementModal"
      >
        <div class="rocktile-modal-box relative w-full max-w-lg border-2 border-navy bg-white p-6 shadow-2xl sm:p-7">
          <!-- Bezárás gomb -->
          <button
            type="button"
            class="rc-modal-close absolute top-4 right-4"
            @click="closeMeasurementModal"
            aria-label="Bezárás"
          >
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" class="size-5">
              <line x1="18" y1="6" x2="6" y2="18"></line>
              <line x1="6" y1="6" x2="18" y2="18"></line>
            </svg>
          </button>

          <!-- Fejléc -->
          <div class="flex items-center gap-3 border-b border-slate-200 pb-3.5 pr-8">
            <span class="flex size-9 shrink-0 items-center justify-center rounded-full bg-navy text-white font-extrabold text-base">
              ?
            </span>
            <div class="flex flex-col">
              <span class="font-sans text-xs font-bold text-muted uppercase tracking-wider">Mérési segítség egyszerűen</span>
              <h3 id="measurement-modal-title" class="m-0 font-heading text-lg font-bold text-navy">
                {{ currentMeasurementTip?.title }}
              </h3>
            </div>
          </div>

          <!-- Tartalom -->
          <div class="mt-4 flex flex-col gap-4 font-sans text-sm text-slate-700 leading-relaxed">
            <!-- 1. Mi ez a méret? -->
            <div class="flex flex-col gap-1">
              <strong class="text-xs uppercase tracking-wide text-navy font-bold">1. Mi ez a méret a tetőn?</strong>
              <p class="m-0 bg-slate-50 border border-slate-200/80 p-3 text-slate-800 text-xs sm:text-sm leading-relaxed">
                {{ currentMeasurementTip?.concept }}
              </p>
            </div>

            <!-- 2. Hogyan mérd le egyszerűen? -->
            <div class="flex flex-col gap-1">
              <strong class="text-xs uppercase tracking-wide text-navy font-bold">2. Hogyan mérd le egyszerűen (lépésekkel)?</strong>
              <div class="bg-blue-50 border-2 border-blue-200 p-3.5 text-blue-950 text-xs sm:text-sm leading-relaxed">
                <div class="flex items-center gap-1.5 font-bold text-blue-900 mb-1">
                  <span>🚶</span>
                  <span>Praktikus mérési trükk:</span>
                </div>
                <p class="m-0">
                  {{ currentMeasurementTip?.howTo }}
                </p>
              </div>
            </div>

            <!-- 3. Számolási példa -->
            <div v-if="currentMeasurementTip?.example" class="flex flex-col gap-1">
              <strong class="text-xs uppercase tracking-wide text-navy font-bold">3. Konkrét példa a számoláshoz:</strong>
              <div class="bg-amber-50 border border-amber-200 p-3 text-amber-950 text-xs sm:text-sm">
                💡 {{ currentMeasurementTip?.example }}
              </div>
            </div>
          </div>

          <!-- Lábléc -->
          <div class="mt-6 flex justify-end gap-3 border-t border-slate-200 pt-4">
            <button
              type="button"
              class="rc-btn rc-btn-primary w-full sm:w-auto"
              @click="closeMeasurementModal"
            >
              Értem, beírom a méretet
            </button>
          </div>
        </div>
      </div>
    </transition>
  </div>
</template>

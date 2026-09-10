<script setup>
import { ref, computed } from 'vue';
import { useCalculator } from '../composables/useCalculator.js';

const { state, selectedRoofConfig, activeFields, setDimension, setHasChimney, setChimneyCount } = useCalculator();

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
              class="rc-field appearance-none py-2.5 pr-14 pl-3.5 text-base font-semibold [&::-webkit-inner-spin-button]:m-0 [&::-webkit-inner-spin-button]:appearance-none [&::-webkit-outer-spin-button]:m-0 [&::-webkit-outer-spin-button]:appearance-none"
              :class="hasError(field) ? 'border-red-600 bg-red-50' : ''"
              :aria-describedby="hasError(field) ? 'err-' + field.id : 'help-' + field.id"
              :aria-invalid="hasError(field)"
              @input="handleInput(field.id, $event)"
              @blur="handleBlur(field.id)"
            />
            <span class="pointer-events-none absolute right-3 select-none font-sans text-xs font-bold text-muted" aria-hidden="true">{{ field.unit }}</span>
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

    <!-- Tájékoztató doboz -->
    <div class="flex items-start gap-2.5 border border-slate-300 bg-navy/4 px-4 py-3.5 font-sans text-[13px] leading-6 text-ink" role="note">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mt-px size-[18px] shrink-0 text-navy" aria-hidden="true">
        <circle cx="12" cy="12" r="10"></circle>
        <line x1="12" y1="16" x2="12" y2="12"></line>
        <line x1="12" y1="8" x2="12.01" y2="8"></line>
      </svg>
      <span>Minden megjelenített méretmezőt ki kell tölteni a továbblépéshez. Az értékek pozitív számok legyenek.</span>
    </div>
  </div>
</template>

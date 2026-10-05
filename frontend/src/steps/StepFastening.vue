<script setup>
import { computed } from 'vue';
import { useCalculator } from '../composables/useCalculator.js';

const { state, selectedColor, selectFastening } = useCalculator();

// Szegelt rögzítés csak Antracit (shadow-rock) színnél érhető el
const isNailAvailable = computed(() => {
  return state.color === 'shadow-rock';
});

const isNailDisabled = computed(() => !isNailAvailable.value);

function handleSelectFastening(optionId) {
  if (optionId === 'nail' && isNailDisabled.value) {
    return;
  }
  selectFastening(optionId);
}
</script>

<template>
  <div class="flex flex-col gap-6">
    <!-- Step header -->
    <div class="rc-step-header">
      <span class="rc-step-badge">5. lépés</span>
      <h2 class="rc-step-heading">Rögzítés módja</h2>
    </div>

    <!-- Tájékoztató sáv ha a szegelt rögzítés nem elérhető (egységes kék stílus) -->
    <div
      v-if="isNailDisabled"
      class="rc-info-block"
      role="note"
    >
      <div class="rc-info-icon">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" class="size-4" aria-hidden="true">
          <circle cx="12" cy="12" r="10"></circle>
          <line x1="12" y1="8" x2="12" y2="12"></line>
          <line x1="12" y1="16" x2="12.01" y2="16"></line>
        </svg>
      </div>
      <div class="flex flex-col gap-1">
        <strong class="font-heading text-sm font-bold text-navy">Csavaros rögzítés szükséges</strong>
        <p class="m-0 font-sans text-xs leading-relaxed text-slate-700">
          A kiválasztott <strong>{{ selectedColor?.name || 'szín' }}</strong> esetében a szegelt rögzítés jelenleg nem elérhető, ezért ehhez a színhez kizárólag a <strong>csavaros rögzítés</strong> választható.
        </p>
      </div>
    </div>

    <!-- Opció kártyák grid - kompakt mobilon és desktopon -->
    <div class="grid grid-cols-2 gap-4 max-sm:grid-cols-1">
      
      <!-- 1. CSAVAROS RÖGZÍTÉS (Ajánlott, mindig elérhető) -->
      <div
        class="group relative flex cursor-pointer flex-col justify-between border-2 p-4 sm:p-5 outline-none transition duration-200 hover:-translate-y-0.5 hover:border-slate-400 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brick motion-reduce:transition-none"
        :class="state.fastening === 'screw' ? 'border-navy bg-warm-white shadow-xs' : 'border-line bg-white'"
        role="button"
        :tabindex="0"
        :aria-pressed="state.fastening === 'screw'"
        aria-label="Rögzítés csavarral (Ajánlott)"
        @click="handleSelectFastening('screw')"
        @keydown.enter.prevent="handleSelectFastening('screw')"
        @keydown.space.prevent="handleSelectFastening('screw')"
      >
        <div class="flex flex-col gap-3">
          <!-- Fejléc / jelvény -->
          <div class="flex items-center justify-between">
            <span class="bg-brick/10 px-2 py-0.5 font-sans text-[11px] font-bold tracking-[0.5px] text-brick uppercase">
              Ajánlott
            </span>
            <div
              v-if="state.fastening === 'screw'"
              class="flex size-6 items-center justify-center rounded-full bg-navy text-white shadow-xs"
              aria-hidden="true"
            >
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" class="size-3.5">
                <polyline points="20 6 9 17 4 12"></polyline>
              </svg>
            </div>
          </div>

          <!-- Kép / Illusztráció (kisebb méret) -->
          <div class="flex h-16 sm:h-20 w-full items-center justify-center border border-cream bg-[#f8fafc] p-2 text-navy">
            <svg viewBox="0 0 64 64" fill="none" stroke="currentColor" class="size-10 sm:size-12" aria-hidden="true">
              <!-- Csavar fej és menet illusztráció -->
              <polygon points="20,12 44,12 48,20 16,20" fill="#022a50" stroke="#022a50" stroke-width="2" />
              <rect x="28" y="20" width="8" height="32" fill="#022a50" opacity="0.8" />
              <line x1="24" y1="26" x2="40" y2="26" stroke="#ffffff" stroke-width="2" stroke-linecap="round" />
              <line x1="24" y1="32" x2="40" y2="32" stroke="#ffffff" stroke-width="2" stroke-linecap="round" />
              <line x1="24" y1="38" x2="40" y2="38" stroke="#ffffff" stroke-width="2" stroke-linecap="round" />
              <line x1="24" y1="44" x2="40" y2="44" stroke="#ffffff" stroke-width="2" stroke-linecap="round" />
              <polygon points="28,52 36,52 32,58" fill="#022a50" />
            </svg>
          </div>

          <!-- Szöveges tartalom -->
          <div class="flex flex-col gap-1">
            <h3 class="m-0 font-heading text-sm sm:text-base font-bold text-navy">Rögzítés csavarral</h3>
            <p class="m-0 font-sans text-xs leading-relaxed text-muted">
              Gyári, EPDM gumialátétes színezett tetőcsavarral történő rögzítés a komplett tetőfelületre. Készleten lévő, gyors és megbízható megoldás.
            </p>
          </div>
        </div>
      </div>

      <!-- 2. SZEGELT RÖGZÍTÉS (Csak Antracit színnél érhető el) -->
      <div
        class="group relative flex flex-col justify-between border-2 p-4 sm:p-5 outline-none transition duration-200 motion-reduce:transition-none"
        :class="[
          isNailDisabled
            ? 'cursor-not-allowed border-slate-200 bg-slate-50 opacity-60'
            : state.fastening === 'nail'
              ? 'cursor-pointer border-navy bg-warm-white shadow-xs hover:-translate-y-0.5'
              : 'cursor-pointer border-line bg-white hover:-translate-y-0.5 hover:border-slate-400'
        ]"
        :role="isNailDisabled ? undefined : 'button'"
        :tabindex="isNailDisabled ? -1 : 0"
        :aria-pressed="state.fastening === 'nail'"
        :aria-disabled="isNailDisabled"
        aria-label="Rögzítés szeggel"
        @click="handleSelectFastening('nail')"
        @keydown.enter.prevent="handleSelectFastening('nail')"
        @keydown.space.prevent="handleSelectFastening('nail')"
      >
        <div class="flex flex-col gap-3">
          <!-- Fejléc / jelvény -->
          <div class="flex items-center justify-between">
            <span
              class="px-2 py-0.5 font-sans text-[11px] font-bold tracking-[0.5px] uppercase"
              :class="isNailDisabled ? 'bg-slate-200 text-slate-500' : 'bg-cream text-muted'"
            >
              {{ isNailDisabled ? 'Nem elérhető' : 'Szegelt opció' }}
            </span>
            <div
              v-if="state.fastening === 'nail' && !isNailDisabled"
              class="flex size-6 items-center justify-center rounded-full bg-navy text-white shadow-xs"
              aria-hidden="true"
            >
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" class="size-3.5">
                <polyline points="20 6 9 17 4 12"></polyline>
              </svg>
            </div>
          </div>

          <!-- Kép / Illusztráció (kisebb méret) -->
          <div class="flex h-16 sm:h-20 w-full items-center justify-center border border-cream bg-[#f8fafc] p-2 text-navy">
            <svg viewBox="0 0 64 64" fill="none" stroke="currentColor" class="size-10 sm:size-12" aria-hidden="true">
              <!-- Szeg illusztráció -->
              <rect x="20" y="14" width="24" height="6" fill="#022a50" rx="1" />
              <rect x="29" y="20" width="6" height="34" fill="#022a50" opacity="0.8" />
              <polygon points="29,54 35,54 32,60" fill="#022a50" />
            </svg>
          </div>

          <!-- Szöveges tartalom -->
          <div class="flex flex-col gap-1">
            <h3 class="m-0 font-heading text-sm sm:text-base font-bold" :class="isNailDisabled ? 'text-slate-400' : 'text-navy'">
              Rögzítés szeggel
            </h3>
            <p class="m-0 font-sans text-xs leading-relaxed" :class="isNailDisabled ? 'text-slate-400' : 'text-muted'">
              <template v-if="isNailDisabled">
                Jelenleg nem elérhető a választott színhez. Kizárólag Antracit színben választható.
              </template>
              <template v-else>
                Hagyományos szegelt rögzítés a tetősíkon, a kúpozáshoz színezett csavarokkal.
              </template>
            </p>
          </div>
        </div>
      </div>

    </div>
  </div>
</template>

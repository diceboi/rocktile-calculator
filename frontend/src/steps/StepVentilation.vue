<script setup>
import { computed, onMounted } from 'vue';
import { useCalculator } from '../composables/useCalculator.js';
import { useProductCatalog } from '../composables/useProductCatalog.js';

const { state, setHasVentilation, setVentilationCount } = useCalculator();
const { catalog, loadCatalog } = useProductCatalog();

const activeOption = {
  id: 'ventilation-standard',
  name: 'ROCKTILE strangszellőző',
  description: 'Átvezető elem a tetőn keresztüli szellőztetéshez.',
  image: null,
};

const isBrownColor = computed(() => state.color === 'earthwood-chestnut');

const ventilationImage = computed(() =>
  catalog.value?.ventilation?.colors?.[state.color]?.image || activeOption.image
);

const onCountInput = (e) => {
  const val = parseInt(e.target.value, 10);
  if (!isNaN(val) && val >= 1) {
    setVentilationCount(val);
  }
};

const decreaseCount = () => {
  if (state.ventilationCount > 1) {
    setVentilationCount(state.ventilationCount - 1);
  }
};

const increaseCount = () => {
  setVentilationCount((state.ventilationCount || 1) + 1);
};

onMounted(() => {
  loadCatalog();
  if (isBrownColor.value && state.hasVentilation) {
    setHasVentilation(false);
  }
});
</script>

<template>
  <div class="flex flex-col gap-6">
    <!-- Step header -->
    <div class="rc-step-header">
      <span class="rc-step-badge">4. lépés</span>
      <h2 class="rc-step-heading">Szellőző (átvezető elem)</h2>
      <p class="rc-step-subheading">
        Ennek a termékcsaládnak nincs külön szellőzőcserepe. Amennyiben tetőn átmenő szellőzőre vagy strangszellőzőre van szüksége, itt igényelheti.
      </p>
    </div>

    <!-- Tájékoztató barna szín esetén -->
    <div v-if="isBrownColor" class="flex lg:flex-row flex-col items-start gap-3.5 border border-amber-200 bg-amber-50/80 p-4.5 rounded-sm">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="size-5 shrink-0 text-amber-700 mt-0.5" aria-hidden="true">
        <circle cx="12" cy="12" r="10"></circle>
        <line x1="12" y1="8" x2="12" y2="12"></line>
        <line x1="12" y1="16" x2="12.01" y2="16"></line>
      </svg>
      <div class="flex flex-col gap-1">
        <strong class="font-heading text-sm font-bold text-amber-900">Barna színben nem elérhető</strong>
        <p class="m-0 font-sans text-xs leading-relaxed text-amber-800">
          A kiválasztott <strong>Barna (Earthwood Chestnut)</strong> színváltozatban jelenleg nem rendelhető szellőző átvezető elem. A kalkuláció szellőző nélkül folytatódik.
        </p>
      </div>
    </div>

    <!-- Igen / Nem választó kártyák -->
    <div class="grid grid-cols-2 gap-4 max-sm:grid-cols-1">
      <!-- NEM KÉREK -->
      <div
        class="flex cursor-pointer flex-col justify-between border-2 p-5 transition duration-150"
        :class="!state.hasVentilation ? 'border-navy bg-warm-white shadow-xs' : 'border-line bg-white hover:border-slate-300'"
        @click="setHasVentilation(false)"
      >
        <div class="flex flex-col gap-2">
          <div class="flex items-center justify-between">
            <span class="font-heading text-base font-bold text-navy">Nem kérek szellőzőt</span>
            <span
              class="flex size-5 items-center justify-center rounded-full border-2"
              :class="!state.hasVentilation ? 'border-navy bg-navy text-white' : 'border-slate-300'"
            >
              <svg v-if="!state.hasVentilation" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3.5" class="size-3">
                <polyline points="20 6 9 17 4 12"></polyline>
              </svg>
            </span>
          </div>
          <p class="m-0 font-sans text-xs leading-5 text-muted">
            Nem kerül szellőző elem a kalkulációba és az anyagszükségletbe.
          </p>
        </div>
      </div>

      <!-- IGEN, KÉREK -->
      <div
        class="flex flex-col justify-between border-2 p-5 transition duration-150"
        :class="[
          isBrownColor ? 'cursor-not-allowed opacity-50 border-slate-200 bg-slate-50' : 'cursor-pointer',
          state.hasVentilation ? 'border-brick bg-warm-white shadow-xs' : 'border-line bg-white hover:border-slate-300'
        ]"
        @click="!isBrownColor && setHasVentilation(true)"
      >
        <div class="flex flex-col gap-2">
          <div class="flex items-center justify-between">
            <span class="font-heading text-base font-bold text-navy">Igen, kérek szellőzőt</span>
            <span
              class="flex size-5 items-center justify-center rounded-full border-2"
              :class="state.hasVentilation ? 'border-brick bg-brick text-white' : 'border-slate-300'"
            >
              <svg v-if="state.hasVentilation" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3.5" class="size-3">
                <polyline points="20 6 9 17 4 12"></polyline>
              </svg>
            </span>
          </div>
          <p class="m-0 font-sans text-xs leading-5 text-muted">
            {{ isBrownColor ? 'Barna színben jelenleg nem választható.' : 'ROCKTILE strangszellőző elem a választott tetőszínben.' }}
          </p>
        </div>
      </div>
    </div>

    <!-- Ha Igen: termék részletek és darabszám megadása -->
    <transition name="rc-expand">
      <div v-if="state.hasVentilation" class="flex flex-col gap-4 border-2 border-slate-300 bg-white p-5">
        <div class="flex items-center gap-4 max-sm:flex-col max-sm:items-start">
          <!-- Kép -->
          <div class="relative flex size-20 shrink-0 items-center justify-center border border-cream bg-surface-muted p-1">
            <img v-if="ventilationImage" :src="ventilationImage" :alt="activeOption.name" class="size-full object-contain" />
            <svg v-else viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="size-8 text-slate-400">
              <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
            </svg>
          </div>

          <!-- Részletek -->
          <div class="flex flex-1 flex-col gap-1">
            <span class="font-heading text-sm font-bold text-navy">{{ activeOption.name }}</span>
            <span class="font-sans text-xs text-muted">{{ activeOption.description }}</span>
          </div>

          <!-- Darabszám beállító -->
          <div class="flex flex-col items-end gap-1 max-sm:w-full max-sm:items-start">
            <label for="vent-count-input" class="font-sans text-xs font-semibold text-muted">Darabszám:</label>
            <div class="inline-flex items-center border border-slate-300 bg-warm-white">
              <button
                type="button"
                class="rc-qty-btn"
                @click="decreaseCount"
                aria-label="Darabszám csökkentése"
              >
                -
              </button>
              <input
                id="vent-count-input"
                type="number"
                min="1"
                step="1"
                :value="state.ventilationCount || 1"
                @input="onCountInput"
                class="rc-qty-input"
              />
              <button
                type="button"
                class="rc-qty-btn"
                @click="increaseCount"
                aria-label="Darabszám növelése"
              >
                +
              </button>
            </div>
          </div>
        </div>

        <div class="flex items-center gap-2 border border-slate-200 bg-navy/4 px-3.5 py-2 font-sans text-xs font-semibold text-navy">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" class="size-4 shrink-0 text-brick">
            <polyline points="20 6 9 17 4 12"></polyline>
          </svg>
          {{ state.ventilationCount || 1 }} db strangszellőző kerül a rendelésbe.
        </div>
      </div>
    </transition>
  </div>
</template>

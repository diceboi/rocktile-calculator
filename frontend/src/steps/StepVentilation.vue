<script setup>
import { computed, onMounted } from 'vue';
import { useCalculator } from '../composables/useCalculator.js';
import { useProductCatalog } from '../composables/useProductCatalog.js';

const { state, setHasVentilation, setVentilationCount } = useCalculator();
const { catalog, loadCatalog } = useProductCatalog();

const activeOption = {
  id: 'ventilation-standard',
  name: 'ROCKTILE szellőző / átvezető elem',
  description: 'Átvezető elem csövek vagy vezetékek tetőn keresztüli kivezetéséhez.',
  image: null,
};

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
});
</script>

<template>
  <div class="flex flex-col gap-6">
    <!-- Step header -->
    <div class="rc-step-header">
      <span class="rc-step-badge">4. lépés</span>
      <h2 class="rc-step-heading">Tetőn átmenő szellőző / átvezető elem</h2>
    </div>

    <!-- Kiemelt gyors magyarázó sáv: Fontos tudnivaló -->
    <div class="rc-info-block">
      <div class="rc-info-icon">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" class="size-4" aria-hidden="true">
          <circle cx="12" cy="12" r="10"></circle>
          <line x1="12" y1="16" x2="12" y2="12"></line>
          <line x1="12" y1="8" x2="12.01" y2="8"></line>
        </svg>
      </div>
      <div class="flex flex-col gap-1">
        <strong class="font-heading text-sm font-bold text-navy">
          Fontos tudnivaló a szellőzésről
        </strong>
        <p class="m-0 font-sans text-xs leading-relaxed text-slate-700">
          A Rocktile tető szellőzése <strong>alapból teljes és tökéletesen megoldott a gerincnél</strong> lévő szellőzőréseken át. Ezt a külön átvezető elemet <strong>csak akkor válassza, ha valamilyen csövet</strong> (pl. konyhai páraelszívó kivezetést, ventilátor csövet) <strong>vagy kábelt</strong> (pl. napelem vezetéket, antennát) kell átvezetnie a tetőn a szabadba!
        </p>
      </div>
    </div>

    <!-- Igen / Nem választó kártyák -->
    <div class="grid grid-cols-2 gap-4 max-sm:grid-cols-1">
      <!-- 1. NEM KÉREK -->
      <div
        class="group relative flex cursor-pointer flex-col justify-between border-2 p-4 sm:p-5 transition duration-150 outline-none focus-visible:outline-2 focus-visible:outline-brick"
        :class="!state.hasVentilation ? 'border-navy bg-warm-white shadow-xs' : 'border-line bg-white hover:border-slate-300'"
        role="button"
        :tabindex="0"
        :aria-pressed="!state.hasVentilation"
        @click="setHasVentilation(false)"
        @keydown.enter.prevent="setHasVentilation(false)"
        @keydown.space.prevent="setHasVentilation(false)"
      >
        <div class="flex flex-col gap-3">
          <!-- Kép áthúzva -->
          <div class="relative flex h-28 sm:h-32 w-full items-center justify-center overflow-hidden border border-cream bg-[#f8fafc] p-2">
            <img
              v-if="ventilationImage"
              :src="ventilationImage"
              alt="Nem kérek átvezető elemet"
              class="size-full object-contain grayscale opacity-40"
            />
            <svg v-else viewBox="0 0 64 48" fill="none" class="size-16 text-slate-300">
              <circle cx="32" cy="24" r="12" stroke="currentColor" stroke-width="2" fill="currentColor" opacity="0.1"/>
              <path d="M32 12V6M32 42v-6M12 24H6M58 24h-6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" opacity="0.5"/>
            </svg>
            <!-- Áthúzó vonal (monokróm) -->
            <div class="absolute inset-0 flex items-center justify-center pointer-events-none p-2">
              <svg viewBox="0 0 100 100" class="size-full text-slate-400" preserveAspectRatio="none">
                <line x1="12" y1="88" x2="88" y2="12" stroke="currentColor" stroke-width="3.5" stroke-linecap="round" />
              </svg>
            </div>
          </div>

          <div class="flex items-center justify-between">
            <span class="font-heading text-sm sm:text-base font-bold text-navy">Nem kérek átvezető elemet</span>
            <span
              class="flex size-5 shrink-0 items-center justify-center rounded-full border-2"
              :class="!state.hasVentilation ? 'border-navy bg-navy text-white' : 'border-slate-300'"
              aria-hidden="true"
            >
              <svg v-if="!state.hasVentilation" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3.5" class="size-3">
                <polyline points="20 6 9 17 4 12"></polyline>
              </svg>
            </span>
          </div>
          <p class="m-0 font-sans text-xs leading-relaxed text-muted">
            <strong>Ajánlott választás, ha nincs átvezetendő cső vagy vezeték.</strong> A tető természetes szellőzése a gerincnél enélkül is 100%-ban garantált.
          </p>
        </div>
      </div>

      <!-- 2. IGEN, KÉREK -->
      <div
        class="group relative flex flex-col justify-between border-2 p-4 sm:p-5 transition duration-150 outline-none focus-visible:outline-2 focus-visible:outline-brick"
        :class="[
          isBrownColor ? 'cursor-not-allowed opacity-50 border-slate-200 bg-slate-50' : 'cursor-pointer',
          state.hasVentilation ? 'border-brick bg-warm-white shadow-xs' : 'border-line bg-white hover:border-slate-300'
        ]"
        :role="isBrownColor ? undefined : 'button'"
        :tabindex="isBrownColor ? -1 : 0"
        :aria-pressed="state.hasVentilation"
        :aria-disabled="isBrownColor"
        @click="!isBrownColor && setHasVentilation(true)"
        @keydown.enter.prevent="!isBrownColor && setHasVentilation(true)"
        @keydown.space.prevent="!isBrownColor && setHasVentilation(true)"
      >
        <div class="flex flex-col gap-3">
          <!-- Kép a választott színben -->
          <div class="relative flex h-28 sm:h-32 w-full items-center justify-center overflow-hidden border border-cream bg-[#f8fafc] p-2">
            <img
              v-if="ventilationImage"
              :src="ventilationImage"
              :alt="activeOption.name"
              class="size-full object-contain"
            />
            <div v-else class="flex flex-col items-center justify-center text-slate-400 gap-1">
              <svg viewBox="0 0 64 48" fill="none" class="size-14">
                <circle cx="32" cy="24" r="12" stroke="currentColor" stroke-width="2" fill="currentColor" opacity="0.1"/>
                <path d="M32 12V6M32 42v-6M12 24H6M58 24h-6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" opacity="0.5"/>
              </svg>
            </div>
          </div>

          <div class="flex items-center justify-between">
            <span class="font-heading text-sm sm:text-base font-bold text-navy">Igen, kérek átvezető elemet</span>
            <span
              class="flex size-5 shrink-0 items-center justify-center rounded-full border-2"
              :class="state.hasVentilation ? 'border-brick bg-brick text-white' : 'border-slate-300'"
              aria-hidden="true"
            >
              <svg v-if="state.hasVentilation" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3.5" class="size-3">
                <polyline points="20 6 9 17 4 12"></polyline>
              </svg>
            </span>
          </div>
          <p class="m-0 font-sans text-xs leading-relaxed text-muted">
            {{ isBrownColor ? 'Barna színben jelenleg nem választható.' : 'Csak akkor szükséges, ha konyhai páraelszívó csövet, ventilátort vagy kábelt vezet át a tetőn.' }}
          </p>

          <!-- Lenyíló darabszám választó a kártyán belül -->
          <transition name="rc-expand">
            <div
              v-if="state.hasVentilation"
              class="mt-2 flex flex-col gap-3 border-t border-slate-200 pt-3"
              @click.stop
            >
              <div class="flex items-center justify-between gap-3 max-sm:flex-wrap">
                <label for="vent-count-input" class="font-heading text-xs font-bold text-navy">Darabszám:</label>
                <div class="flex items-center gap-2">
                  <div class="inline-flex items-center border border-slate-300 bg-white">
                    <button
                      type="button"
                      class="rc-qty-btn"
                      @click.stop="decreaseCount"
                      aria-label="Darabszám csökkentése"
                    >-</button>
                    <input
                      id="vent-count-input"
                      type="number"
                      min="1"
                      step="1"
                      :value="state.ventilationCount || 1"
                      @input="onCountInput"
                      class="rc-qty-input font-bold"
                    />
                    <button
                      type="button"
                      class="rc-qty-btn"
                      @click.stop="increaseCount"
                      aria-label="Darabszám növelése"
                    >+</button>
                  </div>
                  <span class="font-sans text-xs font-semibold text-muted">db</span>
                </div>
              </div>
              <div class="flex items-center gap-1.5 font-sans text-[11px] font-semibold text-brick">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" class="size-3.5 shrink-0">
                  <polyline points="20 6 9 17 4 12"></polyline>
                </svg>
                {{ state.ventilationCount || 1 }} db szellőző / átvezető elem kerül a rendelésbe
              </div>
            </div>
          </transition>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { useCalculator } from '../composables/useCalculator.js';

const { state, canProceed, nextStep, prevStep, openStep3Help } = useCalculator();

// Hint szöveg ha a Tovább gomb tiltva
const hintText = computed(() => {
  if (canProceed.value) return null;
  switch (state.currentStep) {
    case 1: return 'Válassza ki a tetőformát (és altípust) a folytatáshoz.';
    case 2: return 'Töltse ki a tetőfelületet a folytatáshoz.';
    case 3: return 'Válasszon színt a folytatáshoz.';
    case 4: return 'Válasszon szellőzési módot a folytatáshoz.';
    case 5: return 'Válassza ki a rögzítés módját a folytatáshoz.';
    default: return null;
  }
});

const navigateAndScroll = (navigate) => {
  navigate();

  requestAnimationFrame(() => {
    const calculator = document.querySelector('.rocktile-calculator-ui');
    if (!calculator) return;

    const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    const top = calculator.getBoundingClientRect().top + window.scrollY - 20;
    window.scrollTo({ top, behavior: reduceMotion ? 'auto' : 'smooth' });
  });
};
</script>

<template>
  <div class="mt-8 border-t border-cream pt-5 flex flex-col gap-3">
    <!-- Hint szöveg ha a Tovább gomb tiltva -->
    <transition name="rc-hint" mode="out-in">
      <p v-if="hintText" class="m-0 text-left sm:text-right font-sans text-xs text-muted" role="alert" aria-live="polite">
        {{ hintText }}
      </p>
    </transition>

    <!-- Segítségkérő gomb Step 2-nél (ha aktív) mobilon külön sorban -->
    <div v-if="state.currentStep === 2" class="w-full flex justify-end">
      <button
        type="button"
        class="rc-nav-help-btn w-full sm:w-auto"
        @click="openStep3Help"
        aria-label="Segítséget kérek a méretek megadásához"
      >
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="size-4" aria-hidden="true">
          <circle cx="12" cy="12" r="10"></circle>
          <path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"></path>
          <line x1="12" y1="17" x2="12.01" y2="17"></line>
        </svg>
        <span>Segítséget kérek</span>
      </button>
    </div>

    <!-- Navigációs gombok sora -->
    <div
      class="w-full gap-3"
      :class="state.currentStep > 1 && state.currentStep < 6 ? 'grid grid-cols-2 sm:flex sm:items-center sm:justify-between' : 'flex items-center justify-end'"
    >
      <!-- Visszalépés gomb -->
      <button
        v-if="state.currentStep > 1"
        type="button"
        class="rc-btn rc-btn-secondary w-full sm:w-auto"
        @click="navigateAndScroll(prevStep)"
        aria-label="Vissza az előző lépésre"
      >
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="size-4" aria-hidden="true">
          <line x1="19" y1="12" x2="5" y2="12"></line>
          <polyline points="12 19 5 12 12 5"></polyline>
        </svg>
        <span>Vissza</span>
      </button>

      <!-- Tovább gomb -->
      <button
        v-if="state.currentStep < 6"
        type="button"
        class="rc-btn rc-btn-primary w-full sm:w-auto"
        :class="{ 'sm:ml-auto': state.currentStep === 1 }"
        :disabled="!canProceed"
        :aria-disabled="!canProceed"
        @click="navigateAndScroll(nextStep)"
        aria-label="Tovább a következő lépésre"
      >
        <span>Tovább</span>
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="size-4" aria-hidden="true">
          <line x1="5" y1="12" x2="19" y2="12"></line>
          <polyline points="12 5 19 12 12 19"></polyline>
        </svg>
      </button>
    </div>
  </div>
</template>

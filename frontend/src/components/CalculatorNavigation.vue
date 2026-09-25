<script setup>
import { computed } from 'vue';
import { useCalculator } from '../composables/useCalculator.js';

const { state, canProceed, nextStep, prevStep, openStep3Help } = useCalculator();

// Hint szöveg ha a Tovább gomb tiltva
const hintText = computed(() => {
  if (canProceed.value) return null;
  switch (state.currentStep) {
    case 1: return 'Válasszon terméket és színt a folytatáshoz.';
    case 2: return 'Válassza ki a tetőformát (és altípust) a folytatáshoz.';
    case 3: return 'Töltse ki az összes mértmezőt a folytatáshoz.';
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
  <div class="mt-9 flex items-center justify-between gap-4 border-t border-cream pt-5 max-[480px]:flex-col max-[480px]:gap-3">
    <!-- Visszalépés gomb -->
    <button
      v-if="state.currentStep > 1"
      type="button"
      class="rc-btn rc-btn-secondary max-[480px]:w-full"
      @click="navigateAndScroll(prevStep)"
      aria-label="Vissza az előző lépésre"
    >
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="size-4" aria-hidden="true">
        <line x1="19" y1="12" x2="5" y2="12"></line>
        <polyline points="12 19 5 12 12 5"></polyline>
      </svg>
      Vissza
    </button>
    <div v-else class="shrink-0"></div>

    <!-- Jobb oldal: hint + Tovább gomb -->
    <div class="flex flex-wrap items-center justify-end gap-3 max-[480px]:w-full max-[480px]:justify-center">
      <transition name="rc-hint" mode="out-in">
        <p v-if="hintText" class="m-0 max-w-[260px] text-right font-sans text-[13px] text-muted max-[480px]:max-w-full max-[480px]:text-center" role="alert" aria-live="polite">
          {{ hintText }}
        </p>
      </transition>

      <button
        v-if="state.currentStep === 3"
        type="button"
        class="rc-nav-help-btn max-[480px]:w-full"
        @click="openStep3Help"
        aria-label="Segítséget kérek a méretek megadásához"
      >
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" class="size-4" aria-hidden="true">
          <circle cx="12" cy="12" r="10"></circle>
          <path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"></path>
          <line x1="12" y1="17" x2="12.01" y2="17"></line>
        </svg>
        Segítséget kérek
      </button>

      <button
        v-if="state.currentStep < 6"
        type="button"
        class="rc-btn rc-btn-primary max-[480px]:-order-1 max-[480px]:w-full"
        :disabled="!canProceed"
        :aria-disabled="!canProceed"
        @click="navigateAndScroll(nextStep)"
        aria-label="Tovább a következő lépésre"
      >
        Tovább
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="size-4" aria-hidden="true">
          <line x1="5" y1="12" x2="19" y2="12"></line>
          <polyline points="12 5 19 12 12 19"></polyline>
        </svg>
      </button>
    </div>
  </div>
</template>

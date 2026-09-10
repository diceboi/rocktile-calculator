<script setup>
import { computed } from 'vue';
import { useCalculator } from '../composables/useCalculator.js';

const { state, hasVerge, goToStep } = useCalculator();

const allSteps = [
  { step: 1, title: 'Termék & Szín',  shortTitle: 'Termék',     icon: '🏠' },
  { step: 2, title: 'Tetőforma',       shortTitle: 'Tetőforma',  icon: '⬡' },
  { step: 3, title: 'Méretek',         shortTitle: 'Méretek',    icon: '📐' },
  { step: 4, title: 'Szellőzés',       shortTitle: 'Szellőzés',  icon: '💨' },
  { step: 5, title: 'Oromszegély',     shortTitle: 'Orom',       requiresVerge: true },
  { step: 6, title: 'Összesítés',      shortTitle: 'Összesítés', icon: '✅' },
];

const visibleSteps = computed(() =>
  allSteps.filter((s) => !s.requiresVerge || hasVerge.value)
);

// Az aktuális lépés sorszáma a látható lista alapján
const currentVisibleIndex = computed(() =>
  visibleSteps.value.findIndex((s) => s.step === state.currentStep)
);

const progressPercent = computed(() => {
  const total = visibleSteps.value.length - 1;
  const cur   = currentVisibleIndex.value;
  return total > 0 ? Math.round((cur / total) * 100) : 0;
});

const currentStepTitle = computed(
  () => visibleSteps.value.find((s) => s.step === state.currentStep)?.title || ''
);
</script>

<template>
  <div class="mb-9 w-full">

    <!-- ─── Mobil progress (< 640px) ─── -->
    <div class="hidden flex-col gap-2.5 max-sm:flex" aria-hidden="true">
      <div class="flex items-baseline gap-2.5">
        <span class="shrink-0 font-heading text-[13px] font-bold text-muted">
          {{ currentVisibleIndex + 1 }} / {{ visibleSteps.length }}
        </span>
        <span class="font-heading text-[15px] font-bold text-navy">{{ currentStepTitle }}</span>
      </div>
      <div class="h-[5px] w-full overflow-hidden bg-cream" role="progressbar" :aria-valuenow="progressPercent" aria-valuemin="0" aria-valuemax="100">
        <div class="h-full bg-linear-to-r from-navy to-brick transition-[width] duration-300 motion-reduce:transition-none" :style="{ width: progressPercent + '%' }"></div>
      </div>
    </div>

    <!-- ─── Desktop stepper (≥ 640px) ─── -->
    <ol class="m-0 grid w-full list-none p-0 max-sm:hidden" :style="{ gridTemplateColumns: `repeat(${visibleSteps.length}, minmax(0, 1fr))` }" aria-label="Kalkulátor lépések">
      <li
        v-for="(item, index) in visibleSteps"
        :key="item.step"
        class="relative flex min-w-0 flex-col items-center focus-visible:outline-2 focus-visible:outline-offset-3 focus-visible:outline-brick"
        :class="{
          'group cursor-pointer': state.currentStep > item.step,
        }"
        @click="state.currentStep > item.step && goToStep(item.step)"
        :tabindex="state.currentStep > item.step ? 0 : -1"
        @keydown.enter="state.currentStep > item.step && goToStep(item.step)"
        @keydown.space.prevent="state.currentStep > item.step && goToStep(item.step)"
        :aria-current="state.currentStep === item.step ? 'step' : undefined"
        :aria-label="`${index + 1}. lépés: ${item.title}${state.currentStep > item.step ? ' – teljesítve' : state.currentStep === item.step ? ' – aktív' : ''}`"
      >
        <!-- Összekötő vonal (az első elé nem kell) -->
        <div
          v-if="index > 0"
          class="absolute top-[17px] right-1/2 h-px w-full bg-line transition-colors duration-300 motion-reduce:transition-none"
          :class="state.currentStep > item.step ? 'bg-navy' : 'bg-line'"
        ></div>

        <!-- Kör jelző -->
        <div
          class="z-1 flex size-9 shrink-0 items-center justify-center rounded-full border-[2.5px] font-heading text-[13px] font-bold transition duration-200 motion-reduce:transition-none"
          :class="state.currentStep > item.step
            ? 'border-navy bg-navy text-white group-hover:scale-[1.08] group-hover:border-navy-dark group-hover:bg-navy-dark group-focus-visible:scale-[1.08] group-focus-visible:border-navy-dark group-focus-visible:bg-navy-dark'
            : state.currentStep === item.step
              ? 'border-brick bg-brick text-white'
              : 'border-slate-300 bg-white text-slate-400'"
        >
          <svg
            v-if="state.currentStep > item.step"
            viewBox="0 0 24 24" fill="none"
            stroke="currentColor" stroke-width="3"
            stroke-linecap="round" stroke-linejoin="round"
            class="size-4"
          >
            <polyline points="20 6 9 17 4 12"></polyline>
          </svg>
          <span v-else class="leading-none">{{ index + 1 }}</span>
        </div>

        <!-- Szöveg -->
        <div class="mt-2 min-w-0 text-center">
          <span
            class="font-sans text-[12.5px] leading-4 font-semibold"
            :class="state.currentStep > item.step ? 'text-navy' : state.currentStep === item.step ? 'font-bold text-brick' : 'text-slate-400'"
          >{{ item.title }}</span>
        </div>
      </li>
    </ol>

  </div>
</template>

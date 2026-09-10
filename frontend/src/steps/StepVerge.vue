<script setup>
import { computed, onMounted } from 'vue';
import { vergeOptions } from '../config/vergeTypes.js';
import { useCalculator } from '../composables/useCalculator.js';
import { useProductCatalog } from '../composables/useProductCatalog.js';

const { state, selectVergeType } = useCalculator();
const { catalog, loadCatalog } = useProductCatalog();

onMounted(() => {
  loadCatalog();
});

const getVergeImage = (optId) => {
  if (optId === 'under-cover' || optId === 'under') {
    return catalog.value?.vergeUnder?.colors?.[state.color]?.image || null;
  }
  if (optId === 'over-cover' || optId === 'over') {
    return catalog.value?.vergeOver?.colors?.[state.color]?.image || null;
  }
  return null;
};

// Leírások a két típushoz
const vergeDetails = {
  'under-cover': {
    subtitle: 'Alátakarós',
    hint: 'A szegélyelem a cserepek alá simulva védi az oromszegélyt. Esztétikus, alacsony profil megjelenés.',
  },
  'over-cover': {
    subtitle: 'Fölétakarós',
    hint: 'A szegélyelem a cserepek peremét felülről takarja. Erős védelem, kiemelkedő megjelenés.',
  },
};
</script>

<template>
  <div class="flex flex-col gap-7">
    <!-- Step header -->
    <div class="rc-step-header">
      <span class="rc-step-badge">5. lépés</span>
      <h2 class="rc-step-heading">Oromszegély típusa</h2>
      <p class="rc-step-subheading">
        A kiválasztott tetőforma oromzattal rendelkezik. Válassza ki, hogy az oromszegély alulról vagy felülről takarja-e a cserepet.
      </p>
    </div>

    <!-- Nagy kártyák -->
    <div class="grid grid-cols-[repeat(auto-fill,minmax(240px,1fr))] gap-4 max-[480px]:grid-cols-1 max-[480px]:gap-3.5">
      <div
        v-for="opt in vergeOptions"
        :key="opt.id"
        class="group relative cursor-pointer overflow-hidden border-2 outline-none transition duration-200 hover:-translate-y-0.5 hover:border-slate-400 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brick motion-reduce:transition-none"
        :class="state.vergeType === opt.id ? 'border-navy bg-warm-white' : 'border-line bg-white'"
        role="button"
        :tabindex="0"
        :aria-pressed="state.vergeType === opt.id"
        :aria-label="opt.name"
        @click="selectVergeType(opt.id)"
        @keydown.enter.prevent="selectVergeType(opt.id)"
        @keydown.space.prevent="selectVergeType(opt.id)"
      >
        <!-- Media -->
        <div class="relative flex h-52 w-full items-center justify-center overflow-hidden border-b border-cream bg-[#f2efe9]">
          <img v-if="getVergeImage(opt.id) || opt.image" :src="getVergeImage(opt.id) || opt.image" :alt="opt.name" class="size-full object-cover" />
          <div v-else class="flex size-full items-center justify-center bg-linear-to-br from-surface-muted to-cream text-slate-400 p-4">
            <svg viewBox="0 0 80 60" fill="none" class="h-auto max-h-[110px] w-auto max-w-[140px]" aria-hidden="true">
              <!-- Under cover (alátakarós) -->
              <template v-if="opt.id === 'under-cover'">
                <path d="M10 44L40 8l30 36" fill="currentColor" opacity="0.08" stroke="currentColor" stroke-width="2"/>
                <!-- szegélyelem alulról -->
                <path d="M10 44H70" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"/>
                <path d="M8 44L8 50" stroke="#CF3F29" stroke-width="3" stroke-linecap="round"/>
                <path d="M72 44L72 50" stroke="#CF3F29" stroke-width="3" stroke-linecap="round"/>
                <rect x="5" y="49" width="70" height="5" rx="2" fill="#CF3F29" opacity="0.7"/>
                <text x="40" y="58" text-anchor="middle" font-size="8" fill="#022A50" font-family="Inter,sans-serif" font-weight="600">alátakarós</text>
              </template>
              <!-- Over cover (fölétakarós) -->
              <template v-else>
                <path d="M10 44L40 8l30 36" fill="currentColor" opacity="0.08" stroke="currentColor" stroke-width="2"/>
                <path d="M10 44H70" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"/>
                <rect x="5" y="38" width="70" height="7" rx="2" fill="#CF3F29" opacity="0.7"/>
                <text x="40" y="58" text-anchor="middle" font-size="8" fill="#022A50" font-family="Inter,sans-serif" font-weight="600">fölétakarós</text>
              </template>
            </svg>
          </div>

          <!-- Selected check -->
          <div v-if="state.vergeType === opt.id" class="absolute top-2.5 right-2.5 flex size-7 items-center justify-center rounded-full bg-navy text-white shadow-sm" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" class="size-[15px]">
              <polyline points="20 6 9 17 4 12"></polyline>
            </svg>
          </div>
        </div>

        <!-- Content -->
        <div class="flex flex-col gap-2 p-5">
          <span
            class="w-fit px-2 py-0.5 font-sans text-[10px] font-bold tracking-[0.6px] uppercase"
            :class="state.vergeType === opt.id ? 'bg-brick/10 text-brick' : 'bg-cream text-ink'"
          >{{ vergeDetails[opt.id]?.subtitle }}</span>
          
          <h3 class="m-0 font-heading text-base font-bold text-navy leading-snug">{{ opt.name }}</h3>
          
          <p class="m-0 font-sans text-[13px] leading-relaxed text-muted">{{ vergeDetails[opt.id]?.hint || opt.description }}</p>
        </div>
      </div>
    </div>
  </div>
</template>

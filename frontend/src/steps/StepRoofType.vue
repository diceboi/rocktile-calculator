<script setup>
import { ref, nextTick } from 'vue';
import { roofTypes } from '../config/roofTypes.js';
import { useCalculator } from '../composables/useCalculator.js';
import OptionCard from '../components/OptionCard.vue';

const { state, selectedRoofGroup, selectRoofType, selectRoofSubtype } = useCalculator();

const subtypeSectionRef = ref(null);

const handleRoofTypeSelect = (roofId) => {
  selectRoofType(roofId);
  const group = roofTypes.find((r) => r.id === roofId);
  if (group?.subtypes?.length > 0) {
    nextTick(() => {
      setTimeout(() => {
        if (subtypeSectionRef.value) {
          const reduceMotion = typeof window !== 'undefined' && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
          const isMobile = typeof window !== 'undefined' && window.innerWidth <= 768;
          const offset = isMobile ? 24 : 40;
          const rect = subtypeSectionRef.value.getBoundingClientRect();
          const scrollY = window.pageYOffset || window.scrollY || 0;
          const targetY = rect.top + scrollY - offset;

          window.scrollTo({
            top: Math.max(0, targetY),
            behavior: reduceMotion ? 'auto' : 'smooth',
          });
        }
      }, 80);
    });
  }
};
</script>

<template>
  <div class="flex flex-col gap-7">
    <!-- Step header -->
    <div class="rc-step-header">
      <span class="rc-step-badge">1. lépés</span>
      <h2 class="rc-step-heading">Válassza ki a tetőformát</h2>
    </div>

    <!-- Fő tetőtípusok -->
    <div class="grid grid-cols-[repeat(auto-fill,minmax(175px,1fr))] gap-3.5 max-[480px]:grid-cols-2 max-[480px]:gap-2.5">
      <OptionCard
        v-for="roof in roofTypes"
        :key="roof.id"
        :title="roof.name"
        :description="roof.description"
        :image="roof.image"
        image-fit="contain"
        placeholder-variant="roof"
        :selected="state.roofType === roof.id"
        @click="handleRoofTypeSelect(roof.id)"
      />
    </div>

    <!-- Altípus szekció – fade-in ha szükséges -->
    <transition name="rc-fade">
      <div
        v-if="selectedRoofGroup && selectedRoofGroup.subtypes && selectedRoofGroup.subtypes.length > 0"
        ref="subtypeSectionRef"
        class="border-t border-cream pt-6 scroll-mt-6"
      >
        <div class="mb-4 flex items-center gap-2">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-[18px] shrink-0 text-brick" aria-hidden="true">
            <polyline points="9 18 15 12 9 6"></polyline>
          </svg>
          <h3 class="m-0 font-heading text-base font-bold text-navy">
            Milyen {{ selectedRoofGroup.name.toLowerCase() }}ről van szó?
          </h3>
        </div>

        <div class="grid grid-cols-[repeat(auto-fill,minmax(180px,1fr))] gap-3.5 max-[480px]:grid-cols-2 max-[480px]:gap-2.5">
          <OptionCard
            v-for="subtype in selectedRoofGroup.subtypes"
            :key="subtype.id"
            :title="subtype.name"
            :description="subtype.description"
            :image="subtype.image"
            image-fit="contain"
            placeholder-variant="roof"
            :selected="state.roofSubtype === subtype.id"
            @click="selectRoofSubtype(subtype.id)"
          />
        </div>
      </div>
    </transition>
  </div>
</template>

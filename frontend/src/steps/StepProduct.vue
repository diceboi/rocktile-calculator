<script setup>
import { onMounted, computed } from 'vue';
import { products } from '../config/products.js';
import { useCalculator } from '../composables/useCalculator.js';
import { useProductCatalog } from '../composables/useProductCatalog.js';

const { state, selectedProduct, selectProduct, selectColor } = useCalculator();
const { catalog, loadCatalog } = useProductCatalog();

const enabledProducts = products.filter((p) => p.enabled !== false);
const defaultProduct = enabledProducts[0] || products[0];

const getColorImage = (productId, color) =>
  catalog.value?.products?.[productId]?.colors?.[color.id]?.image || color.image;

const getColorSubtitle = (color) => {
  if (!color?.fullName) return '';
  if (color.code) {
    return color.fullName.replace(color.code, '').trim();
  }
  return color.fullName;
};

const currentProduct = computed(() => selectedProduct.value || defaultProduct);

const handleSelectColor = (colorId) => {
  if (!state.product && currentProduct.value) {
    selectProduct(currentProduct.value.id);
  }
  selectColor(colorId);
};

onMounted(() => {
  loadCatalog();
  if (!state.product && defaultProduct) {
    selectProduct(defaultProduct.id);
  }
});
</script>

<template>
  <div class="flex flex-col gap-6">
    <!-- Step header -->
    <div class="rc-step-header">
      <span class="rc-step-badge">3. lépés</span>
      <h2 class="rc-step-heading">Válassza ki a színt</h2>
    </div>

    <!-- Szín választó – mindig megjelenik az alapértelmezett termék színeivel -->
    <div v-if="currentProduct" class="flex flex-col gap-3">
      <!-- Színek sorokban mobilon, kis téglalapokban -->
      <div class="grid grid-cols-1 gap-2.5 sm:grid-cols-2 lg:grid-cols-3">
        <div
          v-for="color in currentProduct.colors.filter((c) => c.enabled !== false)"
          :key="color.id"
          class="group relative flex cursor-pointer items-center gap-3.5 border-2 p-3 transition duration-150 outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brick"
          :class="state.color === color.id ? 'border-navy bg-warm-white shadow-xs' : 'border-line bg-white hover:border-slate-400'"
          role="button"
          :tabindex="0"
          :aria-pressed="state.color === color.id"
          @click="handleSelectColor(color.id)"
          @keydown.enter.prevent="handleSelectColor(color.id)"
          @keydown.space.prevent="handleSelectColor(color.id)"
        >
          <!-- Szín minta / kép thumbnail (kompakt) -->
          <div class="relative size-14 shrink-0 overflow-hidden border border-cream bg-surface-muted flex items-center justify-center">
            <img
              v-if="getColorImage(currentProduct.id, color)"
              :src="getColorImage(currentProduct.id, color)"
              :alt="color.name"
              class="size-full object-cover transition-transform duration-200 group-hover:scale-105"
              loading="lazy"
            />
            <div
              v-else-if="color.hex"
              class="size-full"
              :style="{ backgroundColor: color.hex }"
            ></div>
          </div>

          <!-- Adatok -->
          <div class="flex flex-1 min-w-0 flex-col gap-0.5">
            <div class="flex items-center gap-2">
              <span class="font-heading text-sm font-bold truncate" :class="state.color === color.id ? 'text-brick' : 'text-navy'">
                {{ color.name }}
              </span>
              <span v-if="color.code" class="bg-cream px-1.5 py-0.5 font-sans text-[10px] font-bold text-muted uppercase">
                {{ color.code }}
              </span>
            </div>
            <span class="font-sans text-xs text-muted truncate">
              {{ getColorSubtitle(color) }}
            </span>
          </div>

          <!-- Kiválasztva pipa -->
          <div
            v-if="state.color === color.id"
            class="flex size-6 shrink-0 items-center justify-center rounded-full bg-navy text-white mr-1"
            aria-hidden="true"
          >
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" class="size-3.5">
              <polyline points="20 6 9 17 4 12"></polyline>
            </svg>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

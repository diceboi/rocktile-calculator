<script setup>
import { onMounted, computed } from 'vue';
import { products } from '../config/products.js';
import { useCalculator } from '../composables/useCalculator.js';
import { useProductCatalog } from '../composables/useProductCatalog.js';
import OptionCard from '../components/OptionCard.vue';

const { state, selectedProduct, selectProduct, selectColor } = useCalculator();
const { catalog, loadCatalog } = useProductCatalog();

const enabledProducts = products.filter((p) => p.enabled !== false);

const getProductImage = (product) =>
  catalog.value?.products?.[product.id]?.colors?.[state.color]?.image ||
  catalog.value?.products?.[product.id]?.image ||
  product.image;

const getColorImage = (productId, color) =>
  catalog.value?.products?.[productId]?.colors?.[color.id]?.image || color.image;

const getColorSubtitle = (color) => {
  if (!color?.fullName) return '';
  if (color.code) {
    return color.fullName.replace(color.code, '').trim();
  }
  return color.fullName;
};

const currentProduct = computed(() => selectedProduct.value || enabledProducts[0]);

const handleSelectColor = (colorId) => {
  if (!state.product && currentProduct.value) {
    selectProduct(currentProduct.value.id);
  }
  selectColor(colorId);
};

onMounted(() => {
  loadCatalog();
  if (!state.product && enabledProducts.length > 0) {
    selectProduct(enabledProducts[0].id);
  }
});
</script>

<template>
  <div class="flex flex-col gap-7">
    <!-- Step header -->
    <div class="rc-step-header">
      <span class="rc-step-badge">3. lépés</span>
      <h2 class="rc-step-heading">Válasszon cserepet és színt</h2>
    </div>

    <!-- Termék kártya (egyetlen cserép, elegáns teljes szélességű horizontális kártya) -->
    <div
      v-for="prod in enabledProducts"
      :key="prod.id"
      class="w-full border-2 border-navy bg-warm-white p-4 sm:p-5 flex flex-col sm:flex-row items-center sm:items-stretch gap-4 sm:gap-6 relative transition duration-150 cursor-pointer"
      role="button"
      tabindex="0"
      :aria-pressed="state.product === prod.id"
      @click="selectProduct(prod.id)"
      @keydown.enter.prevent="selectProduct(prod.id)"
      @keydown.space.prevent="selectProduct(prod.id)"
    >
      <!-- Cserép fotó -->
      <div class="relative w-full sm:w-44 h-36 sm:h-auto shrink-0 bg-white border border-cream flex items-center justify-center p-3 overflow-hidden">
        <img
          v-if="getProductImage(prod)"
          :src="getProductImage(prod)"
          :alt="prod.name"
          class="size-full object-contain"
          loading="lazy"
        />
        <div v-else class="flex size-full items-center justify-center text-slate-300">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="size-12">
            <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
          </svg>
        </div>
      </div>

      <!-- Termék adatok -->
      <div class="flex flex-1 flex-col justify-between gap-2 text-left w-full">
        <div class="flex flex-col gap-1">
          <div class="flex items-center gap-2">
            <span class="bg-navy/8 px-2 py-0.5 font-sans text-[11px] font-bold tracking-wider text-navy uppercase">
              Kőzúzalékos acél cserepeslemez
            </span>
          </div>
          <h3 class="m-0 font-heading text-lg sm:text-xl font-bold text-navy">
            {{ prod.name }}
          </h3>
          <p class="m-0 font-sans text-xs sm:text-sm leading-relaxed text-slate-700">
            {{ prod.description || 'Hagyományos hullámos profil prémium kőzúzalékos felületkezeléssel (2,16 db/m²)' }}
          </p>
        </div>

        <div class="flex items-center gap-2 pt-2 border-t border-cream">
          <div class="flex size-5 shrink-0 items-center justify-center rounded-full bg-navy text-white" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" class="size-3">
              <polyline points="20 6 9 17 4 12"></polyline>
            </svg>
          </div>
          <span class="font-sans text-xs font-bold text-navy">
            Alapértelmezett választás
          </span>
        </div>
      </div>
    </div>

    <!-- Szín választó – mindig megjelenik az alapértelmezett termék színeivel -->
    <div v-if="currentProduct" class="border-t border-cream pt-6">
      <div class="mb-4">
        <h3 class="m-0 font-heading text-base font-bold text-navy">
          Elérhető színek
          <span class="text-[15px] font-semibold text-muted">– {{ currentProduct.name }}</span>
        </h3>
      </div>

      <!-- Színek sorokban mobilon, hogy ne kelljen sokat görgetni -->
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

<script setup>
import { onMounted } from 'vue';
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

onMounted(loadCatalog);
</script>

<template>
  <div class="flex flex-col gap-7">
    <!-- Step header -->
    <div class="rc-step-header">
      <span class="rc-step-badge">1. lépés</span>
      <h2 class="rc-step-heading">Válasszon cserepet és színt</h2>
      <p class="rc-step-subheading">Válassza ki az Önnek tetsző Rocktile termékcsaládot, majd a kívánt színt.</p>
    </div>

    <!-- Termék kártyák -->
    <div class="grid grid-cols-[repeat(auto-fill,minmax(220px,1fr))] gap-4 max-[480px]:grid-cols-2 max-[480px]:gap-3">
      <OptionCard
        v-for="prod in enabledProducts"
        :key="prod.id"
        :title="prod.name"
        :description="prod.description"
        :image="getProductImage(prod)"
        placeholder-variant="generic"
        :selected="state.product === prod.id"
        @click="selectProduct(prod.id)"
      />
    </div>

    <!-- Szín választó -->
    <transition name="rc-fade">
      <div v-if="selectedProduct" class="border-t border-cream pt-6">
        <div class="mb-4">
          <h3 class="m-0 font-heading text-base font-bold text-navy">
            Elérhető színek
            <span class="text-[15px] font-semibold text-muted">– {{ selectedProduct.name }}</span>
          </h3>
        </div>

        <div class="grid grid-cols-[repeat(auto-fill,minmax(180px,1fr))] gap-3.5 max-[480px]:grid-cols-2 max-[480px]:gap-3">
          <OptionCard
            v-for="color in selectedProduct.colors.filter((c) => c.enabled !== false)"
            :key="color.id"
            :title="color.name"
            :subtitle="color.fullName"
            :color-hex="color.hex"
            :color-code="color.code"
            :image="getColorImage(selectedProduct.id, color)"
            :selected="state.color === color.id"
            @click="selectColor(color.id)"
          />
        </div>
      </div>
    </transition>
  </div>
</template>

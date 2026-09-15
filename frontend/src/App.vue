<script setup>
import { onMounted, watch } from "vue";
import { useCalculator } from "./composables/useCalculator.js";
import { trackStepView } from "./utils/dataLayer.js";
import CalculatorProgress from "./components/CalculatorProgress.vue";
import CalculatorNavigation from "./components/CalculatorNavigation.vue";
import StepProduct from "./steps/StepProduct.vue";
import StepRoofType from "./steps/StepRoofType.vue";
import StepDimensions from "./steps/StepDimensions.vue";
import StepVentilation from "./steps/StepVentilation.vue";
import StepFastening from "./steps/StepFastening.vue";
import StepSummary from "./steps/StepSummary.vue";

const { state, selectedProduct } = useCalculator();

function reportCurrentStep(step) {
  trackStepView(step, {
    productId: state.product,
    productName: selectedProduct.value?.name,
    colorId: state.color,
    roofType: state.roofType,
    roofSubtype: state.roofSubtype,
  });
}

onMounted(() => {
  reportCurrentStep(state.currentStep || 1);
});

watch(
  () => state.currentStep,
  (newStep) => {
    reportCurrentStep(newStep);
  }
);
</script>

<template>
  <div
    class="rocktile-calculator-ui flex w-full items-start justify-center px-4 pt-8 pb-12 font-sans text-navy-dark max-sm:px-3 max-sm:pt-4 max-sm:pb-8"
  >
    <div
      class="w-full max-w-[1100px] border border-line bg-white px-11 py-10 max-[900px]:px-6 max-[900px]:py-7 max-sm:px-4 max-sm:py-5"
    >

      <!-- Folyamatjelző -->
      <CalculatorProgress />

      <!-- Aktív lépés -->
      <div class="min-h-[340px]">
        <transition name="rc-step" mode="out-in">
          <component
            :is="
              state.currentStep === 1
                ? StepProduct
                : state.currentStep === 2
                  ? StepRoofType
                  : state.currentStep === 3
                    ? StepDimensions
                    : state.currentStep === 4
                      ? StepVentilation
                      : state.currentStep === 5
                        ? StepFastening
                        : StepSummary
            "
            :key="state.currentStep"
          />
        </transition>
      </div>

      <!-- Navigáció -->
      <CalculatorNavigation />
    </div>
  </div>
</template>

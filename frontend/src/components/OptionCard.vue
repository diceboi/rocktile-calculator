<script setup>
const props = defineProps({
  title: {
    type: String,
    required: true,
  },
  subtitle: {
    type: String,
    default: '',
  },
  description: {
    type: String,
    default: '',
  },
  image: {
    type: String,
    default: null,
  },
  colorHex: {
    type: String,
    default: null,
  },
  colorCode: {
    type: String,
    default: null,
  },
  badge: {
    type: String,
    default: null,
  },
  selected: {
    type: Boolean,
    default: false,
  },
  disabled: {
    type: Boolean,
    default: false,
  },
  // Image fit style: 'contain' (default) | 'cover'
  imageFit: {
    type: String,
    default: 'contain',
  },
  // Placeholder icon variant: 'roof' | 'color' | 'ventilation' | 'verge' | 'generic'
  placeholderVariant: {
    type: String,
    default: 'generic',
  },
});

defineEmits(['click']);
</script>

<template>
  <div
    class="group relative flex cursor-pointer flex-col overflow-hidden border-2 text-left outline-none transition duration-200 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brick motion-reduce:transition-none"
    :class="[
      selected
        ? 'border-navy bg-warm-white'
        : 'border-line bg-white hover:-translate-y-0.5 hover:border-slate-400',
      disabled ? 'is-disabled pointer-events-none cursor-not-allowed opacity-48' : '',
    ]"
    role="button"
    :tabindex="disabled ? -1 : 0"
    :aria-pressed="selected"
    :aria-disabled="disabled"
    @click="!disabled && $emit('click')"
    @keydown.enter.prevent="!disabled && $emit('click')"
    @keydown.space.prevent="!disabled && $emit('click')"
  >
    <!-- Media area (1:1 square ratio) -->
    <div class="relative flex aspect-square w-full items-center justify-center overflow-hidden border-b border-cream bg-surface-muted">
      <!-- Kép -->
      <img
        v-if="image"
        :src="image"
        :alt="title"
        class="size-full transition-transform duration-300 group-hover:scale-[1.04] motion-reduce:transition-none"
        :class="imageFit === 'cover' ? 'object-cover' : 'object-contain p-3.5'"
        loading="lazy"
      />

      <!-- Szín swatch -->
      <div
        v-else-if="colorHex"
        class="relative size-full transition-transform duration-200 group-hover:scale-[1.04] motion-reduce:transition-none"
        :style="{ backgroundColor: colorHex }"
      >
        <div class="pointer-events-none absolute inset-0 bg-linear-to-br from-white/15 to-60% to-transparent"></div>
      </div>

      <!-- Placeholder – roof -->
      <div v-else-if="placeholderVariant === 'roof'" class="flex size-full items-center justify-center bg-linear-to-br from-surface-muted to-cream text-slate-400">
        <svg viewBox="0 0 64 48" fill="none" class="size-14" aria-hidden="true">
          <path d="M32 4L60 28H4L32 4Z" fill="currentColor" opacity="0.18" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>
          <rect x="12" y="28" width="40" height="16" fill="currentColor" opacity="0.10" stroke="currentColor" stroke-width="1.5"/>
          <rect x="24" y="33" width="16" height="11" fill="currentColor" opacity="0.15" stroke="currentColor" stroke-width="1.5"/>
        </svg>
      </div>

      <!-- Placeholder – ventilation -->
      <div v-else-if="placeholderVariant === 'ventilation'" class="flex size-full items-center justify-center bg-linear-to-br from-surface-muted to-cream text-slate-400">
        <svg viewBox="0 0 64 48" fill="none" class="size-14" aria-hidden="true">
          <circle cx="32" cy="24" r="12" stroke="currentColor" stroke-width="2" fill="currentColor" opacity="0.1"/>
          <path d="M32 12V6M32 42v-6M12 24H6M58 24h-6M17.5 17.5l-4.2-4.2M50.7 50.7l-4.2-4.2M46.5 17.5l4.2-4.2" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" opacity="0.5"/>
          <circle cx="32" cy="24" r="4" fill="currentColor" opacity="0.35"/>
        </svg>
      </div>

      <!-- Placeholder – verge -->
      <div v-else-if="placeholderVariant === 'verge'" class="flex size-full items-center justify-center bg-linear-to-br from-surface-muted to-cream text-slate-400">
        <svg viewBox="0 0 64 48" fill="none" class="size-14" aria-hidden="true">
          <path d="M8 36L32 8l24 28" stroke="currentColor" stroke-width="2" stroke-linejoin="round" fill="currentColor" opacity="0.08"/>
          <rect x="8" y="36" width="48" height="6" fill="currentColor" opacity="0.12" stroke="currentColor" stroke-width="1.5"/>
          <path d="M8 36L32 8" stroke="currentColor" stroke-width="3" stroke-linecap="round"/>
          <path d="M32 8L56 36" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-dasharray="3 3" opacity="0.5"/>
        </svg>
      </div>

      <!-- Placeholder – generic -->
      <div v-else class="flex size-full items-center justify-center bg-linear-to-br from-surface-muted to-cream text-slate-400">
        <svg viewBox="0 0 64 48" fill="none" class="size-14" aria-hidden="true">
          <rect x="8" y="8" width="48" height="32" rx="4" fill="currentColor" opacity="0.08" stroke="currentColor" stroke-width="1.5"/>
          <path d="M8 30l14-12 10 8 8-6 16 10" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round" opacity="0.4"/>
          <circle cx="22" cy="18" r="4" fill="currentColor" opacity="0.2"/>
        </svg>
      </div>

      <!-- Badge -->
      <span v-if="badge" class="absolute top-2 left-2 bg-navy px-2 py-[3px] font-sans text-[10px] font-bold tracking-[0.4px] text-white uppercase">{{ badge }}</span>

      <!-- Selected check -->
      <div v-if="selected" class="absolute top-2 right-2 flex size-[26px] items-center justify-center rounded-full bg-navy text-white" aria-hidden="true">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" class="size-3.5">
          <polyline points="20 6 9 17 4 12"></polyline>
        </svg>
      </div>
    </div>

    <!-- Content -->
    <div class="flex flex-col gap-1 px-4 pt-3 pb-3.5">
      <div class="flex flex-wrap items-center gap-2">
        <span class="font-heading text-sm leading-[1.3] font-bold" :class="selected ? 'text-brick' : 'text-navy'">{{ title }}</span>
        <span v-if="colorCode" class="bg-cream px-[7px] py-0.5 font-sans text-[10px] font-bold tracking-[0.5px] text-muted">{{ colorCode }}</span>
      </div>
      <p v-if="subtitle" class="m-0 font-sans text-xs font-semibold text-ink">{{ subtitle }}</p>
      <p v-if="description" class="m-0 font-sans text-xs leading-[1.45] text-muted">{{ description }}</p>
    </div>
  </div>
</template>

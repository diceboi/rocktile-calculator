import { readonly, ref } from 'vue';

const catalog = ref(null);
const loading = ref(false);
const loaded = ref(false);

async function loadCatalog() {
  if (loaded.value || loading.value) return;

  loading.value = true;
  try {
    const response = await fetch('/wp-json/rocktile/v1/catalog', {
      credentials: 'same-origin',
    });
    if (!response.ok) throw new Error('A termékképek nem tölthetők be.');
    catalog.value = await response.json();
    loaded.value = true;
  } catch (error) {
    // The calculator remains usable with its built-in placeholders.
    console.warn('[Rocktile Calculator]', error);
  } finally {
    loading.value = false;
  }
}

export function useProductCatalog() {
  return {
    catalog: readonly(catalog),
    loading: readonly(loading),
    loadCatalog,
  };
}

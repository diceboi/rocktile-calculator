import { reactive, computed } from 'vue';
import { products } from '../config/products.js';
import { roofTypes, dimensionFields } from '../config/roofTypes.js';
import { ventilationOptions } from '../config/ventilation.js';
import { vergeOptions } from '../config/vergeTypes.js';

// Kezdeti érkezési oldal és hivatkozó oldal rögzítése
function getInitialLandingUrl() {
  if (typeof window === 'undefined') return '';
  try {
    let stored = sessionStorage.getItem('rocktile_landing_url');
    if (!stored) {
      stored = window.location.href;
      sessionStorage.setItem('rocktile_landing_url', stored);
    }
    return stored;
  } catch (e) {
    return window.location.href || '';
  }
}

function getInitialReferrer() {
  if (typeof window === 'undefined') return '';
  try {
    let stored = sessionStorage.getItem('rocktile_referrer');
    if (!stored) {
      stored = document.referrer || '';
      sessionStorage.setItem('rocktile_referrer', stored);
    }
    return stored;
  } catch (e) {
    return document.referrer || '';
  }
}

// Központi singleton reaktív állapot
const state = reactive({
  currentStep: 1,

  product: null,      // termék ID
  color: null,        // szín ID

  roofType: null,     // fő tetőforma ID (pl. 'gable')
  roofSubtype: null,  // altípus ID (pl. 'gable-simple')

  dimensions: {
    roofArea: null,
    ridge: null,
    hip: null,
    valley: null,
    eaves: null,
    verge: null,
  },

  ventilation: 'none',     // szellőzés opció ID ('none' | 'standard')
  hasVentilation: false,   // szellőző kérve (boolean)
  ventilationCount: 1,     // szellőzők darabszáma ha kérik (alapértelmezett 1 db)

  hasChimney: false,       // kémény van-e a tetőn
  chimneyCount: 1,         // kémények darabszáma (ha van, kéményenként 2 db síklemez)

  fastening: 'screw',      // rögzítés módja ('screw' | 'nail') - alapértelmezetten 'screw' (csavar)
  vergeType: 'over-cover', // fölétakarós oromszegély alapértelmezetten (az oromkialakítás kérdés kivezetve)

  addSparePackage: false,  // opcionális +1 csomag alapcserép tartalék

  note: '',                // opcionális megjegyzés

  isStep3HelpModalOpen: false, // 3. lépés segítségkérő felugró ablak
  landingUrl: getInitialLandingUrl(),
  referrerUrl: getInitialReferrer(),
});

export function useCalculator() {
  // Kiválasztott termék objektum
  const selectedProduct = computed(() => {
    if (!state.product) return null;
    return products.find((p) => p.id === state.product) || null;
  });

  // Kiválasztott szín objektum
  const selectedColor = computed(() => {
    if (!selectedProduct.value || !state.color) return null;
    return selectedProduct.value.colors.find((c) => c.id === state.color) || null;
  });

  // Kiválasztott fő tetőcsoport objektum
  const selectedRoofGroup = computed(() => {
    if (!state.roofType) return null;
    return roofTypes.find((r) => r.id === state.roofType) || null;
  });

  // Kiválasztott effektív tetőforma konfiguráció (ha van altípus, az altípus, egyébként a fő típus)
  const selectedRoofConfig = computed(() => {
    if (!selectedRoofGroup.value) return null;
    const group = selectedRoofGroup.value;

    if (group.subtypes && group.subtypes.length > 0) {
      if (!state.roofSubtype) return null;
      return group.subtypes.find((s) => s.id === state.roofSubtype) || null;
    }

    return group;
  });

  // Van-e orom a kiválasztott tetőformánál
  const hasVerge = computed(() => {
    return Boolean(selectedRoofConfig.value?.hasVerge);
  });

  // Aktív méretmezők listája a kiválasztott tetőforma alapján
  const activeFieldKeys = computed(() => {
    return selectedRoofConfig.value?.fields || [];
  });

  // Aktív mezők definíciós objektumai a kiválasztott tetőforma specifikus képeivel
  const activeFields = computed(() => {
    const config = selectedRoofConfig.value;
    return activeFieldKeys.value.map((key) => {
      const baseField = dimensionFields[key];
      if (!baseField) return null;
      const customImage = config?.fieldImages?.[key] || baseField.image;
      return {
        ...baseField,
        image: customImage,
      };
    }).filter(Boolean);
  });

  // Kiválasztott szellőzés objektum
  const selectedVentilation = computed(() => {
    if (!state.ventilation) return null;
    return ventilationOptions.find((v) => v.id === state.ventilation) || null;
  });

  // Kiválasztott oromszegély objektum
  const selectedVerge = computed(() => {
    if (!state.vergeType) return null;
    return vergeOptions.find((v) => v.id === state.vergeType) || null;
  });

  // Tisztítja a nem releváns méretmezőket tetőváltáskor
  const cleanupDimensions = () => {
    const validKeys = activeFieldKeys.value;

    Object.keys(state.dimensions).forEach((key) => {
      if (!validKeys.includes(key)) {
        state.dimensions[key] = null;
      }
    });
  };

  // Termék kiválasztása
  const selectProduct = (productId) => {
    if (state.product !== productId) {
      state.product = productId;
      state.color = null; // új terméknél szín reset
    }
  };

  // Szín kiválasztása
  const selectColor = (colorId) => {
    state.color = colorId;
    // Ha a kiválasztott szín nem Antracit ('shadow-rock'), a szegelt rögzítés nem elérhető -> csavarosra váltás
    if (colorId !== 'shadow-rock' && state.fastening === 'nail') {
      state.fastening = 'screw';
    }
  };

  // Rögzítés módjának kiválasztása
  const selectFastening = (fasteningId) => {
    if (fasteningId === 'nail' && state.color !== 'shadow-rock') {
      state.fastening = 'screw';
      return;
    }
    state.fastening = fasteningId || 'screw';
  };

  // Fő tetőtípus kiválasztása
  const selectRoofType = (roofTypeId) => {
    if (state.roofType !== roofTypeId) {
      state.roofType = roofTypeId;
      const group = roofTypes.find((r) => r.id === roofTypeId);

      // Ha nincs altípusa (pl. sátortető vagy egyedi), automatikusan nincs altípus
      if (!group?.subtypes?.length) {
        state.roofSubtype = null;
      } else {
        state.roofSubtype = null; // meg kell választani az altípust
      }

      cleanupDimensions();
    }
  };

  // Tető altípus kiválasztása
  const selectRoofSubtype = (subtypeId) => {
    if (state.roofSubtype !== subtypeId) {
      state.roofSubtype = subtypeId;
      cleanupDimensions();
    }
  };

  // Méret érték beállítása (pozitív számok)
  const setDimension = (fieldKey, value) => {
    if (value === '' || value === null || value === undefined) {
      state.dimensions[fieldKey] = null;
      return;
    }

    // Tizedesvessző normalizálása tizedespontra
    const normalized = typeof value === 'string' ? value.replace(',', '.') : value;
    const num = parseFloat(normalized);

    if (!isNaN(num) && num >= 0) {
      state.dimensions[fieldKey] = num;
    } else {
      state.dimensions[fieldKey] = null;
    }
  };

  // Szellőzés beállítása
  const selectVentilation = (ventilationId) => {
    state.ventilation = ventilationId;
    state.hasVentilation = ventilationId !== 'none' && Boolean(ventilationId);
  };

  const setHasVentilation = (val) => {
    state.hasVentilation = Boolean(val);
    state.ventilation = val ? 'standard' : 'none';
    if (val && (!state.ventilationCount || state.ventilationCount < 1)) {
      state.ventilationCount = 1;
    }
  };

  const setVentilationCount = (count) => {
    const n = parseInt(count, 10);
    state.ventilationCount = isNaN(n) || n < 1 ? 1 : n;
  };

  // Kémény beállítása
  const setHasChimney = (val) => {
    state.hasChimney = Boolean(val);
    if (val && (!state.chimneyCount || state.chimneyCount < 1)) {
      state.chimneyCount = 1;
    }
  };

  const setChimneyCount = (count) => {
    const n = parseInt(count, 10);
    state.chimneyCount = isNaN(n) || n < 1 ? 1 : n;
  };

  // Oromszegély kiválasztása (visszakompatibilitás)
  const selectVergeType = (vergeTypeId) => {
    state.vergeType = vergeTypeId;
  };

  // Lépésenkénti validáció
  const canProceed = computed(() => {
    switch (state.currentStep) {
      case 1:
        // Termék és szín kötelező
        return Boolean(state.product && state.color);

      case 2:
        // Tetőforma (és ha van altípus, az altípus) kötelező
        if (!state.roofType) return false;
        if (selectedRoofGroup.value?.subtypes?.length > 0 && !state.roofSubtype) {
          return false;
        }
        return true;

      case 3:
        // Tetőfelület kötelezően > 0, minden egyéb aktív méretmező >= 0 érvényes
        if (!activeFieldKeys.value.length) return false;
        const roofAreaVal = state.dimensions.roofArea;
        if (typeof roofAreaVal !== 'number' || isNaN(roofAreaVal) || roofAreaVal <= 0) {
          return false;
        }
        return activeFieldKeys.value.every((key) => {
          if (key === 'roofArea') return true;
          const val = state.dimensions[key];
          return typeof val === 'number' && !isNaN(val) && val >= 0;
        });

      case 4:
        // Szellőzés opcionális, mindig tovább lehet lépni
        return true;

      case 5:
        // Rögzítés módja kötelező (alapértelmezetten 'screw')
        return Boolean(state.fastening);

      case 6:
        return true;

      default:
        return true;
    }
  });

  // Tovább navigáció
  const nextStep = () => {
    if (!canProceed.value) return;
    if (state.currentStep < 6) {
      state.currentStep++;
    }
  };

  // Vissza navigáció
  const prevStep = () => {
    if (state.currentStep > 1) {
      state.currentStep--;
    }
  };

  // Ugrás közvetlen lépésre (ha engedélyezett)
  const goToStep = (stepNumber) => {
    if (stepNumber < 1 || stepNumber > 6) return;
    if (stepNumber < state.currentStep) {
      state.currentStep = stepNumber;
    }
  };

  // Teljes reset
  const resetCalculator = () => {
    state.currentStep = 1;
    state.product = null;
    state.color = null;
    state.roofType = null;
    state.roofSubtype = null;
    Object.keys(state.dimensions).forEach((k) => (state.dimensions[k] = null));
    state.ventilation = 'none';
    state.hasVentilation = false;
    state.ventilationCount = 1;
    state.hasChimney = false;
    state.chimneyCount = 1;
    state.fastening = 'screw';
    state.vergeType = 'over-cover';
    state.addSparePackage = false;
    state.note = '';
  };

  // 3. lépés segítségkérő ablak kezelése
  const openStep3Help = () => {
    state.isStep3HelpModalOpen = true;
  };

  const closeStep3Help = () => {
    state.isStep3HelpModalOpen = false;
  };

  return {
    state,
    selectedProduct,
    selectedColor,
    selectedRoofGroup,
    selectedRoofConfig,
    hasVerge,
    activeFieldKeys,
    activeFields,
    selectedVentilation,
    selectedVerge,
    canProceed,
    selectProduct,
    selectColor,
    selectFastening,
    selectRoofType,
    selectRoofSubtype,
    setDimension,
    selectVentilation,
    setHasVentilation,
    setVentilationCount,
    setHasChimney,
    setChimneyCount,
    selectVergeType,
    cleanupDimensions,
    nextStep,
    prevStep,
    goToStep,
    resetCalculator,
    openStep3Help,
    closeStep3Help,
  };
}

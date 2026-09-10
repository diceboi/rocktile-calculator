/**
 * Rocktile Termék és Szín konfiguráció.
 * A WooCommerce termékkatalógus valós adatai alapján.
 *
 * Jelenleg aktív (enabled: true) színek:
 * - Antracit (Shadow Rock W8318)
 * - Vörös (Desert Sunset W2188)
 *
 * Kikapcsolt (enabled: false) színek teszteléshez:
 * - Sötétvörös (Crimson Ember W2218)
 * - Barna (Earthwood Chestnut W4618)
 */
export const products = [
  {
    id: 'classic-bond',
    name: 'Classic Bond',
    description: 'Hagyományos hullámos profil prémium kőzúzalékos felületkezeléssel (2,16 db/m²)',
    image: null,
    enabled: true,
    colors: [
      {
        id: 'shadow-rock',
        code: 'W8318',
        name: 'Antracit',
        fullName: 'Shadow Rock W8318',
        hex: '#374151',
        image: null,
        enabled: true,
      },
      {
        id: 'desert-sunset',
        code: 'W2188',
        name: 'Vörös (Téglavörös)',
        fullName: 'Desert Sunset W2188',
        hex: '#b91c1c',
        image: null,
        enabled: false, // Kikapcsolva
      },
      {
        id: 'crimson-ember',
        code: 'W2218',
        name: 'Sötétvörös',
        fullName: 'Crimson Ember W2218',
        hex: '#8b2626',
        image: null,
        enabled: true,
      },
      {
        id: 'earthwood-chestnut',
        code: 'W4618',
        name: 'Barna',
        fullName: 'Earthwood Chestnut W4618',
        hex: '#5c3a21',
        image: null,
        enabled: true,
      },
    ],
  },
];
